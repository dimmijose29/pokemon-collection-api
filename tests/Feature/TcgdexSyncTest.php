<?php

use App\Exceptions\TcgdexException;
use App\Jobs\SyncCardsJob;
use App\Models\Artist;
use App\Models\Card;
use App\Models\CardType;
use App\Models\Language;
use App\Models\Rarity;
use App\Models\Set;
use App\Services\TcgdexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

uses(RefreshDatabase::class);

const TCGDEX = 'https://api.tcgdex.net/v2';

beforeEach(function () {
    Sleep::fake(); // niente attese reali tra i retry
    config(['services.tcgdex.retries' => 2]);

    $this->en = Language::create(['code' => 'en', 'name' => 'Inglese', 'country_code' => 'GB']);
    $this->it = Language::create(['code' => 'it', 'name' => 'Italiano', 'country_code' => 'IT']);
    Language::create(['code' => 'zh-CN', 'name' => 'Cinese', 'country_code' => 'CN']);
    Language::create(['code' => 'xx', 'name' => 'Non supportata', 'country_code' => 'XX']);

    Rarity::create(['name' => 'Common']);
    CardType::create(['name' => 'Pokémon']);
    $this->set = Set::factory()->create(['tcgdex_id' => 'tst1']);
});

function fakeTcgdex(array $overrides = []): void
{
    Http::fake($overrides + [
        TCGDEX . '/en/sets/tst1' => Http::response(['id' => 'tst1', 'cards' => [
            ['id' => 'tst1-1', 'localId' => '1', 'name' => 'Pikachu'],
            ['id' => 'tst1-2', 'localId' => '2', 'name' => 'Raichu'],
        ]]),
        TCGDEX . '/en/cards/tst1-1' => Http::response([
            'id' => 'tst1-1', 'rarity' => 'Common', 'category' => 'Pokemon', 'illustrator' => 'Mitsuhiro Arita',
            'image' => 'https://assets.tcgdex.net/en/tst/tst1/1', 'stage' => 'Basic', 'types' => ['Lightning'],
            'pricing' => ['cardmarket' => [
                'updated' => '2026-10-22T19:57:14.129Z', 'unit' => 'EUR', 'idProduct' => 427231,
                'avg' => 2.54, 'low' => 0.5, 'trend' => 2.68, 'avg7' => 2.25, 'trend-holo' => null,
            ]],
        ]),
        TCGDEX . '/en/cards/tst1-2' => Http::response(['id' => 'tst1-2', 'rarity' => 'Common', 'category' => 'Pokemon']),
        TCGDEX . '/it/sets/tst1' => Http::response(['id' => 'tst1', 'cards' => [
            ['id' => 'tst1-1', 'localId' => '1', 'name' => 'Pikachu IT'],
            ['id' => 'tst1-2', 'localId' => '2', 'name' => 'Raichu IT'],
        ]]),
        // Set non disponibile in cinese: TCGdex risponde 404
        TCGDEX . '/zh-cn/sets/tst1' => Http::response(['error' => 'not found'], 404),
    ]);
}

function runSync(): void
{
    (new SyncCardsJob('tst1'))->handle(app(TcgdexService::class));
}

test('sync saves cards with relations, English and translated names', function () {
    fakeTcgdex();
    runSync();

    $card = Card::where('tcgdex_id', 'tst1-1')->firstOrFail();
    expect($card->set_id)->toBe($this->set->id)
        ->and($card->rarity->name)->toBe('Common')
        ->and($card->cardType->name)->toBe('Pokémon')
        ->and($card->artist->name)->toBe('Mitsuhiro Arita')
        ->and($card->energy_types)->toBe(['Lightning']);

    $names = $card->translations()->pluck('name', 'language_id');
    expect($names[$this->en->id])->toBe('Pikachu')
        ->and($names[$this->it->id])->toBe('Pikachu IT')
        ->and($names)->toHaveCount(2);
});

test('sync makes one call per card and one per language, not per card per language', function () {
    fakeTcgdex();
    runSync();

    // set en + 2 dettagli carta + set it + set zh-cn (404); la lingua non supportata non viene chiamata
    Http::assertSentCount(5);
    Http::assertSent(fn ($request) => $request->url() === TCGDEX . '/zh-cn/sets/tst1');
});

test('sync stores a daily Cardmarket price snapshot with currency', function () {
    fakeTcgdex();
    runSync();

    $prices = DB::table('card_prices')->orderBy('price_type')->get();
    expect($prices->pluck('price_type')->all())->toBe(['avg', 'avg7', 'low', 'trend'])
        ->and($prices->pluck('currency')->unique()->all())->toBe(['EUR'])
        ->and($prices->pluck('recorded_on')->unique()->all())->toBe(['2026-10-22'])
        ->and((float) $prices->firstWhere('price_type', 'trend')->price)->toBe(2.68);
});

test('running the sync again updates the snapshot instead of duplicating it', function () {
    $card = fn (float $trend, string $updated) => Http::response([
        'id' => 'tst1-1', 'rarity' => 'Common', 'category' => 'Pokemon',
        'pricing' => ['cardmarket' => ['updated' => $updated, 'unit' => 'EUR', 'trend' => $trend]],
    ]);
    // Seconda sync nello stesso giorno con un prezzo aggiornato
    fakeTcgdex([
        TCGDEX . '/en/cards/tst1-1' => Http::sequence()
            ->pushResponse($card(2.68, '2026-10-22T08:00:00Z'))
            ->pushResponse($card(3.10, '2026-10-22T23:00:00Z')),
    ]);

    runSync();
    runSync();

    expect(Card::count())->toBe(2)
        ->and(DB::table('card_prices')->where('price_type', 'trend')->count())->toBe(1)
        ->and((float) DB::table('card_prices')->where('price_type', 'trend')->value('price'))->toBe(3.10)
        ->and(DB::table('card_translations')->count())->toBe(4);
});

test('sync reuses records hidden by the admin instead of failing on unique names', function () {
    Artist::create(['name' => 'Mitsuhiro Arita'])->delete();
    fakeTcgdex();
    runSync();
    $card = Card::where('tcgdex_id', 'tst1-1')->firstOrFail();
    $card->delete();

    runSync();

    expect(Artist::withTrashed()->where('name', 'Mitsuhiro Arita')->count())->toBe(1)
        ->and(Card::withTrashed()->count())->toBe(2)
        ->and($card->fresh()->trashed())->toBeTrue();
});

test('sync fails when TCGdex keeps answering with server errors', function () {
    fakeTcgdex([TCGDEX . '/en/sets/tst1' => Http::response('down', 503)]);

    expect(fn () => runSync())->toThrow(TcgdexException::class);
    Http::assertSentCount(2); // un retry, poi l'errore
});

test('tcgdex service retries transient errors and returns null on 404', function () {
    Http::fake([
        TCGDEX . '/en/cards/ok' => Http::sequence()->push('busy', 503)->push(['id' => 'ok']),
        TCGDEX . '/en/cards/missing' => Http::response([], 404),
    ]);

    $service = new TcgdexService('en');

    expect($service->fetchCard('ok'))->toBe(['id' => 'ok'])
        ->and($service->fetchCard('missing'))->toBeNull();
});

test('tcgdex service maps database language codes to TCGdex codes', function () {
    expect(TcgdexService::apiLang('zh-CN'))->toBe('zh-cn')
        ->and(TcgdexService::apiLang('IT'))->toBe('it')
        ->and(TcgdexService::apiLang('xx'))->toBeNull()
        ->and((new TcgdexService('zh-TW'))->lang())->toBe('zh-tw');
});
