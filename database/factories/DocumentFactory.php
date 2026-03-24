<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => $this->faker->words(3, true) . '.pdf',
            'file_path' => 'schools/test/documents/' . $this->faker->uuid() . '.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => $this->faker->numberBetween(10000, 5000000),
            'uploaded_by' => User::factory(),
            'is_parent_facing' => true,
            'is_staff_facing' => true,
            'processing_status' => 'indexed',
        ];
    }

    public function pending(): static
    {
        return $this->state(['processing_status' => 'pending']);
    }

    public function failed(): static
    {
        return $this->state(['processing_status' => 'failed']);
    }

    public function staffOnly(): static
    {
        return $this->state(['is_parent_facing' => false, 'is_staff_facing' => true]);
    }
}
