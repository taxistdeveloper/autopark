<?php
require __DIR__ . '/init.php';
requireAuth();

$vehicleModel = new \App\Models\Vehicle($db);
$mhModel = new \App\Models\MotorHoursHistory($db);
$auditModel = new \App\Models\AuditLog($db);
$user = $_SESSION['user'];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $vehicleId = isset($_GET['vehicleId']) ? (int) $_GET['vehicleId'] : 0;
    if (!$vehicleId) {
        jsonError('Укажите vehicleId');
    }
    $list = $mhModel->byVehicle($vehicleId);
    jsonResponse($list);
    exit;
}

if ($method === 'POST') {
    $data = getJsonInput();
    $vehicleId = (int) ($data['vehicleId'] ?? $data['vehicle_id'] ?? 0);
    $amount = (float) ($data['amount'] ?? 0);
    if (!$vehicleId || $amount <= 0) {
        jsonError('Укажите vehicleId и положительное количество моточасов');
    }
    $v = $vehicleModel->get($vehicleId);
    if (!$v) {
        jsonError('ТС не найдено', 404);
    }
    $current = $v['motorHours'] ?? 0;
    $newTotal = $current + $amount;
    $vehicleModel->update($vehicleId, ['motorHours' => $newTotal]);
    $mhModel->add($vehicleId, $amount, $newTotal);
    $auditModel->add('motohours', 'motohours', (string) $vehicleId, $v['name'] ?? $v['grnz'] ?? 'ТС', '+' . $amount . ' м/ч, всего: ' . $newTotal, $vehicleId, $user['id'] ?? null, $user['name'] ?? null);
    jsonResponse(['ok' => true, 'motorHours' => $newTotal, 'history' => $mhModel->byVehicle($vehicleId)]);
    exit;
}

jsonError('Метод не поддерживается', 405);
