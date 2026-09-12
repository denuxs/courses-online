<?php

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Database\QueryException;

test('lesson progress belongs to an enrollment and a lesson', function () {
    $progress = LessonProgress::factory()->create();

    expect($progress->enrollment)->toBeInstanceOf(Enrollment::class);
    expect($progress->lesson)->toBeInstanceOf(Lesson::class);
});

test('isCompleted reflects whether completed_at is set', function () {
    expect(LessonProgress::factory()->make()->isCompleted())->toBeFalse();
    expect(LessonProgress::factory()->completed()->make()->isCompleted())->toBeTrue();
});

test('a lesson cannot have two progress records for the same enrollment', function () {
    $enrollment = Enrollment::factory()->create();
    $lesson = Lesson::factory()->create();

    LessonProgress::factory()->for($enrollment)->for($lesson)->create();

    expect(fn () => LessonProgress::factory()->for($enrollment)->for($lesson)->create())
        ->toThrow(QueryException::class);
});

test('deleting an enrollment cascades to its lesson progress', function () {
    $enrollment = Enrollment::factory()->create();
    $progress = LessonProgress::factory()->for($enrollment)->create();

    $enrollment->delete();

    expect(LessonProgress::find($progress->id))->toBeNull();
});
