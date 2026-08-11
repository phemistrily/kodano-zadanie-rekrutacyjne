# Kodano – recruitment task

REST API for managing **products** and **categories**, built with **Symfony 8.1**,
**API Platform 4.3** and **MySQL 8.4**, and run with **Docker** (FrankenPHP + MySQL + Mailpit).

## Stack

- PHP 8.4 / Symfony 8.1
- API Platform 4.3 (CRUD generated from `#[ApiResource]` attributes)
- Doctrine ORM 3 + Doctrine Migrations
- MySQL 8.4
- JWT authentication (LexikJWTAuthenticationBundle)
- Symfony Mailer + Mailpit (preview of sent e-mails)
- Monolog (operation log)
- PHPUnit 13 (unit + functional tests)

## Architecture

The code follows a domain-oriented layout:

```
src/
├── Domain/                            # entities, value objects, contracts (framework-agnostic)
│   ├── Product/Entity/Product.php
│   ├── Category/Entity/Category.php
│   ├── User/Entity/User.php           # JWT auth user
│   ├── Shared/Entity/Traits/          # id + timestamps
│   └── Notification/                  # Notification, NotifierInterface, NotificationChannelInterface
└── Infrastructure/
    ├── Doctrine/EventListener/        # sets created/updated dates automatically (prePersist/preUpdate)
    ├── Doctrine/Repository/
    ├── Console/                       # app:create-user command
    ├── Notification/                  # ChannelNotifier + channels (Log, Email)
    ├── ApiPlatform/State/             # ProductNotificationProcessor (post-save hook)
    └── ApiPlatform/Serializer/        # ProductCategoryCodesDenormalizer (categoryCodes -> categories)
```

## Running the app

```bash
make up          # build and start containers (app + database + mailer)
make install     # composer install inside the container (if vendor/ is missing)
make jwt-keys    # generate the JWT keypair (config/jwt/*.pem)
make migrate     # run migrations (creates the schema + a demo user)
make fixtures    # (optional) load sample data — users, categories, products
```

- API: `http://localhost:8080/api`
- API docs (Swagger UI): `http://localhost:8080/api`
- Mailpit (e-mail preview): `http://localhost:8025`

Useful shortcuts: `make down`, `make sh`, `make logs`, `make console <command>`.

### Fixtures (sample data)

`make fixtures` (`doctrine:fixtures:load`) **purges** the database and loads a fresh sample set
(`src/DataFixtures/`): 5 categories, 8 products linked to them, and two users:

| E-mail | Password | Roles |
|---|---|---|
| `admin@example.com` | `admin1234` | `ROLE_ADMIN` |
| `user@example.com` | `user1234` | `ROLE_USER` |

## Entities

- **Product**: `id`, `name`, `price`, `createdAt`, `updatedAt`, `categories` (at least one category).
- **Category**: `id`, `code` (unique, max 10 chars), `createdAt`, `updatedAt`.

`createdAt` / `updatedAt` are set automatically by `TimestampableListener` (registered with the
`#[AsDoctrineListener]` attribute). Responses are returned as clean `application/json`; JSON-LD is
still available via the `Accept: application/ld+json` header.

## Authentication (JWT)

The whole `/api` is protected with JWT; only the login endpoint and the API docs are public.
Obtain a token by posting credentials to `/api/login_check`, then send it as a Bearer token.

A demo user is seeded by the migrations: **`admin@example.com` / `admin1234`**. Create more with
`make console app:create-user <email> <password>` (add `--admin` for `ROLE_ADMIN`).

```bash
# 1) Log in and grab the token
TOKEN=$(curl -s -X POST http://localhost:8080/api/login_check \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"admin1234"}' | jq -r .token)

# 2) Call a protected endpoint with the token
curl http://localhost:8080/api/products -H "Authorization: Bearer $TOKEN"
```

In Swagger UI (`/api`) the login endpoint is documented as `POST /api/login_check`; click
**Authorize** and paste just the token (the scheme is HTTP Bearer, so no `Bearer ` prefix).

## API usage

All the calls below require the `Authorization: Bearer $TOKEN` header (see above).

Create a category:

```bash
curl -X POST http://localhost:8080/api/categories \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"code":"ELEC"}'
```

Create a product. Categories are provided through the write-only **`categoryCodes`** field (plain
codes, no IRIs). Existing categories are matched by code; unknown codes are created automatically:

```bash
curl -X POST http://localhost:8080/api/products \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"name":"Laptop","price":"1999.99","categoryCodes":["ELEC"]}'
```

In responses (and on `GET`) the `categories` field returns the full category objects
(`id`, `code`, dates). `categoryCodes` is used for writing only.

Read / update / delete:

```bash
curl http://localhost:8080/api/products -H "Authorization: Bearer $TOKEN"
curl -X PATCH http://localhost:8080/api/products/1 \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/merge-patch+json' \
  -d '{"price":"1499.00"}'
curl -X DELETE http://localhost:8080/api/products/1 -H "Authorization: Bearer $TOKEN"
```

### Validation rules

- `Category.code`: required, max 10 characters, unique, and may contain only letters
  (upper- or lowercase), digits, `_` and `-`.
- `Product.name`: required, 2–255 characters.
- `Product.price`: required, numeric, `>= 0`, at most two decimals.
- A product must be linked to at least one category.

## Notifications

After a product (and its categories) is saved (`POST`/`PATCH`), the
`ProductNotificationProcessor` (a decorator of API Platform's persist processor) builds a
`Notification` (with a subject) and passes it to `ChannelNotifier`, which fans it out to every
registered channel:

- **LogNotificationChannel** – writes an operation log entry (Monolog `notification` channel,
  visible in `var/log/dev.log`).
- **EmailNotificationChannel** – sends an e-mail (subject + body). The transport is `null://null`
  locally and Mailpit in Docker, where the message is visible at `http://localhost:8025`.

A failing channel is isolated (logged, not re-thrown), so it never breaks the request or the other
channels.

### Adding more channels (Slack, SMS, ...)

Just create a class implementing `NotificationChannelInterface` — it is auto-tagged
(`app.notification_channel`) and picked up by `ChannelNotifier` with no configuration change:

```php
final class SlackNotificationChannel implements NotificationChannelInterface
{
    public function send(Notification $notification): void
    {
        // send $notification->getSubject() / getMessage() to a Slack webhook
    }
}
```

## Tests

The test suite (PHPUnit 13) has two layers:

- **Unit tests** (`tests/Unit`) – entities, the notification layer (notifier, log & e-mail
  channels), the notification processor, the `categoryCodes` denormalizer and the timestamp
  listener, all with test doubles (no I/O).
- **Functional API tests** (`tests/Functional`) – full HTTP round-trips against a real database
  (`ApiTestCase`): CRUD, validation, `categoryCodes` auto-create/reuse, automatic timestamps,
  the notification e-mail, and JWT auth (401 without a token, login, public docs). Each test seeds
  a user + JWT and runs against empty tables (truncated between tests) for full isolation.

Run everything (creates/prepares the `app_test` database, then runs PHPUnit):

```bash
make test
```

You can also run a subset directly in the container:

```bash
docker compose exec app php vendor/bin/phpunit tests/Unit
docker compose exec app php vendor/bin/phpunit tests/Functional
```

## Notes

- API validation errors are returned in the standard structured (Hydra `ConstraintViolation`)
  format; successful responses are plain JSON.
- JWT keys live in `config/jwt/` and are git-ignored — run `make jwt-keys` after cloning. The
  passphrase is in `.env` (`JWT_PASSPHRASE`); use real secrets outside of this demo.
