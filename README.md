markdown
# Meeting Rooms Booking Service

Микросервис для бронирования переговорных комнат. JSON API на чистом PHP + MySQL.

## Стек

- PHP 8.1+ (без фреймворка), расширение `pdo_mysql`
- MySQL 8.0

Параметры подключения переопределяются через ENV: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.

## API

| Метод | Путь | Описание |
|-------|------|----------|
| POST  | `/bookings`                  | Создать бронирование |
| GET   | `/bookings?user_id=u-123`    | Бронирования пользователя |
| GET   | `/bookings?room_id=1`        | Бронирования комнаты |
| GET   | `/rooms`                     | Список переговорок |

### Пример

```bash
curl -X POST http://127.0.0.1:8080/bookings \
  -H 'Content-Type: application/json' \
  -d '{"user_id":"u-123","room_id":1,"starts_at":"2026-05-01T10:00:00","ends_at":"2026-05-01T11:00:00","title":"Sync"}'
```

## Коды ответов

| Код | Когда |
|-----|-------|
| 201 | Создано |
| 200 | Успешный GET |
| 400 | Невалидный JSON / нет параметров выборки |
| 404 | Маршрут не найден |
| 422 | Ошибка валидации |
| 500 | Внутренняя ошибка |
Как собрать локально
 
 
bash
mkdir -p meeting-rooms/{public,src,migrations,examples}
cd meeting-rooms
# создайте файлы по содержимому выше
git init
git add .
git commit -m "feat: meeting rooms booking service"
