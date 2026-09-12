<?php

use App\Models\Course;
use App\Models\User;

test('an instructor can create a course', function () {
    $instructor = User::factory()->instructor()->create();

    $response = $this->actingAs($instructor)->post(route('courses.store'), [
        'title' => 'Laravel from Scratch',
        'slug' => 'laravel-from-scratch',
        'category_id' => null,
        'description' => 'Learn Laravel.',
        'price' => 29.99,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('courses', [
        'slug' => 'laravel-from-scratch',
        'instructor_id' => $instructor->id,
    ]);
});

test('a student cannot create a course', function () {
    $student = User::factory()->create();

    $response = $this->actingAs($student)->post(route('courses.store'), [
        'title' => 'Laravel from Scratch',
        'slug' => 'laravel-from-scratch',
        'price' => 0,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('courses', ['slug' => 'laravel-from-scratch']);
});

test('an instructor can update their own course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->for($instructor, 'instructor')->create();

    $response = $this->actingAs($instructor)->put(route('courses.update', $course), [
        'title' => 'Updated title',
        'slug' => $course->slug,
        'price' => 49.99,
        'status' => 'published',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'title' => 'Updated title',
        'status' => 'published',
    ]);
});

test('an instructor cannot update another instructor\'s course', function () {
    $owner = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();
    $course = Course::factory()->for($owner, 'instructor')->create();

    $response = $this->actingAs($otherInstructor)->put(route('courses.update', $course), [
        'title' => 'Hijacked title',
        'slug' => $course->slug,
        'price' => 10,
        'status' => 'draft',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => $course->title]);
});

test('an instructor can delete their own course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->for($instructor, 'instructor')->create();

    $response = $this->actingAs($instructor)->delete(route('courses.destroy', $course));

    $response->assertRedirect();
    $this->assertDatabaseMissing('courses', ['id' => $course->id]);
});

test('guests cannot manage courses', function () {
    $response = $this->post(route('courses.store'), []);

    $response->assertRedirect(route('login'));
});

test('the course owner sees update and delete permissions on show', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->published()->for($instructor, 'instructor')->create();

    $response = $this->actingAs($instructor)->get(route('courses.show', $course));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('courses/Show')
        ->where('can.update', true)
        ->where('can.delete', true)
    );
});

test('a stranger does not see update and delete permissions on show', function () {
    $instructor = User::factory()->instructor()->create();
    $stranger = User::factory()->create();
    $course = Course::factory()->published()->for($instructor, 'instructor')->create();

    $response = $this->actingAs($stranger)->get(route('courses.show', $course));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('can.update', false)
        ->where('can.delete', false)
    );
});

test('a guest does not see update and delete permissions on show', function () {
    $course = Course::factory()->published()->create();

    $response = $this->get(route('courses.show', $course));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('can.update', false)
        ->where('can.delete', false)
        ->where('is_enrolled', false)
    );
});
