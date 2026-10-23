<?php

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crea admin e utente normale
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user  = User::factory()->create(['is_admin' => false]);
});

test('anyone can view artists list', function () {
    Artist::factory()->count(2)->create();

    $response = $this->getJson('/api/artists');

    $response->assertStatus(200)
             ->assertJsonCount(2);
});

test('anyone can view single artist', function () {
    $artist = Artist::factory()->create([
        'name' => 'Shinji Kanda',
    ]);

    $response = $this->getJson("/api/artists/{$artist->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'id' => $artist->id,
                 'name' => 'Shinji Kanda',
             ]);
});

test('admin can create artist', function () {
    $payload = [
        'name' => 'Tomokazu Komiya',
    ];

    $response = $this->actingAs($this->admin)
                     ->postJson('/api/artists', $payload);

    $response->assertStatus(201)
             ->assertJsonPath('name', 'Tomokazu Komiya');

    $this->assertDatabaseHas('artists', [
        'name' => 'Tomokazu Komiya',
    ]);
});

test('non admin cannot create artist', function () {
    $payload = [
        'name' => 'Tika Matsuno',
    ];

    $response = $this->actingAs($this->user)
                     ->postJson('/api/artists', $payload);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('artists', ['name' => 'Tika Matsuno']);
});

test('code must be unique on create', function () {
    Artist::factory()->create(['name' => 'Tika Matsuno']);

    $response = $this->actingAs($this->admin)
                     ->postJson('/api/artists', [
                         'name' => 'Tika Matsuno', // nome già esistente
                     ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors('name');
});

test('admin can update artist', function () {
    $artist = Artist::factory()->create(['name' => 'Shinji Kanda']);

    $payload = [
    'name' => 'Tika Matsuno'
];

    $response = $this->actingAs($this->admin)
                     ->putJson("/api/artists/{$artist->id}", $payload);

    $response->assertStatus(200)
             ->assertJsonPath('name', 'Tika Matsuno');

    $this->assertDatabaseHas('artists', [
        'id' => $artist->id,
        'name' => 'Tika Matsuno',
    ]);
});

test('non admin cannot update artist', function () {
    $artist = Artist::factory()->create();

    $response = $this->actingAs($this->user)
                     ->putJson("/api/artists/{$artist->id}", [
                         'name' => 'Aggiornato da non-admin',
                     ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('artists', ['name' => 'Aggiornato da non-admin']);
});

test('admin can delete artist', function () {
    $artist = Artist::factory()->create();

    $response = $this->actingAs($this->admin)
                     ->deleteJson("/api/artists/{$artist->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('artists', ['id' => $artist->id]);
    // se usi soft deletes
    // oppure $this->assertDatabaseMissing('artists', ['id' => $artist->id]);
});

test('non admin cannot delete artist', function () {
    $artist = Artist::factory()->create();

    $response = $this->actingAs($this->user)
                     ->deleteJson("/api/artists/{$artist->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('artists', ['id' => $artist->id]);
});

test('admin can restore artist', function () {
    $artist = Artist::factory()->create();
    $artist->delete();
    $this->assertSoftDeleted('artists', ['id' => $artist->id]);

    $response = $this->actingAs($this->admin)
                     ->putJson("/api/artists/{$artist->id}/restore");

    $response->assertStatus(200);

    $this->assertDatabaseHas('artists', [
        'id' => $artist->id,
        'deleted_at' => null,
    ]);
});

test('index returns soft deleted with trashed param', function () {
    Artist::factory()->count(2)->create();
    $deleted = Artist::factory()->create();
    $deleted->delete();

    // Senza withTrashed: solo i 2 attivi
    $this->getJson('/api/artists')
         ->assertStatus(200)
         ->assertJsonCount(2);

    // Con withTrashed=true: tutti e 3
    $this->getJson('/api/artists?withTrashed=true')
         ->assertStatus(200)
         ->assertJsonCount(3);
});

test('unauthenticated cannot create artist', function () {
    $response = $this->postJson('/api/artists', ['name' => 'Artista Non Autenticato']);

    $response->assertStatus(401);

    $this->assertDatabaseMissing('artists', ['name' => 'Artista Non Autenticato']);
});