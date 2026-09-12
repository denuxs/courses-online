<?php

use App\Models\Course;
use App\Models\User;

test('an instructor sees their own draft and published courses', function () {
    $instructor = User::factory()->instructor()->create();
    $draft = Course::factory()->for($instructor, 'instructor')->create();
    $published = Course::factory()->published()->for($instructor, 'instructor')->create();

    $response = $this->actingAs($instructor)->get(route('instructor.courses.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('instructor/Courses')
        ->has('courses.data', 2)
    );
    expect(collect($response->viewData('page')['props']['courses']['data'])->pluck('id')->sort()->values()->all())
        ->toBe(collect([$draft->id, $published->id])->sort()->values()->all());
});

test('an instructor does not see another instructor\'s courses', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->instructor()->create();
    Course::factory()->for($other, 'instructor')->create();

    $response = $this->actingAs($instructor)->get(route('instructor.courses.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('courses.data', 0));
});

test('guests cannot view the instructor courses page', function () {
    $response = $this->get(route('instructor.courses.index'));

    $response->assertRedirect(route('login'));
});
