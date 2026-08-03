<?php

namespace App\Actions\Profile;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateProfileAction
{
    /**
     * @param  array{name?: string, email?: string}  $data
     */
    public function execute(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            if (array_key_exists('email', $data) && $data['email'] !== $user->email) {
                $user->email_verified_at = null;
            }

            $user->fill($data);
            $user->save();

            return $user->fresh(['roles', 'permissions']);
        });
    }
}
