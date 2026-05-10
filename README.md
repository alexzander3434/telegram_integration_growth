# test_telegram_integrations

Symfony-style API endpoint to create Telegram integrations.

## Endpoint

`POST /shops/{shopId}/telegram/connect`

Payload:

```json
{
  "botToken": "string",
  "chatId": "string",
  "enabled": true
}
```

Responses:

- `201`: integration created
- `409`: integration for `shopId` already exists (`UNIQUE(shop_id)`)
- `422`: validation errors
- `400`: invalid JSON

## Local run (example)

1. Install PHP 8.2+ and Composer.
2. Set `DATABASE_URL` in `.env`.
3. Install deps:

```bash
composer install
```

4. Run migrations:

```bash
php bin/console doctrine:migrations:migrate
```

5. Run server:

```bash
php -S 127.0.0.1:8000 -t public
```

