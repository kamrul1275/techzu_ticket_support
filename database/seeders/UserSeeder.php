<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Demo users should only be created locally.
        if (!app()->environment(['local', 'testing'])) {
            return;
        }

        $users = [
            [
                'name' => 'System Admin',
                'email' => 'admin@supportdesk.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Support Agent',
                'email' => 'agent@supportdesk.test',
                'role' => 'agent',
            ],
            [
                'name' => 'Demo Customer',
                'email' => 'customer@supportdesk.test',
                'role' => 'customer',
            ],
        ];

        foreach ($users as $data) {

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('Demo@12345'),
                ]
            );

            // Role is assigned by trusted server-side code.
            $user->role = $data['role'];
            $user->save();
        }
    }
}