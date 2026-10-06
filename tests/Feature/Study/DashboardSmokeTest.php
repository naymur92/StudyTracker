<?php

namespace Tests\Feature\Study;

class DashboardSmokeTest extends StudyApiTestCase
{
    public function test_dashboard_returns_success_envelope(): void
    {
        $this->getJson('/api/study/dashboard')
            ->assertOk()
            ->assertJsonPath('flag', true);
    }
}
