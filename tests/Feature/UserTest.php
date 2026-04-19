<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user  = User::factory()->create(['is_admin' => false]);
    }

    /** @test */
    public function admin_can_view_users_list()
    {
        User::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
                         ->getJson('/api/users');

        // admin + user + 3 factory = 5
        $response->assertStatus(200)
                 ->assertJsonCount(5);
    }

    /** @test */
    public function non_admin_cannot_view_users_list()
    {
        $response = $this->actingAs($this->user)
                         ->getJson('/api/users');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_cannot_view_users_list()
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_view_single_user()
    {
        $target = User::factory()->create(['username' => 'testuser']);

        $response = $this->actingAs($this->admin)
                         ->getJson("/api/users/{$target->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('username', 'testuser');
    }

    /** @test */
    public function admin_can_create_user()
    {
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
    }

    /** @test */
    public function non_admin_cannot_create_user()
    {
        $response = $this->actingAs($this->user)
                         ->postJson('/api/users', [
                             'email'    => 'nuovo@utente.it',
                             'username' => 'nuovoutente',
                             'password' => 'password123',
                         ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'nuovo@utente.it']);
    }

    /** @test */
    public function admin_can_toggle_is_admin()
    {
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
    }

    /** @test */
    public function non_admin_cannot_update_another_user()
    {
        $target = User::factory()->create(['username' => 'original']);

        $response = $this->actingAs($this->user)
                         ->patchJson("/api/users/{$target->id}", [
                             'username' => 'modificato',
                         ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('users', ['username' => 'original']);
    }

    /** @test */
    public function admin_can_upload_avatar()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->actingAs($this->admin)
                         ->patch("/api/users/{$this->user->id}", [
                             'avatar' => $file,
                         ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('avatar_url'));
        Storage::disk('public')->assertExists('avatars/' . $file->hashName());
    }

    /** @test */
    public function admin_can_delete_user()
    {
        $target = User::factory()->create();

        $response = $this->actingAs($this->admin)
                         ->deleteJson("/api/users/{$target->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    /** @test */
    public function admin_cannot_delete_themselves()
    {
        $response = $this->actingAs($this->admin)
                         ->deleteJson("/api/users/{$this->admin->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('users', [
            'id'         => $this->admin->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function non_admin_cannot_delete_user()
    {
        $target = User::factory()->create();

        $response = $this->actingAs($this->user)
                         ->deleteJson("/api/users/{$target->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    /** @test */
    public function admin_can_restore_deleted_user()
    {
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
    }

    /** @test */
    public function index_returns_soft_deleted_with_trashed_param()
    {
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
    }

    /** @test */
    public function username_must_be_unique_on_update()
    {
        $other = User::factory()->create(['username' => 'taken_username']);

        $response = $this->actingAs($this->admin)
                         ->patchJson("/api/users/{$this->user->id}", [
                             'username' => 'taken_username',
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('username');
    }
}
