<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Http\Requests\Courses\StoreCourseRequest;
use App\Http\Requests\Courses\UpdateCourseRequest;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * Display a listing of the published courses.
     */
    public function index(Request $request): Response
    {
        $courses = Course::query()
            ->published()
            ->with(['instructor:id,name', 'category'])
            ->withCount('lessons')
            ->when($request->string('category')->isNotEmpty(), fn ($query) => $query->whereHas(
                'category',
                fn ($query) => $query->where('slug', $request->string('category'))
            ))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('courses/Index', [
            'courses' => $courses,
            'categories' => Category::query()->orderBy('name')->get(),
            'filters' => $request->only('category'),
            'can_create' => Gate::allows('create', Course::class),
        ]);
    }

    /**
     * Show the form for creating a new course.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Course::class);

        return Inertia::render('courses/Create', [
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $course = $request->user()->courses()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Course created.')]);

        return to_route('courses.edit', $course);
    }

    /**
     * Display the specified course.
     */
    public function show(Request $request, Course $course): Response
    {
        Gate::authorize('view', $course);

        $course->load(['instructor:id,name', 'category', 'modules.lessons']);

        return Inertia::render('courses/Show', [
            'course' => $course,
            'can' => [
                'update' => Gate::allows('update', $course),
                'delete' => Gate::allows('delete', $course),
            ],
            'is_enrolled' => $request->user() !== null && $request->user()
                ->enrollments()
                ->where('course_id', $course->id)
                ->whereNot('status', EnrollmentStatus::Cancelled)
                ->exists(),
        ]);
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Course $course): Response
    {
        Gate::authorize('update', $course);

        $course->load('modules.lessons');

        return Inertia::render('courses/Edit', [
            'course' => $course,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified course in storage.
     */
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Course updated.')]);

        return to_route('courses.edit', $course);
    }

    /**
     * Remove the specified course from storage.
     */
    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('delete', $course);

        $course->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Course deleted.')]);

        return to_route('courses.index');
    }
}
