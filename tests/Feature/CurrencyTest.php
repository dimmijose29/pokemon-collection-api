<?php

namespace Tests\Feature\Currency;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyTest extends TestCase
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

    /** @test */
    public function anyone_can_view_currencies_list()
    {
        Currency::factory()->count(2)->create();

        $response = $this->getJson('/api/currencies');

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    /** @test */
    public function anyone_can_view_single_currency()
    {
        $currency = Currency::factory()->create([
            'symbol' => '€',
            'code' => 'EUR',
        ]);

        $response = $this->getJson("/api/currencies/{$currency->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $currency->id,
                     'symbol' => '€',
                     'code' => 'EUR',
                 ]);
    }

    /** @test */
    public function admin_can_create_currency()
    {
        $payload = [
            'symbol' => '$',
            'code' => 'USD',
        ];

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/currencies', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('symbol', '$');

        $this->assertDatabaseHas('currencies', [
            'symbol' => '$',
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_currency()
    {
        $payload = [
            'symbol' => '€',
            'code' => 'EUR',
        ];

        $response = $this->actingAs($this->user)
                         ->postJson('/api/currencies', $payload);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('currencies', ['symbol' => '€']);
    }

    /** @test */
    public function code_must_be_unique_on_create()
    {
        Currency::factory()->create(['symbol' => '€']);

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/currencies', [
                             'symbol' => '€', // nome già esistente
                             'code' => 'EUR', // codice già esistente
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('symbol');
    }

    /** @test */
    public function admin_can_update_currency()
    {
        $currency = Currency::factory()->create(['symbol' => '€', 'code' => 'EUR']);
            
            $payload = [
                'symbol' => '€',
                'code' => 'EUR',
            ];

        $response = $this->actingAs($this->admin)
                         ->putJson("/api/currencies/{$currency->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('symbol', '€');

        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'symbol' => '€',
        ]);
    }

    /** @test */
    public function non_admin_cannot_update_currency()
    {
        $currency = Currency::factory()->create();

        $response = $this->actingAs($this->user)
                         ->putJson("/api/currencies/{$currency->id}", [
                             'symbol' => 'Aggiornato da non-admin',
                         ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('currencies', ['symbol' => 'Aggiornato da non-admin']);
    }

    /** @test */
    public function admin_can_delete_currency()
    {
        $currency = Currency::factory()->create();

        $response = $this->actingAs($this->admin)
                         ->deleteJson("/api/currencies/{$currency->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('currencies', ['id' => $currency->id]); // se usi soft deletes
        // oppure $this->assertDatabaseEURissing('currencies', ['id' => $currency->id]);
    }

    /** @test */
    public function non_admin_cannot_delete_currency()
    {
        $currency = Currency::factory()->create();

        $response = $this->actingAs($this->user)
                         ->deleteJson("/api/currencies/{$currency->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('currencies', ['id' => $currency->id]);
    }
}