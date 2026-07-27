<?php
/**
 * Синхронизация changelog с git HEAD и подготовка данных для модалки «Что нового».
 */
class WhatsNew
{
    private const STORAGE_REL = 'storage/changelog.json';
    private const FALLBACK_REL = 'changelog.json';
    private const MAX_ENTRIES = 30;

    /** @var string[] */
    private static $secretPatterns = [
        '/\.env\b/i',
        '/\bpassword\b/i',
        '/\bpasswd\b/i',
        '/\bпарол/iu',
        '/\bsecret\b/i',
        '/\btoken\b/i',
        '/\bcredentials?\b/i',
        '/\bapi[_-]?key\b/i',
        '/\bprivate[_-]?key\b/i',
        '/\bdatabase\.php\b/i',
        '/\bconfig\/database\b/i',
        '/\bDSN\b/',
        '/\bmysql:\/\/\S+/i',
        '/\bBearer\s+\S+/i',
    ];

    public static function rootDir(): string
    {
        return dirname(__DIR__);
    }

    public static function storagePath(): string
    {
        return self::rootDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::STORAGE_REL);
    }

    public static function fallbackPath(): string
    {
        return self::rootDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::FALLBACK_REL);
    }

    /**
     * Синхронизирует storage/changelog.json с текущим HEAD (если доступен git).
     * При смене HEAD собирает коммиты previous..HEAD (--no-merges).
     *
     * @return array{version: string, entries: list<array{sha?: string, message: string}>, updated_at?: string, source?: string}
     */
    public static function sync(): array
    {
        $head = self::gitShortHead();
        $stored = self::readJson(self::storagePath());

        if ($head !== '' && is_array($stored) && ($stored['version'] ?? '') === $head) {
            return self::normalizePayload($stored, 'storage');
        }

        $entries = [];
        $source = 'git';

        if ($head !== '') {
            $prev = is_array($stored) ? (string) ($stored['version'] ?? '') : '';
            if ($prev !== '' && preg_match('/^[0-9a-f]{4,40}$/i', $prev)) {
                $entries = self::gitLogRange($prev . '..' . $head);
            } else {
                // Первый синк: несколько последних коммитов, без «шума» от всей истории
                $entries = self::gitLogRange('HEAD', 15);
            }
        }

        if ($entries === []) {
            $fallback = self::readJson(self::fallbackPath());
            if (is_array($fallback)) {
                $payload = self::normalizePayload($fallback, 'fallback');
                if ($head !== '') {
                    $payload['version'] = $head;
                }
                if ($payload['version'] !== '') {
                    self::writeStorage($payload);
                }
                return $payload;
            }
            // Git недоступен и нет fallback — пустой ответ без версии
            if ($head === '') {
                return ['version' => '', 'entries' => [], 'source' => 'none'];
            }
            $entries = [['message' => 'Обновление установлено.']];
            $source = 'empty';
        }

        $payload = [
            'version' => $head,
            'entries' => self::sanitizeEntries($entries),
            'updated_at' => gmdate('c'),
            'source' => $source,
        ];
        self::writeStorage($payload);
        return $payload;
    }

    /**
     * Принудительная пересборка (для скрипта деплоя).
     *
     * @return array{version: string, entries: list<array{sha?: string, message: string}>, updated_at?: string, source?: string}
     */
    public static function rebuild(): array
    {
        $path = self::storagePath();
        if (is_file($path)) {
            @unlink($path);
        }
        return self::sync();
    }

    public static function gitShortHead(): string
    {
        $out = self::git(['rev-parse', '--short', 'HEAD']);
        if ($out === null) {
            return '';
        }
        $sha = trim($out);
        return preg_match('/^[0-9a-f]{4,40}$/i', $sha) ? strtolower($sha) : '';
    }

    /**
     * @return list<array{sha: string, message: string}>
     */
    private static function gitLogRange(string $rangeOrRev, int $limit = 0): array
    {
        // Разделитель без %VAR% и без | — безопаснее для Windows
        $args = ['log', '--no-merges', '--pretty=format:%h<<<%s'];
        if ($limit > 0) {
            $args[] = '-n';
            $args[] = (string) $limit;
        }
        $args[] = $rangeOrRev;

        $out = self::git($args);
        if ($out === null || trim($out) === '') {
            return [];
        }

        $entries = [];
        foreach (preg_split("/\r\n|\n|\r/", trim($out)) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode('<<<', $line, 2);
            $sha = isset($parts[0]) ? trim($parts[0]) : '';
            $message = isset($parts[1]) ? trim($parts[1]) : '';
            if ($message === '' || !preg_match('/^[0-9a-f]{4,40}$/i', $sha)) {
                continue;
            }
            $entries[] = [
                'sha' => $sha,
                'message' => $message,
            ];
            if (count($entries) >= self::MAX_ENTRIES) {
                break;
            }
        }
        return $entries;
    }

    /**
     * @param list<string> $args
     */
    private static function git(array $args): ?string
    {
        $git = self::findGitBinary();
        if ($git === null) {
            return null;
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // Массив аргументов — без cmd.exe, иначе на Windows %h/%s в --pretty ломаются
        $cmd = array_merge([$git], $args);
        $proc = @proc_open($cmd, $descriptors, $pipes, self::rootDir(), null, ['bypass_shell' => true]);
        if (!is_resource($proc)) {
            return null;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        if ($code !== 0 || !is_string($stdout)) {
            return null;
        }
        return $stdout;
    }

    private static function findGitBinary(): ?string
    {
        static $cached = false;
        static $path = null;
        if ($cached) {
            return $path;
        }
        $cached = true;

        $candidates = [];
        if (DIRECTORY_SEPARATOR === '\\') {
            $candidates[] = 'C:\\Program Files\\Git\\cmd\\git.exe';
            $candidates[] = 'C:\\Program Files\\Git\\bin\\git.exe';
            $candidates[] = 'C:\\Program Files (x86)\\Git\\cmd\\git.exe';
        }
        $candidates[] = 'git';

        foreach ($candidates as $bin) {
            if ($bin !== 'git' && !is_file($bin)) {
                continue;
            }
            $testCmd = ($bin === 'git') ? ['git', '--version'] : [$bin, '--version'];
            $test = @proc_open(
                $testCmd,
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                self::rootDir(),
                null,
                ['bypass_shell' => true]
            );
            if (is_resource($test)) {
                fclose($pipes[0]);
                stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                stream_get_contents($pipes[2]);
                fclose($pipes[2]);
                $code = proc_close($test);
                if ($code === 0) {
                    $path = $bin;
                    return $path;
                }
            }
        }
        $path = null;
        return null;
    }

    /**
     * @param list<array{sha?: string, message: string}> $entries
     * @return list<array{sha?: string, message: string}>
     */
    private static function sanitizeEntries(array $entries): array
    {
        $out = [];
        foreach ($entries as $entry) {
            $message = isset($entry['message']) ? (string) $entry['message'] : '';
            $message = self::redactSecrets($message);
            if ($message === '' || self::isSecretHeavy($message)) {
                continue;
            }
            $item = ['message' => $message];
            if (!empty($entry['sha']) && preg_match('/^[0-9a-f]{4,40}$/i', (string) $entry['sha'])) {
                $item['sha'] = strtolower((string) $entry['sha']);
            }
            $out[] = $item;
        }
        return $out;
    }

    private static function redactSecrets(string $text): string
    {
        $redacted = $text;
        foreach (self::$secretPatterns as $pattern) {
            $redacted = preg_replace($pattern, '[скрыто]', $redacted) ?? $redacted;
        }
        // Убрать явные значения вида key=... / key: ...
        $redacted = preg_replace(
            '/\b(password|passwd|secret|token|api[_-]?key)\s*[:=]\s*\S+/iu',
            '$1=[скрыто]',
            $redacted
        ) ?? $redacted;
        return trim(preg_replace('/\s{2,}/u', ' ', $redacted) ?? $redacted);
    }

    private static function isSecretHeavy(string $text): bool
    {
        // Если почти всё сообщение — [скрыто], не показываем
        $plain = trim(str_replace(['[скрыто]', '—', '-', '.'], '', $text));
        return $plain === '';
    }

    /**
     * @return array{version: string, entries: list<array{sha?: string, message: string}>, updated_at?: string, source?: string}
     */
    private static function normalizePayload(array $data, string $source): array
    {
        $entriesRaw = isset($data['entries']) && is_array($data['entries']) ? $data['entries'] : [];
        $entries = [];
        foreach ($entriesRaw as $row) {
            if (is_string($row)) {
                $msg = self::redactSecrets($row);
                if ($msg !== '' && !self::isSecretHeavy($msg)) {
                    $entries[] = ['message' => $msg];
                }
                continue;
            }
            if (!is_array($row)) {
                continue;
            }
            $msg = self::redactSecrets((string) ($row['message'] ?? $row['text'] ?? ''));
            if ($msg === '' || self::isSecretHeavy($msg)) {
                continue;
            }
            $item = ['message' => $msg];
            if (!empty($row['sha']) && preg_match('/^[0-9a-f]{4,40}$/i', (string) $row['sha'])) {
                $item['sha'] = strtolower((string) $row['sha']);
            }
            $entries[] = $item;
        }

        return [
            'version' => (string) ($data['version'] ?? ''),
            'entries' => array_slice($entries, 0, self::MAX_ENTRIES),
            'updated_at' => (string) ($data['updated_at'] ?? ''),
            'source' => (string) ($data['source'] ?? $source),
        ];
    }

    private static function readJson(string $path): ?array
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $dec = json_decode($raw, true);
        return is_array($dec) ? $dec : null;
    }

    private static function writeStorage(array $payload): void
    {
        $dir = dirname(self::storagePath());
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            return;
        }
        @file_put_contents(self::storagePath(), $json . "\n", LOCK_EX);
    }
}
