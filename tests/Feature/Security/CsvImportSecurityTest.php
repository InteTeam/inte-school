<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Jobs\ProcessStudentCsvImportJob;
use App\Models\School;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class CsvImportSecurityTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->rootAdmin()->create(['email' => 'root@example.com']);
        $this->school = School::factory()->create();

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'admin',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);
    }

    private function actAsAdmin(): self
    {
        return $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id]);
    }

    private function fulfillLegalRequirements(User $user): void
    {
        $rootAdmin = User::where('is_root_admin', true)->first();

        // Reuse existing docs if already created, otherwise create new ones
        $privacyDoc = \App\Models\SchoolLegalDocument::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->where('school_id', $this->school->id)
            ->where('type', 'privacy_policy')
            ->first();

        if ($privacyDoc === null) {
            $privacyDoc = \App\Models\SchoolLegalDocument::forceCreate([
                'school_id' => $this->school->id,
                'type' => 'privacy_policy',
                'content' => '<p>Privacy</p>',
                'version' => '1.0',
                'is_published' => true,
                'published_at' => now(),
                'published_by' => $rootAdmin->id,
                'created_by' => $rootAdmin->id,
            ]);
        }

        $termsDoc = \App\Models\SchoolLegalDocument::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->where('school_id', $this->school->id)
            ->where('type', 'terms_conditions')
            ->first();

        if ($termsDoc === null) {
            $termsDoc = \App\Models\SchoolLegalDocument::forceCreate([
                'school_id' => $this->school->id,
                'type' => 'terms_conditions',
                'content' => '<p>Terms</p>',
                'version' => '1.0',
                'is_published' => true,
                'published_at' => now(),
                'published_by' => $rootAdmin->id,
                'created_by' => $rootAdmin->id,
            ]);
        }

        foreach ([$privacyDoc, $termsDoc] as $doc) {
            \App\Models\UserLegalAcceptance::forceCreate([
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

    private function makeCsvFile(string $content, string $filename = 'students.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($filename, $content);
    }

    // --- CSV injection sanitization (service level) ---

    public function test_equals_prefix_is_sanitized(): void
    {
        $service = app(UserManagementService::class);
        $result = $service->sanitizeCsvRow(['=cmd|/C calc.exe']);

        $this->assertSame("'=cmd|/C calc.exe", $result[0]);
    }

    public function test_plus_prefix_is_sanitized(): void
    {
        $service = app(UserManagementService::class);
        $result = $service->sanitizeCsvRow(['+cmd|/C calc.exe']);

        $this->assertSame("'+cmd|/C calc.exe", $result[0]);
    }

    public function test_minus_prefix_is_sanitized(): void
    {
        $service = app(UserManagementService::class);
        $result = $service->sanitizeCsvRow(['-cmd|/C calc.exe']);

        $this->assertSame("'-cmd|/C calc.exe", $result[0]);
    }

    public function test_at_prefix_is_sanitized(): void
    {
        $service = app(UserManagementService::class);
        $result = $service->sanitizeCsvRow(['@SUM(A1:A10)']);

        $this->assertSame("'@SUM(A1:A10)", $result[0]);
    }

    public function test_tab_before_safe_value_passes_through(): void
    {
        // ltrim() strips tab, remaining "cmd" is safe — no sanitization needed
        $service = app(UserManagementService::class);
        $result = $service->sanitizeCsvRow(["\tcmd"]);

        $this->assertSame("\tcmd", $result[0]);
    }

    public function test_tab_before_dangerous_prefix_is_sanitized(): void
    {
        // ltrim() strips tab, revealing "=cmd" which IS dangerous
        $service = app(UserManagementService::class);
        $result = $service->sanitizeCsvRow(["\t=cmd|inject"]);

        $this->assertSame("'=cmd|inject", $result[0]);
    }

    public function test_carriage_return_before_safe_value_passes_through(): void
    {
        // ltrim() strips CR, remaining "cmd" is safe
        $service = app(UserManagementService::class);
        $result = $service->sanitizeCsvRow(["\rcmd"]);

        $this->assertSame("\rcmd", $result[0]);
    }

    public function test_carriage_return_before_dangerous_prefix_is_sanitized(): void
    {
        $service = app(UserManagementService::class);
        $result = $service->sanitizeCsvRow(["\r+evil"]);

        $this->assertSame("'+evil", $result[0]);
    }

    public function test_safe_values_pass_through_unchanged(): void
    {
        $service = app(UserManagementService::class);
        $row = ['John Smith', 'john@example.com', 'Year 3', '3A'];
        $result = $service->sanitizeCsvRow($row);

        $this->assertSame($row, $result);
    }

    public function test_multiple_injection_prefixes_in_single_row(): void
    {
        $service = app(UserManagementService::class);
        $row = [
            '=malicious',
            '+evil',
            '-bad',
            '@inject',
            'safe name',
            'safe@email.com',
        ];

        $result = $service->sanitizeCsvRow($row);

        $this->assertSame("'=malicious", $result[0]);
        $this->assertSame("'+evil", $result[1]);
        $this->assertSame("'-bad", $result[2]);
        $this->assertSame("'@inject", $result[3]);
        $this->assertSame('safe name', $result[4]);
        $this->assertSame('safe@email.com', $result[5]);
    }

    // --- CSV parsing with sanitization ---

    public function test_parsed_csv_rows_are_sanitized(): void
    {
        $service = app(UserManagementService::class);
        $csv = "name,email,year_group\n=cmd|evil,+evil@example.com,Year 3\n";

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $csv);
        rewind($handle);

        $rows = $service->parseCsvImport($handle);
        fclose($handle);

        $this->assertCount(1, $rows);
        $this->assertSame("'=cmd|evil", $rows[0]['name']);
        $this->assertSame("'+evil@example.com", $rows[0]['email']);
        $this->assertSame('Year 3', $rows[0]['year_group']);
    }

    public function test_malformed_csv_rows_are_skipped(): void
    {
        $service = app(UserManagementService::class);
        $csv = "name,email,year_group\nAlice,alice@example.com,Year 3\nBob,bob@example.com\n";

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $csv);
        rewind($handle);

        $rows = $service->parseCsvImport($handle);
        fclose($handle);

        // Bob's row has 2 columns instead of 3, should be skipped
        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
    }

    // --- HTTP import route ---

    public function test_import_requires_admin_role(): void
    {
        $teacher = User::factory()->create(['email' => 'teacher@example.com']);
        $this->school->users()->attach($teacher->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'teacher',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);
        $this->fulfillLegalRequirements($teacher);

        $csv = $this->makeCsvFile("name,email,year_group\nAlice,alice@example.com,Year 3\n");

        $this->withoutExceptionHandling();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->actingAs($teacher)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('admin.students.import'), ['csv' => $csv]);
    }

    public function test_import_dispatches_job_for_valid_csv(): void
    {
        Queue::fake();
        $this->fulfillLegalRequirements($this->admin);

        $csv = $this->makeCsvFile("name,email,year_group\nAlice,alice@example.com,Year 3\n");

        $response = $this->actAsAdmin()
            ->post(route('admin.students.import'), ['csv' => $csv]);

        $response->assertRedirect(route('admin.students.index'));
        Queue::assertPushed(ProcessStudentCsvImportJob::class);
    }

    public function test_import_rejects_non_csv_file(): void
    {
        $this->fulfillLegalRequirements($this->admin);

        $file = UploadedFile::fake()->create('students.pdf', 100, 'application/pdf');

        $response = $this->actAsAdmin()
            ->post(route('admin.students.import'), ['csv' => $file]);

        $response->assertSessionHasErrors('csv');
    }

    public function test_import_rejects_oversized_file(): void
    {
        $this->fulfillLegalRequirements($this->admin);

        // 10240 KB = 10 MB max, create 11 MB file
        $file = UploadedFile::fake()->create('students.csv', 11264, 'text/csv');

        $response = $this->actAsAdmin()
            ->post(route('admin.students.import'), ['csv' => $file]);

        $response->assertSessionHasErrors('csv');
    }

    public function test_guest_cannot_access_import(): void
    {
        $response = $this->post(route('admin.students.import'), []);

        $response->assertRedirect('/login');
    }
}
