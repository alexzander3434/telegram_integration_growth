# test_telegram_integrations

Symfony-style API endpoint to create Telegram integrations.

`GET /shops/{shopId}/telegram/status` — статистика отправок, флаги и маскированный `chatId` (без полей для предзаполнения формы подключения).

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

`chatId` — числовой id пользователя или группы (для супергрупп часто вида `-100…`).

Responses:

- `201`: integration created (first time for this shop)
- `200`: integration updated (same `shopId`, new token/chat/enabled)
- `422`: validation errors
- `400`: invalid JSON

## Docker Compose

Перед первым запуском создайте `.env` из шаблона: `cp .env.example .env` (Compose подхватывает переменные из `.env` в корне репозитория).

PHP (`app`, `worker`) монтирует `./src`, `./config`, `./public`, `./migrations` в контейнер — **изменения PHP видны после перезапуска контейнера** (`docker compose restart app worker`), **без** `docker compose build`.

Если вы **не** используете эти volumes (старый compose) или меняли только образ: пересоберите и поднимите заново:

```bash
docker compose build app worker && docker compose up -d app worker
```

**Angular UI** в образе `frontend` собирается при `build` и **не** монтируется. После правок во `frontend/`:

```bash
docker compose build frontend && docker compose up -d frontend
```

Жёсткое обновление страницы в браузере (Ctrl+Shift+R) сбрасывает кэш старого JS.

## Local run (example)

1. Install PHP 8.2+ and Composer.
2. Скопируйте `.env.example` в `.env` и задайте учётные данные PostgreSQL: `POSTGRES_USER`, `POSTGRES_PASSWORD`, `POSTGRES_DB` и согласованный с ними `DATABASE_URL` (для Docker Compose те же переменные читаются из `.env` в корне проекта).
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

## Тесты (PHPUnit)

Конфигурация: `phpunit.xml.dist`, точка входа — `tests/bootstrap.php`.

**Окружение.** Bootstrap подменяет `DATABASE_URL` на **SQLite** (`var/test.db`), принудительно выставляет **`APP_ENV=test`** и **`APP_DEBUG=0`** (в т.ч. после `bootEnv`, чтобы не подхватить `APP_ENV=dev` из Docker Compose или из `.env`/`.env.example`), очищает `var/cache/test` перед прогоном. Файл `.env` в репозитории не хранится: если его нет, подхватывается **`.env.example`** (чтобы PHPUnit в Docker с `-v "$PWD:/var/www/html"` не падал на отсутствии `.env`). Поднимается реальный Symfony Kernel (`KernelTestCase`), используется тот же `framework` test-конфиг, что и в приложении (в т.ч. `cache.adapter.array`, mock HTTP-клиент для Telegram из `config/services.yaml` в профиле `test`).

**Схема БД.** В `CreateOrderTelegramSentTest::setUp()` схема пересоздаётся через Doctrine `SchemaTool` по **метаданным сущностей**, миграции при тестах не выполняются. Перед каждым тестом в БД кладутся минимальные фикстуры: один `Shop` и одна активная `TelegramIntegration` для этого магазина.

**Запуск локально** (нужны PHP и зависимости Composer):

```bash
./vendor/bin/phpunit -c phpunit.xml.dist
```

Через Docker (каталог проекта смонтирован в контейнер, чтобы подхватывались актуальные `tests/` и `src/`):

```bash
docker compose run --rm --no-deps -v "$PWD:/var/www/html" app php ./vendor/bin/phpunit -c phpunit.xml.dist
```

**Класс `CreateOrderTelegramSentTest`.** Сценарии помечены `#[RunInSeparateProcess]` — каждый тест в отдельном PHP-процессе, чтобы изолировать состояние ядра и обработчиков. Кратко по методам:

| Тест | Что проверяет |
|------|----------------|
| `testCreateOrderDispatchesTelegramSendAndWritesSentLog` | `POST /shops/{id}/orders` возвращает `201`, в логе отправок одна запись со статусом «отправлено», к mock Telegram ушёл хотя бы один HTTP-запрос. |
| `testRepeatedOrderCreatedMessageDoesNotDuplicateTelegramSendNorSendLogRows` | Повторная диспатчеризация того же `OrderCreatedMessage` не дублирует вызов API и не создаёт вторую строку лога. |
| `testRetryHttpCreateOrderWithSameNumberDoesNotDuplicateTelegramSendNorSendLog` | Повтор того же HTTP-запроса с тем же `number` даёт `409`, повторной отправки в Telegram нет, в логе по-прежнему одна запись. |
| `testTelegramFailurePersistsFailedSendLogWhileOrderStillCreated` | При ответе Telegram «не ok» заказ всё равно создан (`201`), в логе статус ошибки и текст ошибки сохранены. |
| `testCreateOrderReturns404WhenShopDoesNotExist` | Для несуществующего `shopId` — `404` и `Shop not found` (проверка при первом обращении к кешу id магазинов). |
| `testCreateOrderReturns404ForUnknownShopWhenShopIdsCacheAlreadyWarm` | После успешного `POST` заказа для существующего магазина кеш id уже заполнен; повторный `POST` для несуществующего `shopId` по-прежнему даёт `404` и `Shop not found`. |

## Допущения и упрощения

- **Без аутентификации и авторизации.** API и демо-UI не защищены: любой, кто достучался до сервиса, может читать список магазинов, подключать Telegram к любому `shopId`, смотреть статус интеграции и создавать заказы для существующих магазинов.
- **Токен бота в БД в открытом виде** (без шифрования на уровне приложения).
- **`POST .../orders` и существование магазина:** перед созданием заказа проверяется, что `shopId` есть в таблице `shops`; набор id кешируется в `cache.app` (Redis в compose, in-memory в test) на **1 час**, при промахе пересобирается из БД. Новый магазин без инвалидации кеша может стать доступен для заказов с задержкой до истечения TTL (для явного сброса в коде есть `ShopIdsCache::invalidate()`).
- **Уведомление в Telegram асинхронное** (Symfony Messenger): заказ создаётся до фактической отправки; ошибки доставки не отражаются в ответе `POST .../orders`.
- **Текст уведомления упрощён:** валюта в сообщении зашита как «₽», без мультивалютности и локализации.
- **Интеграция с Telegram — только `sendMessage`:** нет вебхуков, проверки владельца чата, тестового `getMe` при сохранении и т.п.
- **Docker Compose для разработки:** слабые пароли (`postgres`, `guest`), `APP_SECRET: change_me`, открытые порты БД/брокера — не использовать как шаблон продакшена без доработки.
- **Angular в Docker** собирается в образ при `build` и не монтируется исходниками — для правок UI нужна пересборка образа `frontend` (см. выше).
- **Локальная разработка UI:** `proxy.conf.json` шьёт `/api` на `http://localhost:8000`; при другом хосте/порте бэкенда прокси нужно поправить вручную.
- **Переменная `TELEGRAM_SIMULATE_SEND_FAILURE`** — искусственная ошибка отправки до HTTP-вызова Telegram (удобно для тестов и отладки воркера).

