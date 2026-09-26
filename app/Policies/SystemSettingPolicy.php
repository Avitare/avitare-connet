<?php

namespace App\Policies;

use App\Models\SystemSetting;
use App\Models\User;

class SystemSettingPolicy
{
    public function view(User $user, SystemSetting $setting): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, SystemSetting $setting): bool
    {
        return $user->hasRole('admin');
    }
}
