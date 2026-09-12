<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $categories = collect(['Web Development', 'Data Science', 'Design', 'Business'])
            ->map(fn (string $name) => Category::factory()->create(['name' => $name, 'slug' => str($name)->slug()]));

        $instructors = User::factory()->instructor()->count(3)->create();

        $students = User::factory()->count(10)->create();

        $courses = $instructors->flatMap(function (User $instructor) use ($categories) {
            return Course::factory()
                ->published()
                ->count(3)
                ->for($instructor, 'instructor')
                ->create(['category_id' => $categories->random()->id]);
        });

        $courses->push(
            Course::factory()
                ->for($instructors->first(), 'instructor')
                ->create(['category_id' => $categories->random()->id])
        );

        $courses->each(function (Course $course) {
            $modules = $course->modules()->createMany(
                collect(range(1, 3))->map(fn (int $position) => [
                    'title' => "Module {$position}",
                    'position' => $position,
                ])
            );

            $modules->each(function ($module) {
                $module->lessons()->createMany(
                    collect(range(1, 4))->map(fn (int $position) => [
                        'title' => "Lesson {$position}",
                        'content' => fake()->paragraphs(3, true),
                        'duration_minutes' => fake()->numberBetween(5, 30),
                        'is_free_preview' => $position === 1,
                        'position' => $position,
                    ])
                );
            });
        });

        $students->each(function (User $student) use ($courses) {
            Enrollment::factory()
                ->for($student)
                ->for($courses->random(), 'course')
                ->create();
        });
    }
}
