<?php
require __DIR__ . '/database.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\Models\\';
    $base = dirname(__DIR__) . '/models/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $rel = str_replace('\\', '/', substr($class, $len));
    $file = $base . $rel . '.php';
    if (is_file($file)) require $file;
});
