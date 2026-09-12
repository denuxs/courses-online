<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
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

        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

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

        $students->each(function (User $student) use ($courses, $admin) {
            $course = $courses->random();

            Payment::factory()
                ->for($student)
                ->for($course)
                ->confirmed($admin)
                ->create();

            $enrollment = Enrollment::factory()
                ->for($student)
                ->for($course, 'course')
                ->create();

            $course->lessons()->inRandomOrder()->take(3)->get()->each(
                fn ($lesson, int $index) => $enrollment->lessonProgress()->create([
                    'lesson_id' => $lesson->id,
                    'seconds_watched' => fake()->numberBetween(30, 900),
                    'completed_at' => $index === 0 ? now() : null,
                ])
            );
        });

        $students->take(4)->each(function (User $student) use ($courses) {
            Payment::factory()
                ->for($student)
                ->for($courses->random())
                ->create();
        });

        $students->take(2)->each(function (User $student) use ($courses, $admin) {
            Payment::factory()
                ->for($student)
                ->for($courses->random())
                ->rejected($admin)
                ->create();
        });
    }
}
