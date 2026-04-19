<?php

namespace Tests\Feature\GradingCompany;

use App\Models\GradingCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingCompanyTest extends TestCase
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
    public function anyone_can_view_grading_companies_list()
    {
        GradingCompany::factory()->count(2)->create();

        $response = $this->getJson('/api/grading-companies');

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    /** @test */
    public function anyone_can_view_single_grading_company()
    {
        $gradingCompany = GradingCompany::factory()->create([
            'name' => 'PSA',
        ]);

        $response = $this->getJson("/api/grading-companies/{$gradingCompany->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $gradingCompany->id,
                     'name' => 'PSA',
                 ]);
    }

    /** @test */
    public function admin_can_create_grading_company()
    {
        $payload = [
            'name' => 'BGS',
        ];

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/grading-companies', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('name', 'BGS');

        $this->assertDatabaseHas('grading_companies', [
            'name' => 'BGS',
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_grading_company()
    {
        $payload = [
            'name' => 'TAG',
        ];

        $response = $this->actingAs($this->user)
                         ->postJson('/api/grading-companies', $payload);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('grading_companies', ['name' => 'TAG']);
    }

    /** @test */
    public function code_must_be_unique_on_create()
    {
        GradingCompany::factory()->create(['name' => 'TAG']);

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/grading-companies', [
                             'name' => 'TAG', // nome già esistente
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('name');
    }

    /** @test */
    public function admin_can_update_grading_company()
    {
        $gradingCompany = GradingCompany::factory()->create(['name' => 'PSA']);
            
            $payload = [
            'name' => 'TAG'
        ];

        $response = $this->actingAs($this->admin)
                         ->putJson("/api/grading-companies/{$gradingCompany->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('name', 'TAG');

        $this->assertDatabaseHas('grading_companies', [
            'id' => $gradingCompany->id,
            'name' => 'TAG',
        ]);
    }

    /** @test */
    public function non_admin_cannot_update_grading_company()
    {
        $gradingCompany = GradingCompany::factory()->create();

        $response = $this->actingAs($this->user)
                         ->putJson("/api/grading-companies/{$gradingCompany->id}", [
                             'name' => 'Aggiornato da non-admin',
                         ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('grading_companies', ['name' => 'Aggiornato da non-admin']);
    }

    /** @test */
    public function admin_can_delete_grading_company()
    {
        $gradingCompany = GradingCompany::factory()->create();

        $response = $this->actingAs($this->admin)
                         ->deleteJson("/api/grading-companies/{$gradingCompany->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('grading_companies', ['id' => $gradingCompany->id]); // se usi soft deletes
        // oppure $this->assertDatabaseMissing('grading_companies', ['id' => $gradingCompany->id]);
    }

    /** @test */
    public function non_admin_cannot_delete_grading_company()
    {
        $gradingCompany = GradingCompany::factory()->create();

        $response = $this->actingAs($this->user)
                         ->deleteJson("/api/grading-companies/{$gradingCompany->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('grading_companies', ['id' => $gradingCompany->id]);
    }

    /** @test */
    public function admin_can_restore_grading_company()
    {
        $gradingCompany = GradingCompany::factory()->create();
        $gradingCompany->delete();
        $this->assertSoftDeleted('grading_companies', ['id' => $gradingCompany->id]);

        $response = $this->actingAs($this->admin)
                         ->putJson("/api/grading-companies/{$gradingCompany->id}/restore");

        $response->assertStatus(200);

        $this->assertDatabaseHas('grading_companies', [
            'id' => $gradingCompany->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function index_returns_soft_deleted_with_trashed_param()
    {
        GradingCompany::factory()->count(2)->create();
        $deleted = GradingCompany::factory()->create();
        $deleted->delete();

        // Senza withTrashed: solo i 2 attivi
        $this->getJson('/api/grading-companies')
             ->assertStatus(200)
             ->assertJsonCount(2);

        // Con withTrashed=true: tutti e 3
        $this->getJson('/api/grading-companies?withTrashed=true')
             ->assertStatus(200)
             ->assertJsonCount(3);
    }

    /** @test */
    public function unauthenticated_cannot_create_grading_company()
    {
        $response = $this->postJson('/api/grading-companies', ['name' => 'GradingCompany Non Autenticata']);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('grading_companies', ['name' => 'GradingCompany Non Autenticata']);
    }
}