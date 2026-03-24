<?php

declare(strict_types=1);

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\SendMessageRequest;
use App\Jobs\SendBulkMessageJob;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\School;
use App\Services\MessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MessageController extends Controller
{
    public function __construct(
        private readonly MessagingService $messagingService,
    ) {}

    public function index(): InertiaResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $outbox = Message::query()
            ->where('sender_id', $user->id)
            ->whereNull('thread_id')
            ->with(['recipients' => fn ($q) => $q->limit(3)])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $inbox = MessageRecipient::query()
            ->where('recipient_id', $user->id)
            ->with(['message.sender'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Messages/Index', [
            'outbox' => $outbox,
            'inbox' => $inbox,
        ]);
    }

    public function show(Message $message): InertiaResponse
    {
        if (! auth()->user()->can('view', $message)) {
            abort(403);
        }

        $message->load(['sender', 'recipients.recipient', 'attachments', 'thread.sender']);

        return Inertia::render('Admin/Messages/Thread', [
            'message' => $message,
        ]);
    }

    public function send(SendMessageRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->can('create', Message::class)) {
            abort(403);
        }

        $school = $this->currentSchool();
        $validated = $request->validated();

        $recipientIds = $this->resolveRecipients($school, $validated);

        if (count($recipientIds) <= 10) {
            $this->messagingService->send(
                $school,
                $user,
                $validated,
                $recipientIds,
                $request->file('attachments') ?? [],
            );
        } else {
            SendBulkMessageJob::dispatch($school, $user, $validated, $recipientIds);
        }

        return redirect()->route('messages.index')
            ->with(['alert' => __('messages.sent'), 'type' => 'success']);
    }

    public function markRead(Request $request, string $messageId): Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $this->messagingService->markRead($messageId, $user->id);

        return response()->noContent();
    }

    public function quickReply(Request $request, Message $message): RedirectResponse
    {
        $request->validate([
            'reply' => ['required', 'string', 'max:100'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $this->messagingService->recordQuickReply(
            $message->id,
            $user->id,
            $request->string('reply')->toString(),
        );

        return redirect()->back()
            ->with(['alert' => __('messages.reply_recorded'), 'type' => 'success']);
    }

    public function downloadAttachment(\App\Models\MessageAttachment $attachment): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $message = $attachment->message;

        if (! auth()->user()->can('view', $message)) {
            abort(403);
        }

        return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))
            ->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, string>
     */
    private function resolveRecipients(School $school, array $validated): array
    {
        if (! empty($validated['class_id'])) {
            return $this->messagingService->resolveClassRecipients($validated['class_id']);
        }

        if (! empty($validated['recipient_id'])) {
            return [$validated['recipient_id']];
        }

        return [];
    }

    private function currentSchool(): School
    {
        /** @var School $school */
        $school = School::find(session('current_school_id'));

        return $school;
    }
}
