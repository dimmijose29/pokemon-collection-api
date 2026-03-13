<?php

namespace Tests\Feature\Rarity;

use App\Models\Rarity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RarityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Crea admin e utente normale
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user  = User::factory()->create(['is_admin' => false]);
    }

    /** @test */
    public function anyone_can_view_rarities_list()
    {
        Rarity::factory()->count(5)->create();

        $response = $this->getJson('/api/rarities');

        $response->assertStatus(200)
                 ->assertJsonCount(5);
    }

    /** @test */
    public function anyone_can_view_single_language()
    {
        $language = Rarity::factory()->create([
            'name' => 'Common',
        ]);

        $response = $this->getJson("/api/rarities/{$language->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $language->id,
                     'name' => 'Common',
                 ]);
    }

    /** @test */
    public function admin_can_create_language()
    {
        $payload = [
            'name' => 'Reverse Holo',
        ];

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/rarities', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('name', 'Reverse Holo');

        $this->assertDatabaseHas('rarities', [
            'name' => 'Reverse Holo',
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_language()
    {
        $payload = [
            'name' => 'EX',
        ];

        $response = $this->actingAs($this->user)
                         ->postJson('/api/rarities', $payload);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('rarities', ['code' => 'YY']);
    }

    /** @test */
    public function code_must_be_unique_on_create()
    {
        Rarity::factory()->create(['name' => 'VMAX']);

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/rarities', [
                             'name' => 'VMAX', // nome già esistente
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('name');
    }

    /** @test */
    public function admin_can_update_language()
    {
        $language = Rarity::factory()->create(['name' => 'VSTAR']);
            
            $payload = [
            'name' => 'VMAX'
        ];

        $response = $this->actingAs($this->admin)
                         ->putJson("/api/rarities/{$language->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('name', 'VMAX');

        $this->assertDatabaseHas('rarities', [
            'id' => $language->id,
            'name' => 'VMAX',
        ]);
    }

    /** @test */
    public function non_admin_cannot_update_language()
    {
        $language = Rarity::factory()->create();

        $response = $this->actingAs($this->user)
                         ->putJson("/api/rarities/{$language->id}", [
                             'name' => 'Aggiornato da non-admin',
                         ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('rarities', ['name' => 'Aggiornato da non-admin']);
    }

    /** @test */
    public function admin_can_delete_language()
    {
        $language = Rarity::factory()->create();

        $response = $this->actingAs($this->admin)
                         ->deleteJson("/api/rarities/{$language->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('rarities', ['id' => $language->id]); // se usi soft deletes
        // oppure $this->assertDatabaseMissing('rarities', ['id' => $language->id]);
    }

    /** @test */
    public function non_admin_cannot_delete_language()
    {
        $language = Rarity::factory()->create();

        $response = $this->actingAs($this->user)
                         ->deleteJson("/api/rarities/{$language->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('rarities', ['id' => $language->id]);
    }
}