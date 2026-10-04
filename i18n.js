(function () {
    var STORAGE_KEY = 'autopark_lang';
    var lang = 'ru';
    try {
        var saved = localStorage.getItem(STORAGE_KEY);
        if (saved === 'en' || saved === 'ru') lang = saved;
        var q = new URLSearchParams(window.location.search).get('lang');
        if (q === 'en' || q === 'ru') {
            lang = q;
            localStorage.setItem(STORAGE_KEY, lang);
        }
    } catch (e) { /* ignore */ }

    var EN = {
        'Язык': 'Language',
        'Автопарк': 'Autopark',
        'Автопарк — учёт ТС и дедлайнов': 'Autopark — vehicles and deadlines',
        'Админ-панель — Автопарк': 'Admin — Autopark',
        'Вход для администратора / менеджера': 'Sign in for an administrator or manager',
        'Логин': 'Username',
        'Пароль': 'Password',
        'Войти': 'Sign in',
        'Админ-панель': 'Admin',
        'Выйти': 'Sign out',
        'Транспортные средства': 'Vehicles',
        'Транспортное средство': 'Vehicle',
        'Скачать шаблон Excel': 'Download Excel template',
        'Импорт из Excel': 'Import from Excel',
        'Добавить ТС': 'Add vehicle',
        'Отчёты': 'Reports',
        'Транспорт': 'Vehicles',
        'Аренда': 'Rent',
        'Запчасти': 'Spare parts',
        'Тендер': 'Tender',
        'Страховка': 'Insurance',
        'истекает в течение 30 дней': 'expires within 30 days',
        'Тех. осмотр': 'Inspection',
        'в течение 30 дней': 'within 30 days',
        'Налог': 'Tax',
        'Поиск: наименование, владелец, ГРНЗ, локация…': 'Search: name, owner, plate, location…',
        'Очистить': 'Clear',
        'Наименование': 'Name',
        'Вид техники': 'Equipment type',
        'Применение': 'Use',
        'Собственник': 'Owner',
        'ГРНЗ': 'Plate',
        'Норма расхода': 'Consumption rate',
        'Диз. топливо': 'Diesel',
        'Год': 'Year',
        'Моточас': 'Hours',
        'ТО 1': 'Service 1',
        'ТО 2': 'Service 2',
        'ТО 3': 'Service 3',
        'Текущий': 'Current',
        'До ТО': 'To service',
        'Локация': 'Location',
        'Действия': 'Actions',
        'Список ТС': 'Vehicle list',
        'Добавить аренду': 'Add rental',
        'Поиск по аренде: ТС, арендатор, статус...': 'Search rentals: vehicle, tenant, status...',
        'ТС': 'Vehicle',
        'Арендатор': 'Tenant',
        'Начало': 'Start',
        'Окончание': 'End',
        'Стоимость': 'Cost',
        'Приход': 'Income',
        'Прибыль': 'Profit',
        'Диз топливу': 'Diesel',
        'Статус': 'Status',
        'Добавить запчасть': 'Add spare part',
        'Поиск: ТС, кому, дата...': 'Search: vehicle, recipient, date...',
        'Наименование (ТС) и кому': 'Vehicle and recipient',
        'Название запчасти': 'Part name',
        'Количество': 'Quantity',
        'Сумма': 'Amount',
        'Дата': 'Date',
        'Смета тендер — сверка с парком': 'Tender estimate — match with the fleet',
        'Только техника': 'Equipment only',
        'Загрузить смету (Excel)': 'Upload estimate (Excel)',
        'Выберите файл Excel со сметой тендера': 'Choose an Excel file with the tender estimate',
        'Файл сметы Excel': 'Excel estimate file',
        'Сбросить привязки': 'Clear links',
        'Очистить привязки строк к записям запчастей и аренды (сами записи в базе не удаляются)': 'Clear links from estimate rows to spare-part and rental records (the records themselves stay in the database)',
        '№ п/п': 'No.',
        'Номер по порядку': 'Item number',
        'Шифр': 'Code',
        'Шифр позиции норматива': 'Norm item code',
        'Наименование работ и затрат': 'Work and cost description',
        'Ед. изм.': 'Unit',
        'Единица измерения': 'Unit of measure',
        'Стоимость ед.': 'Unit cost',
        'Стоимость единицы': 'Unit cost',
        'Общая стоимость, ₸': 'Total cost, ₸',
        'Общая стоимость, тенге': 'Total cost, tenge',
        'В парке': 'In fleet',
        'Совпало с ТС': 'Matched vehicle',
        'Купить / арендовать': 'Buy / rent',
        'Нет строк для сравнения. Проверьте, что на листе есть колонка с наименованием (например «Наименование», «Техника», «Ресурс»).': 'No rows to compare. Check that the sheet has a name column (for example “Name”, “Equipment”, “Resource”).',
        'Закрыть': 'Close',
        'Добавить транспортное средство': 'Add vehicle',
        'Основные данные, моточасы и сроки документов': 'Main details, engine hours, and document deadlines',
        'Основные данные': 'Main details',
        'Например: Камаз 5511': 'For example: KamAZ 5511',
        'Не указан': 'Not specified',
        'Экскаватор': 'Excavator',
        'Бульдозер': 'Bulldozer',
        'Погрузчик': 'Loader',
        'Самосвал': 'Dump truck',
        'Автокран': 'Truck crane',
        'Кран': 'Crane',
        'Трактор': 'Tractor',
        'Каток': 'Roller',
        'Грейдер': 'Grader',
        'Автомобиль': 'Car',
        'Грузовик': 'Truck',
        'Автобус': 'Bus',
        'Манипулятор': 'Manipulator',
        'Прицеп': 'Trailer',
        'Полуприцеп': 'Semi-trailer',
        'Компрессор': 'Compressor',
        'Генератор': 'Generator',
        'Полуприцеп-трал': 'Lowboy semi-trailer',
        'Бензовоз': 'Fuel tanker',
        'Автобетоносмеситель': 'Concrete mixer truck',
        'Седельный тягач': 'Tractor unit',
        'Бетоносмеситель': 'Concrete mixer',
        'Дизельный двигатель': 'Diesel engine',
        'Автогрейдер': 'Motor grader',
        'Асфальтоукладчик': 'Asphalt paver',
        'Бетономешалка': 'Cement mixer',
        'ГАЗель': 'GAZelle',
        'Автогудронатор (битумораспределитель)': 'Bitumen distributor',
        'Дорожная фреза': 'Road milling machine',
        'Зерноуборочный комбайн': 'Combine harvester',
        'Другое': 'Other',
        'ФИО или организация': 'Name or organization',
        'А 123 БВ 01': 'A 123 BC 01',
        'Находится': 'Location',
        'Например: Алмас/Жез, Жез': 'For example: Almaty / Zhezkazgan',
        'Год выпуска': 'Year of manufacture',
        '5–6 л/ч': '5–6 L/h',
        'Объём / тип': 'Volume / type',
        'Моточасы и ТО': 'Engine hours and service',
        'Текущие м/ч': 'Current hours',
        'Моточас (база)': 'Hours (base)',
        'База': 'Base',
        'Добавить сейчас': 'Add now',
        'Прибавить к текущему': 'Add to current',
        'След. ТО 1': 'Next service 1',
        'След. ТО 2': 'Next service 2',
        'След. ТО 3': 'Next service 3',
        'м/ч': 'h',
        'Сроки документов': 'Document deadlines',
        'Оформление': 'Issued',
        'Дедлайн': 'Deadline',
        'Прохождение': 'Completed',
        'Уплата': 'Paid',
        'Отмена': 'Cancel',
        'Сохранить': 'Save',
        'Арендовать можно только ТС из парка. Выберите транспортное средство из списка.': 'You can rent only vehicles from the fleet. Choose a vehicle from the list.',
        'Транспортное средство из парка': 'Vehicle from the fleet',
        'Выберите ТС из парка...': 'Choose a vehicle from the fleet...',
        'Первоначальная стоимость': 'Initial cost',
        'Например: 2 500 000': 'For example: 2,500,000',
        'Иные расходы': 'Other expenses',
        'Например: 100 000': 'For example: 100,000',
        'ТО': 'Service',
        'Зарплата': 'Wages',
        'Например: 500 000': 'For example: 500,000',
        'Например: 50 000': 'For example: 50,000',
        'Дата начала': 'Start date',
        'Дата окончания': 'End date',
        'Приход (доход от аренды)': 'Income (rental revenue)',
        'Сумма полученная от арендатора': 'Amount received from the tenant',
        'Стоимость = первоначальная − расходы. Прибыль = приход − стоимость.': 'Cost = initial amount − expenses. Profit = income − cost.',
        'Сохранить аренду': 'Save rental',
        'Наименование и кому запчасть — из списка ТС.': 'The vehicle and recipient come from the vehicle list.',
        'ТС (наименование и кому)': 'Vehicle (name and recipient)',
        'Выберите ТС из списка...': 'Choose a vehicle from the list...',
        'Например: Фильтр масляный': 'For example: oil filter',
        'шт.': 'pcs',
        'Сумма (₸)': 'Amount (₸)',
        'Добавить моточасы': 'Add engine hours',
        'Текущие моточасы:': 'Current engine hours:',
        'До след. ТО:': 'Until next service:',
        'Сколько прибавить': 'How many to add',
        'Добавить': 'Add',
        'История добавлений': 'Addition history',
        'Налог': 'Tax',
        'Моточасы': 'Engine hours',
        'Сводка ТС': 'Vehicle summary',
        'Нет данных для отчёта.': 'No data for this report.',
        'Скачать Excel': 'Download Excel',
        'Что нового': 'What’s new',
        'Обновления после деплоя': 'Updates after deploy',
        'Версия (git HEAD)': 'Version (git HEAD)',
        'Обновление установлено.': 'Update installed.',
        'Понятно': 'Got it',
        'Вход только для администратора': 'Administrator sign-in only',
        'Введите логин': 'Enter username',
        'Введите пароль': 'Enter password',
        'Войти в админ-панель': 'Sign in to admin',
        'Вернуться в автопарк': 'Back to the fleet',
        'В автопарк': 'To the fleet',
        'Журналы действий, бортовой журнал и отчёты по ТС': 'Activity logs, vehicle log, and vehicle reports',
        'Общий журнал': 'General log',
        'Бортовой журнал': 'Vehicle log',
        'Пользователи': 'Users',
        'Все действия пользователей в системе.': 'All user actions in the system.',
        'Очистить журнал': 'Clear log',
        'Дата и время': 'Date and time',
        'Действие': 'Action',
        'Кто': 'Who',
        'Объект': 'Object',
        'Детали': 'Details',
        'Записей пока нет.': 'No records yet.',
        'Журнал по выбранному ТС: моточасы и связанные действия.': 'Log for the selected vehicle: engine hours and related actions.',
        'Выберите ТС...': 'Choose a vehicle...',
        'Выберите ТС или записей нет.': 'Choose a vehicle, or there are no records.',
        'Отчёты по налогу, страховке, ТО, моточасам, запчастям, аренде.': 'Reports for tax, insurance, service, engine hours, spare parts, and rent.',
        'Выберите тип отчёта.': 'Choose a report type.',
        'Добавление менеджеров и просмотр пользователей системы.': 'Add managers and view system users.',
        'Добавить менеджера': 'Add a manager',
        'Имя': 'Name',
        'Имя (необязательно)': 'Name (optional)',
        'Роль': 'Role',
        'Создан': 'Created',
        'Пользователей пока нет.': 'No users yet.',
        'Добавление': 'Added',
        'Редактирование': 'Edited',
        'Удаление': 'Deleted',
        'Запчасть': 'Spare part',
        'Выберите ТС.': 'Choose a vehicle.',
        'Записей по этому ТС нет.': 'No records for this vehicle.',
        'Удалить эту запись': 'Delete this entry',
        'Нет записей': 'No records',
        'Пора ТО!': 'Service due!',
        'Нет данных. Нажмите «Добавить ТС».': 'No data. Click “Add vehicle”.',
        'Ничего не найдено.': 'Nothing found.',
        'Не указано': 'Not specified',
        'Моточасов до следующего ТО': 'Engine hours until the next service',
        'Редактировать': 'Edit',
        'Удалить': 'Delete',
        'Нет данных для отображения.': 'Nothing to show.',
        'До след. ТО': 'Until next service',
        'Моточас': 'Hours',
        'Удалить это транспортное средство?': 'Delete this vehicle?',
        'Тип': 'Type',
        'Дней осталось': 'Days left',
        'Просрочено': 'Overdue',
        'Дедлайны (страховка, техосмотр, налог) — ближайшие 30 дней': 'Deadlines (insurance, inspection, tax) — next 30 days',
        'Отчет по налогу — ближайшие 30 дней': 'Tax report — next 30 days',
        'Отчет по страховке — ближайшие 30 дней': 'Insurance report — next 30 days',
        'Отчет по ТО — ближайшие 30 дней': 'Service report — next 30 days',
        'Текущий м/ч': 'Current hours',
        'До след. ТО (м/ч)': 'Until next service (h)',
        'Моточасы — до следующего ТО': 'Engine hours — until the next service',
        'Сводка по транспортным средствам': 'Vehicle summary',
        'ТС (наименование и кому)': 'Vehicle (name and recipient)',
        'Отчет по запчастям': 'Spare parts report',
        'Отчёт по аренде': 'Rent report',
        'Активна': 'Active',
        'Выберите тип отчёта': 'Choose a report type',
        'Ошибка загрузки отчёта': 'Could not load the report',
        'Отчёт': 'Report',
        'Отчет': 'Report',
        'Ошибка удаления': 'Could not delete',
        'Записей пока нет': 'No records yet',
        'Ошибка': 'Error',
        'Редактировать ТС': 'Edit vehicle',
        'Ошибка сохранения': 'Could not save',
        'Заполните наименование и собственника.': 'Enter the name and the owner.',
        'Без названия': 'Untitled',
        'Записей аренды пока нет.': 'No rental records yet.',
        'Удалить запись аренды?': 'Delete this rental record?',
        'Выберите транспортное средство из парка.': 'Choose a vehicle from the fleet.',
        'Выбранное ТС не найдено в парке.': 'The selected vehicle was not found in the fleet.',
        'Укажите арендатора.': 'Enter the tenant.',
        'Укажите дату начала аренды.': 'Enter the rental start date.',
        'Выберите ТС (наименование и кому)...': 'Choose a vehicle (name and recipient)...',
        'Записей запчастей пока нет.': 'No spare-part records yet.',
        'Удалить запись о запчасти?': 'Delete this spare-part record?',
        'Редактировать запчасть': 'Edit spare part',
        'Запчасть по позиции тендера': 'Spare part for a tender item',
        'Выберите ТС из списка (наименование и кому).': 'Choose a vehicle from the list (name and recipient).',
        'Выбранное ТС не найдено.': 'The selected vehicle was not found.',
        'Аренда по позиции тендера': 'Rental for a tender item',
        'Техника в смете не найдена. Снимите «Только техника», чтобы увидеть все строки.': 'No equipment found in the estimate. Turn off “Equipment only” to see every row.',
        'Да': 'Yes',
        'Нет': 'No',
        'Открыть': 'Open',
        'Ещё закупка': 'Another purchase',
        'Добавить ещё запись': 'Add another record',
        'Ещё аренда': 'Another rental',
        'Добавить ещё аренду': 'Add another rental',
        'Купить': 'Buy',
        'Внести закупку в раздел «Запчасти»': 'Record the purchase under Spare parts',
        'Арендовать': 'Rent',
        'Внести в раздел «Аренда»': 'Record it under Rent',
        'Чтение файла и сверка с парком…': 'Reading the file and matching it with the fleet…',
        'Библиотека Excel (SheetJS) не загрузилась с интернета. Проверьте сеть и обновите страницу.': 'The Excel library (SheetJS) did not load. Check the network and refresh the page.',
        'Не удалось открыть файл как Excel. Сохраните как .xlsx или .xls и попробуйте снова.': 'Could not open the file as Excel. Save it as .xlsx or .xls and try again.',
        'В файле нет листов.': 'The file has no sheets.',
        'Неизвестная ошибка': 'Unknown error',
        'Ошибка разбора': 'Could not parse',
        'Не удалось прочитать файл. Попробуйте другой файл или скопируйте смету в новый .xlsx.': 'Could not read the file. Try another file, or copy the estimate into a new .xlsx.',
        'На первом листе нет данных.': 'The first sheet has no data.',
        'Библиотека Excel не загружена.': 'The Excel library is not loaded.',
        'неверный формат': 'invalid format',
        'Не выбран тип отчёта.': 'No report type selected.',
        'Укажите логин и пароль.': 'Enter the username and password.',
        'Ошибка добавления.': 'Could not add the user.',
        'Администратор': 'Administrator',
        'Менеджер': 'Manager',
        'Не удалось загрузить список.': 'Could not load the list.',
        'Доступ только для администратора.': 'Only an administrator can sign in here.',
        'Неверный логин или пароль.': 'Wrong username or password.',
        'Неверный логин или пароль': 'Wrong username or password',
        'Доступ только для администратора': 'Administrator access only',
        'Необходима авторизация': 'Sign-in required',
        'Укажите логин и пароль': 'Enter the username and password',
        'Укажите vehicleId': 'Specify the vehicle',
        'Укажите vehicleId и положительное количество моточасов': 'Specify the vehicle and a positive number of engine hours',
        'ТС не найдено': 'Vehicle not found',
        'Метод не поддерживается': 'Method not supported',
        'Запись не найдена': 'Record not found',
        'Выберите ТС': 'Choose a vehicle',
        'Заполните наименование и собственника': 'Enter the name and the owner',
        'Нет данных для обновления': 'No data to update',
        'Укажите арендатора': 'Enter the tenant',
        'Укажите дату начала': 'Enter the start date',
        'Укажите логин': 'Enter the username',
        'Укажите пароль': 'Enter the password',
        'Пользователь с таким логином уже существует': 'A user with this username already exists',
        'Логин не может быть пустым': 'Username cannot be empty',
        'Укажите id': 'Specify the id',
        'Неизвестный тип отчёта': 'Unknown report type',
        'Укажите type: tax, insurance, tech, motohours, summary, spares, rent': 'Specify type: tax, insurance, tech, motohours, summary, spares, rent',
        'Сбросить привязки строк тендера к записям в системе (сами запчасти и аренды в базе не удаляются)?': 'Clear links from tender rows to records in the system (spare parts and rentals in the database are not deleted)?',
        'Удалить эту запись из журнала?': 'Delete this log entry?',
        'Удалить весь журнал действий? Это действие нельзя отменить.': 'Delete the entire activity log? This cannot be undone.',
        'Файл прочитан, но позиции не распознаны. Нужна строка заголовков с колонкой <strong>«Наименование»</strong> / <strong>«Техника»</strong> (или таблица с первой строки листа без «красивых» заголовков — тогда берётся самая длинная ячейка в строке).': 'The file was read, but no items were recognized. A header row with a <strong>“Name”</strong> / <strong>“Equipment”</strong> column is required (or a table from the first row — then the longest cell in the row is used).',
        'Показано техники: {shown} из {total}.': 'Equipment shown: {shown} of {total}.',
        'Строк в смете: {n}.': 'Rows in the estimate: {n}.',
        'В парке: {inPark}. Не в парке: {missing}.{decisions}': 'In fleet: {inPark}. Not in fleet: {missing}.{decisions}',
        'Внесено в учёт (запчасти / аренда): {spares} / {rents}. Без записи: {open}.': 'Recorded (spare parts / rent): {spares} / {rents}. Not recorded: {open}.',
        'Запчасть №{id}': 'Spare part #{id}',
        'Аренда №{id}': 'Rental #{id}',
        'Менеджер «{login}» добавлен.': 'Manager “{login}” added.',
        'Импортировано записей: {n}': 'Imported records: {n}',
        'Ошибка при чтении файла: {msg}': 'Could not read the file: {msg}',
        'Автопарк': 'Autopark'
    };

    var HTML = {
        mhHint: {
            ru: 'Сначала укажите <strong>текущий</strong> моточас. След. ТО — каждые 250 м/ч. Ежедневное добавление — кнопкой «Моточас» в таблице.',
            en: 'Enter the <strong>current</strong> engine hours first. The next service is every 250 h. Add hours daily with the “Hours” button in the table.'
        }
    };

    var EQUIPMENT = [
        ['Экскаватор', 'Excavator'],
        ['Бульдозер', 'Bulldozer'],
        ['Погрузчик', 'Loader'],
        ['Самосвал', 'Dump truck'],
        ['Автокран', 'Truck crane'],
        ['Кран', 'Crane'],
        ['Трактор', 'Tractor'],
        ['Каток', 'Roller'],
        ['Грейдер', 'Grader'],
        ['Автомобиль', 'Car'],
        ['Грузовик', 'Truck'],
        ['Автобус', 'Bus'],
        ['Манипулятор', 'Manipulator'],
        ['Прицеп', 'Trailer'],
        ['Полуприцеп', 'Semi-trailer'],
        ['Компрессор', 'Compressor'],
        ['Генератор', 'Generator'],
        ['Полуприцеп-трал', 'Lowboy semi-trailer'],
        ['Бензовоз', 'Fuel tanker'],
        ['Автобетоносмеситель', 'Concrete mixer truck'],
        ['Седельный тягач', 'Tractor unit'],
        ['Бетоносмеситель', 'Concrete mixer'],
        ['Дизельный двигатель', 'Diesel engine'],
        ['Автогрейдер', 'Motor grader'],
        ['Асфальтоукладчик', 'Asphalt paver'],
        ['Бетономешалка', 'Cement mixer'],
        ['ГАЗель', 'GAZelle'],
        ['Автогудронатор (битумораспределитель)', 'Bitumen distributor'],
        ['Дорожная фреза', 'Road milling machine'],
        ['Зерноуборочный комбайн', 'Combine harvester'],
        ['Другое', 'Other']
    ];

    function interpolate(s, vars) {
        if (!vars) return s;
        Object.keys(vars).forEach(function (k) {
            s = s.split('{' + k + '}').join(String(vars[k]));
        });
        return s;
    }

    function translatePattern(s) {
        var m;
        m = s.match(/^Ошибка запроса (\d+)$/);
        if (m) return 'Request failed (' + m[1] + ')';
        m = s.match(/^Отчет по запчастям \(записей: ([^,]+), итого: (.+)\)$/);
        if (m) return 'Spare parts report (records: ' + m[1] + ', total: ' + m[2] + ')';
        m = s.match(/^Отчёт по аренде \(записей: ([^,]+), приход: (.+), прибыль: (.+)\)$/);
        if (m) return 'Rent report (records: ' + m[1] + ', income: ' + m[2] + ', profit: ' + m[3] + ')';
        m = s.match(/^Импортировано записей: (.+)$/);
        if (m) return 'Imported records: ' + m[1];
        m = s.match(/^Менеджер «(.+)» добавлен\.$/);
        if (m) return 'Manager “' + m[1] + '” added.';
        m = s.match(/^Ошибка при чтении файла: (.+)$/);
        if (m) return 'Could not read the file: ' + (EN[m[1]] || m[1]);
        m = s.match(/^Ошибка базы данных: (.+)$/);
        if (m) return 'Database error: ' + m[1];
        m = s.match(/^Ошибка: (.+)$/);
        if (m) return 'Error: ' + (EN[m[1]] || m[1]);
        m = s.match(/^Запчасть №(.+)$/);
        if (m) return 'Spare part #' + m[1];
        m = s.match(/^Аренда №(.+)$/);
        if (m) return 'Rental #' + m[1];
        return s;
    }

    function t(input, vars) {
        var s = input == null ? '' : String(input);
        if (lang === 'en') {
            if (Object.prototype.hasOwnProperty.call(EN, s)) s = EN[s];
            else s = translatePattern(s);
        }
        return interpolate(s, vars);
    }

    function apply(root) {
        var scope = root || document;
        document.documentElement.lang = lang === 'en' ? 'en' : 'ru';
        scope.querySelectorAll('[data-i18n]').forEach(function (el) {
            el.textContent = t(el.getAttribute('data-i18n'));
        });
        scope.querySelectorAll('[data-i18n-placeholder]').forEach(function (el) {
            el.setAttribute('placeholder', t(el.getAttribute('data-i18n-placeholder')));
        });
        scope.querySelectorAll('[data-i18n-title]').forEach(function (el) {
            el.setAttribute('title', t(el.getAttribute('data-i18n-title')));
        });
        scope.querySelectorAll('[data-i18n-aria]').forEach(function (el) {
            el.setAttribute('aria-label', t(el.getAttribute('data-i18n-aria')));
        });
        scope.querySelectorAll('[data-i18n-html]').forEach(function (el) {
            var key = el.getAttribute('data-i18n-html');
            var pack = HTML[key];
            if (!pack) return;
            el.innerHTML = lang === 'en' ? pack.en : pack.ru;
        });
        document.querySelectorAll('[data-set-lang]').forEach(function (btn) {
            var on = btn.getAttribute('data-set-lang') === lang;
            btn.classList.toggle('is-active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    function setLang(next) {
        if (next !== 'en' && next !== 'ru') return;
        lang = next;
        try { localStorage.setItem(STORAGE_KEY, lang); } catch (e) { /* ignore */ }
        apply();
        if (typeof window.autoparkRefreshI18n === 'function') window.autoparkRefreshI18n();
    }

    function canonicalEquipmentType(value) {
        var s = String(value || '').trim().toLowerCase();
        if (!s) return value || '';
        for (var i = 0; i < EQUIPMENT.length; i++) {
            if (EQUIPMENT[i][0].toLowerCase() === s || EQUIPMENT[i][1].toLowerCase() === s) return EQUIPMENT[i][0];
        }
        return String(value).trim();
    }

    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-set-lang]') : null;
        if (!btn) return;
        e.preventDefault();
        setLang(btn.getAttribute('data-set-lang'));
    });

    window.t = t;
    window.AutoparkI18n = {
        t: t,
        apply: apply,
        setLang: setLang,
        getLang: function () { return lang; },
        canonicalEquipmentType: canonicalEquipmentType
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { apply(); });
    } else {
        apply();
    }
})();
