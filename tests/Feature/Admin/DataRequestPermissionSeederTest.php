<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\CreateAdminUserSeeder;
use Database\Seeders\PermissionTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DataRequestPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = [
        'data-request-list',
        'data-request-view',
        'data-request-approve',
        'data-request-restore',
        'data-request-archive-download',
    ];

    public function test_fresh_seed_gives_super_admin_every_data_request_permission(): void
    {
        // The seeder hard-codes created_by = 1.
        if (! User::whereKey(1)->exists()) {
            User::factory()->create(['id' => 1]);
        }

        $this->seed(PermissionTableSeeder::class);
        $this->seed(CreateAdminUserSeeder::class);

        $role = Role::findByName('Super Admin');

        foreach (self::PERMISSIONS as $permission) {
            $this->assertTrue($role->hasPermissionTo($permission), "Super Admin lacks {$permission}");
        }
    }

    public function test_reseeding_permissions_updates_an_existing_super_admin_role(): void
    {
        $role = Role::create(['name' => 'Super Admin']);

        $this->seed(PermissionTableSeeder::class);

        foreach (self::PERMISSIONS as $permission) {
            $this->assertTrue($role->fresh()->hasPermissionTo($permission), "Super Admin lacks {$permission}");
        }
    }
}
