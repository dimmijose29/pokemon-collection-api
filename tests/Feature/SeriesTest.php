<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeriesTest extends TestCase
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

    #[Test]
    public function anyone_can_view_series_list()
    {
        Series::factory()->count(3)->create();

        $this->getJson('/api/series')->assertStatus(200);
    }

    #[Test]
    public function admin_can_create_series()
    {
        $this->actingAs($this->admin)
             ->postJson('/api/series', ['name' => 'Sword & Shield', 'tcgdex_id' => 'swsh'])
             ->assertStatus(201)
             ->assertJsonPath('name', 'Sword & Shield');

        $this->assertDatabaseHas('series', ['tcgdex_id' => 'swsh']);
    }

    #[Test]
    public function admin_can_update_series()
    {
        $series = Series::factory()->create(['name' => 'Vecchio nome']);

        $this->actingAs($this->admin)
             ->putJson("/api/series/{$series->id}", ['name' => 'Nuovo nome'])
             ->assertStatus(200)
             ->assertJsonPath('name', 'Nuovo nome');
    }

    #[Test]
    public function series_name_is_required()
    {
        $this->actingAs($this->admin)
             ->postJson('/api/series', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function non_admin_cannot_create_series()
    {
        $this->actingAs($this->user)
             ->postJson('/api/series', ['name' => 'Bloccata'])
             ->assertStatus(403);
    }

    #[Test]
    public function admin_can_delete_and_restore_series()
    {
        $series = Series::factory()->create();

        $this->actingAs($this->admin)->deleteJson("/api/series/{$series->id}")->assertSuccessful();
        $this->assertSoftDeleted('series', ['id' => $series->id]);

        $this->actingAs($this->admin)->putJson("/api/series/{$series->id}/restore")->assertSuccessful();
        $this->assertNotSoftDeleted('series', ['id' => $series->id]);
    }
}
