# Evruni V1.3 — установка на Jino.ru

Эта версия сделана специально для стандартной структуры Jino, где домен
`evruni.com` указывает на:

`/domains/evruni.com/`

## 1. Распаковать архив

В файловом менеджере Jino откройте папку:

`domains/evruni.com/`

Распакуйте **содержимое ZIP именно в эту папку**.

После распаковки в ней должны быть:

- `index.php`
- `login.php`
- `register.php`
- `install.php`
- `api/`
- `assets/`
- `_private/`
- `.htaccess`
- `.env.jino.example`
- `media/`

Не должно получиться:

`domains/evruni.com/evruni-v1.3-jino/...`

## 2. Создать `.env`

Сделайте копию:

`.env.jino.example` → `.env`

И заполните реальные значения базы данных, `APP_KEY`, AI API и Stripe.

`.env` находится в корне сайта, но закрыт от HTTP-доступа через `.htaccess`.

## 3. База данных

Создайте MySQL/MariaDB базу и пользователя в панели Jino.

Данные внесите в `.env`:

- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

## 4. PHP

Для `evruni.com` выберите PHP 8.3+ (у вас доступен PHP 8.4).

Нужны расширения:

- PDO
- PDO MySQL
- cURL
- OpenSSL
- mbstring
- fileinfo
- JSON

## 5. Установка

Откройте:

`https://evruni.com/install.php`

Создайте первого администратора.

После успешной установки удалите `install.php` из сайта или ограничьте к нему доступ.

## 6. Структура безопасности

Публичная часть:

- `index.php`
- `login.php`
- `register.php`
- `api/`
- `assets/`
- `media/generated/`

Внутренняя часть:

- `_private/config/`
- `_private/lib/`
- `_private/database/`
- `_private/storage/`
- `_private/workers/`

Каталог `_private/` заблокирован от HTTP-доступа.

Пользовательские загрузки хранятся в `_private/storage/uploads/` и не публикуются напрямую.

## 7. Cron / worker

Для текстовых генераций текущая версия работает синхронно через API.
Для будущих очередей worker находится здесь:

`_private/workers/worker.php`

Если вы включите очередные типы генерации, настройте cron по правилам Jino.

## 8. Stripe

Webhook:

`https://evruni.com/api/stripe_webhook.php`

Настройте секрет webhook и Price ID в `.env`.

## 9. Важно

Перед запуском в production:

- включите HTTPS;
- замените `APP_KEY` на длинный случайный секрет;
- используйте реальные AI/Stripe ключи;
- сделайте резервные копии БД и `_private/storage`;
- не публикуйте `.env`.
