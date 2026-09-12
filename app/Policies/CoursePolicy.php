<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Course $course): bool
    {
        if ($course->status->isPublished()) {
            return true;
        }

        return $this->owns($user, $course);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isInstructor() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Course $course): bool
    {
        return $this->owns($user, $course);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Course $course): bool
    {
        return $this->owns($user, $course);
    }

    /**
     * Determine whether the given user owns the course (or is an admin).
     */
    protected function owns(?User $user, Course $course): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->id === $course->instructor_id || $user->isAdmin();
    }
}
