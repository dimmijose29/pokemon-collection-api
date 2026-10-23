<?php

use App\Models\Card;
use App\Models\CardPrice;
use App\Models\Series;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Le statistiche usano funzioni MySQL (YEAR): girano solo perché la suite usa MySQL e non SQLite.
uses(RefreshDatabase::class);

test('stats require authentication', function () {
    $this->getJson('/api/stats')->assertStatus(401);
});

test('stats return kpi and charts', function () {
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
        'recorded_on' => now()->toDateString(),
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
});
test('price stats use only the latest trend price of each card', function () {
    $set = Set::factory()->create();
    [$a, $b] = Card::factory()->count(2)->create(['set_id' => $set->id]);

    $price = fn (Card $card, string $type, float $value, string $day) => CardPrice::create([
        'card_id' => $card->id, 'source' => 'cardmarket', 'price_type' => $type,
        'price' => $value, 'recorded_at' => $day, 'recorded_on' => $day,
    ]);

    // Storico di A: conta solo l'ultimo giorno (20); gli altri tipi di prezzo sono ignorati
    $price($a, 'trend', 100, '2026-10-01');
    $price($a, 'trend', 20, '2026-10-20');
    $price($a, 'low', 1, '2026-10-20');
    // B sta esattamente sul confine 5: va solo nella fascia €5 – €20
    $price($b, 'trend', 5, '2026-10-20');

    $response = $this->actingAs(User::factory()->create(), 'sanctum')->getJson('/api/stats');

    $response->assertStatus(200)
             ->assertJsonPath('kpi.max_price', 20)
             ->assertJsonPath('kpi.total_value', 25)
             ->assertJsonPath('kpi.avg_price', 12.5)
             ->assertJsonPath('top_cards.0.price', 20);

    $ranges = collect($response->json('charts.price_ranges'))->pluck('value', 'label');
    expect($ranges['€1 – €5'])->toBe(0)
        ->and($ranges['€5 – €20'])->toBe(1)
        ->and($ranges['€20 – €50'])->toBe(1)
        ->and($ranges->sum())->toBe(2);
});
