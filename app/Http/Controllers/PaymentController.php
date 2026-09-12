<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Request access to the given course by creating a pending payment.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        if (! $course->status->isPublished()) {
            throw ValidationException::withMessages([
                'course' => __('This course is not available for enrollment.'),
            ]);
        }

        $user = $request->user();

        $alreadyEnrolled = $user->enrollments()
            ->where('course_id', $course->id)
            ->whereNot('status', EnrollmentStatus::Cancelled)
            ->exists();

        if ($alreadyEnrolled) {
            throw ValidationException::withMessages([
                'course' => __('You already have access to this course.'),
            ]);
        }

        $hasPendingPayment = $user->payments()
            ->where('course_id', $course->id)
            ->where('status', PaymentStatus::Pending)
            ->exists();

        if ($hasPendingPayment) {
            throw ValidationException::withMessages([
                'course' => __('You already requested access to this course.'),
            ]);
        }

        $user->payments()->create([
            'course_id' => $course->id,
            'amount' => $course->price,
            'status' => PaymentStatus::Pending,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Access requested. You will be enrolled once your payment is confirmed.')]);

        return to_route('courses.show', $course);
    }
}
