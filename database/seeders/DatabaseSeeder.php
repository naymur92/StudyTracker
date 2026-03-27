<?php

namespace Database\Seeders;

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
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'SuperAdmin',
        //     'email' => 'superadmin@gmail.com',
        //     'password' => bcrypt('123456'),
        //     'type' => 1,
        //     'is_active' => 1,
        //     'created_by' => 1
        // ]);

        $this->call(PermissionTableSeeder::class);

        $this->call(CreateAdminUserSeeder::class);

        $this->call(TopicRevisionTemplateSeeder::class);
    }
}
