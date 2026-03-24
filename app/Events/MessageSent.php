<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, string>  $recipientIds
     */
    public function __construct(
        public readonly Message $message,
        public readonly array $recipientIds,
    ) {}

    /**
     * Broadcast on the school channel (admin dashboards) + each recipient's personal channel.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel("school.{$this->message->school_id}")];

        foreach ($this->recipientIds as $recipientId) {
            $channels[] = new PrivateChannel("user.{$recipientId}");
        }

        return $channels;
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'type' => $this->message->type,
            'body' => Str::limit($this->message->body, 100),
            'sender_name' => $this->message->sender?->name,
            'sent_at' => $this->message->sent_at !== null ? (string) $this->message->sent_at : null,
            'requires_read_receipt' => $this->message->requires_read_receipt,
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
