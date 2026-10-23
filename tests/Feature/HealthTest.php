<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    /** @test */
    public function health_endpoint_responds()
    {
        $this->get('/up')->assertStatus(200);
        $this->getJson('/api/auth/up')->assertStatus(200)->assertJson(['status' => 'ok']);
    }
}
