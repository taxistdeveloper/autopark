<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="Автопарк — учёт ТС и дедлайнов">Автопарк — учёт ТС и дедлайнов</title>
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="autopark.css">
</head>

<body>
    <div id="apToastHost" class="ap-toast-host" aria-live="polite" aria-atomic="true"></div>

    <!-- Экран входа -->
    <div id="loginScreen" class="login-screen">
        <div class="login-card card">
            <div class="card-body p-4 p-md-5">
                <?php require __DIR__ . '/partials/lang_switch.php'; ?>
                <div class="brand-mark mb-2">
                    <span class="brand-mark__icon" aria-hidden="true"><i class="bi bi-truck"></i></span>
                    <span class="brand-mark__text" data-i18n="Автопарк">Автопарк</span>
                </div>
                <p class="text-muted text-center small mb-4" data-i18n="Вход для администратора / менеджера">Вход для администратора / менеджера</p>
                <form id="loginForm">
                    <div class="mb-3">
                        <label class="form-label" data-i18n="Логин">Логин</label>
                        <input type="text" class="form-control" id="loginUsername" required autocomplete="username"
                            data-i18n-placeholder="Логин" placeholder="Логин">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" data-i18n="Пароль">Пароль</label>
                        <input type="password" class="form-control" id="loginPassword" required
                            autocomplete="current-password" data-i18n-placeholder="Пароль" placeholder="Пароль">
                    </div>
                    <div id="loginError" class="alert alert-danger py-2 mb-3 d-none"></div>
                    <button type="submit" class="btn btn-primary w-100" data-i18n="Войти">Войти</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Основное приложение (после входа) -->
    <div id="appScreen" class="d-none">
        <nav class="navbar app-navbar">
            <div class="container-fluid gap-2">
                <span class="navbar-brand brand-mark mb-0">
                    <span class="brand-mark__icon" aria-hidden="true"><i class="bi bi-truck"></i></span>
                    <span class="brand-mark__text" data-i18n="Автопарк">Автопарк</span>
                </span>
                <div class="d-flex align-items-center gap-2 ms-auto flex-wrap justify-content-end">
                    <?php require __DIR__ . '/partials/lang_switch.php'; ?>
                    <span class="nav-meta"><i class="bi bi-person"></i> <span id="currentUser"></span></span>
                    <a href="admin.php" class="btn btn-nav btn-sm d-none" id="adminPanelLink"><i class="bi bi-gear-wide-connected"></i> <span data-i18n="Админ-панель">Админ-панель</span></a>
                    <button type="button" class="btn btn-nav btn-sm" id="logoutBtn" data-i18n="Выйти">Выйти</button>
                </div>
            </div>
        </nav>

        <div class="app-shell">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h1 class="page-title" data-i18n="Транспортные средства">Транспортные средства</h1>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary" id="downloadTemplateBtn">
                        <i class="bi bi-download"></i> <span data-i18n="Скачать шаблон Excel">Скачать шаблон Excel</span>
                    </button>
                    <button type="button" class="btn btn-success"
                        onclick="document.getElementById('importXlsInput').click()">
                        <i class="bi bi-file-earmark-excel"></i> <span data-i18n="Импорт из Excel">Импорт из Excel</span>
                    </button>
                    <input type="file" id="importXlsInput" accept=".xls,.xlsx" class="d-none">
                    <button type="button" class="btn btn-primary" id="addVehicleBtn">
                        <i class="bi bi-plus-lg"></i> <span data-i18n="Добавить ТС">Добавить ТС</span>
                    </button>
                    <button type="button" class="btn btn-info" id="reportsBtn">
                        <i class="bi bi-file-earmark-text"></i> <span data-i18n="Отчёты">Отчёты</span>
                    </button>
                </div>
            </div>

            <ul class="nav nav-tabs mb-3" id="mainTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehiclesTab"
                        type="button" role="tab" aria-controls="vehiclesTab" aria-selected="true">
                        <span data-i18n="Транспорт">Транспорт</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rent-tab" data-bs-toggle="tab" data-bs-target="#rentTab" type="button"
                        role="tab" aria-controls="rentTab" aria-selected="false">
                        <span data-i18n="Аренда">Аренда</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="spares-tab" data-bs-toggle="tab" data-bs-target="#sparesTab" type="button"
                        role="tab" aria-controls="sparesTab" aria-selected="false">
                        <span data-i18n="Запчасти">Запчасти</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tender-tab" data-bs-toggle="tab" data-bs-target="#tenderTab" type="button"
                        role="tab" aria-controls="tenderTab" aria-selected="false">
                        <i class="bi bi-clipboard2-check"></i> <span data-i18n="Тендер">Тендер</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="vehiclesTab" role="tabpanel"
                    aria-labelledby="vehicles-tab">

                    <!-- Сводка по дедлайнам -->
                    <div class="row mb-4 g-3">
                        <div class="col-md-4">
                            <div class="deadline-card deadline-card--warning">
                                <div class="deadline-card__icon" aria-hidden="true"><i class="bi bi-shield-check"></i></div>
                                <div class="deadline-card__body">
                                    <span class="deadline-card__label" data-i18n="Страховка">Страховка</span>
                                    <span class="deadline-card__desc" data-i18n="истекает в течение 30 дней">истекает в течение 30 дней</span>
                                </div>
                                <div id="deadlineInsuranceCount" class="deadline-card__value">0</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="deadline-card deadline-card--info">
                                <div class="deadline-card__icon" aria-hidden="true"><i class="bi bi-tools"></i></div>
                                <div class="deadline-card__body">
                                    <span class="deadline-card__label" data-i18n="Тех. осмотр">Тех. осмотр</span>
                                    <span class="deadline-card__desc" data-i18n="в течение 30 дней">в течение 30 дней</span>
                                </div>
                                <div id="deadlineTechCount" class="deadline-card__value">0</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="deadline-card deadline-card--danger">
                                <div class="deadline-card__icon" aria-hidden="true"><i class="bi bi-receipt"></i></div>
                                <div class="deadline-card__body">
                                    <span class="deadline-card__label" data-i18n="Налог">Налог</span>
                                    <span class="deadline-card__desc" data-i18n="в течение 30 дней">в течение 30 дней</span>
                                </div>
                                <div id="deadlineTaxCount" class="deadline-card__value">0</div>
                            </div>
                        </div>
                    </div>

                    <div class="card ap-card">
                        <div class="card-body p-0">
                            <div class="table-search-wrap p-3 border-bottom">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="search" class="form-control" id="vehicleSearchInput"
                                        data-i18n-placeholder="Поиск: наименование, владелец, ГРНЗ, локация…" placeholder="Поиск: наименование, владелец, ГРНЗ, локация…">
                                    <button class="btn" type="button" id="vehicleSearchClearBtn" data-i18n="Очистить">Очистить</button>
                                </div>
                            </div>
                            <div class="table-responsive vehicles-table-wrap table-scroll">
                                <table class="table table-hover mb-0" id="vehiclesTable">
                                    <thead>
                                        <tr>
                                            <th data-i18n="Наименование">Наименование</th>
                                            <th data-i18n="Вид техники">Вид техники</th>
                                            <th data-i18n="Применение">Применение</th>
                                            <th data-i18n="Собственник">Собственник</th>
                                            <th data-i18n="ГРНЗ">ГРНЗ</th>
                                            <th data-i18n="Норма расхода">Норма расхода</th>
                                            <th data-i18n="Диз. топливо">Диз. топливо</th>
                                            <th data-i18n="Год">Год</th>
                                            <th class="mh-head" data-i18n="Моточас">Моточас</th>
                                            <th class="mh-head" data-i18n="ТО 1">ТО 1</th>
                                            <th class="mh-head" data-i18n="ТО 2">ТО 2</th>
                                            <th class="mh-head" data-i18n="ТО 3">ТО 3</th>
                                            <th class="mh-head" data-i18n="Текущий">Текущий</th>
                                            <th class="mh-head" data-i18n="До ТО">До ТО</th>
                                            <th data-i18n="Страховка">Страховка</th>
                                            <th data-i18n="Тех. осмотр">Тех. осмотр</th>
                                            <th data-i18n="Налог">Налог</th>
                                            <th data-i18n="Локация">Локация</th>
                                            <th class="actions-col" data-i18n="Действия">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody id="vehiclesTableBody">
                                    </tbody>
                                </table>
                            </div>
                            <div id="vehiclesCards" class="vehicles-cards" data-i18n-aria="Список ТС" aria-label="Список ТС"></div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="rentTab" role="tabpanel" aria-labelledby="rent-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h2 class="page-title fs-5" data-i18n="Аренда">Аренда</h2>
                        <button type="button" class="btn btn-primary" id="addRentBtn">
                            <i class="bi bi-plus-lg"></i> <span data-i18n="Добавить аренду">Добавить аренду</span>
                        </button>
                    </div>
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-search-wrap p-3 border-bottom">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="search" class="form-control" id="rentSearchInput"
                                        data-i18n-placeholder="Поиск по аренде: ТС, арендатор, статус..." placeholder="Поиск по аренде: ТС, арендатор, статус...">
                                    <button class="btn btn-outline-secondary" type="button" id="rentSearchClearBtn" data-i18n="Очистить">Очистить</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="rentTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th data-i18n="ТС">ТС</th>
                                            <th data-i18n="Арендатор">Арендатор</th>
                                            <th data-i18n="Начало">Начало</th>
                                            <th data-i18n="Окончание">Окончание</th>
                                            <th data-i18n="Стоимость">Стоимость</th>
                                            <th data-i18n="Приход">Приход</th>
                                            <th data-i18n="Прибыль">Прибыль</th>
                                            <th data-i18n="Диз топливу">Диз топливу</th>
                                            <th data-i18n="Статус">Статус</th>
                                            <th class="actions-col" data-i18n="Действия">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rentTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="sparesTab" role="tabpanel" aria-labelledby="spares-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h2 class="page-title fs-5" data-i18n="Запчасти">Запчасти</h2>
                        <button type="button" class="btn btn-primary" id="addSpareBtn">
                            <i class="bi bi-plus-lg"></i> <span data-i18n="Добавить запчасть">Добавить запчасть</span>
                        </button>
                    </div>
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-search-wrap p-3 border-bottom">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="search" class="form-control" id="spareSearchInput"
                                        data-i18n-placeholder="Поиск: ТС, кому, дата..." placeholder="Поиск: ТС, кому, дата...">
                                    <button class="btn btn-outline-secondary" type="button" id="spareSearchClearBtn" data-i18n="Очистить">Очистить</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="spareTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th data-i18n="Наименование (ТС) и кому">Наименование (ТС) и кому</th>
                                            <th data-i18n="Название запчасти">Название запчасти</th>
                                            <th data-i18n="Количество">Количество</th>
                                            <th data-i18n="Сумма">Сумма</th>
                                            <th data-i18n="Дата">Дата</th>
                                            <th class="actions-col" data-i18n="Действия">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody id="spareTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tenderTab" role="tabpanel" aria-labelledby="tender-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h2 class="page-title fs-5"><i class="bi bi-clipboard2-check text-warning"></i> <span data-i18n="Смета тендер — сверка с парком">Смета тендер — сверка с парком</span></h2>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="tenderOnlyEquipment" checked>
                                <label class="form-check-label small" for="tenderOnlyEquipment" data-i18n="Только техника">Только техника</label>
                            </div>
                            <label class="btn btn-warning text-dark mb-0" id="tenderUploadBtn"
                                data-i18n-title="Выберите файл Excel со сметой тендера" title="Выберите файл Excel со сметой тендера">
                                <i class="bi bi-upload"></i> <span data-i18n="Загрузить смету (Excel)">Загрузить смету (Excel)</span>
                                <input type="file" id="tenderXlsInput" class="visually-hidden" accept=".xls,.xlsx,.csv" tabindex="-1" data-i18n-aria="Файл сметы Excel" aria-label="Файл сметы Excel">
                            </label>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="tenderClearDecisionsBtn" data-i18n-title="Очистить привязки строк к записям запчастей и аренды (сами записи в базе не удаляются)" title="Очистить привязки строк к записям запчастей и аренды (сами записи в базе не удаляются)"><span data-i18n="Сбросить привязки">Сбросить привязки</span></button>
                        </div>
                    </div>
                    
                    <div id="tenderSummary" class="alert alert-light border small mb-3 d-none"></div>
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive tender-smeta-wrap table-scroll">
                                <table class="table table-sm table-bordered table-hover mb-0 tender-smeta-table" id="tenderSmetaTable">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th scope="col" class="text-center text-nowrap" data-i18n-title="Номер по порядку" title="Номер по порядку" data-i18n="№ п/п">№ п/п</th>
                                            <th scope="col" class="text-nowrap" data-i18n-title="Шифр позиции норматива" title="Шифр позиции норматива" data-i18n="Шифр">Шифр</th>
                                            <th scope="col" class="tender-col-name" data-i18n-title="Наименование работ и затрат" title="Наименование работ и затрат" data-i18n="Наименование работ и затрат">Наименование работ и затрат</th>
                                            <th scope="col" class="text-nowrap" data-i18n="Вид техники">Вид техники</th>
                                            <th scope="col" class="text-nowrap" data-i18n-title="Единица измерения" title="Единица измерения" data-i18n="Ед. изм.">Ед. изм.</th>
                                            <th scope="col" class="text-end text-nowrap" data-i18n-title="Количество" title="Количество" data-i18n="Количество">Количество</th>
                                            <th scope="col" class="text-end text-nowrap" data-i18n-title="Стоимость единицы" title="Стоимость единицы" data-i18n="Стоимость ед.">Стоимость ед.</th>
                                            <th scope="col" class="text-end text-nowrap" data-i18n-title="Общая стоимость, тенге" title="Общая стоимость, тенге" data-i18n="Общая стоимость, ₸">Общая стоимость, ₸</th>
                                            <th scope="col" class="text-center tender-col-fleet" data-i18n="В парке">В парке</th>
                                            <th scope="col" class="tender-col-fleet" data-i18n="Совпало с ТС">Совпало с ТС</th>
                                            <th scope="col" class="text-nowrap tender-col-fleet" data-i18n="Купить / арендовать">Купить / арендовать</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tenderTableBody">
                                    </tbody>
                                </table>
                            </div>
                            <p id="tenderEmpty" class="text-muted small p-3 mb-0 border-top d-none" data-i18n="Нет строк для сравнения. Проверьте, что на листе есть колонка с наименованием (например «Наименование», «Техника», «Ресурс»).">Нет строк для сравнения. Проверьте, что на листе есть колонка с наименованием (например «Наименование», «Техника», «Ресурс»).</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Модальное окно: добавление / редактирование ТС -->
    <div class="modal fade" id="vehicleModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content ap-modal">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="vehicleModalTitle">
                            <i class="bi bi-truck text-primary me-1" id="vehicleModalTitleIcon"></i>
                            <span id="vehicleModalTitleText" data-i18n="Добавить транспортное средство">Добавить транспортное средство</span>
                        </h5>
                        <p class="modal-subtitle mb-0" id="vehicleModalHint" data-i18n="Основные данные, моточасы и сроки документов">Основные данные, моточасы и сроки документов</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-i18n-aria="Закрыть" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <form id="vehicleForm" class="ap-form">
                        <input type="hidden" id="vehicleId">

                        <section class="ap-form-section">
                            <h6 class="ap-form-section__title"><i class="bi bi-card-heading"></i> <span data-i18n="Основные данные">Основные данные</span></h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="vName"><span data-i18n="Наименование">Наименование</span> <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="vName" required data-i18n-placeholder="Например: Камаз 5511" placeholder="Например: Камаз 5511">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="vEquipmentType" data-i18n="Вид техники">Вид техники</label>
                                    <select class="form-select" id="vEquipmentType">
                                        <option value="" data-i18n="Не указан">Не указан</option>
                                        <option value="Экскаватор" data-i18n="Экскаватор">Экскаватор</option>
                                        <option value="Бульдозер" data-i18n="Бульдозер">Бульдозер</option>
                                        <option value="Погрузчик" data-i18n="Погрузчик">Погрузчик</option>
                                        <option value="Самосвал" data-i18n="Самосвал">Самосвал</option>
                                        <option value="Автокран" data-i18n="Автокран">Автокран</option>
                                        <option value="Кран" data-i18n="Кран">Кран</option>
                                        <option value="Трактор" data-i18n="Трактор">Трактор</option>
                                        <option value="Каток" data-i18n="Каток">Каток</option>
                                        <option value="Грейдер" data-i18n="Грейдер">Грейдер</option>
                                        <option value="Автомобиль" data-i18n="Автомобиль">Автомобиль</option>
                                        <option value="Грузовик" data-i18n="Грузовик">Грузовик</option>
                                        <option value="Автобус" data-i18n="Автобус">Автобус</option>
                                        <option value="Манипулятор" data-i18n="Манипулятор">Манипулятор</option>
                                        <option value="Прицеп" data-i18n="Прицеп">Прицеп</option>
                                        <option value="Полуприцеп" data-i18n="Полуприцеп">Полуприцеп</option>
                                        <option value="Компрессор" data-i18n="Компрессор">Компрессор</option>
                                        <option value="Генератор" data-i18n="Генератор">Генератор</option>
                                        <option value="Полуприцеп-трал" data-i18n="Полуприцеп-трал">Полуприцеп-трал</option>
                                        <option value="Бензовоз" data-i18n="Бензовоз">Бензовоз</option>
                                        <option value="Автобетоносмеситель" data-i18n="Автобетоносмеситель">Автобетоносмеситель</option>
                                        <option value="Седельный тягач" data-i18n="Седельный тягач">Седельный тягач</option>
                                        <option value="Бетоносмеситель" data-i18n="Бетоносмеситель">Бетоносмеситель</option>
                                        <option value="Дизельный двигатель" data-i18n="Дизельный двигатель">Дизельный двигатель</option>
                                        <option value="Автогрейдер" data-i18n="Автогрейдер">Автогрейдер</option>
                                        <option value="Асфальтоукладчик" data-i18n="Асфальтоукладчик">Асфальтоукладчик</option>
                                        <option value="Бетономешалка" data-i18n="Бетономешалка">Бетономешалка</option>
                                        <option value="ГАЗель" data-i18n="ГАЗель">ГАЗель</option>
                                        <option value="Автогудронатор (битумораспределитель)" data-i18n="Автогудронатор (битумораспределитель)">Автогудронатор (битумораспределитель)</option>
                                        <option value="Дорожная фреза" data-i18n="Дорожная фреза">Дорожная фреза</option>
                                        <option value="Зерноуборочный комбайн" data-i18n="Зерноуборочный комбайн">Зерноуборочный комбайн</option>
                                        <option value="Другое" data-i18n="Другое">Другое</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="vOwner"><span data-i18n="Собственник">Собственник</span> <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="vOwner" required data-i18n-placeholder="ФИО или организация" placeholder="ФИО или организация">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="vGrnz" data-i18n="ГРНЗ">ГРНЗ</label>
                                    <input type="text" class="form-control grnz-plate-input" id="vGrnz" data-i18n-placeholder="А 123 БВ 01" placeholder="А 123 БВ 01" autocomplete="off">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="vLocation" data-i18n="Находится">Находится</label>
                                    <input type="text" class="form-control" id="vLocation" data-i18n-placeholder="Например: Алмас/Жез, Жез" placeholder="Например: Алмас/Жез, Жез">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vYear" data-i18n="Год выпуска">Год выпуска</label>
                                    <input type="number" class="form-control" id="vYear" min="1980" max="2030" placeholder="2020">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vConsumption" data-i18n="Норма расхода">Норма расхода</label>
                                    <input type="text" class="form-control" id="vConsumption" data-i18n-placeholder="5–6 л/ч" placeholder="5–6 л/ч">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vDieselFuel" data-i18n="Диз. топливо">Диз. топливо</label>
                                    <input type="text" class="form-control" id="vDieselFuel" data-i18n-placeholder="Объём / тип" placeholder="Объём / тип">
                                </div>
                            </div>
                        </section>

                        <section class="ap-form-section ap-form-section--mh">
                            <h6 class="ap-form-section__title"><i class="bi bi-speedometer2"></i> <span data-i18n="Моточасы и ТО">Моточасы и ТО</span></h6>
                            <p class="ap-form-section__hint" data-i18n-html="mhHint">Сначала укажите <strong>текущий</strong> моточас. След. ТО — каждые 250 м/ч. Ежедневное добавление — кнопкой «Моточас» в таблице.</p>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="vMotorHours" data-i18n="Текущий">Текущий</label>
                                    <input type="number" class="form-control" id="vMotorHours" min="0" step="0.1" data-i18n-placeholder="Текущие м/ч" placeholder="Текущие м/ч">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vMotorHoursBase" data-i18n="Моточас (база)">Моточас (база)</label>
                                    <input type="number" class="form-control" id="vMotorHoursBase" min="0" step="0.1" data-i18n-placeholder="База" placeholder="База">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vAddMotorHours" data-i18n="Добавить сейчас">Добавить сейчас</label>
                                    <input type="number" class="form-control" id="vAddMotorHours" min="0" step="0.1" data-i18n-placeholder="Прибавить к текущему" placeholder="Прибавить к текущему">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vMotorHoursNext1" data-i18n="След. ТО 1">След. ТО 1</label>
                                    <input type="number" class="form-control" id="vMotorHoursNext1" min="0" step="0.1" data-i18n-placeholder="м/ч" placeholder="м/ч">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vMotorHoursNext2" data-i18n="След. ТО 2">След. ТО 2</label>
                                    <input type="number" class="form-control" id="vMotorHoursNext2" min="0" step="0.1" data-i18n-placeholder="м/ч" placeholder="м/ч">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vMotorHoursNext3" data-i18n="След. ТО 3">След. ТО 3</label>
                                    <input type="number" class="form-control" id="vMotorHoursNext3" min="0" step="0.1" data-i18n-placeholder="м/ч" placeholder="м/ч">
                                </div>
                            </div>
                        </section>

                        <section class="ap-form-section">
                            <h6 class="ap-form-section__title"><i class="bi bi-calendar2-check"></i> <span data-i18n="Сроки документов">Сроки документов</span></h6>
                            <div class="ap-deadline-blocks">
                                <div class="ap-deadline-block ap-deadline-block--insurance">
                                    <div class="ap-deadline-block__label"><i class="bi bi-shield-check"></i> <span data-i18n="Страховка">Страховка</span></div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="vInsuranceDate" data-i18n="Оформление">Оформление</label>
                                            <input type="date" class="form-control" id="vInsuranceDate">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="vInsuranceDeadline" data-i18n="Дедлайн">Дедлайн</label>
                                            <input type="date" class="form-control" id="vInsuranceDeadline">
                                        </div>
                                    </div>
                                </div>
                                <div class="ap-deadline-block ap-deadline-block--tech">
                                    <div class="ap-deadline-block__label"><i class="bi bi-tools"></i> <span data-i18n="Тех. осмотр">Тех. осмотр</span></div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="vTechDate" data-i18n="Прохождение">Прохождение</label>
                                            <input type="date" class="form-control" id="vTechDate">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="vTechDeadline" data-i18n="Дедлайн">Дедлайн</label>
                                            <input type="date" class="form-control" id="vTechDeadline">
                                        </div>
                                    </div>
                                </div>
                                <div class="ap-deadline-block ap-deadline-block--tax">
                                    <div class="ap-deadline-block__label"><i class="bi bi-receipt"></i> <span data-i18n="Налог">Налог</span></div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="vTaxDate" data-i18n="Уплата">Уплата</label>
                                            <input type="date" class="form-control" id="vTaxDate">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="vTaxDeadline" data-i18n="Дедлайн">Дедлайн</label>
                                            <input type="date" class="form-control" id="vTaxDeadline">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-nav" data-bs-dismiss="modal" data-i18n="Отмена">Отмена</button>
                    <button type="button" class="btn btn-primary" id="saveVehicleBtn"><i class="bi bi-check-lg me-1"></i> <span data-i18n="Сохранить">Сохранить</span></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно: добавление аренды -->
    <div class="modal fade" id="rentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rentModalTitle"><i class="bi bi-calendar-check text-primary"></i> <span id="rentModalTitleText" data-i18n="Добавить аренду">Добавить аренду</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3"><i class="bi bi-info-circle"></i> <span data-i18n="Арендовать можно только ТС из парка. Выберите транспортное средство из списка.">Арендовать можно только ТС из парка. Выберите транспортное средство из списка.</span></p>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold"><span data-i18n="Транспортное средство из парка">Транспортное средство из парка</span> <span class="text-danger">*</span></label>
                            <select id="rentVehicleSelect" class="form-select" required>
                                <option value="" data-i18n="Выберите ТС из парка...">Выберите ТС из парка...</option>
                            </select>
                            <p id="rentVehicleHint" class="text-muted small mb-0 mt-2 d-none"></p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" data-i18n="Первоначальная стоимость">Первоначальная стоимость</label>
                            <div class="input-group">
                                <input type="text" class="form-control rent-cost-input" id="rentInitialCost" data-i18n-placeholder="Например: 2 500 000" placeholder="Например: 2 500 000">
                                <span class="input-group-text">₸</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-i18n="Иные расходы">Иные расходы</label>
                            <div class="input-group">
                                <input type="text" class="form-control rent-cost-input" id="rentOtherCost" data-i18n-placeholder="Например: 100 000" placeholder="Например: 100 000">
                                <span class="input-group-text">₸</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-i18n="ТО">ТО</label>
                            <div class="input-group">
                                <input type="text" class="form-control rent-cost-input" id="rentToCost" data-i18n-placeholder="Например: 100 000" placeholder="Например: 100 000">
                                <span class="input-group-text">₸</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-i18n="Зарплата">Зарплата</label>
                            <div class="input-group">
                                <input type="text" class="form-control rent-cost-input" id="rentSalaryCost" data-i18n-placeholder="Например: 500 000" placeholder="Например: 500 000">
                                <span class="input-group-text">₸</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-i18n="Диз топливу">Диз топливу</label>
                            <div class="input-group">
                                <input type="text" class="form-control rent-cost-input" id="rentDieselCost" data-i18n-placeholder="Например: 50 000" placeholder="Например: 50 000">
                                <span class="input-group-text">₸</span>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label class="form-label" data-i18n="Арендатор">Арендатор</label>
                            <input type="text" class="form-control" id="rentTenant" data-i18n-placeholder="ФИО или организация" placeholder="ФИО или организация">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-i18n="Дата начала">Дата начала</label>
                            <input type="date" class="form-control" id="rentStartDate">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-i18n="Дата окончания">Дата окончания</label>
                            <input type="date" class="form-control" id="rentEndDate">
                        </div>
                    </div>
                    <hr>
                    <div class="row g-3 mb-2">
                        <div class="col-md-4">
                            <label class="form-label" data-i18n="Приход (доход от аренды)">Приход (доход от аренды)</label>
                            <div class="input-group">
                                <input type="text" class="form-control rent-summary-input" id="rentIncome" data-i18n-placeholder="Сумма полученная от арендатора" placeholder="Сумма полученная от арендатора">
                                <span class="input-group-text">₸</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted" data-i18n="Стоимость = первоначальная − расходы. Прибыль = приход − стоимость.">Стоимость = первоначальная − расходы. Прибыль = приход − стоимость.</div>
                        <div class="text-end d-flex flex-column align-items-end gap-1">
                            <div><span class="text-muted small" data-i18n="Стоимость">Стоимость</span><span id="rentTotal" class="fw-bold ms-2">—</span></div>
                            <div><span class="text-muted small" data-i18n="Приход">Приход</span><span id="rentIncomeDisplay" class="fw-bold ms-2">—</span></div>
                            <div><span class="text-muted small" data-i18n="Прибыль">Прибыль</span><span id="rentProfit" class="fw-bold ms-2 text-success">—</span></div>
                            <input type="hidden" id="rentTotalRaw" value="">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="Отмена">Отмена</button>
                    <button type="button" class="btn btn-primary" id="rentSaveBtn" data-i18n="Сохранить аренду">Сохранить аренду</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно: добавление запчасти -->
    <div class="modal fade" id="spareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="spareModalTitle"><i class="bi bi-gear"></i> <span id="spareModalTitleText" data-i18n="Добавить запчасть">Добавить запчасть</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3" data-i18n="Наименование и кому запчасть — из списка ТС.">Наименование и кому запчасть — из списка ТС.</p>
                    <input type="hidden" id="spareId">
                    <div class="mb-3">
                        <label class="form-label"><span data-i18n="ТС (наименование и кому)">ТС (наименование и кому)</span> <span class="text-danger">*</span></label>
                        <select id="spareVehicleSelect" class="form-select" required>
                            <option value="" data-i18n="Выберите ТС из списка...">Выберите ТС из списка...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" data-i18n="Название запчасти">Название запчасти</label>
                        <input type="text" class="form-control" id="spareName" data-i18n-placeholder="Например: Фильтр масляный" placeholder="Например: Фильтр масляный">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" data-i18n="Количество">Количество</label>
                            <input type="number" class="form-control" id="spareQuantity" min="0" step="1" value="1" data-i18n-placeholder="шт." placeholder="шт.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" data-i18n="Сумма (₸)">Сумма (₸)</label>
                            <input type="text" class="form-control spare-sum-input" id="spareAmount" data-i18n-placeholder="Например: 50 000" placeholder="Например: 50 000">
                        </div>
                        <div class="col-12">
                            <label class="form-label" data-i18n="Дата">Дата</label>
                            <input type="date" class="form-control" id="spareDate">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="Отмена">Отмена</button>
                    <button type="button" class="btn btn-primary" id="spareSaveBtn" data-i18n="Сохранить">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно: добавить моточасы -->
    <div class="modal fade" id="motorHoursModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-lg text-success"></i> <span data-i18n="Добавить моточасы">Добавить моточасы</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="mhVehicleId">
                    <p class="mb-2"><strong id="mhVehicleName"></strong></p>
                    <p class="text-muted small mb-2"><span data-i18n="Текущие моточасы:">Текущие моточасы:</span> <strong id="mhCurrentValue">0</strong></p>
                    <p class="text-muted small mb-3"><span data-i18n="До след. ТО:">До след. ТО:</span> <strong id="mhUntilTO">—</strong> <span data-i18n="м/ч">м/ч</span></p>
                    <div class="input-group mb-3">
                        <input type="number" class="form-control" id="mhAddAmount" min="0" step="0.1" value="1" data-i18n-placeholder="Сколько прибавить" placeholder="Сколько прибавить">
                        <button type="button" class="btn btn-success" id="mhAddBtn"><i class="bi bi-plus-lg"></i> <span data-i18n="Добавить">Добавить</span></button>
                    </div>
                    <div class="border rounded p-2 bg-light">
                        <small class="text-muted fw-bold" data-i18n="История добавлений">История добавлений</small>
                        <div id="mhHistoryList" class="small mt-1 mh-history-scroll"><span class="text-muted">—</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно: Отчёты -->
    <div class="modal fade" id="reportsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-text text-info"></i> <span data-i18n="Отчёты">Отчёты</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="report-grid mb-3">
                        <button type="button" class="btn report-btn report-type-btn" data-report="tax"><i class="bi bi-receipt-cutoff"></i> <span data-i18n="Налог">Налог</span></button>
                        <button type="button" class="btn report-btn report-type-btn" data-report="insurance"><i class="bi bi-shield-check"></i> <span data-i18n="Страховка">Страховка</span></button>
                        <button type="button" class="btn report-btn report-type-btn" data-report="tech"><i class="bi bi-tools"></i> <span data-i18n="ТО">ТО</span></button>
                        <button type="button" class="btn report-btn report-type-btn" data-report="motohours"><i class="bi bi-speedometer2"></i> <span data-i18n="Моточасы">Моточасы</span></button>
                        <button type="button" class="btn report-btn report-type-btn" data-report="summary"><i class="bi bi-list-ul"></i> <span data-i18n="Сводка ТС">Сводка ТС</span></button>
                        <button type="button" class="btn report-btn report-type-btn" data-report="spares"><i class="bi bi-gear"></i> <span data-i18n="Запчасти">Запчасти</span></button>
                        <button type="button" class="btn report-btn report-type-btn" data-report="rent"><i class="bi bi-calendar-check"></i> <span data-i18n="Аренда">Аренда</span></button>
                    </div>
                    <div id="reportTitle" class="fw-bold mb-2 text-muted"></div>
                    <div class="table-responsive report-table-scroll" id="reportTableWrap">
                        <table class="table table-sm table-hover mb-0" id="reportTable">
                            <thead class="table-light sticky-top" id="reportTableHead"></thead>
                            <tbody id="reportTableBody"></tbody>
                        </table>
                    </div>
                    <p id="reportEmpty" class="text-muted small mt-2 d-none" data-i18n="Нет данных для отчёта.">Нет данных для отчёта.</p>
                    <div class="mt-3">
                        <button type="button" class="btn btn-success btn-sm" id="reportDownloadExcel" disabled><i class="bi bi-file-earmark-excel"></i> <span data-i18n="Скачать Excel">Скачать Excel</span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/partials/whats_new.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/xlsx.full.min.js"></script>
    <script src="i18n.js"></script>
    <script src="autopark.js"></script>
</body>

</html>
