<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /** @test */
    public function admin_can_create_language()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
                        ->postJson('/api/languages', [
                            'code' => 'xx',
                            'name' => 'Lingua Test',
                            'country_code' => 'xx',
                        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('languages', ['country_code' => 'XX']);
    }

    /** @test */
    public function non_admin_cannot_create_language()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
                        ->postJson('/api/languages', [
                            'code' => 'xx',
                            'name' => 'Lingua Test',
                            'country_code' => 'xx',
                        ]);

        $response->assertStatus(403);
    }

    // Aggiungi test per update, delete, index, show
}