<?php

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

test('a student can enroll in a published course', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();

    $response = $this->actingAs($student)->post(route('enrollments.store', $course));

    $response->assertRedirect();
    $this->assertDatabaseHas('enrollments', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active->value,
    ]);
});

test('enrolling twice does not create duplicate enrollments', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();

    $this->actingAs($student)->post(route('enrollments.store', $course));
    $this->actingAs($student)->post(route('enrollments.store', $course));

    expect(Enrollment::where('user_id', $student->id)->where('course_id', $course->id)->count())->toBe(1);
});

test('a student cannot enroll in a draft course', function () {
    $student = User::factory()->create();
    $course = Course::factory()->create();

    $response = $this->actingAs($student)->post(route('enrollments.store', $course));

    $response->assertSessionHasErrors('course');
    $this->assertDatabaseMissing('enrollments', ['user_id' => $student->id, 'course_id' => $course->id]);
});

test('a student can cancel their enrollment', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $student->enrollments()->create([
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    $response = $this->actingAs($student)->delete(route('enrollments.destroy', $course));

    $response->assertRedirect();
    $this->assertDatabaseHas('enrollments', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Cancelled->value,
    ]);
});

test('guests cannot enroll in a course', function () {
    $course = Course::factory()->published()->create();

    $response = $this->post(route('enrollments.store', $course));

    $response->assertRedirect(route('login'));
});

test('an enrolled student sees is_enrolled on the course show page', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $student->enrollments()->create([
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    $response = $this->actingAs($student)->get(route('courses.show', $course));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('is_enrolled', true));
});

test('a student with a cancelled enrollment does not see is_enrolled', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $student->enrollments()->create([
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Cancelled,
        'enrolled_at' => now(),
    ]);

    $response = $this->actingAs($student)->get(route('courses.show', $course));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('is_enrolled', false));
});
