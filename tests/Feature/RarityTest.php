<?php

use App\Models\Rarity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crea admin e utente normale
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user  = User::factory()->create(['is_admin' => false]);
});

test('anyone can view rarities list', function () {
    Rarity::factory()->count(5)->create();

    $response = $this->getJson('/api/rarities');

    $response->assertStatus(200)
             ->assertJsonCount(5);
});

test('anyone can view single rarity', function () {
    $rarity = Rarity::factory()->create([
        'name' => 'Common',
    ]);

    $response = $this->getJson("/api/rarities/{$rarity->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'id' => $rarity->id,
                 'name' => 'Common',
             ]);
});

test('admin can create rarity', function () {
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
});

test('non admin cannot create rarity', function () {
    $payload = [
        'name' => 'EX',
    ];

    $response = $this->actingAs($this->user)
                     ->postJson('/api/rarities', $payload);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('rarities', ['name' => 'EX']);
});

test('code must be unique on create', function () {
    Rarity::factory()->create(['name' => 'VMAX']);

    $response = $this->actingAs($this->admin)
                     ->postJson('/api/rarities', [
                         'name' => 'VMAX', // nome già esistente
                     ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors('name');
});

test('admin can update rarity', function () {
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
});

test('non admin cannot update rarity', function () {
    $rarity = Rarity::factory()->create();

    $response = $this->actingAs($this->user)
                     ->putJson("/api/rarities/{$rarity->id}", [
                         'name' => 'Aggiornato da non-admin',
                     ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('rarities', ['name' => 'Aggiornato da non-admin']);
});

test('admin can delete rarity', function () {
    $rarity = Rarity::factory()->create();

    $response = $this->actingAs($this->admin)
                     ->deleteJson("/api/rarities/{$rarity->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('rarities', ['id' => $rarity->id]);
    // se usi soft deletes
    // oppure $this->assertDatabaseMissing('rarities', ['id' => $rarity->id]);
});

test('non admin cannot delete rarity', function () {
    $rarity = Rarity::factory()->create();

    $response = $this->actingAs($this->user)
                     ->deleteJson("/api/rarities/{$rarity->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('rarities', ['id' => $rarity->id]);
});

test('admin can restore rarity', function () {
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
});

test('index returns soft deleted with trashed param', function () {
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
});

test('unauthenticated cannot create rarity', function () {
    $response = $this->postJson('/api/rarities', ['name' => 'Rarity Non Autenticata']);

    $response->assertStatus(401);

    $this->assertDatabaseMissing('rarities', ['name' => 'Rarity Non Autenticata']);
});