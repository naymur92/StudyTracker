<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\PermissionTableSeeder;
use Database\Seeders\TopicRevisionTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Admin panel tests: session auth as an active type-1 admin holding only the
 * permissions a test grants.
 */
abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionTableSeeder::class);
        $this->seed(TopicRevisionTemplateSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @param  list<string>  $permissions */
    protected function adminWith(array $permissions): User
    {
        $admin = User::factory()->create(['type' => 1, 'is_active' => 1]);
        $admin->givePermissionTo($permissions);

        $this->actingAs($admin, 'web');

        return $admin;
    }

    /** Flash messages are turned into php-flasher envelopes by its middleware. */
    protected function assertFlashed(string $type, string $message): void
    {
        $flashed = collect(session('flasher::envelopes', []))
            ->map(fn ($envelope) => is_string($envelope) ? unserialize($envelope) : $envelope)
            ->map(fn ($envelope) => [$envelope->getType(), $envelope->getMessage()])
            ->all();

        $this->assertContains([$type, $message], $flashed, 'Flashed: '.json_encode($flashed));
    }

    protected function allDataRequestPermissions(): array
    {
        return [
            'data-request-list',
            'data-request-view',
            'data-request-approve',
            'data-request-restore',
            'data-request-archive-download',
        ];
    }
}
