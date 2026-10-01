<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="Админ-панель — Автопарк">Админ-панель — Автопарк</title>
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="autopark.css">
</head>

<body id="adminPage">
    <div id="apToastHost" class="ap-toast-host" aria-live="polite" aria-atomic="true"></div>

    <!-- Вход только для администратора -->
    <div id="adminLoginScreen" class="login-screen">
        <div class="login-card card">
            <div class="card-body p-4 p-md-5">
                <?php require __DIR__ . '/partials/lang_switch.php'; ?>
                <div class="brand-mark mb-2">
                    <span class="brand-mark__icon" aria-hidden="true"><i class="bi bi-gear-wide-connected"></i></span>
                    <span class="brand-mark__text" data-i18n="Админ-панель">Админ-панель</span>
                </div>
                <p class="text-muted text-center small mb-4" data-i18n="Вход только для администратора">Вход только для администратора</p>
                <form id="adminLoginForm">
                    <div class="mb-3">
                        <label class="form-label" for="adminLoginUsername" data-i18n="Логин">Логин</label>
                        <input type="text" class="form-control" id="adminLoginUsername" required data-i18n-placeholder="Введите логин" placeholder="Введите логин" autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="adminLoginPassword" data-i18n="Пароль">Пароль</label>
                        <input type="password" class="form-control" id="adminLoginPassword" required data-i18n-placeholder="Введите пароль" placeholder="Введите пароль" autocomplete="current-password">
                    </div>
                    <div id="adminLoginError" class="alert alert-danger py-2 mb-3 d-none" role="alert"></div>
                    <button type="submit" class="btn btn-primary w-100" data-i18n="Войти в админ-панель">Войти в админ-панель</button>
                </form>
                <p class="text-center small mt-4 mb-0"><a href="index.php"><i class="bi bi-arrow-left me-1"></i> <span data-i18n="Вернуться в автопарк">Вернуться в автопарк</span></a></p>
            </div>
        </div>
    </div>

    <!-- Админ-панель (показывается после входа) -->
    <div id="adminPanelScreen" class="d-none">
        <nav class="navbar admin-navbar">
            <div class="container-fluid gap-2">
                <a class="navbar-brand brand-mark mb-0" href="index.php">
                    <span class="brand-mark__icon" aria-hidden="true"><i class="bi bi-truck"></i></span>
                    <span class="brand-mark__text" data-i18n="Автопарк">Автопарк</span>
                </a>
                <div class="d-flex align-items-center gap-2 ms-auto flex-wrap justify-content-end">
                    <?php require __DIR__ . '/partials/lang_switch.php'; ?>
                    <span class="d-none d-md-inline nav-meta"><i class="bi bi-gear-wide-connected me-1"></i> <span data-i18n="Админ-панель">Админ-панель</span></span>
                    <span class="nav-meta"><i class="bi bi-person-circle me-1"></i> <span id="adminCurrentUser"></span></span>
                    <a href="index.php" class="btn btn-nav btn-sm" data-i18n="В автопарк">В автопарк</a>
                    <button type="button" class="btn btn-nav btn-sm" id="adminLogoutBtn" data-i18n="Выйти">Выйти</button>
                </div>
            </div>
        </nav>

        <div class="admin-container container-fluid">
            <h1 class="admin-page-title"><i class="bi bi-journal-check me-2"></i> <span data-i18n="Админ-панель">Админ-панель</span></h1>
            <p class="admin-page-desc" data-i18n="Журналы действий, бортовой журнал и отчёты по ТС">Журналы действий, бортовой журнал и отчёты по ТС</p>

            <div class="admin-card card">
                <div class="card-header">
                    <ul class="nav nav-pills admin-tabs" id="adminSubTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="journal-general-tab" data-bs-toggle="pill" data-bs-target="#journalGeneral" type="button" role="tab" aria-selected="true">
                                <i class="bi bi-journal-text me-1"></i> <span data-i18n="Общий журнал">Общий журнал</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="journal-board-tab" data-bs-toggle="pill" data-bs-target="#journalBoard" type="button" role="tab" aria-selected="false">
                                <i class="bi bi-truck me-1"></i> <span data-i18n="Бортовой журнал">Бортовой журнал</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="admin-reports-tab" data-bs-toggle="pill" data-bs-target="#adminReports" type="button" role="tab" aria-selected="false">
                                <i class="bi bi-file-earmark-bar-graph me-1"></i> <span data-i18n="Отчёты">Отчёты</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="admin-users-tab" data-bs-toggle="pill" data-bs-target="#adminUsers" type="button" role="tab" aria-selected="false">
                                <i class="bi bi-people me-1"></i> <span data-i18n="Пользователи">Пользователи</span>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="journalGeneral" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <p class="admin-section-caption mb-0" data-i18n="Все действия пользователей в системе.">Все действия пользователей в системе.</p>
                                <button type="button" class="btn btn-outline-danger btn-sm admin-btn-clear" id="adminClearJournalBtn"><i class="bi bi-trash me-1"></i> <span data-i18n="Очистить журнал">Очистить журнал</span></button>
                            </div>
                            <div class="table-responsive admin-table-wrap admin-table-scroll">
                                <table class="table table-sm table-hover mb-0" id="adminGeneralJournalTable">
                                    <thead class="sticky-top">
                                        <tr>
                                            <th data-i18n="Дата и время">Дата и время</th>
                                            <th data-i18n="Действие">Действие</th>
                                            <th data-i18n="Кто">Кто</th>
                                            <th data-i18n="Объект">Объект</th>
                                            <th data-i18n="Детали">Детали</th>
                                        </tr>
                                    </thead>
                                    <tbody id="adminGeneralJournalBody"></tbody>
                                </table>
                            </div>
                            <p id="adminGeneralJournalEmpty" class="text-muted small mt-3 d-none" data-i18n="Записей пока нет.">Записей пока нет.</p>
                        </div>

                        <div class="tab-pane fade" id="journalBoard" role="tabpanel">
                            <p class="admin-section-caption" data-i18n="Журнал по выбранному ТС: моточасы и связанные действия.">Журнал по выбранному ТС: моточасы и связанные действия.</p>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-medium" for="adminBoardVehicleSelect" data-i18n="Транспортное средство">Транспортное средство</label>
                                <select class="form-select admin-select" id="adminBoardVehicleSelect">
                                    <option value="" data-i18n="Выберите ТС...">Выберите ТС...</option>
                                </select>
                            </div>
                            <div class="table-responsive admin-table-wrap admin-table-scroll">
                                <table class="table table-sm table-hover mb-0" id="adminBoardJournalTable">
                                    <thead class="sticky-top">
                                        <tr>
                                            <th data-i18n="Дата и время">Дата и время</th>
                                            <th data-i18n="Действие">Действие</th>
                                            <th data-i18n="Кто">Кто</th>
                                            <th data-i18n="Детали">Детали</th>
                                        </tr>
                                    </thead>
                                    <tbody id="adminBoardJournalBody"></tbody>
                                </table>
                            </div>
                            <p id="adminBoardJournalEmpty" class="text-muted small mt-3 d-none" data-i18n="Выберите ТС или записей нет.">Выберите ТС или записей нет.</p>
                        </div>

                        <div class="tab-pane fade" id="adminReports" role="tabpanel">
                            <p class="admin-section-caption" data-i18n="Отчёты по налогу, страховке, ТО, моточасам, запчастям, аренде.">Отчёты по налогу, страховке, ТО, моточасам, запчастям, аренде.</p>
                            <div class="report-grid admin-report-grid mb-4">
                                <button type="button" class="btn report-btn admin-report-btn" data-report="tax"><i class="bi bi-receipt-cutoff"></i> <span data-i18n="Налог">Налог</span></button>
                                <button type="button" class="btn report-btn admin-report-btn" data-report="insurance"><i class="bi bi-shield-check"></i> <span data-i18n="Страховка">Страховка</span></button>
                                <button type="button" class="btn report-btn admin-report-btn" data-report="tech"><i class="bi bi-tools"></i> <span data-i18n="ТО">ТО</span></button>
                                <button type="button" class="btn report-btn admin-report-btn" data-report="motohours"><i class="bi bi-speedometer2"></i> <span data-i18n="Моточасы">Моточасы</span></button>
                                <button type="button" class="btn report-btn admin-report-btn" data-report="summary"><i class="bi bi-list-ul"></i> <span data-i18n="Сводка ТС">Сводка ТС</span></button>
                                <button type="button" class="btn report-btn admin-report-btn" data-report="spares"><i class="bi bi-gear"></i> <span data-i18n="Запчасти">Запчасти</span></button>
                                <button type="button" class="btn report-btn admin-report-btn" data-report="rent"><i class="bi bi-calendar-check"></i> <span data-i18n="Аренда">Аренда</span></button>
                            </div>
                            <div id="adminReportTitle" class="fw-bold mb-2 text-muted small"></div>
                            <div class="table-responsive admin-table-wrap admin-report-scroll d-none" id="adminReportTableWrap">
                                <table class="table table-sm table-hover mb-0" id="adminReportTable">
                                    <thead class="sticky-top" id="adminReportTableHead"></thead>
                                    <tbody id="adminReportTableBody"></tbody>
                                </table>
                            </div>
                            <p id="adminReportEmpty" class="text-muted small mt-3 d-none" data-i18n="Выберите тип отчёта.">Выберите тип отчёта.</p>
                        </div>

                        <div class="tab-pane fade" id="adminUsers" role="tabpanel">
                            <p class="admin-section-caption" data-i18n="Добавление менеджеров и просмотр пользователей системы.">Добавление менеджеров и просмотр пользователей системы.</p>
                            <div class="admin-card card mb-4">
                                <div class="card-body">
                                    <h6 class="card-title mb-3"><i class="bi bi-person-plus me-1"></i> <span data-i18n="Добавить менеджера">Добавить менеджера</span></h6>
                                    <form id="adminAddUserForm" class="row g-3 align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label small" for="adminNewUserLogin" data-i18n="Логин">Логин</label>
                                            <input type="text" class="form-control form-control-sm" id="adminNewUserLogin" required data-i18n-placeholder="Логин" placeholder="Логин" autocomplete="off">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small" for="adminNewUserPassword" data-i18n="Пароль">Пароль</label>
                                            <input type="password" class="form-control form-control-sm" id="adminNewUserPassword" required data-i18n-placeholder="Пароль" placeholder="Пароль" autocomplete="new-password">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small" for="adminNewUserName" data-i18n="Имя">Имя</label>
                                            <input type="text" class="form-control form-control-sm" id="adminNewUserName" data-i18n-placeholder="Имя (необязательно)" placeholder="Имя (необязательно)">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg me-1"></i> <span data-i18n="Добавить">Добавить</span></button>
                                        </div>
                                    </form>
                                    <div id="adminAddUserError" class="alert alert-danger py-2 mt-3 mb-0 d-none" role="alert"></div>
                                    <div id="adminAddUserSuccess" class="alert alert-success py-2 mt-3 mb-0 d-none" role="alert"></div>
                                </div>
                            </div>
                            <div class="table-responsive admin-table-wrap admin-users-scroll">
                                <table class="table table-sm table-hover mb-0" id="adminUsersTable">
                                    <thead class="sticky-top">
                                        <tr>
                                            <th data-i18n="Логин">Логин</th>
                                            <th data-i18n="Имя">Имя</th>
                                            <th data-i18n="Роль">Роль</th>
                                            <th data-i18n="Создан">Создан</th>
                                        </tr>
                                    </thead>
                                    <tbody id="adminUsersBody"></tbody>
                                </table>
                            </div>
                            <p id="adminUsersEmpty" class="text-muted small mt-3 d-none" data-i18n="Пользователей пока нет.">Пользователей пока нет.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/partials/whats_new.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="i18n.js"></script>
    <script src="autopark.js"></script>
</body>

</html>
