<?php

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('students see their enrollment and payment counters', function () {
    $student = User::factory()->create();
    $admin = User::factory()->admin()->create();

    Enrollment::factory()->count(2)->for($student, 'user')->create();
    Enrollment::factory()->for($student, 'user')->completed()->create();
    Enrollment::factory()->for($student, 'user')->create(['status' => EnrollmentStatus::Cancelled]);
    Enrollment::factory()->create();

    Payment::factory()->for($student, 'user')->create();
    Payment::factory()->for($student, 'user')->confirmed($admin)->create();
    Payment::factory()->create();

    $response = $this->actingAs($student)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('role', 'student')
        ->where('stats.active_courses', 2)
        ->where('stats.completed_courses', 1)
        ->where('stats.pending_payments', 1)
        ->missing('pending_payments')
        ->missing('courses')
    );
});

test('instructors see stats and lists scoped to their own courses', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->instructor()->create();

    $published = Course::factory()->for($instructor, 'instructor')->published()->create();
    Course::factory()->for($instructor, 'instructor')->published()->create();
    Course::factory()->for($instructor, 'instructor')->create();
    $foreign = Course::factory()->for($other, 'instructor')->published()->create();

    $studentA = User::factory()->create();
    $studentB = User::factory()->create();
    Enrollment::factory()->for($studentA, 'user')->for($published, 'course')->create();
    Enrollment::factory()->for($studentB, 'user')->for($published, 'course')->create(['status' => EnrollmentStatus::Cancelled]);
    Enrollment::factory()->for($studentA, 'user')->for($foreign, 'course')->create();

    $response = $this->actingAs($instructor)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('role', 'instructor')
        ->where('stats.published_courses', 2)
        ->where('stats.draft_courses', 1)
        ->where('stats.students', 1)
        ->has('courses', 3)
        ->has('recent_enrollments', 2)
        ->where('recent_enrollments.0.course.id', $published->id)
        ->where('recent_enrollments.1.course.id', $published->id)
    );

    $courseIds = collect($response->viewData('page')['props']['courses'])->pluck('id');
    expect($courseIds)->not->toContain($foreign->id);
});

test('admins see global metrics and pending payments', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->published()->create(['price' => '50.00']);

    Payment::factory()->count(2)->for($course, 'course')->create();
    Payment::factory()->for($course, 'course')->confirmed($admin)->create();
    Payment::factory()->for($course, 'course')->confirmed($admin)->create();
    Payment::factory()->for($course, 'course')->rejected($admin)->create();
    Enrollment::factory()->for($course, 'course')->create();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('role', 'admin')
        ->where('stats.pending_payments', 2)
        ->where('stats.confirmed_revenue', 100)
        ->where('stats.active_enrollments', 1)
        ->where('stats.courses', 1)
        ->has('pending_payments', 2)
        ->has('pending_payments.0.user')
        ->has('pending_payments.0.course')
    );
});
