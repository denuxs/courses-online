<?php

use App\Models\Course;
use App\Models\Module;
use App\Models\User;

test('an instructor can add a module to their course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->for($instructor, 'instructor')->create();

    $response = $this->actingAs($instructor)->post(route('courses.modules.store', $course), [
        'title' => 'Getting started',
        'position' => 1,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('modules', ['course_id' => $course->id, 'title' => 'Getting started']);
});

test('an instructor can add a lesson to a module', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->for($instructor, 'instructor')->create();
    $module = Module::factory()->for($course)->create();

    $response = $this->actingAs($instructor)->post(route('courses.modules.lessons.store', [$course, $module]), [
        'title' => 'Introduction',
        'content' => 'Welcome to the course.',
        'position' => 1,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('lessons', ['module_id' => $module->id, 'title' => 'Introduction']);
});

test('another instructor cannot add a module to a course they do not own', function () {
    $owner = User::factory()->instructor()->create();
    $intruder = User::factory()->instructor()->create();
    $course = Course::factory()->for($owner, 'instructor')->create();

    $response = $this->actingAs($intruder)->post(route('courses.modules.store', $course), [
        'title' => 'Sneaky module',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('modules', ['course_id' => $course->id, 'title' => 'Sneaky module']);
});

test('deleting a course cascades to its modules and lessons', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->for($instructor, 'instructor')->create();
    $module = Module::factory()->for($course)->create();
    $lesson = $module->lessons()->create([
        'title' => 'Lesson 1',
        'position' => 1,
    ]);

    $this->actingAs($instructor)->delete(route('courses.destroy', $course));

    $this->assertDatabaseMissing('modules', ['id' => $module->id]);
    $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
});
