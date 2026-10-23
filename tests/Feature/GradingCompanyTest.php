<?php

use App\Models\GradingCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crea admin e utente normale
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user  = User::factory()->create(['is_admin' => false]);
});

test('anyone can view grading companies list', function () {
    GradingCompany::factory()->count(2)->create();

    $response = $this->getJson('/api/grading-companies');

    $response->assertStatus(200)
             ->assertJsonCount(2);
});

test('anyone can view single grading company', function () {
    $gradingCompany = GradingCompany::factory()->create([
        'name' => 'PSA',
    ]);

    $response = $this->getJson("/api/grading-companies/{$gradingCompany->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'id' => $gradingCompany->id,
                 'name' => 'PSA',
             ]);
});

test('admin can create grading company', function () {
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
});

test('non admin cannot create grading company', function () {
    $payload = [
        'name' => 'TAG',
    ];

    $response = $this->actingAs($this->user)
                     ->postJson('/api/grading-companies', $payload);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('grading_companies', ['name' => 'TAG']);
});

test('code must be unique on create', function () {
    GradingCompany::factory()->create(['name' => 'TAG']);

    $response = $this->actingAs($this->admin)
                     ->postJson('/api/grading-companies', [
                         'name' => 'TAG', // nome già esistente
                     ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors('name');
});

test('admin can update grading company', function () {
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
});

test('non admin cannot update grading company', function () {
    $gradingCompany = GradingCompany::factory()->create();

    $response = $this->actingAs($this->user)
                     ->putJson("/api/grading-companies/{$gradingCompany->id}", [
                         'name' => 'Aggiornato da non-admin',
                     ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('grading_companies', ['name' => 'Aggiornato da non-admin']);
});

test('admin can delete grading company', function () {
    $gradingCompany = GradingCompany::factory()->create();

    $response = $this->actingAs($this->admin)
                     ->deleteJson("/api/grading-companies/{$gradingCompany->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('grading_companies', ['id' => $gradingCompany->id]);
    // se usi soft deletes
    // oppure $this->assertDatabaseMissing('grading_companies', ['id' => $gradingCompany->id]);
});

test('non admin cannot delete grading company', function () {
    $gradingCompany = GradingCompany::factory()->create();

    $response = $this->actingAs($this->user)
                     ->deleteJson("/api/grading-companies/{$gradingCompany->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('grading_companies', ['id' => $gradingCompany->id]);
});

test('admin can restore grading company', function () {
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
});

test('index returns soft deleted with trashed param', function () {
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
});

test('unauthenticated cannot create grading company', function () {
    $response = $this->postJson('/api/grading-companies', ['name' => 'GradingCompany Non Autenticata']);

    $response->assertStatus(401);

    $this->assertDatabaseMissing('grading_companies', ['name' => 'GradingCompany Non Autenticata']);
});