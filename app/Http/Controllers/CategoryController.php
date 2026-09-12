<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(): Response
    {
        return Inertia::render('categories/Index', [
            'categories' => Category::query()
                ->withCount('courses')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Display the published courses for the specified category.
     */
    public function show(Category $category): Response
    {
        $courses = $category->courses()
            ->published()
            ->with(['instructor:id,name'])
            ->withCount('lessons')
            ->latest('published_at')
            ->paginate(12);

        return Inertia::render('categories/Show', [
            'category' => $category,
            'courses' => $courses,
        ]);
    }
}
