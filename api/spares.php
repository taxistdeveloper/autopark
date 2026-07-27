<?php
require __DIR__ . '/init.php';
requireAuth();

$spareModel = new \App\Models\Spare($db);
$vehicleModel = new \App\Models\Vehicle($db);
$auditModel = new \App\Models\AuditLog($db);
$user = $_SESSION['user'];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($method === 'GET') {
    if ($id) {
        $s = $spareModel->get($id);
        if (!$s) jsonError('Запись не найдена', 404);
        jsonResponse($s);
    } else {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        jsonResponse($spareModel->all($search));
    }
    exit;
}

if ($method === 'POST') {
    $data = getJsonInput();
    $vehicleId = (int) ($data['vehicleId'] ?? 0);
    if (!$vehicleId) jsonError('Выберите ТС');
    $vehicle = $vehicleModel->get($vehicleId);
    if (!$vehicle) jsonError('ТС не найдено', 404);
    $newId = $spareModel->create($data);
    $spare = $spareModel->get($newId);
    $entityName = ($vehicle['name'] ?? $vehicle['grnz']) . (isset($data['spareName']) && $data['spareName'] !== '' ? ' — ' . $data['spareName'] : '');
    $auditModel->add('add', 'spare', (string) $newId, $entityName, 'Добавлена запчасть', $vehicleId, $user['id'] ?? null, $user['name'] ?? null);
    jsonResponse(['id' => (string) $newId, 'spare' => $spare]);
    exit;
}

if ($method === 'PUT' && $id) {
    $data = getJsonInput();
    $existing = $spareModel->get($id);
    if (!$existing) jsonError('Запись не найдена', 404);
    $vehicleId = (int) ($data['vehicleId'] ?? $existing['vehicleId'] ?? 0);
    $vehicle = $vehicleModel->get($vehicleId);
    if (!$vehicle) jsonError('ТС не найдено', 404);
    $spareModel->update($id, $data);
    $entityName = ($vehicle['name'] ?? $vehicle['grnz']) . (isset($data['spareName']) ? ' — ' . $data['spareName'] : '');
    $auditModel->add('edit', 'spare', (string) $id, $entityName, 'Редактирование запчасти', $vehicleId, $user['id'] ?? null, $user['name'] ?? null);
    jsonResponse(['ok' => true, 'spare' => $spareModel->get($id)]);
    exit;
}

if ($method === 'DELETE' && $id) {
    $s = $spareModel->get($id);
    if (!$s) jsonError('Запись не найдена', 404);
    $name = ($s['vehicleName'] ?? '') . (isset($s['spareName']) && $s['spareName'] ? ' — ' . $s['spareName'] : '');
    $vehicleId = isset($s['vehicleId']) ? (int) $s['vehicleId'] : null;
    $spareModel->delete($id);
    $auditModel->add('delete', 'spare', (string) $id, $name, 'Удалил запчасть', $vehicleId, $user['id'] ?? null, $user['name'] ?? null);
    jsonResponse(['ok' => true]);
    exit;
}

jsonError('Метод не поддерживается', 405);
