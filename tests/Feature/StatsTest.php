<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use App\Models\Card;
use App\Models\CardPrice;
use App\Models\Series;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le statistiche usano funzioni MySQL (YEAR): questi test girano solo
 * perché la suite usa un database MySQL dedicato e non più SQLite.
 */
class StatsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function stats_require_authentication()
    {
        $this->getJson('/api/stats')->assertStatus(401);
    }

    #[Test]
    public function stats_return_kpi_and_charts()
    {
        $series = Series::factory()->create(['name' => 'Sword & Shield']);
        Set::factory()->count(2)->create(['series_id' => $series->id, 'release_date' => '2024-05-01']);
        $set = Set::factory()->create(['series_id' => $series->id, 'release_date' => '2025-02-01']);
        $card = Card::factory()->create(['set_id' => $set->id]);
        CardPrice::create([
            'card_id'     => $card->id,
            'source'      => 'cardmarket',
            'price_type'  => 'trend',
            'price'       => 12.5,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs(User::factory()->create(), 'sanctum')->getJson('/api/stats');

        $response->assertStatus(200)
                 ->assertJsonPath('kpi.sets_count', 3)
                 ->assertJsonPath('kpi.cards_count', 1)
                 ->assertJsonPath('kpi.series_count', 1)
                 ->assertJsonPath('kpi.cards_with_price', 1)
                 ->assertJsonPath('charts.sets_per_year', [
                     ['label' => '2024', 'value' => 2],
                     ['label' => '2025', 'value' => 1],
                 ])
                 ->assertJsonPath('charts.cards_per_series.0', ['label' => 'Sword & Shield', 'value' => 1])
                 ->assertJsonPath('top_cards.0.price', 12.5);
    }
}
