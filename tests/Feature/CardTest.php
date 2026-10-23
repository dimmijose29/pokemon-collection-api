<?php

use App\Models\Card;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user  = User::factory()->create(['is_admin' => false]);
    $this->set   = Set::factory()->create();
});

test('anyone can view cards list', function () {
    Card::factory()->count(3)->create(['set_id' => $this->set->id]);

    $this->getJson('/api/cards')
         ->assertStatus(200)
         ->assertJsonCount(3);
});

test('index supports set id filter', function () {
    $otherSet = Set::factory()->create();
    Card::factory()->count(2)->create(['set_id' => $this->set->id]);
    Card::factory()->create(['set_id' => $otherSet->id]);

    $this->getJson("/api/cards?set_id={$this->set->id}")
         ->assertStatus(200)
         ->assertJsonCount(2);
});

test('paginated index caps per page', function () {
    Card::factory()->count(3)->create(['set_id' => $this->set->id]);

    $this->getJson('/api/cards?page=1&per_page=100000')
         ->assertStatus(200)
         ->assertJsonPath('meta.per_page', 100);
});

test('anyone can view single card', function () {
    $card = Card::factory()->create([
        'set_id'   => $this->set->id,
        'local_id' => '001',
    ]);

    $this->getJson("/api/cards/{$card->id}")
         ->assertStatus(200)
         ->assertJsonPath('local_id', '001');
});

test('admin can create card', function () {
    $payload = [
        'tcgdex_id' => 'swsh1-001',
        'set_id'    => $this->set->id,
        'local_id'  => '001',
    ];

    $this->actingAs($this->admin)
         ->postJson('/api/cards', $payload)
         ->assertStatus(201)
         ->assertJsonPath('local_id', '001');

    $this->assertDatabaseHas('cards', ['tcgdex_id' => 'swsh1-001']);
});

test('non admin cannot create card', function () {
    $this->actingAs($this->user)
         ->postJson('/api/cards', [
             'set_id'    => $this->set->id,
             'local_id'  => '001',
             'tcgdex_id' => 'swsh1-001',
         ])
         ->assertStatus(403);
});

test('unauthenticated cannot create card', function () {
    $this->postJson('/api/cards', [
        'set_id'    => $this->set->id,
        'local_id'  => '001',
        'tcgdex_id' => 'swsh1-001',
    ])->assertStatus(401);
});

test('set id is required on create', function () {
    $this->actingAs($this->admin)
         ->postJson('/api/cards', ['local_id' => '001', 'tcgdex_id' => 'x-001'])
         ->assertStatus(422)
         ->assertJsonValidationErrors('set_id');
});

test('admin can update card', function () {
    $card = Card::factory()->create(['set_id' => $this->set->id]);

    $this->actingAs($this->admin)
         ->putJson("/api/cards/{$card->id}", [
             'set_id'   => $this->set->id,
             'local_id' => '999',
         ])
         ->assertStatus(200)
         ->assertJsonPath('local_id', '999');
});

test('non admin cannot update card', function () {
    $card = Card::factory()->create(['set_id' => $this->set->id]);

    $this->actingAs($this->user)
         ->putJson("/api/cards/{$card->id}", ['local_id' => '999'])
         ->assertStatus(403);
});

test('admin can delete card', function () {
    $card = Card::factory()->create(['set_id' => $this->set->id]);

    $this->actingAs($this->admin)
         ->deleteJson("/api/cards/{$card->id}")
         ->assertStatus(200);

    $this->assertSoftDeleted('cards', ['id' => $card->id]);
});

test('non admin cannot delete card', function () {
    $card = Card::factory()->create(['set_id' => $this->set->id]);

    $this->actingAs($this->user)
         ->deleteJson("/api/cards/{$card->id}")
         ->assertStatus(403);
});

test('admin can restore card', function () {
    $card = Card::factory()->create(['set_id' => $this->set->id]);
    $card->delete();
    $this->assertSoftDeleted('cards', ['id' => $card->id]);

    $this->actingAs($this->admin)
         ->putJson("/api/cards/{$card->id}/restore")
         ->assertStatus(200);

    $this->assertDatabaseHas('cards', ['id' => $card->id, 'deleted_at' => null]);
});

test('index returns soft deleted with trashed param', function () {
    Card::factory()->count(2)->create(['set_id' => $this->set->id]);
    Card::factory()->create(['set_id' => $this->set->id])->delete();

    $this->getJson('/api/cards')->assertJsonCount(2);
    $this->getJson('/api/cards?withTrashed=true')->assertJsonCount(3);
});

test('image url has quality suffix appended', function () {
    $card = Card::factory()->create([
        'set_id'    => $this->set->id,
        'image_url' => 'https://assets.tcgdex.net/en/swsh/swsh1/1',
    ]);

    $this->getJson("/api/cards/{$card->id}")
         ->assertJsonPath('image_url', 'https://assets.tcgdex.net/en/swsh/swsh1/1/high.webp');
});