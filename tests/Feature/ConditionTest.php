<?php

namespace Tests\Feature\Condition;

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
}