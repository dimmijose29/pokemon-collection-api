<?php

namespace Tests\Feature;

use App\Models\Condition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConditionTest extends TestCase
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
    public function anyone_can_view_conditions_list()
    {
        Condition::factory()->count(2)->create();

        $response = $this->getJson('/api/conditions');

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    /** @test */
    public function anyone_can_view_single_condition()
    {
        $condition = Condition::factory()->create([
            'name' => 'Mint',
            'code' => 'M',
        ]);

        $response = $this->getJson("/api/conditions/{$condition->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $condition->id,
                     'name' => 'Mint',
                     'code' => 'M',
                 ]);
    }

    /** @test */
    public function admin_can_create_condition()
    {
        $payload = [
            'name' => 'Near Mint',
            'code' => 'NM',
        ];

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/conditions', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('name', 'Near Mint');

        $this->assertDatabaseHas('conditions', [
            'name' => 'Near Mint',
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_condition()
    {
        $payload = [
            'name' => 'Poor',
            'code' => 'P',
        ];

        $response = $this->actingAs($this->user)
                         ->postJson('/api/conditions', $payload);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('conditions', ['name' => 'Poor']);
    }

    /** @test */
    public function code_must_be_unique_on_create()
    {
        Condition::factory()->create(['name' => 'Poor']);

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/conditions', [
                             'name' => 'Poor', // nome già esistente
                             'code' => 'P', // codice già esistente
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('name');
    }

    /** @test */
    public function admin_can_update_condition()
    {
        $condition = Condition::factory()->create(['name' => 'Mint', 'code' => 'M']);
            
            $payload = [
                'name' => 'Poor',
                'code' => 'P',
            ];

        $response = $this->actingAs($this->admin)
                         ->putJson("/api/conditions/{$condition->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('name', 'Poor');

        $this->assertDatabaseHas('conditions', [
            'id' => $condition->id,
            'name' => 'Poor',
        ]);
    }

    /** @test */
    public function non_admin_cannot_update_condition()
    {
        $condition = Condition::factory()->create();

        $response = $this->actingAs($this->user)
                         ->putJson("/api/conditions/{$condition->id}", [
                             'name' => 'Aggiornato da non-admin',
                         ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('conditions', ['name' => 'Aggiornato da non-admin']);
    }

    /** @test */
    public function admin_can_delete_condition()
    {
        $condition = Condition::factory()->create();

        $response = $this->actingAs($this->admin)
                         ->deleteJson("/api/conditions/{$condition->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('conditions', ['id' => $condition->id]); // se usi soft deletes
        // oppure $this->assertDatabaseMissing('conditions', ['id' => $condition->id]);
    }

    /** @test */
    public function non_admin_cannot_delete_condition()
    {
        $condition = Condition::factory()->create();

        $response = $this->actingAs($this->user)
                         ->deleteJson("/api/conditions/{$condition->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('conditions', ['id' => $condition->id]);
    }

    /** @test */
    public function admin_can_restore_condition()
    {
        $condition = Condition::factory()->create();
        $condition->delete();
        $this->assertSoftDeleted('conditions', ['id' => $condition->id]);

        $response = $this->actingAs($this->admin)
                         ->putJson("/api/conditions/{$condition->id}/restore");

        $response->assertStatus(200);

        $this->assertDatabaseHas('conditions', [
            'id' => $condition->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function index_returns_soft_deleted_with_trashed_param()
    {
        Condition::factory()->count(2)->create();
        $deleted = Condition::factory()->create();
        $deleted->delete();

        // Senza withTrashed: solo i 2 attivi
        $this->getJson('/api/conditions')
             ->assertStatus(200)
             ->assertJsonCount(2);

        // Con withTrashed=true: tutti e 3
        $this->getJson('/api/conditions?withTrashed=true')
             ->assertStatus(200)
             ->assertJsonCount(3);
    }

    /** @test */
    public function unauthenticated_cannot_create_condition()
    {
        $response = $this->postJson('/api/conditions', [
            'name' => 'Condition Non Autenticata',
            'code' => 'XX',
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('conditions', ['name' => 'Condition Non Autenticata']);
    }
}