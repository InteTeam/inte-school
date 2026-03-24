<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLegalAcceptance extends Model
{
    use HasSchoolScope, HasUlids;

    // Append-only — no updated_at
    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'user_id',
        'document_id',
        'document_type',
        'document_version',
        'accepted_at',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<SchoolLegalDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(SchoolLegalDocument::class);
    }
}
