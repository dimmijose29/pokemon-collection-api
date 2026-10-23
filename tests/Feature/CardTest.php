<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use App\Models\Card;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private Set  $set;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user  = User::factory()->create(['is_admin' => false]);
        $this->set   = Set::factory()->create();
    }

    #[Test]
    public function anyone_can_view_cards_list()
    {
        Card::factory()->count(3)->create(['set_id' => $this->set->id]);

        $this->getJson('/api/cards')
             ->assertStatus(200)
             ->assertJsonCount(3);
    }

    #[Test]
    public function index_supports_set_id_filter()
    {
        $otherSet = Set::factory()->create();
        Card::factory()->count(2)->create(['set_id' => $this->set->id]);
        Card::factory()->create(['set_id' => $otherSet->id]);

        $this->getJson("/api/cards?set_id={$this->set->id}")
             ->assertStatus(200)
             ->assertJsonCount(2);
    }

    #[Test]
    public function paginated_index_caps_per_page()
    {
        Card::factory()->count(3)->create(['set_id' => $this->set->id]);

        $this->getJson('/api/cards?page=1&per_page=100000')
             ->assertStatus(200)
             ->assertJsonPath('meta.per_page', 100);
    }

    #[Test]
    public function anyone_can_view_single_card()
    {
        $card = Card::factory()->create([
            'set_id'   => $this->set->id,
            'local_id' => '001',
        ]);

        $this->getJson("/api/cards/{$card->id}")
             ->assertStatus(200)
             ->assertJsonPath('local_id', '001');
    }

    #[Test]
    public function admin_can_create_card()
    {
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
    }

    #[Test]
    public function non_admin_cannot_create_card()
    {
        $this->actingAs($this->user)
             ->postJson('/api/cards', [
                 'set_id'    => $this->set->id,
                 'local_id'  => '001',
                 'tcgdex_id' => 'swsh1-001',
             ])
             ->assertStatus(403);
    }

    #[Test]
    public function unauthenticated_cannot_create_card()
    {
        $this->postJson('/api/cards', [
            'set_id'    => $this->set->id,
            'local_id'  => '001',
            'tcgdex_id' => 'swsh1-001',
        ])->assertStatus(401);
    }

    #[Test]
    public function set_id_is_required_on_create()
    {
        $this->actingAs($this->admin)
             ->postJson('/api/cards', ['local_id' => '001', 'tcgdex_id' => 'x-001'])
             ->assertStatus(422)
             ->assertJsonValidationErrors('set_id');
    }

    #[Test]
    public function admin_can_update_card()
    {
        $card = Card::factory()->create(['set_id' => $this->set->id]);

        $this->actingAs($this->admin)
             ->putJson("/api/cards/{$card->id}", [
                 'set_id'   => $this->set->id,
                 'local_id' => '999',
             ])
             ->assertStatus(200)
             ->assertJsonPath('local_id', '999');
    }

    #[Test]
    public function non_admin_cannot_update_card()
    {
        $card = Card::factory()->create(['set_id' => $this->set->id]);

        $this->actingAs($this->user)
             ->putJson("/api/cards/{$card->id}", ['local_id' => '999'])
             ->assertStatus(403);
    }

    #[Test]
    public function admin_can_delete_card()
    {
        $card = Card::factory()->create(['set_id' => $this->set->id]);

        $this->actingAs($this->admin)
             ->deleteJson("/api/cards/{$card->id}")
             ->assertStatus(200);

        $this->assertSoftDeleted('cards', ['id' => $card->id]);
    }

    #[Test]
    public function non_admin_cannot_delete_card()
    {
        $card = Card::factory()->create(['set_id' => $this->set->id]);

        $this->actingAs($this->user)
             ->deleteJson("/api/cards/{$card->id}")
             ->assertStatus(403);
    }

    #[Test]
    public function admin_can_restore_card()
    {
        $card = Card::factory()->create(['set_id' => $this->set->id]);
        $card->delete();
        $this->assertSoftDeleted('cards', ['id' => $card->id]);

        $this->actingAs($this->admin)
             ->putJson("/api/cards/{$card->id}/restore")
             ->assertStatus(200);

        $this->assertDatabaseHas('cards', ['id' => $card->id, 'deleted_at' => null]);
    }

    #[Test]
    public function index_returns_soft_deleted_with_trashed_param()
    {
        Card::factory()->count(2)->create(['set_id' => $this->set->id]);
        Card::factory()->create(['set_id' => $this->set->id])->delete();

        $this->getJson('/api/cards')->assertJsonCount(2);
        $this->getJson('/api/cards?withTrashed=true')->assertJsonCount(3);
    }

    #[Test]
    public function image_url_has_quality_suffix_appended()
    {
        $card = Card::factory()->create([
            'set_id'    => $this->set->id,
            'image_url' => 'https://assets.tcgdex.net/en/swsh/swsh1/1',
        ]);

        $this->getJson("/api/cards/{$card->id}")
             ->assertJsonPath('image_url', 'https://assets.tcgdex.net/en/swsh/swsh1/1/high.webp');
    }
}
