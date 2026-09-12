<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard for the authenticated user's role.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', match ($user->role) {
            UserRole::Admin => $this->adminProps(),
            UserRole::Instructor => $this->instructorProps($user),
            default => $this->studentProps($user),
        });
    }

    /**
     * Props for the student dashboard.
     *
     * @return array{role: string, stats: array{active_courses: int, completed_courses: int, pending_payments: int}}
     */
    private function studentProps(User $user): array
    {
        return [
            'role' => 'student',
            'stats' => [
                'active_courses' => $user->enrollments()->where('status', EnrollmentStatus::Active)->count(),
                'completed_courses' => $user->enrollments()->where('status', EnrollmentStatus::Completed)->count(),
                'pending_payments' => $user->payments()->pending()->count(),
            ],
        ];
    }

    /**
     * Props for the instructor dashboard.
     *
     * @return array{role: string, stats: array{published_courses: int, draft_courses: int, students: int}, courses: Collection<int, Course>, recent_enrollments: Collection<int, Enrollment>}
     */
    private function instructorProps(User $user): array
    {
        $courseIds = $user->courses()->select('id');

        return [
            'role' => 'instructor',
            'stats' => [
                'published_courses' => $user->courses()->where('status', CourseStatus::Published)->count(),
                'draft_courses' => $user->courses()->where('status', CourseStatus::Draft)->count(),
                'students' => Enrollment::query()
                    ->whereIn('course_id', $courseIds)
                    ->where('status', '!=', EnrollmentStatus::Cancelled)
                    ->distinct('user_id')
                    ->count('user_id'),
            ],
            'courses' => $user->courses()
                ->with('category')
                ->withCount(['lessons', 'enrollments'])
                ->latest()
                ->limit(5)
                ->get(),
            'recent_enrollments' => Enrollment::query()
                ->whereIn('course_id', $courseIds)
                ->with(['user:id,name', 'course:id,title,slug'])
                ->latest('enrolled_at')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Props for the admin dashboard.
     *
     * @return array{role: string, stats: array{users: int, courses: int, active_enrollments: int, confirmed_revenue: float, pending_payments: int}, pending_payments: Collection<int, Payment>}
     */
    private function adminProps(): array
    {
        return [
            'role' => 'admin',
            'stats' => [
                'users' => User::query()->count(),
                'courses' => Course::query()->count(),
                'active_enrollments' => Enrollment::query()->where('status', EnrollmentStatus::Active)->count(),
                'confirmed_revenue' => (float) Payment::query()->confirmed()->sum('amount'),
                'pending_payments' => Payment::query()->pending()->count(),
            ],
            'pending_payments' => Payment::query()
                ->pending()
                ->with(['user:id,name,email', 'course:id,title,slug'])
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }
}
