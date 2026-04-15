<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\School;
use App\Models\SchoolLegalDocument;
use App\Models\User;
use App\Models\UserLegalAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class FileUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $rootAdmin;

    private User $admin;

    private User $teacher;

    private SchoolLegalDocument $privacyDoc;

    private SchoolLegalDocument $termsDoc;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();

        $this->rootAdmin = User::factory()->rootAdmin()->create(['email' => 'root@example.com']);
        $this->school = School::factory()->create();

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'admin',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        $this->teacher = User::factory()->create(['email' => 'teacher@example.com']);
        $this->school->users()->attach($this->teacher->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'teacher',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        // Create legal docs once — shared across all users
        $this->privacyDoc = SchoolLegalDocument::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'privacy_policy',
            'content' => '<p>Privacy</p>',
            'version' => '1.0',
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $this->rootAdmin->id,
            'created_by' => $this->rootAdmin->id,
        ]);
        $this->termsDoc = SchoolLegalDocument::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'terms_conditions',
            'content' => '<p>Terms</p>',
            'version' => '1.0',
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $this->rootAdmin->id,
            'created_by' => $this->rootAdmin->id,
        ]);

        $this->acceptLegalDocs($this->admin);
        $this->acceptLegalDocs($this->teacher);
    }

    private function acceptLegalDocs(User $user): void
    {
        foreach ([$this->privacyDoc, $this->termsDoc] as $doc) {
            UserLegalAcceptance::forceCreate([
                'school_id' => $this->school->id,
                'user_id' => $user->id,
                'document_id' => $doc->id,
                'document_type' => $doc->type,
                'document_version' => $doc->version,
                'accepted_at' => now(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'created_at' => now(),
            ]);
        }
    }

    private function actAsAdmin(): self
    {
        return $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id]);
    }

    private function actAsTeacher(): self
    {
        return $this->actingAs($this->teacher)
            ->withSession(['current_school_id' => $this->school->id]);
    }

    // --- PDF accepted ---

    public function test_pdf_upload_is_accepted(): void
    {
        $file = UploadedFile::fake()->create('handbook.pdf', 1024, 'application/pdf');

        $response = $this->actAsAdmin()
            ->post(route('documents.store'), [
                'file' => $file,
                'name' => 'School Handbook',
                'is_parent_facing' => true,
                'is_staff_facing' => true,
            ]);

        $response->assertRedirect(route('documents.index'));
        $this->assertDatabaseHas('documents', [
            'school_id' => $this->school->id,
            'name' => 'School Handbook',
        ]);
    }

    public function test_teacher_can_upload_documents(): void
    {
        $file = UploadedFile::fake()->create('lesson-plan.pdf', 500, 'application/pdf');

        $response = $this->actAsTeacher()
            ->post(route('documents.store'), [
                'file' => $file,
                'name' => 'Lesson Plan',
                'is_staff_facing' => true,
            ]);

        $response->assertRedirect(route('documents.index'));
    }

    // --- SVG rejected (CLAUDE.md: no SVG uploads) ---

    public function test_svg_upload_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('logo.svg', 100, 'image/svg+xml');

        $response = $this->actAsAdmin()
            ->post(route('documents.store'), [
                'file' => $file,
                'name' => 'Logo',
            ]);

        $response->assertSessionHasErrors('file');
    }

    // --- Non-PDF MIME types rejected ---

    public function test_jpg_upload_is_rejected(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actAsAdmin()
            ->post(route('documents.store'), [
                'file' => $file,
                'name' => 'Photo',
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_png_upload_is_rejected(): void
    {
        $file = UploadedFile::fake()->image('screenshot.png');

        $response = $this->actAsAdmin()
            ->post(route('documents.store'), [
                'file' => $file,
                'name' => 'Screenshot',
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_docx_upload_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('document.docx', 200, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actAsAdmin()
            ->post(route('documents.store'), [
                'file' => $file,
                'name' => 'Word Doc',
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_exe_upload_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        $response = $this->actAsAdmin()
            ->post(route('documents.store'), [
                'file' => $file,
                'name' => 'Bad File',
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_html_upload_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('page.html', 50, 'text/html');

        $response = $this->actAsAdmin()
            ->post(route('documents.store'), [
                'file' => $file,
                'name' => 'XSS Page',
            ]);

        $response->assertSessionHasErrors('file');
    }

    // --- Size limits (20 MB max) ---

    public function test_file_under_20mb_is_accepted(): void
    {
        $file = UploadedFile::fake()->create('large.pdf', 19000, 'application/pdf');

        $response = $this->actAsAdmin()
            ->post(route('documents.store'), [
                'file' => $file,
                'name' => 'Large Document',
            ]);

        $response->assertRedirect(route('documents.index'));
    }

    public function test_file_over_20mb_is_rejected(): void
    {
        // 20480 KB = 20 MB cap, create 21 MB file
        $file = UploadedFile::fake()->create('too-large.pdf', 21504, 'application/pdf');

        $response = $this->actAsAdmin()
            ->post(route('documents.store'), [
                'file' => $file,
                'name' => 'Too Large',
            ]);

        $response->assertSessionHasErrors('file');
    }

    // --- File is required ---

    public function test_file_is_required(): void
    {
        $response = $this->actAsAdmin()
            ->post(route('documents.store'), [
                'name' => 'No File',
            ]);

        $response->assertSessionHasErrors('file');
    }

    // --- Role gates ---

    public function test_parent_cannot_upload_documents(): void
    {
        $parent = User::factory()->create(['email' => 'parent@example.com']);
        $this->school->users()->attach($parent->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'parent',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);
        $this->acceptLegalDocs($parent);

        $file = UploadedFile::fake()->create('hack.pdf', 100, 'application/pdf');

        $this->withoutExceptionHandling();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->actingAs($parent)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('documents.store'), [
                'file' => $file,
                'name' => 'Unauthorized',
            ]);
    }

    public function test_student_cannot_upload_documents(): void
    {
        $student = User::factory()->create(['email' => 'student@example.com']);
        $this->school->users()->attach($student->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'student',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);
        $this->acceptLegalDocs($student);

        $file = UploadedFile::fake()->create('hack.pdf', 100, 'application/pdf');

        $this->withoutExceptionHandling();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->actingAs($student)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('documents.store'), [
                'file' => $file,
                'name' => 'Unauthorized',
            ]);
    }

    public function test_guest_cannot_upload(): void
    {
        $response = $this->post(route('documents.store'), []);

        $response->assertRedirect('/login');
    }

    // --- Multi-tenant isolation ---

    public function test_document_is_scoped_to_uploading_school(): void
    {
        $file = UploadedFile::fake()->create('scoped.pdf', 100, 'application/pdf');

        $this->actAsAdmin()
            ->post(route('documents.store'), [
                'file' => $file,
                'name' => 'School A Document',
            ]);

        $this->assertDatabaseHas('documents', [
            'school_id' => $this->school->id,
            'name' => 'School A Document',
        ]);

        // Different school should not see it
        $otherSchool = School::factory()->create();
        $otherAdmin = User::factory()->create(['email' => 'other-admin@example.com']);
        $otherSchool->users()->attach($otherAdmin->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'admin',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        $this->actingAs($otherAdmin)
            ->withSession(['current_school_id' => $otherSchool->id]);

        $visible = \App\Models\Document::where('name', 'School A Document')->count();
        $this->assertSame(0, $visible);
    }
}
