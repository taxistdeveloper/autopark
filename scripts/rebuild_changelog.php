<?php
/**
 * CLI: синхронизация storage/changelog.json с git HEAD.
 *   php scripts/rebuild_changelog.php          # sync (prev..HEAD при смене SHA)
 *   php scripts/rebuild_changelog.php --force  # полная пересборка
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Только CLI.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/helpers/WhatsNew.php';

$force = in_array('--force', $argv ?? [], true);
$payload = $force ? WhatsNew::rebuild() : WhatsNew::sync();
$version = $payload['version'] ?? '';
$count = isset($payload['entries']) ? count($payload['entries']) : 0;

if ($version === '') {
    fwrite(STDERR, "Не удалось определить git HEAD. Проверьте git и fallback changelog.json.\n");
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$mode = $force ? 'rebuild' : 'sync';
echo "Changelog ({$mode}): version={$version}, entries={$count}\n";
echo 'Файл: ' . WhatsNew::storagePath() . "\n";
exit(0);
