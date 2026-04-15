<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSchoolScope;
use Database\Factories\SmsLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    /** @use HasFactory<SmsLogFactory> */
    use HasFactory, HasSchoolScope, HasUlids;

    protected $fillable = [
        'recipient_id',
        'message_id',
        'phone_number',
        'notify_message_id',
        'status',
        'segments',
        'cost_pence',
        'sent_at',
        'delivered_at',
        'failed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'segments' => 'integer',
            'cost_pence' => 'integer',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /** @return BelongsTo<Message, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
