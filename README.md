# Studio Booking API

API для системы онлайн-бронирования студии по дням и фиксированным временным слотам с синхронизацией данных с Google Calendar.

## Описание проекта

Это backend API для системы бронирования студии, которое позволяет:

- Проверять доступность временных слотов
- Создавать новые бронирования
- Отменять существующие бронирования
- Получать данные о бронированиях за период
- Синхронизировать данные с Google Calendar

## Требования

- PHP 8.x
- Composer
- MySQL или SQLite
- Google Calendar API credentials

## Установка

### 1. Клонирование репозитория

```bash
git clone https://github.com/zexz/studiobook.git
cd studiobook
```

### 2. Установка зависимостей

```bash
composer install
```

### 3. Настройка окружения

Скопируйте файл `.env.example` в `.env` и настройте параметры подключения к базе данных:

```bash
cp .env.example .env
php artisan key:generate
```

Отредактируйте файл `.env`, указав параметры подключения к базе данных:

```
DB_CONNECTION=mysql  # или sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=calendar_api
DB_USERNAME=root
DB_PASSWORD=
```

Если вы используете SQLite, укажите:

```
DB_CONNECTION=sqlite
```

И создайте файл базы данных:

```bash
touch database/database.sqlite
```

### 4. Настройка Google Calendar

1. Создайте проект в [Google Cloud Console](https://console.cloud.google.com/)
2. Включите Google Calendar API
3. Создайте учетные данные OAuth 2.0
4. Скачайте JSON файл с учетными данными
5. Поместите файл в `storage/app/google-calendar/credentials.json`

Добавьте в файл `.env` следующие параметры:

```
GOOGLE_CALENDAR_ID=primary  # или ID вашего календаря
```

### 5. Выполнение миграций

```bash
php artisan migrate
```

### 6. Настройка временных слотов

Временные слоты можно настроить в файле конфигурации `config/booking.php` или через переменные окружения в `.env`:

```
BOOKING_START_TIME=09:00
BOOKING_END_TIME=21:00
BOOKING_SLOT_DURATION=60  # в минутах
BOOKING_TIMEZONE=Europe/Moscow
```

## Запуск

### Запуск сервера для разработки

```bash
php artisan serve
```

API будет доступно по адресу: http://localhost:8000/api

### Настройка планировщика для синхронизации с Google Calendar

Добавьте в crontab следующую запись для запуска синхронизации каждые 10 минут:

```
*/10 * * * * cd /path/to/project && php artisan app:sync-google-calendar >> /dev/null 2>&1
```

Или для тестирования запустите команду вручную:

```bash
php artisan app:sync-google-calendar
```

## API Документация

Подробная документация API доступна в файле [docs/api.md](docs/api.md).

### Основные эндпоинты

- `GET /api/bookings/period` - Получение бронирований за период
- `GET /api/availability` - Проверка доступности слотов на дату
- `POST /api/bookings` - Создание нового бронирования
- `DELETE /api/bookings/{id}` - Отмена бронирования

## Тестирование

Для запуска тестов выполните:

```bash
php artisan test
```

## Лицензия

MIT
