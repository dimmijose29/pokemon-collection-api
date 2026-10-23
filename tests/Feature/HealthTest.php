<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HealthTest extends TestCase
{
    #[Test]
    public function health_endpoint_responds()
    {
        $this->get('/up')->assertStatus(200);
        $this->getJson('/api/auth/up')->assertStatus(200)->assertJson(['status' => 'ok']);
    }
}
