<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtistTest extends TestCase
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

    #[Test]
    public function anyone_can_view_artists_list()
    {
        Artist::factory()->count(2)->create();

        $response = $this->getJson('/api/artists');

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    #[Test]
    public function anyone_can_view_single_artist()
    {
        $artist = Artist::factory()->create([
            'name' => 'Shinji Kanda',
        ]);

        $response = $this->getJson("/api/artists/{$artist->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $artist->id,
                     'name' => 'Shinji Kanda',
                 ]);
    }

    #[Test]
    public function admin_can_create_artist()
    {
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
    }

    #[Test]
    public function non_admin_cannot_create_artist()
    {
        $payload = [
            'name' => 'Tika Matsuno',
        ];

        $response = $this->actingAs($this->user)
                         ->postJson('/api/artists', $payload);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('artists', ['name' => 'Tika Matsuno']);
    }

    #[Test]
    public function code_must_be_unique_on_create()
    {
        Artist::factory()->create(['name' => 'Tika Matsuno']);

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/artists', [
                             'name' => 'Tika Matsuno', // nome già esistente
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function admin_can_update_artist()
    {
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
    }

    #[Test]
    public function non_admin_cannot_update_artist()
    {
        $artist = Artist::factory()->create();

        $response = $this->actingAs($this->user)
                         ->putJson("/api/artists/{$artist->id}", [
                             'name' => 'Aggiornato da non-admin',
                         ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('artists', ['name' => 'Aggiornato da non-admin']);
    }

    #[Test]
    public function admin_can_delete_artist()
    {
        $artist = Artist::factory()->create();

        $response = $this->actingAs($this->admin)
                         ->deleteJson("/api/artists/{$artist->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('artists', ['id' => $artist->id]); // se usi soft deletes
        // oppure $this->assertDatabaseMissing('artists', ['id' => $artist->id]);
    }

    #[Test]
    public function non_admin_cannot_delete_artist()
    {
        $artist = Artist::factory()->create();

        $response = $this->actingAs($this->user)
                         ->deleteJson("/api/artists/{$artist->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('artists', ['id' => $artist->id]);
    }

    #[Test]
    public function admin_can_restore_artist()
    {
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
    }

    #[Test]
    public function index_returns_soft_deleted_with_trashed_param()
    {
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
    }

    #[Test]
    public function unauthenticated_cannot_create_artist()
    {
        $response = $this->postJson('/api/artists', ['name' => 'Artista Non Autenticato']);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('artists', ['name' => 'Artista Non Autenticato']);
    }
}