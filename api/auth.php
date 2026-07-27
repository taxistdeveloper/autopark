<?php
require __DIR__ . '/init.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? null;
if ($method === 'POST') {
    if ($action === null) {
        $input = getJsonInput();
        $action = $input['action'] ?? null;
    }
    if ($action === 'login') {
        $input = $input ?? getJsonInput();
        $login = trim($input['login'] ?? $input['username'] ?? '');
        $password = $input['password'] ?? '';
        if ($login === '' || $password === '') {
            jsonError('Укажите логин и пароль', 400);
        }
        $userModel = new \App\Models\User($db);
        $user = $userModel->verifyPassword($login, $password);
        if (!$user) {
            jsonError('Неверный логин или пароль', 401);
        }
        $_SESSION['user'] = ['id' => $user['id'], 'login' => $user['login'], 'name' => $user['name'] ?? $user['login'], 'is_admin' => (bool) ($user['is_admin'] ?? false)];
        jsonResponse(['ok' => true, 'user' => $_SESSION['user']]);
        exit;
    }
    if ($action === 'logout') {
        $_SESSION['user'] = null;
        session_destroy();
        jsonResponse(['ok' => true]);
        exit;
    }
}

if ($method === 'GET' && (isset($_GET['session']) || $action === null)) {
    if (!empty($_SESSION['user'])) {
        jsonResponse(['ok' => true, 'user' => $_SESSION['user']]);
    } else {
        http_response_code(401);
        jsonResponse(['ok' => false, 'user' => null]);
    }
    exit;
}

jsonError('Метод не поддерживается', 405);
