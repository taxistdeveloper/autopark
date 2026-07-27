<?php
require __DIR__ . '/init.php';
$user = requireAuth();

$auditModel = new \App\Models\AuditLog($db);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $vehicleId = isset($_GET['vehicleId']) ? (int) $_GET['vehicleId'] : null;
    if ($vehicleId) {
        $list = $auditModel->byVehicle($vehicleId);
    } else {
        $list = $auditModel->all((int) 5000);
    }
    jsonResponse($list);
    exit;
}

if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'clear') {
    if (empty($user['is_admin']) && ($user['login'] ?? '') !== 'admin') {
        jsonError('Доступ только для администратора', 403);
    }
    $auditModel->clear();
    jsonResponse(['ok' => true]);
    exit;
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if (!$id) jsonError('Укажите id');
    if (empty($user['is_admin']) && ($user['login'] ?? '') !== 'admin') {
        jsonError('Доступ только для администратора', 403);
    }
    $auditModel->deleteEntry($id);
    jsonResponse(['ok' => true]);
    exit;
}

jsonError('Метод не поддерживается', 405);
