<?php
require __DIR__ . '/init.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$user = requireAuth();
if (empty($user['is_admin']) && ($user['login'] ?? '') !== 'admin') {
    http_response_code(403);
    jsonError('Доступ только для администратора', 403);
}

$userModel = new \App\Models\User($db);

if ($method === 'GET') {
    $list = $userModel->findAll();
    jsonResponse(['ok' => true, 'users' => $list]);
    exit;
}

if ($method === 'POST') {
    $input = getJsonInput();
    $action = $input['action'] ?? $_GET['action'] ?? 'create';
    if ($action === 'create') {
        $login = trim($input['login'] ?? $input['username'] ?? '');
        $password = $input['password'] ?? '';
        $name = trim($input['name'] ?? '');
        $is_admin = !empty($input['is_admin']);
        if ($login === '') {
            jsonError('Укажите логин', 400);
        }
        if ($password === '') {
            jsonError('Укажите пароль', 400);
        }
        try {
            $created = $userModel->create($login, $password, $name ?: $login, $is_admin);
            jsonResponse(['ok' => true, 'user' => $created]);
        } catch (\InvalidArgumentException $e) {
            jsonError($e->getMessage(), 400);
        } catch (\PDOException $e) {
            $code = $e->getCode();
            if ((int) $code === 23000 || $code === '23000') {
                jsonError('Пользователь с таким логином уже существует', 400);
            }
            jsonError('Ошибка базы данных: ' . $e->getMessage(), 500);
        }
        exit;
    }
}

jsonError('Метод не поддерживается', 405);
