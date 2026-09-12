<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Module;
use App\Models\User;

class ModulePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Module $module): bool
    {
        return app(CoursePolicy::class)->view($user, $module->course);
    }

    /**
     * Determine whether the user can create a module for the given course.
     */
    public function create(User $user, Course $course): bool
    {
        return $user->id === $course->instructor_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Module $module): bool
    {
        return $this->owns($user, $module);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Module $module): bool
    {
        return $this->owns($user, $module);
    }

    /**
     * Determine whether the given user owns the module's course (or is an admin).
     */
    protected function owns(User $user, Module $module): bool
    {
        return $user->id === $module->course->instructor_id || $user->isAdmin();
    }
}
