# Pokémon Collection — Backend

API REST per l'applicazione Pokémon Collection, costruita con **Laravel** e **MySQL**.

---

## Stack

| Tecnologia | Versione | Utilizzo |
|---|---|---|
| PHP | 8.2+ | Linguaggio |
| Laravel | 12.x | Framework |
| MySQL | 8.0+ | Database (obbligatorio: alcune query usano funzioni MySQL) |
| Laravel Sanctum | 4.x | Autenticazione API con token Bearer |
| PragmaRX Google2FA | 2.x | Autenticazione a due fattori (TOTP) |
| TCGdex | — | Sorgente esterna del catalogo (set, carte, prezzi) |

---

## Requisiti

- PHP >= 8.2 con estensioni: `pdo_mysql`, `mbstring`, `openssl`, `pcntl` (timeout dei job), `gd` (test)
- Composer
- MySQL 8.0+
- Accesso a internet verso `api.tcgdex.net` (seed e sincronizzazione)

Non serve Node.js: il progetto è solo API.

---

## Installazione

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configura database, mail e FRONTEND_URL in .env, poi:
php artisan migrate
php artisan db:seed   # admin, dati statici e catalogo TCGdex (~2 minuti)
```

Il seeder crea l'admin `ADMIN_EMAIL`; se `ADMIN_PASSWORD` è vuota genera una password casuale e la stampa una sola volta.

---

## Avvio in sviluppo

```bash
php artisan serve          # API su http://localhost:8000
php artisan queue:work     # job di sincronizzazione
php artisan schedule:work  # pulizia giornaliera dei token scaduti
```

Le carte si importano a parte, perché la sincronizzazione completa è lunga:

```bash
php artisan sync:cards          # tutti i set
php artisan sync:cards swsh1    # un solo set
php artisan sync:cards --queue  # in coda
```

---

## Test

```bash
php artisan test
```

I test usano SQLite in memoria (`phpunit.xml`). Se le variabili `DB_*` sono già impostate nell'ambiente
(ad esempio dentro un container), sovrascrivono `phpunit.xml`: in quel caso lanciare
`DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test`, altrimenti `RefreshDatabase` svuota il database configurato.

---

## Variabili d'ambiente principali

| Variabile | Uso |
|---|---|
| `FRONTEND_URL` | URL del pannello admin, usato nei link delle email (reset password) |
| `CORS_ALLOWED_ORIGINS` | Origini ammesse dal CORS, separate da virgola |
| `SANCTUM_EXPIRATION` | Durata dei token in minuti (default 7 giorni) |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Utente admin creato dal seeder |
| `DB_QUEUE_RETRY_AFTER` | Deve superare il timeout dei job (600 s): usare almeno 660 |

---

## Struttura

```
app/
├── Http/
│   ├── Controllers/   # Un controller per entità
│   ├── Requests/      # FormRequest con validazione e messaggi in italiano
│   └── Resources/     # JSON Resource per serializzazione
├── Models/            # Eloquent models con SoftDeletes
└── Policies/          # Autorizzazioni per ruolo admin

routes/
├── auth.php           # Login, logout, profilo, password, 2FA
├── series.php, sets.php, cards.php
├── artists.php, rarities.php, card_types.php
├── conditions.php, languages.php, currencies.php, grading_companies.php
├── users.php          # Gestione utenti (solo admin)
├── stats.php          # Statistiche dashboard
└── console.php        # Schedulazione
```

La sincronizzazione con TCGdex è in `app/Services/TcgdexService.php`, `app/Jobs/Sync*Job.php`
e nei comandi `app/Console/Commands/Sync*Command.php`.

---

## Convenzioni

**Controllers** — ogni entità espone: `index`, `store`, `show`, `update`, `destroy`, `restore`.
Il metodo `index` accetta `?withTrashed=true` per includere i record eliminati (soft-deleted).

**Soft Delete** — tutti i modelli del catalogo usano `SoftDeletes`. La cancellazione è sempre logica;
il ripristino avviene tramite `PUT /{id}/restore`.

**Resources** — i campi nullable usano `whenNotNull` così sono omessi dalla risposta quando non valorizzati:
```php
'deleted_at' => $this->whenNotNull($this->deleted_at?->toDateTimeString()),
```

**Autorizzazioni** — le route di scrittura richiedono `auth:sanctum` + middleware `admin`.
Le Policy gestiscono i permessi per ogni modello.

---

## Entità Catalogo

| Endpoint | Campi |
|---|---|
| `/artists` | `name` |
| `/rarities` | `name` |
| `/card-types` | `name` |
| `/conditions` | `code`, `name` |
| `/languages` | `code`, `name`, `country_code` |
| `/currencies` | `code`, `symbol` |
| `/grading-companies` | `name` |
| `/series` | `name`, `tcgdex_id`, `logo_url` |
| `/sets` | `tcgdex_id`, `name`, `series_id`, conteggi, `release_date` |
| `/cards` | `tcgdex_id`, `set_id`, `local_id`, rarità, tipo, artista |

---

## Autenticazione

| Endpoint | Metodo | Descrizione |
|---|---|---|
| `/auth/register` | POST | Registrazione |
| `/auth/login` | POST | Login con email o username; con 2FA attiva restituisce un `temp_token` |
| `/auth/2fa/verify` | POST | Completa il login 2FA (`temp_token` + codice) |
| `/auth/forgot-password` | POST | Invia il link di reset via email |
| `/auth/reset-password` | POST | Reimposta la password con il token del link |
| `/auth/logout` | POST | Logout (revoca il token corrente) |
| `/auth/me` | GET | Dati utente autenticato |
| `/auth/me` | PUT | Aggiorna username |
| `/auth/password` | PUT | Cambia password (richiede quella attuale, revoca le altre sessioni) |
| `/auth/2fa/enable` | POST | Avvia setup 2FA (genera QR + secret) |
| `/auth/2fa/confirm` | POST | Conferma e attiva il 2FA |
| `/auth/2fa/cancel` | POST | Annulla il setup 2FA in corso |
| `/auth/2fa/disable` | POST | Disattiva il 2FA (richiede password e codice TOTP) |

Sicurezza:

- il `temp_token` della 2FA vale 5 minuti e non autentica nessun'altra rotta;
- un codice TOTP non è riutilizzabile;
- login, 2FA, recupero password e registrazione hanno un rate limit;
- gli utenti disattivati perdono subito l'accesso.
