<?php
require __DIR__ . '/init.php';
requireAuth();

$rentModel = new \App\Models\Rent($db);
$vehicleModel = new \App\Models\Vehicle($db);
$auditModel = new \App\Models\AuditLog($db);
$user = $_SESSION['user'];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($method === 'GET') {
    if ($id) {
        $r = $rentModel->get($id);
        if (!$r) jsonError('Запись не найдена', 404);
        jsonResponse($r);
    } else {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        jsonResponse($rentModel->all($search));
    }
    exit;
}

if ($method === 'POST') {
    $data = getJsonInput();
    $vehicleId = (int) ($data['vehicleId'] ?? 0);
    if (!$vehicleId) jsonError('Выберите ТС');
    $vehicle = $vehicleModel->get($vehicleId);
    if (!$vehicle) jsonError('ТС не найдено', 404);
    if (empty(trim($data['tenant'] ?? ''))) jsonError('Укажите арендатора');
    if (empty($data['startDate'])) jsonError('Укажите дату начала');
    $data['vehicleName'] = $vehicle['name'] ?? $vehicle['grnz'] ?? '';
    $data['vehicleGrnz'] = $vehicle['grnz'] ?? '';
    $newId = $rentModel->create($data);
    $rent = $rentModel->get($newId);
    $auditModel->add('add', 'rent', (string) $newId, ($vehicle['name'] ?? $vehicle['grnz']) . ' → ' . ($data['tenant'] ?? ''), 'Добавлена аренда', $vehicleId, $user['id'] ?? null, $user['name'] ?? null);
    jsonResponse(['id' => (string) $newId, 'rent' => $rent]);
    exit;
}

if ($method === 'DELETE' && $id) {
    $r = $rentModel->get($id);
    if (!$r) jsonError('Запись не найдена', 404);
    $name = ($r['vehicleName'] ?? '') . ' → ' . ($r['tenant'] ?? '');
    $rentModel->delete($id);
    $auditModel->add('delete', 'rent', (string) $id, $name, 'Удалил аренду', isset($r['vehicleId']) ? (int) $r['vehicleId'] : null, $user['id'] ?? null, $user['name'] ?? null);
    jsonResponse(['ok' => true]);
    exit;
}

jsonError('Метод не поддерживается', 405);
