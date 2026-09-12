<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Courses\StoreModuleRequest;
use App\Http\Requests\Courses\UpdateModuleRequest;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CourseModuleController extends Controller
{
    /**
     * Store a newly created module for the given course.
     */
    public function store(StoreModuleRequest $request, Course $course): RedirectResponse
    {
        $course->modules()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Module added.')]);

        return to_route('courses.edit', $course);
    }

    /**
     * Update the specified module.
     */
    public function update(UpdateModuleRequest $request, Course $course, Module $module): RedirectResponse
    {
        $module->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Module updated.')]);

        return to_route('courses.edit', $course);
    }

    /**
     * Remove the specified module.
     */
    public function destroy(Course $course, Module $module): RedirectResponse
    {
        Gate::authorize('delete', $module);

        $module->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Module removed.')]);

        return to_route('courses.edit', $course);
    }
}
