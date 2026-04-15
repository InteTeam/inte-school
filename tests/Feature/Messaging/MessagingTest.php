<?php

declare(strict_types=1);

namespace Tests\Feature\Messaging;

use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolLegalDocument;
use App\Models\User;
use App\Models\UserLegalAcceptance;
use App\Services\MessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class MessagingTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $rootAdmin;

    private User $admin;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootAdmin = User::factory()->rootAdmin()->create(['email' => 'root@example.com']);
        $this->school = School::factory()->create();

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => Str::ulid(),
            'role' => 'admin',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        $this->parent = User::factory()->create(['email' => 'parent@example.com']);
        $this->school->users()->attach($this->parent->id, [
            'id' => Str::ulid(),
            'role' => 'parent',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);
    }

    // --- Message creation ---

    public function test_announcement_creates_message_and_recipients(): void
    {
        $service = app(MessagingService::class);

        $message = $service->send(
            $this->school,
            $this->admin,
            ['type' => 'announcement', 'body' => 'Hello parents'],
            [$this->parent->id],
        );

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'type' => 'announcement',
            'requires_read_receipt' => false,
        ]);

        $this->assertDatabaseHas('message_recipients', [
            'message_id' => $message->id,
            'recipient_id' => $this->parent->id,
        ]);
    }

    public function test_thread_transaction_id_is_unique_per_message(): void
    {
        $service = app(MessagingService::class);

        $msg1 = $service->send($this->school, $this->admin, ['type' => 'announcement', 'body' => 'A'], [$this->parent->id]);
        $msg2 = $service->send($this->school, $this->admin, ['type' => 'announcement', 'body' => 'B'], [$this->parent->id]);

        $this->assertNotEquals($msg1->transaction_id, $msg2->transaction_id);
        $this->assertDatabaseCount('messages', 2);
    }

    public function test_attendance_alert_sets_requires_read_receipt(): void
    {
        $service = app(MessagingService::class);

        $message = $service->send(
            $this->school,
            $this->admin,
            ['type' => 'attendance_alert', 'body' => 'Your child is absent today'],
            [$this->parent->id],
        );

        $this->assertTrue($message->requires_read_receipt);
        $this->assertDatabaseHas('messages', ['id' => $message->id, 'requires_read_receipt' => true]);
    }

    public function test_trip_permission_sets_requires_read_receipt(): void
    {
        $service = app(MessagingService::class);

        $message = $service->send(
            $this->school,
            $this->admin,
            ['type' => 'trip_permission', 'body' => 'Please consent for the school trip'],
            [$this->parent->id],
        );

        $this->assertTrue($message->requires_read_receipt);
    }

    public function test_announcement_does_not_require_read_receipt(): void
    {
        $service = app(MessagingService::class);

        $message = $service->send(
            $this->school,
            $this->admin,
            ['type' => 'announcement', 'body' => 'Sports day is on Friday'],
            [$this->parent->id],
        );

        $this->assertFalse($message->requires_read_receipt);
    }

    public function test_class_targeting_resolves_all_students_and_guardians(): void
    {
        $this->actingAs($this->admin)->withSession(['current_school_id' => $this->school->id]);

        $class = SchoolClass::factory()->create(['school_id' => $this->school->id]);

        $student = User::factory()->create();
        $this->school->users()->attach($student->id, [
            'id' => Str::ulid(),
            'role' => 'student',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        \DB::table('class_students')->insert([
            'class_id' => $class->id,
            'student_id' => $student->id,
            'school_id' => $this->school->id,
            'enrolled_at' => now(),
        ]);

        \DB::table('guardian_student')->insert([
            'id' => (string) Str::ulid(),
            'school_id' => $this->school->id,
            'guardian_id' => $this->parent->id,
            'student_id' => $student->id,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(MessagingService::class);
        $recipients = $service->resolveClassRecipients($class->id);

        $this->assertContains($student->id, $recipients);
        $this->assertContains($this->parent->id, $recipients);
    }

    // --- Read receipt ---

    public function test_marking_message_as_read_sets_read_at(): void
    {
        $service = app(MessagingService::class);

        $message = $service->send(
            $this->school,
            $this->admin,
            ['type' => 'attendance_alert', 'body' => 'Absent today'],
            [$this->parent->id],
        );

        $service->markRead($message->id, $this->parent->id);

        $recipient = MessageRecipient::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->where('message_id', $message->id)
            ->where('recipient_id', $this->parent->id)
            ->first();

        $this->assertNotNull($recipient?->read_at);
    }

    // --- Quick reply ---

    public function test_quick_reply_recorded_and_marks_read(): void
    {
        $service = app(MessagingService::class);

        $message = $service->send(
            $this->school,
            $this->admin,
            ['type' => 'trip_permission', 'body' => 'Consent form'],
            [$this->parent->id],
        );

        $service->recordQuickReply($message->id, $this->parent->id, 'Yes, I consent');

        $recipient = MessageRecipient::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->where('message_id', $message->id)
            ->where('recipient_id', $this->parent->id)
            ->first();

        $this->assertSame('Yes, I consent', $recipient?->quick_reply);
        $this->assertNotNull($recipient?->read_at);
    }

    // --- Multi-tenant isolation ---

    public function test_messages_are_scoped_to_school(): void
    {
        $otherSchool = School::factory()->create();
        $service = app(MessagingService::class);

        $message = $service->send(
            $this->school,
            $this->admin,
            ['type' => 'announcement', 'body' => 'Secret message'],
            [$this->parent->id],
        );

        // Query from other school's context should not see this message
        $this->actingAs($this->admin)->withSession(['current_school_id' => $otherSchool->id]);

        $count = Message::query()->where('id', $message->id)->count();
        $this->assertSame(0, $count);
    }

    // --- SOP: Guest redirect ---

    public function test_guest_cannot_access_messages(): void
    {
        $this->get(route('messages.index'))->assertRedirect('/login');
    }

    public function test_guest_cannot_send_message(): void
    {
        $this->post(route('messages.send'), [])->assertRedirect('/login');
    }

    // --- SOP: Wrong role ---

    public function test_parent_cannot_send_message(): void
    {
        $this->fulfillLegalRequirements($this->parent);

        $this->withoutExceptionHandling();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->actingAs($this->parent)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('messages.send'), [
                'type' => 'announcement',
                'body' => 'Should not be allowed',
                'recipient_id' => $this->admin->id,
            ]);
    }

    // --- SOP: Validation ---

    public function test_send_requires_body(): void
    {
        $this->fulfillLegalRequirements($this->admin);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('messages.send'), [
                'type' => 'announcement',
                'body' => '',
                'recipient_id' => $this->parent->id,
            ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_send_requires_valid_type(): void
    {
        $this->fulfillLegalRequirements($this->admin);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('messages.send'), [
                'type' => 'invalid_type',
                'body' => 'Test message',
                'recipient_id' => $this->parent->id,
            ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_send_requires_recipient_or_class(): void
    {
        $this->fulfillLegalRequirements($this->admin);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('messages.send'), [
                'type' => 'announcement',
                'body' => 'No recipient specified',
            ]);

        $response->assertSessionHasErrors('recipient_id');
    }

    // --- Helper ---

    private function fulfillLegalRequirements(User $user): void
    {
        $privacyDoc = SchoolLegalDocument::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->where('school_id', $this->school->id)->where('type', 'privacy_policy')->first();

        if ($privacyDoc === null) {
            $privacyDoc = SchoolLegalDocument::forceCreate([
                'school_id' => $this->school->id,
                'type' => 'privacy_policy',
                'content' => '<p>Privacy</p>',
                'version' => '1.0',
                'is_published' => true,
                'published_at' => now(),
                'published_by' => $this->rootAdmin->id,
                'created_by' => $this->rootAdmin->id,
            ]);
        }

        $termsDoc = SchoolLegalDocument::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->where('school_id', $this->school->id)->where('type', 'terms_conditions')->first();

        if ($termsDoc === null) {
            $termsDoc = SchoolLegalDocument::forceCreate([
                'school_id' => $this->school->id,
                'type' => 'terms_conditions',
                'content' => '<p>Terms</p>',
                'version' => '1.0',
                'is_published' => true,
                'published_at' => now(),
                'published_by' => $this->rootAdmin->id,
                'created_by' => $this->rootAdmin->id,
            ]);
        }

        foreach ([$privacyDoc, $termsDoc] as $doc) {
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
}
