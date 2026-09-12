<?php

use App\Models\Category;
use App\Models\Course;

test('published courses are listed', function () {
    $published = Course::factory()->published()->create();

    $response = $this->get(route('courses.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('courses/Index')
        ->has('courses.data', 1)
        ->where('courses.data.0.id', $published->id)
    );
});

test('draft courses do not appear in the listing', function () {
    Course::factory()->create();

    $response = $this->get(route('courses.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('courses/Index')
        ->has('courses.data', 0)
    );
});

test('the listing can be filtered by category', function () {
    $category = Category::factory()->create();
    $matching = Course::factory()->published()->create(['category_id' => $category->id]);
    Course::factory()->published()->create();

    $response = $this->get(route('courses.index', ['category' => $category->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('courses/Index')
        ->has('courses.data', 1)
        ->where('courses.data.0.id', $matching->id)
    );
});
