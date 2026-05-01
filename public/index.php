<?php

declare(strict_types=1);

use App\Bookings;
use App\Response;

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/Response.php';
require __DIR__ . '/../src/Bookings.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path   = rtrim($path, '/') ?: '/';

try {
    switch (true) {

        case $method === 'GET' && $path === '/':
            Response::json([
                'service'   => 'meeting-rooms-booking',
                'endpoints' => [
                    'POST /bookings',
                    'GET  /bookings?user_id=...&page=1&limit=50',
                    'GET  /bookings?room_id=...&page=1&limit=50',
                    'GET  /rooms',
                ],
            ]);
            break;

        case $method === 'GET' && $path === '/rooms':
            Response::json(Bookings::listRooms());
            break;

        case $method === 'POST' && $path === '/bookings':
            $body = file_get_contents('php://input') ?: '';
            $data = json_decode($body, true);
            if (!is_array($data)) {
                Response::error('Invalid JSON body', 400);
                break;
            }
            $created = Bookings::create($data);
            Response::json($created, 201);
            break;

        case $method === 'GET' && $path === '/bookings':
            $userId = isset($_GET['user_id']) ? trim((string)$_GET['user_id']) : '';
            $roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

            if ($userId === '' && $roomId <= 0) {
                Response::error('Provide user_id or room_id query parameter', 400);
                break;
            }

            $result = $userId !== ''
                ? Bookings::listByUser($userId, $page, $limit)
                : Bookings::listByRoom($roomId, $page, $limit);

            Response::json($result);
            break;

        default:
            Response::error('Not found', 404);
    }
} catch (InvalidArgumentException $e) {
    Response::error($e->getMessage(), 422);
} catch (Throwable $e) {
    error_log('[meeting-rooms] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    Response::error('Internal error', 500);
}
