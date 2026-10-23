<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageTest extends TestCase
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
    public function anyone_can_view_languages_list()
    {
        Language::factory()->count(5)->create();

        $response = $this->getJson('/api/languages');

        $response->assertStatus(200)
                 ->assertJsonCount(5);
    }

    #[Test]
    public function anyone_can_view_single_language()
    {
        $language = Language::factory()->create([
            'code' => 'IT',
            'name' => 'Italiano',
            'country_code' => 'IT',
        ]);

        $response = $this->getJson("/api/languages/{$language->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $language->id,
                     'code' => 'IT',
                     'name' => 'Italiano',
                     'country_code' => 'IT',
                 ]);
    }

    #[Test]
    public function admin_can_create_language()
    {
        $payload = [
            'code' => 'XX',
            'name' => 'Lingua Test',
            'country_code' => 'XX',
        ];

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/languages', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('code', 'XX')
                 ->assertJsonPath('name', 'Lingua Test')
                 ->assertJsonPath('country_code', 'XX');

        $this->assertDatabaseHas('languages', [
            'code' => 'XX',
            'name' => 'Lingua Test',
            'country_code' => 'XX',
        ]);
    }

    #[Test]
    public function non_admin_cannot_create_language()
    {
        $payload = [
            'code' => 'yy',
            'name' => 'Lingua Bloccata',
        ];

        $response = $this->actingAs($this->user)
                         ->postJson('/api/languages', $payload);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('languages', ['code' => 'YY']);
    }

    #[Test]
    public function code_must_be_unique_on_create()
    {
        Language::factory()->create(['code' => 'EN', 'name' => 'English']);

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/languages', [
                             'code' => 'EN',
                             'name' => 'Duplicate',
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('code');
    }

    #[Test]
    public function admin_can_update_language()
    {
        $language = Language::factory()->create([
            'code' => 'FR',
            'name' => 'Français',
            'country_code' => 'FR',
            ]);
            
            $payload = [
            'name' => 'Francese Aggiornato',
            'code' => 'FR',
            'country_code' => 'FR',
        ];

        $response = $this->actingAs($this->admin)
                         ->putJson("/api/languages/{$language->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('name', 'Francese Aggiornato');

        $this->assertDatabaseHas('languages', [
            'id' => $language->id,
            'name' => 'Francese Aggiornato',
        ]);
    }

    #[Test]
    public function non_admin_cannot_update_language()
    {
        $language = Language::factory()->create();

        $response = $this->actingAs($this->user)
                         ->putJson("/api/languages/{$language->id}", [
                             'name' => 'Aggiornato da non-admin',
                         ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('languages', ['name' => 'Aggiornato da non-admin']);
    }

    #[Test]
    public function admin_can_delete_language()
    {
        $language = Language::factory()->create();

        $response = $this->actingAs($this->admin)
                         ->deleteJson("/api/languages/{$language->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('languages', ['id' => $language->id]); // se usi soft deletes
        // oppure $this->assertDatabaseMissing('languages', ['id' => $language->id]);
    }

    #[Test]
    public function non_admin_cannot_delete_language()
    {
        $language = Language::factory()->create();

        $response = $this->actingAs($this->user)
                         ->deleteJson("/api/languages/{$language->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('languages', ['id' => $language->id]);
    }

    #[Test]
    public function admin_can_restore_language()
    {
        $language = Language::factory()->create();
        $language->delete();
        $this->assertSoftDeleted('languages', ['id' => $language->id]);

        $response = $this->actingAs($this->admin)
                         ->putJson("/api/languages/{$language->id}/restore");

        $response->assertStatus(200);

        $this->assertDatabaseHas('languages', [
            'id' => $language->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function index_returns_soft_deleted_with_trashed_param()
    {
        Language::factory()->count(2)->create();
        $deleted = Language::factory()->create();
        $deleted->delete();

        // Senza withTrashed: solo i 2 attivi
        $this->getJson('/api/languages')
             ->assertStatus(200)
             ->assertJsonCount(2);

        // Con withTrashed=true: tutti e 3
        $this->getJson('/api/languages?withTrashed=true')
             ->assertStatus(200)
             ->assertJsonCount(3);
    }

    #[Test]
    public function unauthenticated_cannot_create_language()
    {
        $response = $this->postJson('/api/languages', [
            'code' => 'ZZ',
            'name' => 'Lingua Non Autenticata',
            'country_code' => 'ZZ',
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('languages', ['code' => 'ZZ']);
    }
}