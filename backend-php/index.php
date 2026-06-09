<?php
/**
 * Minimal JSON-file PHP backend for the UniSpace frontend.
 * Compatible with PHP 7.4+ and PHP 8.x.
 *
 * Run with PHP built-in server:
 *   php -S localhost:8000 -t backend-php backend-php/router.php
 *
 * Or put this folder inside Laragon's www directory as backend-php.
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Kuala_Lumpur');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
$allowedOrigins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:4173',
    'http://127.0.0.1:4173',
];

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

const DB_FILE = __DIR__ . '/data/db.json';
const VALID_STATUSES = ['pending', 'approved', 'confirmed', 'cancelled', 'rejected', 'completed'];

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function errorResponse(string $message, int $status = 400, string $code = 'BAD_REQUEST'): void
{
    respond(['error' => ['code' => $code, 'message' => $message]], $status);
}

function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        errorResponse('Invalid JSON request body.', 400, 'INVALID_JSON');
    }
    return $data;
}

function loadDb(): array
{
    if (!file_exists(DB_FILE)) {
        return ['rooms' => [], 'bookings' => []];
    }
    $raw = file_get_contents(DB_FILE);
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) {
        errorResponse('Database JSON file is corrupted.', 500, 'DB_CORRUPTED');
    }
    $data['rooms'] = is_array($data['rooms'] ?? null) ? $data['rooms'] : [];
    $data['bookings'] = is_array($data['bookings'] ?? null) ? $data['bookings'] : [];
    return $data;
}

function saveDb(array $db): void
{
    $dir = dirname(DB_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $json = json_encode($db, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false || file_put_contents(DB_FILE, $json . PHP_EOL, LOCK_EX) === false) {
        errorResponse('Failed to write database JSON file.', 500, 'DB_WRITE_FAILED');
    }
}

function nowIso(): string
{
    return date('c');
}

function makeId(string $prefix): string
{
    return $prefix . '-' . bin2hex(random_bytes(4));
}

function findIndexById(array $items, string $id): int
{
    foreach ($items as $index => $item) {
        if (($item['id'] ?? null) === $id) {
            return $index;
        }
    }
    return -1;
}

function asBool($value, bool $default = true): bool
{
    if (is_bool($value)) return $value;
    if (is_string($value)) {
        $lower = strtolower($value);
        if ($lower === 'true' || $lower === '1') return true;
        if ($lower === 'false' || $lower === '0') return false;
    }
    if (is_numeric($value)) return ((int)$value) === 1;
    return $default;
}

function parseFeatures($value): array
{
    if (is_array($value)) {
        $features = $value;
    } elseif (is_string($value)) {
        $features = explode(',', $value);
    } else {
        $features = [];
    }

    $features = array_map(fn($v) => trim((string)$v), $features);
    $features = array_values(array_filter($features, fn($v) => $v !== ''));
    return array_slice($features, 0, 12);
}

function isTime(string $value): bool
{
    return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
}

function toMinutes(string $time): int
{
    [$h, $m] = array_map('intval', explode(':', $time));
    return $h * 60 + $m;
}

function minutesToTime(int $minutes): string
{
    $minutes = max(0, min(23 * 60 + 59, $minutes));
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

function intervalsOverlap(string $startA, string $endA, string $startB, string $endB): bool
{
    return toMinutes($startA) < toMinutes($endB) && toMinutes($startB) < toMinutes($endA);
}

function roomExists(array $rooms, string $roomId): bool
{
    return findIndexById($rooms, $roomId) >= 0;
}

function isRoomActive(array $rooms, string $roomId): bool
{
    $idx = findIndexById($rooms, $roomId);
    if ($idx < 0) return false;
    return (bool)($rooms[$idx]['active'] ?? false);
}

function hasBookingConflict(array $bookings, string $roomId, string $date, string $startTime, string $endTime, ?string $ignoreId = null): bool
{
    foreach ($bookings as $booking) {
        if ($ignoreId !== null && ($booking['id'] ?? '') === $ignoreId) continue;
        if (($booking['roomId'] ?? '') !== $roomId) continue;
        if (($booking['date'] ?? '') !== $date) continue;
        if (in_array($booking['status'] ?? '', ['cancelled', 'rejected', 'completed'], true)) continue;
        if (intervalsOverlap($startTime, $endTime, (string)$booking['startTime'], (string)$booking['endTime'])) {
            return true;
        }
    }
    return false;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

if ($scriptDir !== '' && $scriptDir !== '/' && substr($uriPath, 0, strlen($scriptDir)) === $scriptDir) {
    $uriPath = substr($uriPath, strlen($scriptDir));
}

$path = '/' . trim($uriPath, '/');
if ($path === '/') {
    respond([
        'ok' => true,
        'service' => 'UniSpace PHP API',
        'routes' => [
            'GET /api/rooms',
            'POST /api/rooms',
            'PUT /api/rooms/{id}',
            'DELETE /api/rooms/{id}',
            'GET /api/bookings',
            'POST /api/bookings',
            'PUT /api/bookings/{id}',
            'POST /api/availability'
        ]
    ]);
}

$db = loadDb();

if ($path === '/api/rooms' && $method === 'GET') {
    respond(['rooms' => array_values($db['rooms'])]);
}

if ($path === '/api/rooms' && $method === 'POST') {
    $body = readJsonBody();
    $name = trim((string)($body['name'] ?? ''));
    $location = trim((string)($body['location'] ?? ''));
    $capacity = (int)($body['capacity'] ?? 0);

    if (strlen($name) < 3) errorResponse('Room name must contain at least 3 characters.');
    if (strlen($location) < 2) errorResponse('Room location must contain at least 2 characters.');
    if ($capacity < 1 || $capacity > 100) errorResponse('Room capacity must be between 1 and 100.');

    $now = nowIso();
    $room = [
        'id' => makeId('room'),
        'name' => $name,
        'location' => $location,
        'capacity' => $capacity,
        'features' => parseFeatures($body['features'] ?? []),
        'active' => asBool($body['active'] ?? true),
        'createdAt' => $now,
        'updatedAt' => $now,
    ];

    $db['rooms'][] = $room;
    saveDb($db);
    respond(['room' => $room], 201);
}

if (preg_match('#^/api/rooms/([^/]+)$#', $path, $matches) === 1) {
    $roomId = urldecode($matches[1]);
    $idx = findIndexById($db['rooms'], $roomId);
    if ($idx < 0) errorResponse('Room not found.', 404, 'ROOM_NOT_FOUND');

    if ($method === 'PUT') {
        $body = readJsonBody();
        $room = $db['rooms'][$idx];

        if (array_key_exists('name', $body)) {
            $name = trim((string)$body['name']);
            if (strlen($name) < 3) errorResponse('Room name must contain at least 3 characters.');
            $room['name'] = $name;
        }
        if (array_key_exists('location', $body)) {
            $location = trim((string)$body['location']);
            if (strlen($location) < 2) errorResponse('Room location must contain at least 2 characters.');
            $room['location'] = $location;
        }
        if (array_key_exists('capacity', $body)) {
            $capacity = (int)$body['capacity'];
            if ($capacity < 1 || $capacity > 100) errorResponse('Room capacity must be between 1 and 100.');
            $room['capacity'] = $capacity;
        }
        if (array_key_exists('features', $body)) {
            $room['features'] = parseFeatures($body['features']);
        }
        if (array_key_exists('active', $body)) {
            $room['active'] = asBool($body['active'], (bool)($room['active'] ?? true));
        }

        $room['updatedAt'] = nowIso();
        $db['rooms'][$idx] = $room;
        saveDb($db);
        respond(['room' => $room]);
    }

    if ($method === 'DELETE') {
        array_splice($db['rooms'], $idx, 1);
        saveDb($db);
        respond(['ok' => true]);
    }
}

if ($path === '/api/bookings' && $method === 'GET') {
    $bookings = $db['bookings'];
    $userName = isset($_GET['userName']) ? trim((string)$_GET['userName']) : '';
    $userRole = isset($_GET['userRole']) ? trim((string)$_GET['userRole']) : '';

    if ($userName !== '') {
        $bookings = array_values(array_filter($bookings, fn($b) => strcasecmp((string)($b['userName'] ?? ''), $userName) === 0));
    }
    if ($userRole !== '') {
        $bookings = array_values(array_filter($bookings, fn($b) => ($b['userRole'] ?? '') === $userRole));
    }

    usort($bookings, fn($a, $b) => strcmp(($b['date'] ?? '') . ' ' . ($b['startTime'] ?? ''), ($a['date'] ?? '') . ' ' . ($a['startTime'] ?? '')));
    respond(['bookings' => array_values($bookings)]);
}

if ($path === '/api/bookings' && $method === 'POST') {
    $body = readJsonBody();
    $roomId = trim((string)($body['roomId'] ?? ''));
    $userName = trim((string)($body['userName'] ?? ''));
    $userRole = trim((string)($body['userRole'] ?? 'student'));
    $date = trim((string)($body['date'] ?? ''));
    $startTime = trim((string)($body['startTime'] ?? ''));
    $endTime = trim((string)($body['endTime'] ?? ''));
    $purpose = trim((string)($body['purpose'] ?? 'Study session'));

    if (!roomExists($db['rooms'], $roomId)) errorResponse('Selected room does not exist.', 404, 'ROOM_NOT_FOUND');
    if (!isRoomActive($db['rooms'], $roomId)) errorResponse('Selected room is inactive.', 400, 'ROOM_INACTIVE');
    if ($userName === '') errorResponse('User name is required.');
    if (!in_array($userRole, ['student', 'admin'], true)) errorResponse('Invalid user role.');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) errorResponse('Date must use YYYY-MM-DD format.');
    if (!isTime($startTime) || !isTime($endTime)) errorResponse('Start and end time must use HH:MM format.');
    if (toMinutes($endTime) <= toMinutes($startTime)) errorResponse('End time must be later than start time.');

    if (hasBookingConflict($db['bookings'], $roomId, $date, $startTime, $endTime)) {
        errorResponse('This room is already booked for the selected time slot.', 409, 'BOOKING_CONFLICT');
    }

    $now = nowIso();
    $booking = [
        'id' => makeId('booking'),
        'roomId' => $roomId,
        'userName' => $userName,
        'userRole' => $userRole,
        'date' => $date,
        'startTime' => $startTime,
        'endTime' => $endTime,
        'purpose' => $purpose === '' ? 'Study session' : $purpose,
        'status' => 'pending',
        'createdAt' => $now,
        'updatedAt' => $now,
    ];

    array_unshift($db['bookings'], $booking);
    saveDb($db);
    respond(['booking' => $booking], 201);
}

if (preg_match('#^/api/bookings/([^/]+)$#', $path, $matches) === 1 && $method === 'PUT') {
    $bookingId = urldecode($matches[1]);
    $idx = findIndexById($db['bookings'], $bookingId);
    if ($idx < 0) errorResponse('Booking not found.', 404, 'BOOKING_NOT_FOUND');

    $body = readJsonBody();
    $booking = $db['bookings'][$idx];

    if (array_key_exists('status', $body)) {
        $status = trim((string)$body['status']);
        if (!in_array($status, VALID_STATUSES, true)) errorResponse('Invalid booking status.');
        $booking['status'] = $status;
    }

    foreach (['purpose', 'date', 'startTime', 'endTime'] as $field) {
        if (array_key_exists($field, $body)) {
            $booking[$field] = trim((string)$body[$field]);
        }
    }

    if (!isTime((string)$booking['startTime']) || !isTime((string)$booking['endTime'])) {
        errorResponse('Start and end time must use HH:MM format.');
    }
    if (toMinutes((string)$booking['endTime']) <= toMinutes((string)$booking['startTime'])) {
        errorResponse('End time must be later than start time.');
    }
    if (hasBookingConflict($db['bookings'], (string)$booking['roomId'], (string)$booking['date'], (string)$booking['startTime'], (string)$booking['endTime'], $bookingId)) {
        errorResponse('This room is already booked for the selected time slot.', 409, 'BOOKING_CONFLICT');
    }

    $booking['updatedAt'] = nowIso();
    $db['bookings'][$idx] = $booking;
    saveDb($db);
    respond(['booking' => $booking]);
}

if ($path === '/api/availability' && $method === 'POST') {
    $body = readJsonBody();
    $roomId = trim((string)($body['roomId'] ?? ''));
    $date = trim((string)($body['date'] ?? ''));
    $startTime = trim((string)($body['startTime'] ?? ''));
    $durationHours = (int)($body['durationHours'] ?? 1);

    if (!roomExists($db['rooms'], $roomId)) errorResponse('Selected room does not exist.', 404, 'ROOM_NOT_FOUND');
    if (!isRoomActive($db['rooms'], $roomId)) errorResponse('Selected room is inactive.', 400, 'ROOM_INACTIVE');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) errorResponse('Date must use YYYY-MM-DD format.');
    if (!isTime($startTime)) errorResponse('Start time must use HH:MM format.');
    if ($durationHours < 1 || $durationHours > 6) errorResponse('Duration must be between 1 and 6 hours.');

    $endMinutes = toMinutes($startTime) + $durationHours * 60;
    if ($endMinutes > 24 * 60) {
        errorResponse('Booking cannot pass midnight.');
    }
    $endTime = minutesToTime($endMinutes);
    $available = !hasBookingConflict($db['bookings'], $roomId, $date, $startTime, $endTime);

    respond(['available' => $available, 'endTime' => $endTime]);
}

errorResponse('Route not found: ' . $method . ' ' . $path, 404, 'NOT_FOUND');
