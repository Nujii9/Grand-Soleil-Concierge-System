<?php
// ============================================================
//  config.php — Database Connection & Global Config
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // ← change to your MySQL user
define('DB_PASS', '');              // ← change to your MySQL password
define('DB_NAME', 'hotel_db');
define('DB_PORT', 3306);

define('HOTEL_NAME',    'Grand Soleil Hotel');
define('HOTEL_ADDRESS', '123 Rizal Ave, Makati City, Metro Manila');
define('HOTEL_PHONE',   '+63 2 8888-9999');
define('HOTEL_EMAIL',   'reservations@grandsoleil.ph');
define('TAX_RATE',      0.12);      // VAT 12%
define('SERVICE_CHARGE',0.10);      // Service charge 10%

// ----------------------------
//  Create PDO connection
// ----------------------------
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ----------------------------
//  JSON response helper
// ----------------------------
function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// ----------------------------
//  Sanitize input helper
// ----------------------------
function clean(?string $value): string {
    return htmlspecialchars(trim((string)($value ?? '')), ENT_QUOTES, 'UTF-8');
}

// ----------------------------
//  CORS / JSON header for API calls
// ----------------------------
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
