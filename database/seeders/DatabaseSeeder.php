<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles, permissions, and the admin@staffhub.test User must come first
        // so that DemoDataSeeder can assign roles and link to the admin User.
        $this->call(RolesAndPermissionsSeeder::class);

        // Demo data is only seeded in local / testing environments.
        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoDataSeeder::class);
        }
    }
}

