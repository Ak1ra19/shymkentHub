<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            WorkspaceSeeder::class,
            EventSeeder::class,
        ]);

        User::query()->updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Администратор',
            'iin' => '000000000001',
            'iin_hash' => hash('sha256', '000000000001'),
            'phone' => '+77070000001',
            'position' => 'Администратор',
            'company' => 'Shymkent Hub',
            'role' => UserRole::Admin,
            'is_blocked' => false,
            'rules_accepted_at' => now(),
            'password' => 'password',
        ]);

        User::query()->updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Тестовый резидент',
            'iin' => '000000000002',
            'iin_hash' => hash('sha256', '000000000002'),
            'phone' => '+77070000002',
            'position' => 'Резидент',
            'company' => 'ShymkentHub',
            'role' => UserRole::User,
            'is_blocked' => false,
            'rules_accepted_at' => now(),
            'password' => 'password',
        ]);
    }
}
