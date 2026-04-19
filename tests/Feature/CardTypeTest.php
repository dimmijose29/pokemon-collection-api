<?php

namespace Tests\Feature\CardType;

use App\Models\CardType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardTypeTest extends TestCase
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
    public function anyone_can_view_card_types_list()
    {
        CardType::factory()->count(2)->create();

        $response = $this->getJson('/api/card-types');

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    /** @test */
    public function anyone_can_view_single_card_type()
    {
        $cardType = CardType::factory()->create([
            'name' => 'Pokémon',
        ]);

        $response = $this->getJson("/api/card-types/{$cardType->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $cardType->id,
                     'name' => 'Pokémon',
                 ]);
    }

    /** @test */
    public function admin_can_create_card_type()
    {
        $payload = [
            'name' => 'Trainer',
        ];

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/card-types', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('name', 'Trainer');

        $this->assertDatabaseHas('card_types', [
            'name' => 'Trainer',
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_card_type()
    {
        $payload = [
            'name' => 'Energy',
        ];

        $response = $this->actingAs($this->user)
                         ->postJson('/api/card-types', $payload);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('card_types', ['name' => 'Energy']);
    }

    /** @test */
    public function code_must_be_unique_on_create()
    {
        CardType::factory()->create(['name' => 'Energy']);

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/card-types', [
                             'name' => 'Energy', // nome già esistente
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('name');
    }

    /** @test */
    public function admin_can_update_card_type()
    {
        $cardType = CardType::factory()->create(['name' => 'Pokémon']);
            
            $payload = [
            'name' => 'Energy'
        ];

        $response = $this->actingAs($this->admin)
                         ->putJson("/api/card-types/{$cardType->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('name', 'Energy');

        $this->assertDatabaseHas('card_types', [
            'id' => $cardType->id,
            'name' => 'Energy',
        ]);
    }

    /** @test */
    public function non_admin_cannot_update_card_type()
    {
        $cardType = CardType::factory()->create();

        $response = $this->actingAs($this->user)
                         ->putJson("/api/card-types/{$cardType->id}", [
                             'name' => 'Aggiornato da non-admin',
                         ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('card_types', ['name' => 'Aggiornato da non-admin']);
    }

    /** @test */
    public function admin_can_delete_card_type()
    {
        $cardType = CardType::factory()->create();

        $response = $this->actingAs($this->admin)
                         ->deleteJson("/api/card-types/{$cardType->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('card_types', ['id' => $cardType->id]); // se usi soft deletes
        // oppure $this->assertDatabaseMissing('card_types', ['id' => $cardType->id]);
    }

    /** @test */
    public function non_admin_cannot_delete_card_type()
    {
        $cardType = CardType::factory()->create();

        $response = $this->actingAs($this->user)
                         ->deleteJson("/api/card-types/{$cardType->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('card_types', ['id' => $cardType->id]);
    }

    /** @test */
    public function admin_can_restore_card_type()
    {
        $cardType = CardType::factory()->create();
        $cardType->delete();
        $this->assertSoftDeleted('card_types', ['id' => $cardType->id]);

        $response = $this->actingAs($this->admin)
                         ->putJson("/api/card-types/{$cardType->id}/restore");

        $response->assertStatus(200);

        $this->assertDatabaseHas('card_types', [
            'id' => $cardType->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function index_returns_soft_deleted_with_trashed_param()
    {
        CardType::factory()->count(2)->create();
        $deleted = CardType::factory()->create();
        $deleted->delete();

        // Senza withTrashed: solo i 2 attivi
        $this->getJson('/api/card-types')
             ->assertStatus(200)
             ->assertJsonCount(2);

        // Con withTrashed=true: tutti e 3
        $this->getJson('/api/card-types?withTrashed=true')
             ->assertStatus(200)
             ->assertJsonCount(3);
    }

    /** @test */
    public function unauthenticated_cannot_create_card_type()
    {
        $response = $this->postJson('/api/card-types', ['name' => 'CardType Non Autenticato']);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('card_types', ['name' => 'CardType Non Autenticato']);
    }
}