<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Автопарк — учёт ТС и дедлайнов</title>
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
                <div class="brand-mark mb-2">
                    <span class="brand-mark__icon" aria-hidden="true"><i class="bi bi-truck"></i></span>
                    <span class="brand-mark__text">Автопарк</span>
                </div>
                <p class="text-muted text-center small mb-4">Вход для администратора / менеджера</p>
                <form id="loginForm">
                    <div class="mb-3">
                        <label class="form-label">Логин</label>
                        <input type="text" class="form-control" id="loginUsername" required autocomplete="username"
                            placeholder="Логин">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль</label>
                        <input type="password" class="form-control" id="loginPassword" required
                            autocomplete="current-password" placeholder="Пароль">
                    </div>
                    <div id="loginError" class="alert alert-danger py-2 mb-3 d-none"></div>
                    <button type="submit" class="btn btn-primary w-100">Войти</button>
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
                    <span class="brand-mark__text">Автопарк</span>
                </span>
                <div class="d-flex align-items-center gap-2 ms-auto flex-wrap justify-content-end">
                    <span class="nav-meta"><i class="bi bi-person"></i> <span id="currentUser"></span></span>
                    <a href="admin.php" class="btn btn-nav btn-sm d-none" id="adminPanelLink"><i class="bi bi-gear-wide-connected"></i> Админ-панель</a>
                    <button type="button" class="btn btn-nav btn-sm" id="logoutBtn">Выйти</button>
                </div>
            </div>
        </nav>

        <div class="app-shell">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h1 class="page-title">Транспортные средства</h1>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary" id="downloadTemplateBtn">
                        <i class="bi bi-download"></i> Скачать шаблон Excel
                    </button>
                    <button type="button" class="btn btn-success"
                        onclick="document.getElementById('importXlsInput').click()">
                        <i class="bi bi-file-earmark-excel"></i> Импорт из Excel
                    </button>
                    <input type="file" id="importXlsInput" accept=".xls,.xlsx" class="d-none">
                    <button type="button" class="btn btn-primary" id="addVehicleBtn">
                        <i class="bi bi-plus-lg"></i> Добавить ТС
                    </button>
                    <button type="button" class="btn btn-info" id="reportsBtn">
                        <i class="bi bi-file-earmark-text"></i> Отчёты
                    </button>
                </div>
            </div>

            <ul class="nav nav-tabs mb-3" id="mainTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehiclesTab"
                        type="button" role="tab" aria-controls="vehiclesTab" aria-selected="true">
                        Транспорт
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rent-tab" data-bs-toggle="tab" data-bs-target="#rentTab" type="button"
                        role="tab" aria-controls="rentTab" aria-selected="false">
                        Аренда
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="spares-tab" data-bs-toggle="tab" data-bs-target="#sparesTab" type="button"
                        role="tab" aria-controls="sparesTab" aria-selected="false">
                        Запчасти
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tender-tab" data-bs-toggle="tab" data-bs-target="#tenderTab" type="button"
                        role="tab" aria-controls="tenderTab" aria-selected="false">
                        <i class="bi bi-clipboard2-check"></i> Тендер
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
                                    <span class="deadline-card__label">Страховка</span>
                                    <span class="deadline-card__desc">истекает в течение 30 дней</span>
                                </div>
                                <div id="deadlineInsuranceCount" class="deadline-card__value">0</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="deadline-card deadline-card--info">
                                <div class="deadline-card__icon" aria-hidden="true"><i class="bi bi-tools"></i></div>
                                <div class="deadline-card__body">
                                    <span class="deadline-card__label">Тех. осмотр</span>
                                    <span class="deadline-card__desc">в течение 30 дней</span>
                                </div>
                                <div id="deadlineTechCount" class="deadline-card__value">0</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="deadline-card deadline-card--danger">
                                <div class="deadline-card__icon" aria-hidden="true"><i class="bi bi-receipt"></i></div>
                                <div class="deadline-card__body">
                                    <span class="deadline-card__label">Налог</span>
                                    <span class="deadline-card__desc">в течение 30 дней</span>
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
                                        placeholder="Поиск: наименование, владелец, ГРНЗ, локация…">
                                    <button class="btn" type="button" id="vehicleSearchClearBtn">Очистить</button>
                                </div>
                            </div>
                            <div class="table-responsive vehicles-table-wrap table-scroll">
                                <table class="table table-hover mb-0" id="vehiclesTable">
                                    <thead>
                                        <tr>
                                            <th>Наименование</th>
                                            <th>Вид техники</th>
                                            <th>Применение</th>
                                            <th>Собственник</th>
                                            <th>ГРНЗ</th>
                                            <th>Норма расхода</th>
                                            <th>Диз. топливо</th>
                                            <th>Год</th>
                                            <th class="mh-head">Моточас</th>
                                            <th class="mh-head">ТО 1</th>
                                            <th class="mh-head">ТО 2</th>
                                            <th class="mh-head">ТО 3</th>
                                            <th class="mh-head">Текущий</th>
                                            <th class="mh-head">До ТО</th>
                                            <th>Страховка</th>
                                            <th>Тех. осмотр</th>
                                            <th>Налог</th>
                                            <th>Локация</th>
                                            <th class="actions-col">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody id="vehiclesTableBody">
                                    </tbody>
                                </table>
                            </div>
                            <div id="vehiclesCards" class="vehicles-cards" aria-label="Список ТС"></div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="rentTab" role="tabpanel" aria-labelledby="rent-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h2 class="page-title fs-5">Аренда</h2>
                        <button type="button" class="btn btn-primary" id="addRentBtn">
                            <i class="bi bi-plus-lg"></i> Добавить аренду
                        </button>
                    </div>
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-search-wrap p-3 border-bottom">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="search" class="form-control" id="rentSearchInput"
                                        placeholder="Поиск по аренде: ТС, арендатор, статус...">
                                    <button class="btn btn-outline-secondary" type="button" id="rentSearchClearBtn">Очистить</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="rentTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ТС</th>
                                            <th>Арендатор</th>
                                            <th>Начало</th>
                                            <th>Окончание</th>
                                            <th>Стоимость</th>
                                            <th>Приход</th>
                                            <th>Прибыль</th>
                                            <th>Диз топливу</th>
                                            <th>Статус</th>
                                            <th class="actions-col">Действия</th>
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
                        <h2 class="page-title fs-5">Запчасти</h2>
                        <button type="button" class="btn btn-primary" id="addSpareBtn">
                            <i class="bi bi-plus-lg"></i> Добавить запчасть
                        </button>
                    </div>
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-search-wrap p-3 border-bottom">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="search" class="form-control" id="spareSearchInput"
                                        placeholder="Поиск: ТС, кому, дата...">
                                    <button class="btn btn-outline-secondary" type="button" id="spareSearchClearBtn">Очистить</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="spareTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Наименование (ТС) и кому</th>
                                            <th>Название запчасти</th>
                                            <th>Количество</th>
                                            <th>Сумма</th>
                                            <th>Дата</th>
                                            <th class="actions-col">Действия</th>
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
                        <h2 class="page-title fs-5"><i class="bi bi-clipboard2-check text-warning"></i> Смета тендер — сверка с парком</h2>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="tenderOnlyEquipment" checked>
                                <label class="form-check-label small" for="tenderOnlyEquipment">Только техника</label>
                            </div>
                            <label class="btn btn-warning text-dark mb-0" id="tenderUploadBtn"
                                title="Выберите файл Excel со сметой тендера">
                                <i class="bi bi-upload"></i> Загрузить смету (Excel)
                                <input type="file" id="tenderXlsInput" class="visually-hidden" accept=".xls,.xlsx,.csv" tabindex="-1" aria-label="Файл сметы Excel">
                            </label>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="tenderClearDecisionsBtn" title="Очистить привязки строк к записям запчастей и аренды (сами записи в базе не удаляются)">Сбросить привязки</button>
                        </div>
                    </div>
                    
                    <div id="tenderSummary" class="alert alert-light border small mb-3 d-none"></div>
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive tender-smeta-wrap table-scroll">
                                <table class="table table-sm table-bordered table-hover mb-0 tender-smeta-table" id="tenderSmetaTable">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th scope="col" class="text-center text-nowrap" title="Номер по порядку">№ п/п</th>
                                            <th scope="col" class="text-nowrap" title="Шифр позиции норматива">Шифр</th>
                                            <th scope="col" class="tender-col-name" title="Наименование работ и затрат">Наименование работ и затрат</th>
                                            <th scope="col" class="text-nowrap" title="Единица измерения">Ед. изм.</th>
                                            <th scope="col" class="text-end text-nowrap" title="Количество">Количество</th>
                                            <th scope="col" class="text-end text-nowrap" title="Стоимость единицы">Стоимость ед.</th>
                                            <th scope="col" class="text-end text-nowrap" title="Общая стоимость, тенге">Общая стоимость, ₸</th>
                                            <th scope="col" class="text-center tender-col-fleet">В парке</th>
                                            <th scope="col" class="tender-col-fleet">Совпало с ТС</th>
                                            <th scope="col" class="text-nowrap tender-col-fleet">Купить / арендовать</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tenderTableBody">
                                    </tbody>
                                </table>
                            </div>
                            <p id="tenderEmpty" class="text-muted small p-3 mb-0 border-top d-none">Нет строк для сравнения. Проверьте, что на листе есть колонка с наименованием (например «Наименование», «Техника», «Ресурс»).</p>
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
                            <i class="bi bi-truck text-primary me-1"></i> Добавить транспортное средство
                        </h5>
                        <p class="modal-subtitle mb-0" id="vehicleModalHint">Основные данные, моточасы и сроки документов</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <form id="vehicleForm" class="ap-form">
                        <input type="hidden" id="vehicleId">

                        <section class="ap-form-section">
                            <h6 class="ap-form-section__title"><i class="bi bi-card-heading"></i> Основные данные</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="vName">Наименование <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="vName" required placeholder="Например: Камаз 5511">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="vEquipmentType">Вид техники</label>
                                    <select class="form-select" id="vEquipmentType">
                                        <option value="">Не указан</option>
                                        <option value="Экскаватор">Экскаватор</option>
                                        <option value="Бульдозер">Бульдозер</option>
                                        <option value="Погрузчик">Погрузчик</option>
                                        <option value="Самосвал">Самосвал</option>
                                        <option value="Автокран">Автокран</option>
                                        <option value="Кран">Кран</option>
                                        <option value="Трактор">Трактор</option>
                                        <option value="Каток">Каток</option>
                                        <option value="Грейдер">Грейдер</option>
                                        <option value="Автомобиль">Автомобиль</option>
                                        <option value="Грузовик">Грузовик</option>
                                        <option value="Автобус">Автобус</option>
                                        <option value="Манипулятор">Манипулятор</option>
                                        <option value="Прицеп">Прицеп</option>
                                        <option value="Полуприцеп">Полуприцеп</option>
                                        <option value="Компрессор">Компрессор</option>
                                        <option value="Генератор">Генератор</option>
                                        <option value="Другое">Другое</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="vOwner">Собственник <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="vOwner" required placeholder="ФИО или организация">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="vGrnz">ГРНЗ</label>
                                    <input type="text" class="form-control grnz-plate-input" id="vGrnz" placeholder="А 123 БВ 01" autocomplete="off">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="vLocation">Находится</label>
                                    <input type="text" class="form-control" id="vLocation" placeholder="Например: Алмас/Жез, Жез">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vYear">Год выпуска</label>
                                    <input type="number" class="form-control" id="vYear" min="1980" max="2030" placeholder="2020">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vConsumption">Норма расхода</label>
                                    <input type="text" class="form-control" id="vConsumption" placeholder="5–6 л/ч">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vDieselFuel">Диз. топливо</label>
                                    <input type="text" class="form-control" id="vDieselFuel" placeholder="Объём / тип">
                                </div>
                            </div>
                        </section>

                        <section class="ap-form-section ap-form-section--mh">
                            <h6 class="ap-form-section__title"><i class="bi bi-speedometer2"></i> Моточасы и ТО</h6>
                            <p class="ap-form-section__hint">Сначала укажите <strong>текущий</strong> моточас. След. ТО — каждые 250 м/ч. Ежедневное добавление — кнопкой «Моточас» в таблице.</p>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="vMotorHours">Текущий</label>
                                    <input type="number" class="form-control" id="vMotorHours" min="0" step="0.1" placeholder="Текущие м/ч">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vMotorHoursBase">Моточас (база)</label>
                                    <input type="number" class="form-control" id="vMotorHoursBase" min="0" step="0.1" placeholder="База">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vAddMotorHours">Добавить сейчас</label>
                                    <input type="number" class="form-control" id="vAddMotorHours" min="0" step="0.1" placeholder="Прибавить к текущему">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vMotorHoursNext1">След. ТО 1</label>
                                    <input type="number" class="form-control" id="vMotorHoursNext1" min="0" step="0.1" placeholder="м/ч">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vMotorHoursNext2">След. ТО 2</label>
                                    <input type="number" class="form-control" id="vMotorHoursNext2" min="0" step="0.1" placeholder="м/ч">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="vMotorHoursNext3">След. ТО 3</label>
                                    <input type="number" class="form-control" id="vMotorHoursNext3" min="0" step="0.1" placeholder="м/ч">
                                </div>
                            </div>
                        </section>

                        <section class="ap-form-section">
                            <h6 class="ap-form-section__title"><i class="bi bi-calendar2-check"></i> Сроки документов</h6>
                            <div class="ap-deadline-blocks">
                                <div class="ap-deadline-block ap-deadline-block--insurance">
                                    <div class="ap-deadline-block__label"><i class="bi bi-shield-check"></i> Страховка</div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="vInsuranceDate">Оформление</label>
                                            <input type="date" class="form-control" id="vInsuranceDate">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="vInsuranceDeadline">Дедлайн</label>
                                            <input type="date" class="form-control" id="vInsuranceDeadline">
                                        </div>
                                    </div>
                                </div>
                                <div class="ap-deadline-block ap-deadline-block--tech">
                                    <div class="ap-deadline-block__label"><i class="bi bi-tools"></i> Тех. осмотр</div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="vTechDate">Прохождение</label>
                                            <input type="date" class="form-control" id="vTechDate">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="vTechDeadline">Дедлайн</label>
                                            <input type="date" class="form-control" id="vTechDeadline">
                                        </div>
                                    </div>
                                </div>
                                <div class="ap-deadline-block ap-deadline-block--tax">
                                    <div class="ap-deadline-block__label"><i class="bi bi-receipt"></i> Налог</div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="vTaxDate">Уплата</label>
                                            <input type="date" class="form-control" id="vTaxDate">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="vTaxDeadline">Дедлайн</label>
                                            <input type="date" class="form-control" id="vTaxDeadline">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-nav" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="saveVehicleBtn"><i class="bi bi-check-lg me-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно: добавление аренды -->
    <div class="modal fade" id="rentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rentModalTitle"><i class="bi bi-calendar-check text-primary"></i> Добавить аренду</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3"><i class="bi bi-info-circle"></i> Арендовать можно только ТС из парка. Выберите транспортное средство из списка.</p>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Транспортное средство из парка <span class="text-danger">*</span></label>
                            <select id="rentVehicleSelect" class="form-select" required>
                                <option value="">Выберите ТС из парка...</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Первоначальная стоимость</label>
                            <div class="input-group">
                                <input type="text" class="form-control rent-cost-input" id="rentInitialCost" placeholder="Например: 2 500 000">
                                <span class="input-group-text">₸</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Иные расходы</label>
                            <div class="input-group">
                                <input type="text" class="form-control rent-cost-input" id="rentOtherCost" placeholder="Например: 100 000">
                                <span class="input-group-text">₸</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ТО</label>
                            <div class="input-group">
                                <input type="text" class="form-control rent-cost-input" id="rentToCost" placeholder="Например: 100 000">
                                <span class="input-group-text">₸</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Зарплата</label>
                            <div class="input-group">
                                <input type="text" class="form-control rent-cost-input" id="rentSalaryCost" placeholder="Например: 500 000">
                                <span class="input-group-text">₸</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Диз топливу</label>
                            <div class="input-group">
                                <input type="text" class="form-control rent-cost-input" id="rentDieselCost" placeholder="Например: 50 000">
                                <span class="input-group-text">₸</span>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label class="form-label">Арендатор</label>
                            <input type="text" class="form-control" id="rentTenant" placeholder="ФИО или организация">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Дата начала</label>
                            <input type="date" class="form-control" id="rentStartDate">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Дата окончания</label>
                            <input type="date" class="form-control" id="rentEndDate">
                        </div>
                    </div>
                    <hr>
                    <div class="row g-3 mb-2">
                        <div class="col-md-4">
                            <label class="form-label">Приход (доход от аренды)</label>
                            <div class="input-group">
                                <input type="text" class="form-control rent-summary-input" id="rentIncome" placeholder="Сумма полученная от арендатора">
                                <span class="input-group-text">₸</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted">Стоимость = первоначальная − расходы. Прибыль = приход − стоимость.</div>
                        <div class="text-end d-flex flex-column align-items-end gap-1">
                            <div><span class="text-muted small">Стоимость</span><span id="rentTotal" class="fw-bold ms-2">—</span></div>
                            <div><span class="text-muted small">Приход</span><span id="rentIncomeDisplay" class="fw-bold ms-2">—</span></div>
                            <div><span class="text-muted small">Прибыль</span><span id="rentProfit" class="fw-bold ms-2 text-success">—</span></div>
                            <input type="hidden" id="rentTotalRaw" value="">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="rentSaveBtn">Сохранить аренду</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно: добавление запчасти -->
    <div class="modal fade" id="spareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="spareModalTitle"><i class="bi bi-gear"></i> Добавить запчасть</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Наименование и кому запчасть — из списка ТС.</p>
                    <input type="hidden" id="spareId">
                    <div class="mb-3">
                        <label class="form-label">ТС (наименование и кому) <span class="text-danger">*</span></label>
                        <select id="spareVehicleSelect" class="form-select" required>
                            <option value="">Выберите ТС из списка...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Название запчасти</label>
                        <input type="text" class="form-control" id="spareName" placeholder="Например: Фильтр масляный">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Количество</label>
                            <input type="number" class="form-control" id="spareQuantity" min="0" step="1" value="1" placeholder="шт.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Сумма (₸)</label>
                            <input type="text" class="form-control spare-sum-input" id="spareAmount" placeholder="Например: 50 000">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Дата</label>
                            <input type="date" class="form-control" id="spareDate">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="spareSaveBtn">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно: добавить моточасы -->
    <div class="modal fade" id="motorHoursModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-lg text-success"></i> Добавить моточасы</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="mhVehicleId">
                    <p class="mb-2"><strong id="mhVehicleName"></strong></p>
                    <p class="text-muted small mb-2">Текущие моточасы: <strong id="mhCurrentValue">0</strong></p>
                    <p class="text-muted small mb-3">До след. ТО: <strong id="mhUntilTO">—</strong> м/ч</p>
                    <div class="input-group mb-3">
                        <input type="number" class="form-control" id="mhAddAmount" min="0" step="0.1" value="1" placeholder="Сколько прибавить">
                        <button type="button" class="btn btn-success" id="mhAddBtn"><i class="bi bi-plus-lg"></i> Добавить</button>
                    </div>
                    <div class="border rounded p-2 bg-light">
                        <small class="text-muted fw-bold">История добавлений</small>
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
                    <h5 class="modal-title"><i class="bi bi-file-earmark-text text-info"></i> Отчёты</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="report-grid mb-3">
                        <button type="button" class="btn report-btn report-type-btn" data-report="tax"><i class="bi bi-receipt-cutoff"></i> Налог</button>
                        <button type="button" class="btn report-btn report-type-btn" data-report="insurance"><i class="bi bi-shield-check"></i> Страховка</button>
                        <button type="button" class="btn report-btn report-type-btn" data-report="tech"><i class="bi bi-tools"></i> ТО</button>
                        <button type="button" class="btn report-btn report-type-btn" data-report="motohours"><i class="bi bi-speedometer2"></i> Моточасы</button>
                        <button type="button" class="btn report-btn report-type-btn" data-report="summary"><i class="bi bi-list-ul"></i> Сводка ТС</button>
                        <button type="button" class="btn report-btn report-type-btn" data-report="spares"><i class="bi bi-gear"></i> Запчасти</button>
                        <button type="button" class="btn report-btn report-type-btn" data-report="rent"><i class="bi bi-calendar-check"></i> Аренда</button>
                    </div>
                    <div id="reportTitle" class="fw-bold mb-2 text-muted"></div>
                    <div class="table-responsive report-table-scroll" id="reportTableWrap">
                        <table class="table table-sm table-hover mb-0" id="reportTable">
                            <thead class="table-light sticky-top" id="reportTableHead"></thead>
                            <tbody id="reportTableBody"></tbody>
                        </table>
                    </div>
                    <p id="reportEmpty" class="text-muted small mt-2 d-none">Нет данных для отчёта.</p>
                    <div class="mt-3">
                        <button type="button" class="btn btn-success btn-sm" id="reportDownloadExcel" disabled><i class="bi bi-file-earmark-excel"></i> Скачать Excel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/partials/whats_new.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/xlsx.full.min.js"></script>
    <script src="autopark.js"></script>
</body>

</html>
