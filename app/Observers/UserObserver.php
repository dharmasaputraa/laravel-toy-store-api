<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->assignRole('customer');
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if ($user->isDirty('avatar')) {
            $oldAvatar = $user->getOriginal('avatar');

            // If there was a previous avatar and it's not the same as the new one
            if ($oldAvatar && $oldAvatar !== $user->avatar) {
                Storage::disk('s3')->delete($oldAvatar);
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        if ($user->isForceDeleting() && $user->avatar) {
            Storage::disk('rustfs')->delete($user->avatar);
        }
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
