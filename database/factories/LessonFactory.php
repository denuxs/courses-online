<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(4, true),
            'video_url' => fake()->optional()->url(),
            'duration_minutes' => fake()->numberBetween(3, 45),
            'is_free_preview' => false,
            'position' => 0,
        ];
    }

    /**
     * Indicate that the lesson is a free preview.
     */
    public function freePreview(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_free_preview' => true,
        ]);
    }
}
