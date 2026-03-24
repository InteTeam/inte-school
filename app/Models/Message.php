<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSchoolScope;
use App\Policies\MessagePolicy;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Message types that track read receipts */
const MESSAGE_TYPES_WITH_RECEIPT = ['attendance_alert', 'trip_permission'];

#[UsePolicy(MessagePolicy::class)]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, HasSchoolScope, HasUlids, SoftDeletes;

    protected $fillable = [
        'sender_id',
        'thread_id',
        'transaction_id',
        'type',
        'body',
        'requires_read_receipt',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'requires_read_receipt' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** @return BelongsTo<Message, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'thread_id');
    }

    /** @return HasMany<Message, $this> */
    public function thread(): HasMany
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    /** @return HasMany<MessageRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(MessageRecipient::class);
    }

    /** @return HasMany<MessageAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }
}
