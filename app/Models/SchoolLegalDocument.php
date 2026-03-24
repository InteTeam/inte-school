<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSchoolScope;
use Database\Factories\SchoolLegalDocumentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property \Illuminate\Support\Carbon|null $published_at
 */
class SchoolLegalDocument extends Model
{
    /** @use HasFactory<SchoolLegalDocumentFactory> */
    use HasFactory, HasSchoolScope, HasUlids;

    protected $fillable = [
        'school_id',
        'type',
        'content',
        'version',
        'is_published',
        'published_at',
        'published_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function isPublished(): bool
    {
        return $this->is_published === true;
    }

    /** @return BelongsTo<User, $this> */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
