<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Xuple\EvoLayer\Base\Database\Seeders\AiCapabilitySeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // EvoLayer Base: provision the AI capability ledger.
        $this->call(AiCapabilitySeeder::class);

        // Kitchen-sink demo: a test user with the admin role so every example
        // page — including the admin inbox and PRD studio — is reachable.
        // firstOrCreate keeps the seeder idempotent across re-runs.
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // The name feeds the launcher greeting ("Good afternoon, Ada"), so use
        // one that reads like a person rather than a QA artifact.
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->raw(['name' => 'Ada Lovelace', 'email' => 'test@example.com']),
        );

        $user->assignRole($adminRole);
    }
}
