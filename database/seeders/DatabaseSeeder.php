<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        foreach (['cliente' => 'customer', 'tecnico' => 'staff'] as $name => $role) {
            $user = User::firstOrNew(['email' => $name.'@example.test']);
            $user->name = ucfirst($name);
            $user->password = 'Demo12345!';
            $user->role = $role;
            $user->save();
        }
    }
}
