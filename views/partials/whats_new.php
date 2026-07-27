<?php
/** @var array{version: string, entries: list<array{sha?: string, message: string}>, updated_at?: string, source?: string} $whatsNew */
if (!isset($whatsNew) || !is_array($whatsNew)) {
    $whatsNew = ['version' => '', 'entries' => []];
}
$wnVersion = (string) ($whatsNew['version'] ?? '');
$wnEntries = isset($whatsNew['entries']) && is_array($whatsNew['entries']) ? $whatsNew['entries'] : [];
?>
    <!-- Модальное окно: Что нового -->
    <div class="modal fade" id="whatsNewModal" tabindex="-1" aria-labelledby="whatsNewModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content ap-modal">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="whatsNewModalTitle">
                            <i class="bi bi-stars text-primary me-1"></i> Что нового
                        </h5>
                        <p class="modal-subtitle mb-0">
                            Обновления после деплоя
                            <?php if ($wnVersion !== ''): ?>
                                <span class="whats-new-version" title="Версия (git HEAD)">· <?= htmlspecialchars($wnVersion, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <?php if ($wnEntries === []): ?>
                        <p class="text-muted mb-0">Обновление установлено.</p>
                    <?php else: ?>
                        <ul class="whats-new-list list-unstyled mb-0" id="whatsNewList">
                            <?php foreach ($wnEntries as $entry):
                                $msg = htmlspecialchars((string) ($entry['message'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                $sha = isset($entry['sha']) ? (string) $entry['sha'] : '';
                                if ($msg === '') {
                                    continue;
                                }
                                ?>
                                <li class="whats-new-item">
                                    <?php if ($sha !== '' && preg_match('/^[0-9a-f]{4,40}$/i', $sha)): ?>
                                        <code class="whats-new-sha"><?= htmlspecialchars(strtolower($sha), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                                    <?php endif; ?>
                                    <span class="whats-new-msg"><?= $msg ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="whatsNewOkBtn" data-bs-dismiss="modal">
                        <i class="bi bi-check-lg me-1"></i> Понятно
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script type="application/json" id="whatsNewData"><?= json_encode(
        [
            'version' => $wnVersion,
            'entries' => array_values(array_map(static function ($e) {
                $item = ['message' => (string) ($e['message'] ?? '')];
                if (!empty($e['sha'])) {
                    $item['sha'] = (string) $e['sha'];
                }
                return $item;
            }, $wnEntries)),
        ],
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?></script>
