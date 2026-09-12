<?php

use App\Enums\CourseStatus;
use App\Models\Course;

test('the published scope only returns published courses', function () {
    $published = Course::factory()->published()->create();
    Course::factory()->create();
    Course::factory()->archived()->create();

    $courses = Course::published()->get();

    expect($courses)->toHaveCount(1);
    expect($courses->first()->id)->toBe($published->id);
});

test('the status attribute is cast to a CourseStatus enum', function () {
    $course = Course::factory()->create();

    expect($course->status)->toBeInstanceOf(CourseStatus::class);
    expect($course->status)->toBe(CourseStatus::Draft);
});

test('isPublished reflects the course status', function () {
    expect(CourseStatus::Published->isPublished())->toBeTrue();
    expect(CourseStatus::Draft->isPublished())->toBeFalse();
    expect(CourseStatus::Archived->isPublished())->toBeFalse();
});
