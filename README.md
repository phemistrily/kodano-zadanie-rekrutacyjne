# Kodano – recruitment task

REST API for managing **products** and **categories**, built with **Symfony 8.1**,
**API Platform 4.3** and **MySQL 8.4**, and run with **Docker** (FrankenPHP + MySQL + Mailpit).

## Stack

- PHP 8.4 / Symfony 8.1
- API Platform 4.3 (CRUD generated from `#[ApiResource]` attributes)
- Doctrine ORM 3 + Doctrine Migrations
- MySQL 8.4
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
│   ├── Shared/Entity/Traits/          # id + timestamps
│   └── Notification/                  # Notification, NotifierInterface, NotificationChannelInterface
└── Infrastructure/
    ├── Doctrine/EventListener/        # sets created/updated dates automatically (prePersist/preUpdate)
    ├── Doctrine/Repository/
    ├── Notification/                  # ChannelNotifier + channels (Log, Email)
    ├── ApiPlatform/State/             # ProductNotificationProcessor (post-save hook)
    └── ApiPlatform/Serializer/        # ProductCategoryCodesDenormalizer (categoryCodes -> categories)
```

## Running the app

```bash
make up          # build and start containers (app + database + mailer)
make install     # composer install inside the container (if vendor/ is missing)
make migrate     # run migrations (creates the database schema)
```

- API: `http://localhost:8080/api`
- Mailpit (e-mail preview): `http://localhost:8025`

Useful shortcuts: `make down`, `make sh`, `make logs`, `make console <command>`.

## Entities

- **Product**: `id`, `name`, `price`, `createdAt`, `updatedAt`, `categories` (at least one category).
- **Category**: `id`, `code` (unique, max 10 chars), `createdAt`, `updatedAt`.

`createdAt` / `updatedAt` are set automatically by `TimestampableListener` (registered with the
`#[AsDoctrineListener]` attribute). Responses are returned as clean `application/json`; JSON-LD is
still available via the `Accept: application/ld+json` header.

## API usage

Create a category:

```bash
curl -X POST http://localhost:8080/api/categories \
  -H 'Content-Type: application/json' \
  -d '{"code":"ELEC"}'
```

Create a product. Categories are provided through the write-only **`categoryCodes`** field (plain
codes, no IRIs). Existing categories are matched by code; unknown codes are created automatically:

```bash
curl -X POST http://localhost:8080/api/products \
  -H 'Content-Type: application/json' \
  -d '{"name":"Laptop","price":"1999.99","categoryCodes":["ELEC"]}'
```

In responses (and on `GET`) the `categories` field returns the full category objects
(`id`, `code`, dates). `categoryCodes` is used for writing only.

Read / update / delete:

```bash
curl http://localhost:8080/api/products
curl -X PATCH http://localhost:8080/api/products/1 \
  -H 'Content-Type: application/merge-patch+json' \
  -d '{"price":"1499.00"}'
curl -X DELETE http://localhost:8080/api/products/1
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
  (`ApiTestCase`): CRUD, validation, `categoryCodes` auto-create/reuse, automatic timestamps and
  the notification e-mail. Each test runs against empty tables (truncated between tests) for full isolation.

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
- The API has no authentication (`security.yaml` has no `access_control`) — acceptable for a
  recruitment task, but worth noting.
