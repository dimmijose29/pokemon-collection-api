<?php

use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crea admin e utente normale
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user  = User::factory()->create(['is_admin' => false]);
});

test('anyone can view currencies list', function () {
    Currency::factory()->count(2)->create();

    $response = $this->getJson('/api/currencies');

    $response->assertStatus(200)
             ->assertJsonCount(2);
});

test('anyone can view single currency', function () {
    $currency = Currency::factory()->create([
        'symbol' => '€',
        'code' => 'EUR',
    ]);

    $response = $this->getJson("/api/currencies/{$currency->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'id' => $currency->id,
                 'symbol' => '€',
                 'code' => 'EUR',
             ]);
});

test('admin can create currency', function () {
    $payload = [
        'symbol' => '$',
        'code' => 'USD',
    ];

    $response = $this->actingAs($this->admin)
                     ->postJson('/api/currencies', $payload);

    $response->assertStatus(201)
             ->assertJsonPath('symbol', '$');

    $this->assertDatabaseHas('currencies', [
        'symbol' => '$',
    ]);
});

test('non admin cannot create currency', function () {
    $payload = [
        'symbol' => '€',
        'code' => 'EUR',
    ];

    $response = $this->actingAs($this->user)
                     ->postJson('/api/currencies', $payload);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('currencies', ['symbol' => '€']);
});

test('code must be unique on create', function () {
    Currency::factory()->create(['symbol' => '€']);

    $response = $this->actingAs($this->admin)
                     ->postJson('/api/currencies', [
                         'symbol' => '€', // nome già esistente
                         'code' => 'EUR', // codice già esistente
                     ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors('symbol');
});

test('admin can update currency', function () {
    $currency = Currency::factory()->create(['symbol' => '€', 'code' => 'EUR']);

    $payload = [
        'symbol' => '€',
        'code' => 'EUR',
    ];

    $response = $this->actingAs($this->admin)
                     ->putJson("/api/currencies/{$currency->id}", $payload);

    $response->assertStatus(200)
             ->assertJsonPath('symbol', '€');

    $this->assertDatabaseHas('currencies', [
        'id' => $currency->id,
        'symbol' => '€',
    ]);
});

test('non admin cannot update currency', function () {
    $currency = Currency::factory()->create();

    $response = $this->actingAs($this->user)
                     ->putJson("/api/currencies/{$currency->id}", [
                         'symbol' => 'Aggiornato da non-admin',
                     ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('currencies', ['symbol' => 'Aggiornato da non-admin']);
});

test('admin can delete currency', function () {
    $currency = Currency::factory()->create();

    $response = $this->actingAs($this->admin)
                     ->deleteJson("/api/currencies/{$currency->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('currencies', ['id' => $currency->id]);
    // se usi soft deletes
    // oppure $this->assertDatabaseEURissing('currencies', ['id' => $currency->id]);
});

test('non admin cannot delete currency', function () {
    $currency = Currency::factory()->create();

    $response = $this->actingAs($this->user)
                     ->deleteJson("/api/currencies/{$currency->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('currencies', ['id' => $currency->id]);
});

test('admin can restore currency', function () {
    $currency = Currency::factory()->create();
    $currency->delete();
    $this->assertSoftDeleted('currencies', ['id' => $currency->id]);

    $response = $this->actingAs($this->admin)
                     ->putJson("/api/currencies/{$currency->id}/restore");

    $response->assertStatus(200);

    $this->assertDatabaseHas('currencies', [
        'id' => $currency->id,
        'deleted_at' => null,
    ]);
});

test('index returns soft deleted with trashed param', function () {
    Currency::factory()->count(2)->create();
    $deleted = Currency::factory()->create();
    $deleted->delete();

    // Senza withTrashed: solo i 2 attivi
    $this->getJson('/api/currencies')
         ->assertStatus(200)
         ->assertJsonCount(2);

    // Con withTrashed=true: tutti e 3
    $this->getJson('/api/currencies?withTrashed=true')
         ->assertStatus(200)
         ->assertJsonCount(3);
});

test('unauthenticated cannot create currency', function () {
    $response = $this->postJson('/api/currencies', [
        'symbol' => 'X',
        'code' => 'XXX',
    ]);

    $response->assertStatus(401);

    $this->assertDatabaseMissing('currencies', ['code' => 'XXX']);
});