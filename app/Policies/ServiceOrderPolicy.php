<?php

namespace App\Policies;

use App\Models\ServiceOrder;
use App\Models\User;

class ServiceOrderPolicy
{
    public function view(User $user, ServiceOrder $order): bool
    {
        return $user->role === 'staff' || $user->id === $order->user_id;
    }
}
