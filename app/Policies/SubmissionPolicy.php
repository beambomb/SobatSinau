<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function grade(User $user, Submission $submission): bool
    {
        return $user->isAdmin() || $submission->assignment->classroom->isTaughtBy($user);
    }
}
