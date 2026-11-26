<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_endpoints_are_throttled_globally(): void
    {
        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->getJson('/api/v1/categories')->assertOk();
        }

        $this->getJson('/api/v1/categories')
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonStructure(['message']);
    }
}
