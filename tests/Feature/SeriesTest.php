<?php

use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user  = User::factory()->create(['is_admin' => false]);
});

test('anyone can view series list', function () {
    Series::factory()->count(3)->create();

    $this->getJson('/api/series')->assertStatus(200);
});

test('admin can create series', function () {
    $this->actingAs($this->admin)
         ->postJson('/api/series', ['name' => 'Sword & Shield', 'tcgdex_id' => 'swsh'])
         ->assertStatus(201)
         ->assertJsonPath('name', 'Sword & Shield');

    $this->assertDatabaseHas('series', ['tcgdex_id' => 'swsh']);
});

test('admin can update series', function () {
    $series = Series::factory()->create(['name' => 'Vecchio nome']);

    $this->actingAs($this->admin)
         ->putJson("/api/series/{$series->id}", ['name' => 'Nuovo nome'])
         ->assertStatus(200)
         ->assertJsonPath('name', 'Nuovo nome');
});

test('series name is required', function () {
    $this->actingAs($this->admin)
         ->postJson('/api/series', [])
         ->assertStatus(422)
         ->assertJsonValidationErrors('name');
});

test('non admin cannot create series', function () {
    $this->actingAs($this->user)
         ->postJson('/api/series', ['name' => 'Bloccata'])
         ->assertStatus(403);
});

test('admin can delete and restore series', function () {
    $series = Series::factory()->create();

    $this->actingAs($this->admin)->deleteJson("/api/series/{$series->id}")->assertSuccessful();
    $this->assertSoftDeleted('series', ['id' => $series->id]);

    $this->actingAs($this->admin)->putJson("/api/series/{$series->id}/restore")->assertSuccessful();
    $this->assertNotSoftDeleted('series', ['id' => $series->id]);
});