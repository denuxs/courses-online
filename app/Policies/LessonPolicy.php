<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;

class LessonPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Lesson $lesson): bool
    {
        if ($lesson->is_free_preview) {
            return true;
        }

        return app(CoursePolicy::class)->view($user, $lesson->module->course);
    }

    /**
     * Determine whether the user can create a lesson for the given module.
     */
    public function create(User $user, Module $module): bool
    {
        return $user->id === $module->course->instructor_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Lesson $lesson): bool
    {
        return $this->owns($user, $lesson);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Lesson $lesson): bool
    {
        return $this->owns($user, $lesson);
    }

    /**
     * Determine whether the given user owns the lesson's course (or is an admin).
     */
    protected function owns(User $user, Lesson $lesson): bool
    {
        return $user->id === $lesson->module->course->instructor_id || $user->isAdmin();
    }
}
