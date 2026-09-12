<?php

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

test('the home page renders featured courses and stats', function () {
    $published = Course::factory()->published()->create();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Welcome')
        ->has('featured_courses', 1)
        ->where('featured_courses.0.id', $published->id)
        ->has('stats')
    );
});

test('draft courses are not featured on the home page', function () {
    Course::factory()->create();
    $published = Course::factory()->published()->create();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('featured_courses', 1)
        ->where('featured_courses.0.id', $published->id)
    );
});

test('the home page only features the 6 most recently published courses', function () {
    Course::factory()->count(8)->published()->create();
    $latest = Course::factory()->published()->create(['published_at' => now()->addDay()]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('featured_courses', 6)
        ->where('featured_courses.0.id', $latest->id)
    );
});

test('the home page reports accurate stats', function () {
    $instructor = User::factory()->instructor()->create();
    User::factory()->instructor()->create();

    Course::factory()->for($instructor, 'instructor')->count(2)->published()->create();
    Course::factory()->for($instructor, 'instructor')->create();

    $student = User::factory()->create();
    $course = Course::factory()->for($instructor, 'instructor')->published()->create();
    Enrollment::factory()->for($student, 'user')->for($course, 'course')->create([
        'status' => EnrollmentStatus::Active,
    ]);
    Enrollment::factory()->for($course, 'course')->create(['status' => EnrollmentStatus::Cancelled]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('stats.courses', 3)
        ->where('stats.students', 1)
        ->where('stats.instructors', 2)
    );

    expect($instructor->role)->toBe(UserRole::Instructor);
});

test('an authenticated user still sees the landing page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Welcome'));
});
