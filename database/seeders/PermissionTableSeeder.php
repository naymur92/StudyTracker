<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class PermissionTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $permissions = [
      'permission-list',
      'permission-create',
      'permission-edit',
      'permission-delete',
      'role-list',
      'role-create',
      'role-edit',
      'role-delete',
      'user-list',
      'user-create',
      'user-edit',
      'user-delete',
      'oauth-client-list',
      'oauth-client-create',
      'oauth-client-edit',
      'oauth-client-delete',
      'activity-log-list',
      'activity-log-view',
      'activity-log-delete',
      'login-history-list',
      'login-history-view',
      'system-log-list',
      'system-log-view',
      'system-log-download',
      'system-log-delete',
      'settings-view',
      'settings-edit',
      'backup-create',
      'backup-restore',
      'backup-delete',
      'cache-clear',
      'data-request-list',
      'data-request-view',
      'data-request-approve',
      'data-request-restore',
      'data-request-archive-download',
    ];

    foreach ($permissions as $permission) {
      Permission::firstOrCreate(['name' => $permission]);
    }

    // Keep an existing Super Admin role complete when new permissions ship.
    Role::where('name', 'Super Admin')->first()?->givePermissionTo($permissions);
  }
}
