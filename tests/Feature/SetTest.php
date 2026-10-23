<?php

namespace Tests\Feature\Set;

use App\Models\Series;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetTest extends TestCase
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
    public function anyone_can_view_sets_list()
    {
        Set::factory()->count(3)->create();

        $this->getJson('/api/sets')
             ->assertStatus(200)
             ->assertJsonCount(3);
    }

    /** @test */
    public function anyone_can_view_single_set()
    {
        $set = Set::factory()->create(['name' => 'Sword & Shield']);

        $this->getJson("/api/sets/{$set->id}")
             ->assertStatus(200)
             ->assertJsonPath('name', 'Sword & Shield');
    }

    /** @test */
    public function index_supports_series_filter()
    {
        $swordShield = Series::factory()->create(['name' => 'Sword & Shield']);
        $scarletViolet = Series::factory()->create(['name' => 'Scarlet & Violet']);
        Set::factory()->count(2)->create(['series_id' => $swordShield->id]);
        Set::factory()->create(['series_id' => $scarletViolet->id]);

        $this->getJson("/api/sets?series_id={$swordShield->id}")
             ->assertStatus(200)
             ->assertJsonCount(2);
    }

    /** @test */
    public function admin_can_create_set()
    {
        $payload = [
            'tcgdex_id'           => 'test1',
            'name'                => 'Test Set',
            'series_id'           => Series::factory()->create()->id,
            'card_count_official' => 100,
            'card_count_total'    => 110,
            'release_date'        => '2024-01-01',
        ];

        $this->actingAs($this->admin)
             ->postJson('/api/sets', $payload)
             ->assertStatus(201)
             ->assertJsonPath('name', 'Test Set');

        $this->assertDatabaseHas('sets', ['tcgdex_id' => 'test1']);
    }

    /** @test */
    public function creating_set_requires_tcgdex_id()
    {
        $this->actingAs($this->admin)
             ->postJson('/api/sets', ['name' => 'Senza id'])
             ->assertStatus(422)
             ->assertJsonValidationErrors('tcgdex_id');
    }

    /** @test */
    public function non_admin_cannot_create_set()
    {
        $this->actingAs($this->user)
             ->postJson('/api/sets', ['name' => 'Blocked Set', 'tcgdex_id' => 'blk1'])
             ->assertStatus(403);

        $this->assertDatabaseMissing('sets', ['tcgdex_id' => 'blk1']);
    }

    /** @test */
    public function unauthenticated_cannot_create_set()
    {
        $this->postJson('/api/sets', ['name' => 'Unauth Set', 'tcgdex_id' => 'unauth1'])
             ->assertStatus(401);
    }

    /** @test */
    public function tcgdex_id_must_be_unique_on_create()
    {
        Set::factory()->create(['tcgdex_id' => 'swsh1']);

        $this->actingAs($this->admin)
             ->postJson('/api/sets', ['name' => 'Duplicate', 'tcgdex_id' => 'swsh1'])
             ->assertStatus(422)
             ->assertJsonValidationErrors('tcgdex_id');
    }

    /** @test */
    public function admin_can_update_set()
    {
        $set = Set::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->admin)
             ->putJson("/api/sets/{$set->id}", ['name' => 'New Name'])
             ->assertStatus(200)
             ->assertJsonPath('name', 'New Name');

        $this->assertDatabaseHas('sets', ['id' => $set->id, 'name' => 'New Name']);
    }

    /** @test */
    public function non_admin_cannot_update_set()
    {
        $set = Set::factory()->create();

        $this->actingAs($this->user)
             ->putJson("/api/sets/{$set->id}", ['name' => 'Hacked'])
             ->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_set()
    {
        $set = Set::factory()->create();

        $this->actingAs($this->admin)
             ->deleteJson("/api/sets/{$set->id}")
             ->assertStatus(200);

        $this->assertSoftDeleted('sets', ['id' => $set->id]);
    }

    /** @test */
    public function non_admin_cannot_delete_set()
    {
        $set = Set::factory()->create();

        $this->actingAs($this->user)
             ->deleteJson("/api/sets/{$set->id}")
             ->assertStatus(403);

        $this->assertDatabaseHas('sets', ['id' => $set->id]);
    }

    /** @test */
    public function admin_can_restore_set()
    {
        $set = Set::factory()->create();
        $set->delete();
        $this->assertSoftDeleted('sets', ['id' => $set->id]);

        $this->actingAs($this->admin)
             ->putJson("/api/sets/{$set->id}/restore")
             ->assertStatus(200);

        $this->assertDatabaseHas('sets', ['id' => $set->id, 'deleted_at' => null]);
    }

    /** @test */
    public function index_returns_soft_deleted_with_trashed_param()
    {
        Set::factory()->count(2)->create();
        Set::factory()->create()->delete();

        $this->getJson('/api/sets')->assertJsonCount(2);
        $this->getJson('/api/sets?withTrashed=true')->assertJsonCount(3);
    }

    /** @test */
    public function show_includes_translations_when_present()
    {
        $set = Set::factory()->create();

        // Senza traduzioni la chiave è presente ma vuota
        $response = $this->getJson("/api/sets/{$set->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure(['id', 'name', 'translations']);
    }

    /** @test */
    public function image_urls_have_extension_appended()
    {
        $set = Set::factory()->create([
            'symbol_url' => 'https://assets.tcgdex.net/en/swsh/swsh1/symbol',
            'logo_url'   => 'https://assets.tcgdex.net/en/swsh/swsh1/logo',
        ]);

        $response = $this->getJson("/api/sets/{$set->id}");

        $response->assertJsonPath('symbol_url', 'https://assets.tcgdex.net/en/swsh/swsh1/symbol.png')
                 ->assertJsonPath('logo_url',   'https://assets.tcgdex.net/en/swsh/swsh1/logo.png');
    }
}
