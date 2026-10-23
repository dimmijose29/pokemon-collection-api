<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Series;
use App\Models\Set;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        // --- KPI principali ---
        $setsCount    = Set::count();
        $cardsCount   = Card::count();
        $seriesCount  = Series::count();
        $usersCount   = User::count();

        $cardsWithPrice = Card::whereHas('prices')->count();

        // Prezzi sull'ultimo trend Cardmarket di ogni carta, non sull'intero storico
        // (che conterebbe ogni carta una volta per giorno e per tipo di prezzo)
        $priceStats = DB::query()->fromSub($this->latestTrendPrices(), 'p')
            ->selectRaw('AVG(price) as avg_price, MAX(price) as max_price, SUM(price) as total_value')
            ->first();

        $avgPrice   = $priceStats?->avg_price   ? round((float) $priceStats->avg_price, 2)   : null;
        $maxPrice   = $priceStats?->max_price   ? round((float) $priceStats->max_price, 2)   : null;
        $totalValue = $priceStats?->total_value ? round((float) $priceStats->total_value, 2) : null;

        // --- Carte per rarità (top 10) ---
        $cardsPerRarity = Card::whereNotNull('rarity_id')
            ->join('rarities', 'cards.rarity_id', '=', 'rarities.id')
            ->groupBy('rarities.id', 'rarities.name')
            ->selectRaw('rarities.name as label, COUNT(cards.id) as value')
            ->orderByDesc('value')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['label' => $r->label, 'value' => (int) $r->value]);

        // --- Carte per serie (top 15) ---
        $cardsPerSeries = Card::whereHas('set.series')
            ->join('sets', 'cards.set_id', '=', 'sets.id')
            ->join('series', 'sets.series_id', '=', 'series.id')
            ->groupBy('series.id', 'series.name')
            ->selectRaw('series.name as label, COUNT(cards.id) as value')
            ->orderByDesc('value')
            ->limit(15)
            ->get()
            ->map(fn($r) => ['label' => $r->label, 'value' => (int) $r->value]);

        // --- Set per anno di uscita ---
        $setsPerYear = Set::whereNotNull('release_date')
            ->selectRaw('YEAR(release_date) as label, COUNT(*) as value')
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(fn($r) => ['label' => (string) $r->label, 'value' => (int) $r->value]);

        // --- Distribuzione fasce di prezzo ---
        $priceRanges = collect([
            ['label' => '< €1',     'min' => 0,    'max' => 1],
            ['label' => '€1 – €5',  'min' => 1,    'max' => 5],
            ['label' => '€5 – €20', 'min' => 5,    'max' => 20],
            ['label' => '€20 – €50','min' => 20,   'max' => 50],
            ['label' => '> €50',    'min' => 50,   'max' => PHP_INT_MAX],
        ])->map(function ($range) {
            // Intervalli semiaperti [min, max): un prezzo di confine sta in una sola fascia
            $count = DB::query()->fromSub($this->latestTrendPrices(), 'p')
                ->where('price', '>=', $range['min'])
                ->when($range['max'] !== PHP_INT_MAX, fn ($q) => $q->where('price', '<', $range['max']))
                ->count();

            return ['label' => $range['label'], 'value' => $count];
        });

        // --- Top 10 carte più costose (ultimo trend) ---
        // Passo 1: prendi i top 10 per prezzo (nessun JOIN traduzioni qui)
        $topRows = DB::query()->fromSub($this->latestTrendPrices(), 'card_prices')
            ->join('cards', 'card_prices.card_id', '=', 'cards.id')
            ->join('sets', 'cards.set_id', '=', 'sets.id')
            ->selectRaw('
                cards.id,
                cards.tcgdex_id,
                cards.local_id,
                cards.image_url,
                sets.id          as set_id,
                sets.tcgdex_id   as set_tcgdex_id,
                sets.name        as set_name,
                sets.logo_url    as set_logo_url,
                MAX(card_prices.price) as price
            ')
            ->groupBy(
                'cards.id', 'cards.tcgdex_id', 'cards.local_id', 'cards.image_url',
                'sets.id', 'sets.tcgdex_id', 'sets.name', 'sets.logo_url'
            )
            ->orderByDesc('price')
            ->limit(10)
            ->get();

        // Passo 2: carica le traduzioni EN e IT solo per queste 10 carte
        $cardIds = $topRows->pluck('id')->all();

        $translations = DB::table('card_translations')
            ->join('languages', 'card_translations.language_id', '=', 'languages.id')
            ->whereIn('card_translations.card_id', $cardIds)
            ->whereIn('languages.code', ['en', 'it'])
            ->select('card_translations.card_id', 'card_translations.name', 'languages.code')
            ->get()
            ->groupBy(fn($t) => (string) $t->card_id);   // cast esplicito → chiave sempre stringa

        // Passo 3: assembla — EN → IT → tcgdex_id
        $topCards = $topRows->map(function ($r) use ($translations) {
            $trans = $translations->get((string) $r->id, collect());  // cast esplicito
            $en    = $trans->firstWhere('code', 'en');
            $it    = $trans->firstWhere('code', 'it');

            return [
                'id'            => $r->id,
                'tcgdex_id'     => $r->tcgdex_id,
                'local_id'      => $r->local_id,
                'image_url'     => $r->image_url,
                'name'          => $en?->name ?? $it?->name ?? $r->tcgdex_id,
                'set_id'        => $r->set_id,
                'set_tcgdex_id' => $r->set_tcgdex_id,
                'set_name'      => $r->set_name,
                'set_logo_url'  => $r->set_logo_url ? $r->set_logo_url . '.png' : null,
                'price'         => round((float) $r->price, 2),
            ];
        });

        return response()->json([
            'kpi' => [
                'sets_count'       => $setsCount,
                'cards_count'      => $cardsCount,
                'series_count'     => $seriesCount,
                'users_count'      => $usersCount,
                'cards_with_price' => $cardsWithPrice,
                'avg_price'        => $avgPrice,
                'max_price'        => $maxPrice,
                'total_value'      => $totalValue,
            ],
            'charts' => [
                'cards_per_rarity'  => $cardsPerRarity,
                'cards_per_series'  => $cardsPerSeries,
                'sets_per_year'     => $setsPerYear,
                'price_ranges'      => $priceRanges,
            ],
            'top_cards' => $topCards,
        ]);
    }

    /**
     * Ultimo prezzo trend Cardmarket di ogni carta (card_id, price).
     * Gli snapshot sono unici per giorno, quindi basta l'ultimo recorded_on.
     */
    private function latestTrendPrices(): Builder
    {
        $latestDay = DB::table('card_prices')
            ->select('card_id', DB::raw('MAX(recorded_on) as recorded_on'))
            ->where('source', 'cardmarket')
            ->where('price_type', 'trend')
            ->groupBy('card_id');

        return DB::table('card_prices as cp')
            ->joinSub($latestDay, 'latest', fn ($join) => $join
                ->on('cp.card_id', '=', 'latest.card_id')
                ->on('cp.recorded_on', '=', 'latest.recorded_on'))
            ->where('cp.source', 'cardmarket')
            ->where('cp.price_type', 'trend')
            ->select('cp.card_id', 'cp.price');
    }
}
