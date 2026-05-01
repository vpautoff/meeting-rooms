<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;
use InvalidArgumentException;

final class Bookings
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function create(array $data): array
    {
        $userId = trim((string)($data['user_id'] ?? ''));
        $roomId = (int)($data['room_id'] ?? 0);
        $startsAt = trim((string)($data['starts_at'] ?? ''));
        $endsAt = trim((string)($data['ends_at'] ?? ''));
        $title = trim((string)($data['title'] ?? ''));

        if ($userId === '') {
            throw new InvalidArgumentException('user_id is required');
        }
        if ($roomId <= 0) {
            throw new InvalidArgumentException('room_id is required and must be positive');
        }

        $start = self::parseDate($startsAt, 'starts_at');
        $end   = self::parseDate($endsAt,   'ends_at');

        if ($end <= $start) {
            throw new InvalidArgumentException('ends_at must be greater than starts_at');
        }

        $pdo = Database::pdo();

        $room = $pdo->prepare('SELECT id FROM rooms WHERE id = ?');
        $room->execute([$roomId]);
        if ($room->fetchColumn() === false) {
            throw new InvalidArgumentException("room_id {$roomId} not found");
        }

        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');

        $pdo->beginTransaction();
        try {
            // Overlap detection: find any room booking that overlaps with the requested interval.
            // Two intervals [a, b) and [c, d) overlap if a < d AND b > c.
            $overlap = $pdo->prepare(
                'SELECT 1 FROM bookings
                  WHERE room_id   = :room_id
                    AND starts_at < :ends_at
                    AND ends_at   > :starts_at
                  LIMIT 1
                  FOR UPDATE'
            );
            $overlap->execute([
                'room_id'   => $roomId,
                'starts_at' => $startStr,
                'ends_at'   => $endStr,
            ]);
            if ($overlap->fetchColumn() !== false) {
                throw new InvalidArgumentException('Room is already booked for this time slot');
            }

            $insert = $pdo->prepare(
                'INSERT INTO bookings (user_id, room_id, title, starts_at, ends_at)
                 VALUES (:user_id, :room_id, :title, :starts_at, :ends_at)'
            );
            $insert->execute([
                'user_id'   => $userId,
                'room_id'   => $roomId,
                'title'     => $title,
                'starts_at' => $startStr,
                'ends_at'   => $endStr,
            ]);

            $id = (int)$pdo->lastInsertId();
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return self::find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public static function find(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? [] : self::cast($row);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listByUser(string $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM bookings WHERE user_id = ? ORDER BY starts_at ASC'
        );
        $stmt->execute([$userId]);
        return array_map([self::class, 'cast'], $stmt->fetchAll());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listByRoom(int $roomId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM bookings WHERE room_id = ? ORDER BY starts_at ASC'
        );
        $stmt->execute([$roomId]);
        return array_map([self::class, 'cast'], $stmt->fetchAll());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listRooms(): array
    {
        return Database::pdo()->query('SELECT id, name FROM rooms ORDER BY id')->fetchAll();
    }

    private static function parseDate(string $value, string $field): DateTimeImmutable
    {
        if ($value === '') {
            throw new InvalidArgumentException("{$field} is required");
        }
        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            throw new InvalidArgumentException("{$field} has invalid format, expected ISO-8601");
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function cast(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'user_id' => (string)$row['user_id'],
            'room_id' => (int)$row['room_id'],
            'title' => (string)$row['title'],
            'starts_at' => self::toIso((string)$row['starts_at']),
            'ends_at' => self::toIso((string)$row['ends_at']),
            'created_at' => self::toIso((string)$row['created_at']),
        ];
    }

    private static function toIso(string $mysqlDateTime): string
    {
        return str_replace(' ', 'T', $mysqlDateTime);
    }
}
