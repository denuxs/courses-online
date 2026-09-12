<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * Display the authenticated instructor's own courses, regardless of status.
     */
    public function index(Request $request): Response
    {
        $courses = $request->user()
            ->courses()
            ->with('category')
            ->withCount('lessons')
            ->latest()
            ->paginate(12);

        return Inertia::render('instructor/Courses', [
            'courses' => $courses,
        ]);
    }
}
