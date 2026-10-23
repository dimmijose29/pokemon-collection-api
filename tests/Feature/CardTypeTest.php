<?php

use App\Models\CardType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crea admin e utente normale
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user  = User::factory()->create(['is_admin' => false]);
});

test('anyone can view card types list', function () {
    CardType::factory()->count(2)->create();

    $response = $this->getJson('/api/card-types');

    $response->assertStatus(200)
             ->assertJsonCount(2);
});

test('anyone can view single card type', function () {
    $cardType = CardType::factory()->create([
        'name' => 'Pokémon',
    ]);

    $response = $this->getJson("/api/card-types/{$cardType->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'id' => $cardType->id,
                 'name' => 'Pokémon',
             ]);
});

test('admin can create card type', function () {
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
});

test('non admin cannot create card type', function () {
    $payload = [
        'name' => 'Energy',
    ];

    $response = $this->actingAs($this->user)
                     ->postJson('/api/card-types', $payload);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('card_types', ['name' => 'Energy']);
});

test('code must be unique on create', function () {
    CardType::factory()->create(['name' => 'Energy']);

    $response = $this->actingAs($this->admin)
                     ->postJson('/api/card-types', [
                         'name' => 'Energy', // nome già esistente
                     ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors('name');
});

test('admin can update card type', function () {
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
});

test('non admin cannot update card type', function () {
    $cardType = CardType::factory()->create();

    $response = $this->actingAs($this->user)
                     ->putJson("/api/card-types/{$cardType->id}", [
                         'name' => 'Aggiornato da non-admin',
                     ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('card_types', ['name' => 'Aggiornato da non-admin']);
});

test('admin can delete card type', function () {
    $cardType = CardType::factory()->create();

    $response = $this->actingAs($this->admin)
                     ->deleteJson("/api/card-types/{$cardType->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('card_types', ['id' => $cardType->id]);
    // se usi soft deletes
    // oppure $this->assertDatabaseMissing('card_types', ['id' => $cardType->id]);
});

test('non admin cannot delete card type', function () {
    $cardType = CardType::factory()->create();

    $response = $this->actingAs($this->user)
                     ->deleteJson("/api/card-types/{$cardType->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('card_types', ['id' => $cardType->id]);
});

test('admin can restore card type', function () {
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
});

test('index returns soft deleted with trashed param', function () {
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
});

test('unauthenticated cannot create card type', function () {
    $response = $this->postJson('/api/card-types', ['name' => 'CardType Non Autenticato']);

    $response->assertStatus(401);

    $this->assertDatabaseMissing('card_types', ['name' => 'CardType Non Autenticato']);
});