<?php
require __DIR__ . '/init.php';
requireAuth();

$vehicleModel = new \App\Models\Vehicle($db);
$mhModel = new \App\Models\MotorHoursHistory($db);
$auditModel = new \App\Models\AuditLog($db);
$user = $_SESSION['user'];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($method === 'GET') {
    if ($id) {
        $v = $vehicleModel->get($id);
        if (!$v) {
            jsonError('ТС не найдено', 404);
        }
        $v['motorHoursHistory'] = $mhModel->byVehicle($id);
        jsonResponse($v);
    } else {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $list = $vehicleModel->all($search);
        jsonResponse($list);
    }
    exit;
}

if ($method === 'POST') {
    $data = getJsonInput();
    if (empty($data['name']) || empty($data['owner'])) {
        jsonError('Заполните наименование и собственника');
    }
    $data['motorHoursNext1'] = $data['motorHoursNext1'] ?? ($data['motorHours'] + 250);
    $data['motorHoursNext2'] = $data['motorHoursNext2'] ?? ($data['motorHours'] + 500);
    $data['motorHoursNext3'] = $data['motorHoursNext3'] ?? ($data['motorHours'] + 750);
    $newId = $vehicleModel->create($data);
    $auditModel->add('add', 'vehicle', (string) $newId, $data['name'] ?? $data['grnz'] ?? (string) $newId, 'Добавлено ТС', null, $user['id'] ?? null, $user['name'] ?? null);
    jsonResponse(['id' => (string) $newId, 'vehicle' => $vehicleModel->get($newId)]);
    exit;
}

if ($method === 'PUT' && $id) {
    $data = getJsonInput();
    $existing = $vehicleModel->get($id);
    if (!$existing) {
        jsonError('ТС не найдено', 404);
    }
    $vehicleModel->update($id, $data);
    $auditModel->add('edit', 'vehicle', (string) $id, $existing['name'] ?? (string) $id, 'Редактирование ТС', null, $user['id'] ?? null, $user['name'] ?? null);
    jsonResponse(['ok' => true, 'vehicle' => $vehicleModel->get($id)]);
    exit;
}

if ($method === 'PATCH' && $id) {
    $data = getJsonInput();
    if (array_key_exists('application', $data)) {
        $vehicleModel->updateApplication($id, $data['application']);
        jsonResponse(['ok' => true]);
        exit;
    }
    jsonError('Нет данных для обновления');
}

if ($method === 'DELETE' && $id) {
    $existing = $vehicleModel->get($id);
    if (!$existing) {
        jsonError('ТС не найдено', 404);
    }
    $name = $existing['name'] ?? $existing['grnz'] ?? (string) $id;
    $vehicleModel->delete($id);
    $auditModel->add('delete', 'vehicle', (string) $id, $name, 'Удалил ТС: ' . $name . (isset($existing['grnz']) && $existing['grnz'] ? ' (' . $existing['grnz'] . ')' : ''), null, $user['id'] ?? null, $user['name'] ?? null);
    jsonResponse(['ok' => true]);
    exit;
}

jsonError('Метод не поддерживается', 405);
