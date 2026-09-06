<?php

namespace App\Policies;

use App\Models\ExportSession;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class ExportSessionPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Nullable $user: guests can view a session they started themselves.
     * Ownership for a guest-owned session (user_id === null) is proven by
     * matching the current browser's Laravel session ID against the
     * guest_token stamped on it at creation — see ProductScreeningController.
     * This is what stops one anonymous visitor from viewing another's
     * in-progress quiz by guessing /products/{id}.
     */
    public function view(?User $user, ExportSession $exportSession): bool
    {
        if ($user) {
            return $user->id === $exportSession->user_id;
        }

        return $exportSession->isGuestOwned() && $exportSession->guest_token === Session::getId();
    }

    public function create(?User $user): bool
    {
        return true;
    }

    public function update(?User $user, ExportSession $exportSession): bool
    {
        if ($exportSession->isCompleted()) {
            return false;
        }

        return $this->view($user, $exportSession);
    }
}
