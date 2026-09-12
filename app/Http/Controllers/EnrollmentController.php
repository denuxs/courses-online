<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    /**
     * Display the authenticated user's enrollments.
     */
    public function index(Request $request): Response
    {
        $enrollments = $request->user()
            ->enrollments()
            ->with(['course.instructor:id,name', 'course.category'])
            ->latest('enrolled_at')
            ->paginate(12);

        return Inertia::render('enrollments/Index', [
            'enrollments' => $enrollments,
        ]);
    }

    /**
     * Enroll the authenticated user in the given course.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        if (! $course->status->isPublished()) {
            throw ValidationException::withMessages([
                'course' => __('This course is not available for enrollment.'),
            ]);
        }

        $request->user()->enrollments()->firstOrCreate(
            ['course_id' => $course->id],
            ['status' => EnrollmentStatus::Active, 'enrolled_at' => now()]
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You are enrolled in this course.')]);

        return to_route('courses.show', $course);
    }

    /**
     * Cancel the authenticated user's enrollment in the given course.
     */
    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $enrollment = $request->user()
            ->enrollments()
            ->where('course_id', $course->id)
            ->firstOrFail();

        $enrollment->update(['status' => EnrollmentStatus::Cancelled]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Enrollment cancelled.')]);

        return to_route('enrollments.index');
    }
}
