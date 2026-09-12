<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the landing page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'featured_courses' => Course::query()
                ->published()
                ->with(['instructor:id,name', 'category'])
                ->withCount('lessons')
                ->latest('published_at')
                ->limit(6)
                ->get(),
            'stats' => $this->stats(),
        ]);
    }

    /**
     * Get the landing page statistics, cached for an hour.
     *
     * @return array{courses: int, students: int, instructors: int}
     */
    private function stats(): array
    {
        return Cache::remember('landing.stats', now()->addHour(), fn (): array => [
            'courses' => Course::query()->published()->count(),
            'students' => Enrollment::query()
                ->where('status', EnrollmentStatus::Active)
                ->distinct('user_id')
                ->count('user_id'),
            'instructors' => User::query()->where('role', UserRole::Instructor)->count(),
        ]);
    }
}
