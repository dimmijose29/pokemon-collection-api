<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user  = User::factory()->create(['is_admin' => false]);
});

test('admin can view users list', function () {
    User::factory()->count(3)->create();

    $response = $this->actingAs($this->admin)
                     ->getJson('/api/users');

    // admin + user + 3 factory = 5
    $response->assertStatus(200)
             ->assertJsonCount(5);
});

test('non admin cannot view users list', function () {
    $response = $this->actingAs($this->user)
                     ->getJson('/api/users');

    $response->assertStatus(403);
});

test('unauthenticated cannot view users list', function () {
    $response = $this->getJson('/api/users');

    $response->assertStatus(401);
});

test('admin can view single user', function () {
    $target = User::factory()->create(['username' => 'testuser']);

    $response = $this->actingAs($this->admin)
                     ->getJson("/api/users/{$target->id}");

    $response->assertStatus(200)
             ->assertJsonPath('username', 'testuser');
});

test('admin can create user', function () {
    $payload = [
        'email'    => 'nuovo@utente.it',
        'username' => 'nuovoutente',
        'password' => 'password123',
    ];

    $response = $this->actingAs($this->admin)
                     ->postJson('/api/users', $payload);

    $response->assertStatus(201)
             ->assertJsonPath('email', 'nuovo@utente.it')
             ->assertJsonPath('username', 'nuovoutente')
             ->assertJsonPath('is_admin', false);

    $this->assertDatabaseHas('users', ['email' => 'nuovo@utente.it']);
});

test('non admin cannot create user', function () {
    $response = $this->actingAs($this->user)
                     ->postJson('/api/users', [
                         'email'    => 'nuovo@utente.it',
                         'username' => 'nuovoutente',
                         'password' => 'password123',
                     ]);

    $response->assertStatus(403);
    $this->assertDatabaseMissing('users', ['email' => 'nuovo@utente.it']);
});

test('admin can toggle is admin', function () {
    $target = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($this->admin)
                     ->patchJson("/api/users/{$target->id}", [
                         'is_admin' => true,
                     ]);

    $response->assertStatus(200)
             ->assertJsonPath('is_admin', true);

    $this->assertDatabaseHas('users', [
        'id'       => $target->id,
        'is_admin' => true,
    ]);
});

test('non admin cannot update another user', function () {
    $target = User::factory()->create(['username' => 'original']);

    $response = $this->actingAs($this->user)
                     ->patchJson("/api/users/{$target->id}", [
                         'username' => 'modificato',
                     ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('users', ['username' => 'original']);
});

test('admin can upload avatar', function () {
    Storage::fake('avatars');

    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    $response = $this->actingAs($this->admin)
                     ->patch("/api/users/{$this->user->id}", [
                         'avatar' => $file,
                     ]);

    $response->assertStatus(200);
    expect($response->json('avatar_url'))->not->toBeNull();
    Storage::disk('avatars')->assertExists($file->hashName());
});

test('admin can delete user', function () {
    $target = User::factory()->create();

    $response = $this->actingAs($this->admin)
                     ->deleteJson("/api/users/{$target->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('users', ['id' => $target->id]);
});

test('admin cannot delete themselves', function () {
    $response = $this->actingAs($this->admin)
                     ->deleteJson("/api/users/{$this->admin->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('users', [
        'id'         => $this->admin->id,
        'deleted_at' => null,
    ]);
});

test('non admin cannot delete user', function () {
    $target = User::factory()->create();

    $response = $this->actingAs($this->user)
                     ->deleteJson("/api/users/{$target->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('users', ['id' => $target->id]);
});

test('admin can restore deleted user', function () {
    $target = User::factory()->create();
    $target->delete();
    $this->assertSoftDeleted('users', ['id' => $target->id]);

    $response = $this->actingAs($this->admin)
                     ->putJson("/api/users/{$target->id}/restore");

    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id'         => $target->id,
        'deleted_at' => null,
    ]);
});

test('index returns soft deleted with trashed param', function () {
    $active  = User::factory()->count(2)->create();
    $deleted = User::factory()->create();
    $deleted->delete();

    // Senza withTrashed: solo attivi (admin + user setUp + 2 factory)
    $this->actingAs($this->admin)
         ->getJson('/api/users')
         ->assertStatus(200)
         ->assertJsonCount(4);

    // Con withTrashed: anche il deleted (+ 1)
    $this->actingAs($this->admin)
         ->getJson('/api/users?withTrashed=true')
         ->assertStatus(200)
         ->assertJsonCount(5);
});

test('username must be unique on update', function () {
    $other = User::factory()->create(['username' => 'taken_username']);

    $response = $this->actingAs($this->admin)
                     ->patchJson("/api/users/{$this->user->id}", [
                         'username' => 'taken_username',
                     ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors('username');
});