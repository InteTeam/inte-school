<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSchoolScope;
use App\Policies\DocumentPolicy;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(DocumentPolicy::class)]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, HasSchoolScope, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
        'is_parent_facing',
        'is_staff_facing',
        'processing_status',
    ];

    protected function casts(): array
    {
        return [
            'is_parent_facing' => 'boolean',
            'is_staff_facing' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @return HasMany<DocumentChunk, $this> */
    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }
}
