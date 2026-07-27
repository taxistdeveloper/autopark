<?php
/**
 * Параметры сессии: куки хранится 7 дней, не только до закрытия вкладки.
 * Подключать до session_start().
 */
session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 7, // 7 дней
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
