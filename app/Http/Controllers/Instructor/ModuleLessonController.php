<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Courses\StoreLessonRequest;
use App\Http\Requests\Courses\UpdateLessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ModuleLessonController extends Controller
{
    /**
     * Store a newly created lesson for the given module.
     */
    public function store(StoreLessonRequest $request, Course $course, Module $module): RedirectResponse
    {
        $module->lessons()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lesson added.')]);

        return to_route('courses.edit', $course);
    }

    /**
     * Update the specified lesson.
     */
    public function update(UpdateLessonRequest $request, Course $course, Module $module, Lesson $lesson): RedirectResponse
    {
        $lesson->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lesson updated.')]);

        return to_route('courses.edit', $course);
    }

    /**
     * Remove the specified lesson.
     */
    public function destroy(Course $course, Module $module, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('delete', $lesson);

        $lesson->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lesson removed.')]);

        return to_route('courses.edit', $course);
    }
}
