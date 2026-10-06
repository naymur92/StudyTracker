<?php

namespace Tests\Feature\Study;

use App\Models\User;
use Database\Seeders\TopicRevisionTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

abstract class StudyApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    /** @var array{private: string, public: string}|null */
    private static ?array $passportKeys = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Passport needs a key pair; generate one in memory so tests do not
        // depend on storage/oauth-*.key (absent in CI, unreadable locally).
        self::$passportKeys ??= $this->generateKeyPair();
        config([
            'passport.private_key' => self::$passportKeys['private'],
            'passport.public_key' => self::$passportKeys['public'],
        ]);

        $this->seed(TopicRevisionTemplateSeeder::class);
        $this->user = $this->actingAsUser();
    }

    private function generateKeyPair(): array
    {
        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($resource, $private);
        $public = openssl_pkey_get_details($resource)['key'];

        return ['private' => $private, 'public' => $public];
    }

    protected function actingAsUser(?User $user = null): User
    {
        $user ??= User::factory()->create();
        Passport::actingAs($user, [], 'api');

        return $user;
    }

    protected function actingAsDemo(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_demo' => true])->save();

        return $this->actingAsUser($user);
    }
}
