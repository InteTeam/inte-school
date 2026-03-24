<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageRecipient;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/** Message types that require a read receipt */
const RECEIPT_TYPES = ['attendance_alert', 'trip_permission'];

final class MessagingService
{
    public function __construct(
        private readonly StorageService $storageService,
    ) {}

    /**
     * Create a new message thread root.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>    $recipientIds   User IDs to receive this message
     * @param  UploadedFile[]        $attachments
     */
    public function send(
        School $school,
        User $sender,
        array $data,
        array $recipientIds,
        array $attachments = [],
    ): Message {
        $type = $data['type'];

        $message = Message::forceCreate([
            'school_id' => $school->id,
            'sender_id' => $sender->id,
            'thread_id' => $data['thread_id'] ?? null,
            'transaction_id' => (string) Str::ulid(),
            'type' => $type,
            'body' => $data['body'],
            'requires_read_receipt' => in_array($type, RECEIPT_TYPES, true),
            'sent_at' => now(),
        ]);

        foreach ($recipientIds as $recipientId) {
            MessageRecipient::forceCreate([
                'school_id' => $school->id,
                'message_id' => $message->id,
                'recipient_id' => $recipientId,
            ]);
        }

        foreach ($attachments as $file) {
            $this->storeAttachment($school, $message, $file);
        }

        event(new MessageSent($message, $recipientIds));

        return $message;
    }

    /**
     * Resolve recipient IDs for class-wide targeting.
     *
     * @return array<int, string>
     */
    public function resolveClassRecipients(string $classId): array
    {
        $class = SchoolClass::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->with('students')
            ->find($classId);

        if ($class === null) {
            return [];
        }

        $studentIds = $class->students->pluck('id')->all();

        // Also include guardians of these students
        $guardianIds = \DB::table('guardian_student')
            ->whereIn('student_id', $studentIds)
            ->pluck('guardian_id')
            ->all();

        return array_unique(array_merge($studentIds, $guardianIds));
    }

    /**
     * Mark a message as read for a given recipient.
     */
    public function markRead(string $messageId, string $recipientId): void
    {
        MessageRecipient::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->where('message_id', $messageId)
            ->where('recipient_id', $recipientId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Record a quick-reply from a recipient.
     */
    public function recordQuickReply(string $messageId, string $recipientId, string $reply): void
    {
        MessageRecipient::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->where('message_id', $messageId)
            ->where('recipient_id', $recipientId)
            ->update([
                'quick_reply' => $reply,
                'read_at' => now(),
            ]);
    }

    private function storeAttachment(School $school, Message $message, UploadedFile $file): MessageAttachment
    {
        $path = $this->storageService->store(
            $file,
            "schools/{$school->id}/messages/{$message->id}/attachments"
        );

        return MessageAttachment::forceCreate([
            'school_id' => $school->id,
            'message_id' => $message->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'file_size' => $file->getSize(),
        ]);
    }
}
