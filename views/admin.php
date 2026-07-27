<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель — Автопарк</title>
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
                <div class="brand-mark mb-2">
                    <span class="brand-mark__icon" aria-hidden="true"><i class="bi bi-gear-wide-connected"></i></span>
                    <span class="brand-mark__text">Админ-панель</span>
                </div>
                <p class="text-muted text-center small mb-4">Вход только для администратора</p>
                <form id="adminLoginForm">
                    <div class="mb-3">
                        <label class="form-label" for="adminLoginUsername">Логин</label>
                        <input type="text" class="form-control" id="adminLoginUsername" required placeholder="Введите логин" autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="adminLoginPassword">Пароль</label>
                        <input type="password" class="form-control" id="adminLoginPassword" required placeholder="Введите пароль" autocomplete="current-password">
                    </div>
                    <div id="adminLoginError" class="alert alert-danger py-2 mb-3 d-none" role="alert"></div>
                    <button type="submit" class="btn btn-primary w-100">Войти в админ-панель</button>
                </form>
                <p class="text-center small mt-4 mb-0"><a href="index.php"><i class="bi bi-arrow-left me-1"></i> Вернуться в автопарк</a></p>
            </div>
        </div>
    </div>

    <!-- Админ-панель (показывается после входа) -->
    <div id="adminPanelScreen" class="d-none">
        <nav class="navbar admin-navbar">
            <div class="container-fluid gap-2">
                <a class="navbar-brand brand-mark mb-0" href="index.php">
                    <span class="brand-mark__icon" aria-hidden="true"><i class="bi bi-truck"></i></span>
                    <span class="brand-mark__text">Автопарк</span>
                </a>
                <div class="d-flex align-items-center gap-2 ms-auto flex-wrap justify-content-end">
                    <span class="d-none d-md-inline nav-meta"><i class="bi bi-gear-wide-connected me-1"></i> Админ-панель</span>
                    <span class="nav-meta"><i class="bi bi-person-circle me-1"></i> <span id="adminCurrentUser"></span></span>
                    <a href="index.php" class="btn btn-nav btn-sm">В автопарк</a>
                    <button type="button" class="btn btn-nav btn-sm" id="adminLogoutBtn">Выйти</button>
                </div>
            </div>
        </nav>

        <div class="admin-container container-fluid">
            <h1 class="admin-page-title"><i class="bi bi-journal-check me-2"></i> Админ-панель</h1>
            <p class="admin-page-desc">Журналы действий, бортовой журнал и отчёты по ТС</p>

            <div class="admin-card card">
                <div class="card-header">
                    <ul class="nav nav-pills admin-tabs" id="adminSubTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="journal-general-tab" data-bs-toggle="pill" data-bs-target="#journalGeneral" type="button" role="tab" aria-selected="true">
                                <i class="bi bi-journal-text me-1"></i> Общий журнал
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="journal-board-tab" data-bs-toggle="pill" data-bs-target="#journalBoard" type="button" role="tab" aria-selected="false">
                                <i class="bi bi-truck me-1"></i> Бортовой журнал
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="admin-reports-tab" data-bs-toggle="pill" data-bs-target="#adminReports" type="button" role="tab" aria-selected="false">
                                <i class="bi bi-file-earmark-bar-graph me-1"></i> Отчёты
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="admin-users-tab" data-bs-toggle="pill" data-bs-target="#adminUsers" type="button" role="tab" aria-selected="false">
                                <i class="bi bi-people me-1"></i> Пользователи
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="journalGeneral" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <p class="admin-section-caption mb-0">Все действия пользователей в системе.</p>
                                <button type="button" class="btn btn-outline-danger btn-sm admin-btn-clear" id="adminClearJournalBtn"><i class="bi bi-trash me-1"></i> Очистить журнал</button>
                            </div>
                            <div class="table-responsive admin-table-wrap admin-table-scroll">
                                <table class="table table-sm table-hover mb-0" id="adminGeneralJournalTable">
                                    <thead class="sticky-top">
                                        <tr>
                                            <th>Дата и время</th>
                                            <th>Действие</th>
                                            <th>Кто</th>
                                            <th>Объект</th>
                                            <th>Детали</th>
                                        </tr>
                                    </thead>
                                    <tbody id="adminGeneralJournalBody"></tbody>
                                </table>
                            </div>
                            <p id="adminGeneralJournalEmpty" class="text-muted small mt-3 d-none">Записей пока нет.</p>
                        </div>

                        <div class="tab-pane fade" id="journalBoard" role="tabpanel">
                            <p class="admin-section-caption">Журнал по выбранному ТС: моточасы и связанные действия.</p>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-medium" for="adminBoardVehicleSelect">Транспортное средство</label>
                                <select class="form-select admin-select" id="adminBoardVehicleSelect">
                                    <option value="">Выберите ТС...</option>
                                </select>
                            </div>
                            <div class="table-responsive admin-table-wrap admin-table-scroll">
                                <table class="table table-sm table-hover mb-0" id="adminBoardJournalTable">
                                    <thead class="sticky-top">
                                        <tr>
                                            <th>Дата и время</th>
                                            <th>Действие</th>
                                            <th>Кто</th>
                                            <th>Детали</th>
                                        </tr>
                                    </thead>
                                    <tbody id="adminBoardJournalBody"></tbody>
                                </table>
                            </div>
                            <p id="adminBoardJournalEmpty" class="text-muted small mt-3 d-none">Выберите ТС или записей нет.</p>
                        </div>

                        <div class="tab-pane fade" id="adminReports" role="tabpanel">
                            <p class="admin-section-caption">Отчёты по налогу, страховке, ТО, моточасам, запчастям, аренде.</p>
                            <div class="report-grid admin-report-grid mb-4">
                                <button type="button" class="btn report-btn admin-report-btn" data-report="tax"><i class="bi bi-receipt-cutoff"></i> Налог</button>
                                <button type="button" class="btn report-btn admin-report-btn" data-report="insurance"><i class="bi bi-shield-check"></i> Страховка</button>
                                <button type="button" class="btn report-btn admin-report-btn" data-report="tech"><i class="bi bi-tools"></i> ТО</button>
                                <button type="button" class="btn report-btn admin-report-btn" data-report="motohours"><i class="bi bi-speedometer2"></i> Моточасы</button>
                                <button type="button" class="btn report-btn admin-report-btn" data-report="summary"><i class="bi bi-list-ul"></i> Сводка ТС</button>
                                <button type="button" class="btn report-btn admin-report-btn" data-report="spares"><i class="bi bi-gear"></i> Запчасти</button>
                                <button type="button" class="btn report-btn admin-report-btn" data-report="rent"><i class="bi bi-calendar-check"></i> Аренда</button>
                            </div>
                            <div id="adminReportTitle" class="fw-bold mb-2 text-muted small"></div>
                            <div class="table-responsive admin-table-wrap admin-report-scroll d-none" id="adminReportTableWrap">
                                <table class="table table-sm table-hover mb-0" id="adminReportTable">
                                    <thead class="sticky-top" id="adminReportTableHead"></thead>
                                    <tbody id="adminReportTableBody"></tbody>
                                </table>
                            </div>
                            <p id="adminReportEmpty" class="text-muted small mt-3 d-none">Выберите тип отчёта.</p>
                        </div>

                        <div class="tab-pane fade" id="adminUsers" role="tabpanel">
                            <p class="admin-section-caption">Добавление менеджеров и просмотр пользователей системы.</p>
                            <div class="admin-card card mb-4">
                                <div class="card-body">
                                    <h6 class="card-title mb-3"><i class="bi bi-person-plus me-1"></i> Добавить менеджера</h6>
                                    <form id="adminAddUserForm" class="row g-3 align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label small" for="adminNewUserLogin">Логин</label>
                                            <input type="text" class="form-control form-control-sm" id="adminNewUserLogin" required placeholder="Логин" autocomplete="off">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small" for="adminNewUserPassword">Пароль</label>
                                            <input type="password" class="form-control form-control-sm" id="adminNewUserPassword" required placeholder="Пароль" autocomplete="new-password">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small" for="adminNewUserName">Имя</label>
                                            <input type="text" class="form-control form-control-sm" id="adminNewUserName" placeholder="Имя (необязательно)">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg me-1"></i> Добавить</button>
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
                                            <th>Логин</th>
                                            <th>Имя</th>
                                            <th>Роль</th>
                                            <th>Создан</th>
                                        </tr>
                                    </thead>
                                    <tbody id="adminUsersBody"></tbody>
                                </table>
                            </div>
                            <p id="adminUsersEmpty" class="text-muted small mt-3 d-none">Пользователей пока нет.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/partials/whats_new.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="autopark.js"></script>
</body>

</html>
