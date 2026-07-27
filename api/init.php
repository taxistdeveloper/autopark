<?php
require dirname(__DIR__) . '/config/session.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    require dirname(__DIR__) . '/config/bootstrap.php';
    $db = getDb();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

set_exception_handler(function (Throwable $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
});

function requireAuth(): array {
    if (empty($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Необходима авторизация']);
        exit;
    }
    return $_SESSION['user'];
}

function jsonResponse($data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

function jsonError(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    if ($raw === '') return [];
    $dec = json_decode($raw, true);
    return is_array($dec) ? $dec : [];
}
