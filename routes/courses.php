<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\Instructor\CourseController as InstructorCourseController;
use App\Http\Controllers\Instructor\CourseModuleController;
use App\Http\Controllers\Instructor\ModuleLessonController;
use Illuminate\Support\Facades\Route;

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('courses', [CourseController::class, 'index'])->name('courses.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('instructor/courses', [InstructorCourseController::class, 'index'])->name('instructor.courses.index');

    // Must be registered before the public `courses/{course:slug}` route below,
    // otherwise "create" would be matched as a slug.
    Route::get('courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('courses', [CourseController::class, 'store'])->name('courses.store');

    Route::get('my-enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
});

Route::get('courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('courses/{course:slug}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('courses/{course:slug}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('courses/{course:slug}', [CourseController::class, 'destroy'])->name('courses.destroy');

    Route::post('courses/{course:slug}/modules', [CourseModuleController::class, 'store'])->name('courses.modules.store');
    Route::put('courses/{course:slug}/modules/{module}', [CourseModuleController::class, 'update'])->name('courses.modules.update');
    Route::delete('courses/{course:slug}/modules/{module}', [CourseModuleController::class, 'destroy'])->name('courses.modules.destroy');

    Route::post('courses/{course:slug}/modules/{module}/lessons', [ModuleLessonController::class, 'store'])->name('courses.modules.lessons.store');
    Route::put('courses/{course:slug}/modules/{module}/lessons/{lesson}', [ModuleLessonController::class, 'update'])->name('courses.modules.lessons.update');
    Route::delete('courses/{course:slug}/modules/{module}/lessons/{lesson}', [ModuleLessonController::class, 'destroy'])->name('courses.modules.lessons.destroy');

    Route::post('courses/{course:slug}/enroll', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::delete('courses/{course:slug}/enroll', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
});
