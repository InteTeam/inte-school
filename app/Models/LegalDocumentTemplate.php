<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LegalDocumentTemplateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalDocumentTemplate extends Model
{
    /** @use HasFactory<LegalDocumentTemplateFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'type',
        'name',
        'content',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
