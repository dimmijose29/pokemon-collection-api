# Pokémon Collection — Backend

API REST per l'applicazione Pokémon Collection, costruita con **Laravel** e **MySQL**.

---

## Stack

| Tecnologia | Versione | Utilizzo |
|---|---|---|
| PHP | 8.2+ | Linguaggio |
| Laravel | 11.x | Framework |
| MySQL | 8.0+ | Database |
| Laravel Sanctum | — | Autenticazione API (token + cookie) |
| PragmaRX Google2FA | — | Autenticazione a due fattori (TOTP) |

---

## Requisiti

- PHP >= 8.2 con estensioni: `pdo_mysql`, `mbstring`, `openssl`, `gd`
- Composer
- MySQL 8.0+

---

## Installazione

```bash
# Entra nella cartella backend
cd api

# Installa le dipendenze PHP
composer install

# Copia e configura il file d'ambiente
cp .env.example .env
php artisan key:generate

# Configura il database in .env, poi esegui le migration
php artisan migrate

# (Opzionale) Popola il database con i dati di seed
php artisan db:seed
```

---

## Avvio in sviluppo

```bash
php artisan serve
```

L'API sarà disponibile su `http://localhost:8000`.

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
├── auth.php           # Login, logout, profilo, 2FA
├── artists.php
├── rarities.php
├── card_types.php
├── conditions.php
├── languages.php
├── currencies.php
└── grading_companies.php
```

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

---

## Autenticazione

| Endpoint | Metodo | Descrizione |
|---|---|---|
| `/auth/login` | POST | Login con email e password |
| `/auth/logout` | POST | Logout |
| `/auth/me` | GET | Dati utente autenticato |
| `/auth/me` | PUT | Aggiorna username |
| `/auth/password` | PUT | Cambia password |
| `/auth/2fa/enable` | POST | Avvia setup 2FA (genera QR + secret) |
| `/auth/2fa/confirm` | POST | Conferma e attiva il 2FA |
| `/auth/2fa/cancel` | POST | Annulla il setup 2FA in corso |
| `/auth/2fa/disable` | POST | Disattiva il 2FA (richiede codice TOTP) |
