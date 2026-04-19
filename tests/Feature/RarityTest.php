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
    public function anyone_can_view_single_rarity()
    {
        $rarity = Rarity::factory()->create([
            'name' => 'Common',
        ]);

        $response = $this->getJson("/api/rarities/{$rarity->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $rarity->id,
                     'name' => 'Common',
                 ]);
    }

    /** @test */
    public function admin_can_create_rarity()
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
    public function non_admin_cannot_create_rarity()
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
    public function admin_can_update_rarity()
    {
        $rarity = Rarity::factory()->create(['name' => 'VSTAR']);
            
            $payload = [
            'name' => 'VMAX'
        ];

        $response = $this->actingAs($this->admin)
                         ->putJson("/api/rarities/{$rarity->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('name', 'VMAX');

        $this->assertDatabaseHas('rarities', [
            'id' => $rarity->id,
            'name' => 'VMAX',
        ]);
    }

    /** @test */
    public function non_admin_cannot_update_rarity()
    {
        $rarity = Rarity::factory()->create();

        $response = $this->actingAs($this->user)
                         ->putJson("/api/rarities/{$rarity->id}", [
                             'name' => 'Aggiornato da non-admin',
                         ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('rarities', ['name' => 'Aggiornato da non-admin']);
    }

    /** @test */
    public function admin_can_delete_rarity()
    {
        $rarity = Rarity::factory()->create();

        $response = $this->actingAs($this->admin)
                         ->deleteJson("/api/rarities/{$rarity->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('rarities', ['id' => $rarity->id]); // se usi soft deletes
        // oppure $this->assertDatabaseMissing('rarities', ['id' => $rarity->id]);
    }

    /** @test */
    public function non_admin_cannot_delete_rarity()
    {
        $rarity = Rarity::factory()->create();

        $response = $this->actingAs($this->user)
                         ->deleteJson("/api/rarities/{$rarity->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('rarities', ['id' => $rarity->id]);
    }

    /** @test */
    public function admin_can_restore_rarity()
    {
        $rarity = Rarity::factory()->create();
        $rarity->delete();
        $this->assertSoftDeleted('rarities', ['id' => $rarity->id]);

        $response = $this->actingAs($this->admin)
                         ->putJson("/api/rarities/{$rarity->id}/restore");

        $response->assertStatus(200);

        $this->assertDatabaseHas('rarities', [
            'id' => $rarity->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function index_returns_soft_deleted_with_trashed_param()
    {
        Rarity::factory()->count(2)->create();
        $deleted = Rarity::factory()->create();
        $deleted->delete();

        // Senza withTrashed: solo i 2 attivi
        $this->getJson('/api/rarities')
             ->assertStatus(200)
             ->assertJsonCount(2);

        // Con withTrashed=true: tutti e 3
        $this->getJson('/api/rarities?withTrashed=true')
             ->assertStatus(200)
             ->assertJsonCount(3);
    }

    /** @test */
    public function unauthenticated_cannot_create_rarity()
    {
        $response = $this->postJson('/api/rarities', ['name' => 'Rarity Non Autenticata']);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('rarities', ['name' => 'Rarity Non Autenticata']);
    }
}