(function () {
    'use strict';

    const API = 'api/';
    const DAYS_DEADLINE_WARNING = 30;
    const MOTOHOURS_TO_INTERVAL = 250;
    let vehicleSearchQuery = '';
    let rentSearchQuery = '';
    let spareSearchQuery = '';
    let sessionUser = null;
    const TENDER_COMMITTED_STORAGE = 'aigerim_tender_committed';
    /** @type {{ rowKey: string, type: 'spare'|'rent' }|null} */
    let pendingTenderCommit = null;
    let lastTenderResults = null;
    let lastTenderMultiSheet = false;
    let vehicles = [];
    let rents = [];
    let spares = [];

    function notify(message, tone) {
        const text = String(message == null ? '' : message);
        if (!text) return;
        const host = document.getElementById('apToastHost');
        if (!host) {
            window.alert(text);
            return;
        }
        const el = document.createElement('div');
        el.className = 'ap-toast ap-toast--' + (tone || 'info');
        el.setAttribute('role', 'status');
        el.textContent = text;
        host.appendChild(el);
        window.setTimeout(function () {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.2s';
            window.setTimeout(function () { el.remove(); }, 220);
        }, 4200);
    }

    /** Toast instead of blocking browser alerts inside this app. */
    function alert(message) {
        const text = String(message == null ? '' : message);
        let tone = 'info';
        if (/импортировано|успеш|добавлен|сохранен|удалён|удален/i.test(text)) tone = 'success';
        else if (/выберите|укажите|заполните|не выбран/i.test(text)) tone = 'warning';
        else if (/ошибка|не удалось|не найден|необходим/i.test(text)) tone = 'danger';
        notify(text, tone);
    }

    function normalizeSearchValue(value) {
        return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function vehicleMatchesQuery(vehicle, query) {
        if (!query) return true;
        const haystack = normalizeSearchValue([
            vehicle.name,
            vehicle.owner,
            vehicle.grnz,
            vehicle.consumptionRate,
            vehicle.dieselFuel,
            vehicle.year,
            vehicle.location,
            vehicle.application
        ].join(' '));
        return haystack.indexOf(query) !== -1;
    }

    function rentMatchesQuery(rent, query) {
        if (!query) return true;
        const haystack = normalizeSearchValue([
            rent.vehicleName,
            rent.vehicleGrnz,
            rent.tenant,
            rent.status,
            rent.startDate,
            rent.endDate,
            rent.total,
            rent.income,
            rent.profit,
            rent.dieselCost
        ].join(' '));
        return haystack.indexOf(query) !== -1;
    }

    function spareMatchesQuery(spare, query) {
        if (!query) return true;
        const haystack = normalizeSearchValue([
            spare.vehicleName,
            spare.vehicleOwner,
            spare.spareName,
            spare.quantity,
            spare.amount,
            spare.date
        ].join(' '));
        return haystack.indexOf(query) !== -1;
    }

    async function apiGet(url) {
        const r = await fetch(API + url, { credentials: 'same-origin' });
        let data = null;
        let text = '';
        try {
            text = await r.text();
            data = text ? JSON.parse(text) : null;
        } catch (_) { }
        if (r.status === 401) return { error: 'Необходима авторизация' };
        if (!r.ok) {
            const msg = (data && data.error) ? data.error : (text && text.length < 200 ? text : ('Ошибка запроса ' + r.status));
            throw new Error(msg);
        }
        return data;
    }
    async function apiPost(url, body) {
        const r = await fetch(API + url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body || {}) });
        let data = null;
        try {
            const text = await r.text();
            data = text ? JSON.parse(text) : null;
        } catch (_) { }
        if (r.status === 401) return { error: 'Необходима авторизация' };
        if (!r.ok) throw new Error(data && data.error ? data.error : ('Ошибка запроса ' + r.status));
        return data;
    }
    async function apiPut(url, body) {
        const r = await fetch(API + url, { method: 'PUT', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body || {}) });
        let data = null;
        try { const text = await r.text(); data = text ? JSON.parse(text) : null; } catch (_) { }
        if (r.status === 401) return { error: 'Необходима авторизация' };
        if (!r.ok) throw new Error(data && data.error ? data.error : ('Ошибка запроса ' + r.status));
        return data;
    }
    async function apiPatch(url, body) {
        const r = await fetch(API + url, { method: 'PATCH', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body || {}) });
        let data = null;
        try { const text = await r.text(); data = text ? JSON.parse(text) : null; } catch (_) { }
        if (r.status === 401) return { error: 'Необходима авторизация' };
        if (!r.ok) throw new Error(data && data.error ? data.error : ('Ошибка запроса ' + r.status));
        return data;
    }
    async function apiDelete(url) {
        const r = await fetch(API + url, { method: 'DELETE', credentials: 'same-origin' });
        let data = null;
        try { const text = await r.text(); data = text ? JSON.parse(text) : null; } catch (_) { }
        if (r.status === 401) return { error: 'Необходима авторизация' };
        if (!r.ok) throw new Error(data && data.error ? data.error : ('Ошибка запроса ' + r.status));
        return data;
    }

    async function getSession() {
        const data = await apiGet('auth.php');
        if (data && data.user) { sessionUser = data.user; return data.user; }
        sessionUser = null;
        return null;
    }
    function setSession(user) {
        sessionUser = user;
    }
    async function login(loginVal, password) {
        const data = await apiPost('auth.php?action=login', { action: 'login', login: loginVal, password: password });
        if (data && data.user) { sessionUser = data.user; return data; }
        throw new Error(data && data.error ? data.error : 'Неверный логин или пароль');
    }
    async function logout() {
        await apiPost('auth.php?action=logout', { action: 'logout' });
        sessionUser = null;
    }

    async function loadVehicles(search) {
        const q = search !== undefined && search !== '' ? '?search=' + encodeURIComponent(search) : '';
        vehicles = await apiGet('vehicles.php' + q);
        if (!Array.isArray(vehicles)) vehicles = [];
        return vehicles;
    }
    function getVehicles() {
        return vehicles;
    }
    async function loadRents(search) {
        const q = search !== undefined && search !== '' ? '?search=' + encodeURIComponent(search) : '';
        rents = await apiGet('rents.php' + q);
        if (!Array.isArray(rents)) rents = [];
        return rents;
    }
    function getRents() {
        return rents;
    }
    async function loadSpares(search) {
        const q = search !== undefined && search !== '' ? '?search=' + encodeURIComponent(search) : '';
        spares = await apiGet('spares.php' + q);
        if (!Array.isArray(spares)) spares = [];
        return spares;
    }
    function getSpares() {
        return spares;
    }

    async function getAuditLog() {
        const list = await apiGet('audit.php');
        return Array.isArray(list) ? list : [];
    }
    async function deleteAuditLogEntryById(id) {
        await apiDelete('audit.php?id=' + encodeURIComponent(id));
    }
    async function clearAuditLog() {
        await apiPost('audit.php?action=clear', {});
    }

    function actionLabel(action) {
        const labels = { add: 'Добавление', edit: 'Редактирование', delete: 'Удаление', motohours: 'Моточасы' };
        return labels[action] || action;
    }

    function entityTypeLabel(type) {
        const labels = { vehicle: 'ТС', rent: 'Аренда', spare: 'Запчасть', motohours: 'Моточасы' };
        return labels[type] || type;
    }

    async function renderAdminGeneralJournal() {
        const tbody = document.getElementById('adminGeneralJournalBody');
        const emptyEl = document.getElementById('adminGeneralJournalEmpty');
        if (!tbody) return;
        const list = await getAuditLog();
        const log = list.slice().reverse();
        if (log.length === 0) {
            tbody.innerHTML = '';
            if (emptyEl) { emptyEl.classList.remove('d-none'); emptyEl.textContent = 'Записей пока нет.'; }
            return;
        }
        if (emptyEl) emptyEl.classList.add('d-none');
        tbody.innerHTML = log.map(function (entry) {
            return '<tr>' +
                '<td>' + escapeHtml(formatHistoryDate(entry.date)) + '</td>' +
                '<td>' + escapeHtml(actionLabel(entry.action)) + '</td>' +
                '<td>' + escapeHtml(entry.user || '—') + '</td>' +
                '<td>' + escapeHtml(entityTypeLabel(entry.entityType) + (entry.entityName ? ': ' + entry.entityName : '')) + '</td>' +
                '<td class="small">' + escapeHtml(entry.details || '—') + '</td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger admin-delete-log-btn" data-id="' + escapeHtml(String(entry.id)) + '" title="Удалить эту запись"><i class="bi bi-trash"></i></button></td>' +
                '</tr>';
        }).join('');
    }

    async function deleteAuditLogEntry(id) {
        await deleteAuditLogEntryById(id);
        await renderAdminGeneralJournal();
        await renderAdminBoardJournal();
    }

    async function renderAdminBoardJournal() {
        const vehicleId = document.getElementById('adminBoardVehicleSelect') && document.getElementById('adminBoardVehicleSelect').value;
        const tbody = document.getElementById('adminBoardJournalBody');
        const emptyEl = document.getElementById('adminBoardJournalEmpty');
        if (!tbody) return;
        if (!vehicleId) {
            tbody.innerHTML = '';
            if (emptyEl) { emptyEl.classList.remove('d-none'); emptyEl.textContent = 'Выберите ТС.'; }
            return;
        }
        const entries = await apiGet('audit.php?vehicleId=' + encodeURIComponent(vehicleId));
        const list = Array.isArray(entries) ? entries : [];
        if (list.length === 0) {
            tbody.innerHTML = '';
            if (emptyEl) { emptyEl.classList.remove('d-none'); emptyEl.textContent = 'Записей по этому ТС нет.'; }
            return;
        }
        if (emptyEl) emptyEl.classList.add('d-none');
        tbody.innerHTML = list.map(function (entry) {
            return '<tr>' +
                '<td>' + escapeHtml(formatHistoryDate(entry.date)) + '</td>' +
                '<td>' + escapeHtml(actionLabel(entry.action)) + '</td>' +
                '<td>' + escapeHtml(entry.user || '—') + '</td>' +
                '<td class="small">' + escapeHtml(entry.details || '—') + '</td>' +
                '</tr>';
        }).join('');
    }

    function populateAdminBoardVehicleSelect() {
        const select = document.getElementById('adminBoardVehicleSelect');
        if (!select) return;
        const vehicles = getVehicles();
        const current = select.value;
        let options = '<option value="">Выберите ТС...</option>';
        vehicles.forEach(function (v) {
            var id = v.id != null ? String(v.id) : '';
            var name = (v.name || v.grnz || 'ТС') + (v.grnz ? ' (' + v.grnz + ')' : '');
            options += '<option value="' + id + '">' + escapeHtml(name) + '</option>';
        });
        select.innerHTML = options;
        if (current && vehicles.some(function (v) { return String(v.id) === String(current); })) select.value = current;
    }

    function renderAdminReport(data) {
        const wrap = document.getElementById('adminReportTableWrap');
        const head = document.getElementById('adminReportTableHead');
        const body = document.getElementById('adminReportTableBody');
        const titleEl = document.getElementById('adminReportTitle');
        const emptyEl = document.getElementById('adminReportEmpty');
        if (!wrap || !head || !body) return;
        if (!data || !data.headers || !data.headers.length) {
            if (titleEl) titleEl.textContent = '';
            head.innerHTML = '';
            body.innerHTML = '';
            wrap.classList.add('d-none');
            if (emptyEl) { emptyEl.classList.remove('d-none'); emptyEl.textContent = 'Выберите тип отчёта.'; }
            return;
        }
        if (titleEl) titleEl.textContent = data.title || '';
        head.innerHTML = '<tr>' + data.headers.map(function (h) { return '<th>' + escapeHtml(h) + '</th>'; }).join('') + '</tr>';
        body.innerHTML = data.rows && data.rows.length
            ? data.rows.map(function (row) {
                return '<tr>' + row.map(function (cell) { return '<td>' + escapeHtml(String(cell)) + '</td>'; }).join('') + '</tr>';
            }).join('')
            : '<tr><td colspan="' + data.headers.length + '" class="text-center text-muted">Нет записей</td></tr>';
        wrap.classList.remove('d-none');
        if (emptyEl) emptyEl.classList.add('d-none');
    }

    function parseDate(str) {
        if (!str) return null;
        const d = new Date(str);
        return isNaN(d.getTime()) ? null : d;
    }

    function formatDate(date) {
        if (!date) return '—';
        const d = date instanceof Date ? date : new Date(date);
        if (isNaN(d.getTime())) return '—';
        return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function daysFromToday(date) {
        if (!date) return null;
        const d = date instanceof Date ? date : new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        d.setHours(0, 0, 0, 0);
        return Math.ceil((d - today) / (24 * 60 * 60 * 1000));
    }

    function deadlineClass(deadlineStr) {
        const d = parseDate(deadlineStr);
        if (!d) return '';
        const days = daysFromToday(d);
        if (days < 0) return 'text-danger fw-bold';
        if (days <= DAYS_DEADLINE_WARNING) return 'text-warning fw-bold';
        return '';
    }

    function countDeadlinesInDays(deadlineKey, days) {
        const vehicles = getVehicles();
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const end = new Date(today);
        end.setDate(end.getDate() + days);
        let count = 0;
        vehicles.forEach(v => {
            const d = parseDate(v[deadlineKey]);
            if (!d) return;
            d.setHours(0, 0, 0, 0);
            if (d >= today && d <= end) count++;
        });
        return count;
    }

    function showLogin() {
        document.getElementById('loginScreen').classList.remove('d-none');
        document.getElementById('appScreen').classList.add('d-none');
    }

    async function showApp(user) {
        document.getElementById('loginScreen').classList.add('d-none');
        document.getElementById('appScreen').classList.remove('d-none');
        document.getElementById('currentUser').textContent = user.name || user.login;
        var adminLink = document.getElementById('adminPanelLink');
        if (adminLink) adminLink.classList.toggle('d-none', !user.is_admin && user.login !== 'admin');
        await loadVehicles(vehicleSearchQuery);
        await loadRents(rentSearchQuery);
        await loadSpares(spareSearchQuery);
        renderTable();
        updateDeadlineCounts();
        populateRentVehicleSelect();
        renderRentTable();
        populateSpareVehicleSelect();
        renderSpareTable();
    }

    function formatDateRange(startStr, endStr, endClass) {
        const start = formatDate(startStr);
        const end = formatDate(endStr);
        if (start === '—' && end === '—') return '<span class="cell-empty">—</span>';
        if (start === '—') return '<span class="' + endClass + '">' + end + '</span>';
        if (end === '—') return start;
        return start + ' – ' + (endClass ? '<span class="' + endClass + '">' + end + '</span>' : end);
    }

    function renderTable() {
        const vehicles = getVehicles();
        const filteredVehicles = vehicles.filter(v => vehicleMatchesQuery(v, vehicleSearchQuery));
        const tbody = document.getElementById('vehiclesTableBody');
        function fmtMh(val) {
            const n = val != null && val !== '' ? Number(val) : null;
            if (n != null && !isNaN(n)) return String(n);
            return '<span class="cell-empty">—</span>';
        }
        function emptyCell(val) {
            if (val == null || val === '' || val === '—') return '<span class="cell-empty">—</span>';
            return escapeHtml(String(val));
        }
        function mhDueClass(current, next) {
            const c = current != null ? Number(current) : null;
            const n = next != null ? Number(next) : null;
            if (c == null || n == null || isNaN(c) || isNaN(n)) return '';
            return c >= n ? 'text-warning fw-bold' : '';
        }
        function untilNextTO(current, next1, next2, next3) {
            const c = current != null ? Number(current) : null;
            if (c == null || isNaN(c)) return { text: '<span class="cell-empty">—</span>', isDue: false };
            const n1 = next1 != null ? Number(next1) : null;
            const n2 = next2 != null ? Number(next2) : null;
            const n3 = next3 != null ? Number(next3) : null;
            if (n1 != null && !isNaN(n1) && c < n1) return { text: String(Math.round(n1 - c)), isDue: false };
            if (n2 != null && !isNaN(n2) && c < n2) return { text: String(Math.round(n2 - c)), isDue: true };
            if (n3 != null && !isNaN(n3) && c < n3) return { text: String(Math.round(n3 - c)), isDue: true };
            if (n1 != null && c >= n1) return { text: 'Пора ТО!', isDue: true };
            return { text: '<span class="cell-empty">—</span>', isDue: false };
        }
        if (vehicles.length === 0) {
            tbody.innerHTML = '<tr><td colspan="18" class="text-center text-muted py-4">Нет данных. Нажмите «Добавить ТС».</td></tr>';
            renderVehiclesCards([]);
            return;
        }
        if (filteredVehicles.length === 0) {
            tbody.innerHTML = '<tr><td colspan="18" class="text-center text-muted py-4">Ничего не найдено.</td></tr>';
            renderVehiclesCards([]);
            return;
        }
        tbody.innerHTML = filteredVehicles.map(v => {
                const insClass = deadlineClass(v.insuranceDeadline);
                const techClass = deadlineClass(v.techDeadline);
                const taxClass = deadlineClass(v.taxDeadline);
                const insuranceCell = formatDateRange(v.insuranceDate, v.insuranceDeadline, insClass);
                const techCell = formatDateRange(v.techDate, v.techDeadline, techClass);
                const taxCell = formatDateRange(v.taxDate, v.taxDeadline, taxClass);
                const mhBase = fmtMh(v.motorHoursBase);
                const mhNext1 = fmtMh(v.motorHoursNext1);
                const mhNext2 = fmtMh(v.motorHoursNext2);
                const mhNext3 = fmtMh(v.motorHoursNext3);
                const mhCurrent = fmtMh(v.motorHours);
                const next1Class = mhDueClass(v.motorHours, v.motorHoursNext1);
                const next2Class = mhDueClass(v.motorHours, v.motorHoursNext2);
                const next3Class = mhDueClass(v.motorHours, v.motorHoursNext3);
                const untilTO = untilNextTO(v.motorHours, v.motorHoursNext1, v.motorHoursNext2, v.motorHoursNext3);
                const untilTOClass = untilTO.isDue ? 'text-warning fw-bold' : '';
                return `
<tr data-id="${v.id}">
    <td class="cell-name">${escapeHtml(v.name)}</td>
    <td class="p-1"><input type="text" class="form-control form-control-sm application-input" value="${escapeHtml(v.application || '')}" placeholder="Не указано" data-id="${v.id}"></td>
    <td class="cell-owner">${escapeHtml(v.owner)}</td>
    <td><span class="grnz-plate">${escapeHtml(v.grnz || '')}</span></td>
    <td>${emptyCell(v.consumptionRate)}</td>
    <td>${emptyCell(v.dieselFuel)}</td>
    <td class="cell-num">${emptyCell(v.year)}</td>
    <td class="mh-cell">${mhBase}</td>
    <td class="mh-cell ${next1Class}">${mhNext1}</td>
    <td class="mh-cell ${next2Class}">${mhNext2}</td>
    <td class="mh-cell ${next3Class}">${mhNext3}</td>
    <td class="mh-cell">${mhCurrent}</td>
    <td class="mh-cell ${untilTOClass}" title="Моточасов до следующего ТО">${untilTO.text}</td>
    <td class="${insClass}">${insuranceCell}</td>
    <td class="${techClass}">${techCell}</td>
    <td class="${taxClass}">${taxCell}</td>
    <td>${emptyCell(v.location)}</td>
    <td class="actions-cell">
        <div class="row-actions">
            <button type="button" class="btn btn-sm btn-outline-success mh-add-btn" title="Добавить моточасы"><i class="bi bi-plus-lg"></i></button>
            <button type="button" class="btn btn-sm btn-outline-primary edit-btn" title="Редактировать"><i class="bi bi-pencil"></i></button>
            <button type="button" class="btn btn-sm btn-outline-danger delete-btn" title="Удалить"><i class="bi bi-trash"></i></button>
        </div>
    </td>
</tr>`;
            }).join('');

        bindVehicleRowActions(tbody);
        renderVehiclesCards(filteredVehicles);
    }

    function renderVehiclesCards(list) {
        const cards = document.getElementById('vehiclesCards');
        if (!cards) return;
        if (!list || list.length === 0) {
            cards.innerHTML = '<div class="text-center text-muted py-3">Нет данных для отображения.</div>';
            return;
        }
        cards.innerHTML = list.map(function (v) {
            const insClass = deadlineClass(v.insuranceDeadline);
            const untilTO = (function () {
                const c = v.motorHours != null ? Number(v.motorHours) : null;
                if (c == null || isNaN(c)) return { text: '—', isDue: false };
                const n1 = v.motorHoursNext1 != null ? Number(v.motorHoursNext1) : null;
                const n2 = v.motorHoursNext2 != null ? Number(v.motorHoursNext2) : null;
                const n3 = v.motorHoursNext3 != null ? Number(v.motorHoursNext3) : null;
                if (n1 != null && !isNaN(n1) && c < n1) return { text: String(Math.round(n1 - c)), isDue: false };
                if (n2 != null && !isNaN(n2) && c < n2) return { text: String(Math.round(n2 - c)), isDue: true };
                if (n3 != null && !isNaN(n3) && c < n3) return { text: String(Math.round(n3 - c)), isDue: true };
                if (n1 != null && c >= n1) return { text: 'Пора ТО!', isDue: true };
                return { text: '—', isDue: false };
            })();
            const untilClass = untilTO.isDue ? 'text-warning fw-bold' : '';
            return `
<article class="vehicle-card" data-id="${v.id}">
  <div class="vehicle-card__head">
    <div>
      <h3 class="vehicle-card__title">${escapeHtml(v.name)}</h3>
      <div class="vehicle-card__meta">${escapeHtml(v.owner || '—')}</div>
    </div>
    <span class="grnz-plate">${escapeHtml(v.grnz || '')}</span>
  </div>
  <div class="vehicle-card__grid">
    <div><span class="vehicle-card__label">Локация</span>${escapeHtml(v.location || '—')}</div>
    <div><span class="vehicle-card__label">Моточасы</span>${v.motorHours != null ? escapeHtml(String(v.motorHours)) : '—'}</div>
    <div><span class="vehicle-card__label">До след. ТО</span><span class="${untilClass}">${escapeHtml(untilTO.text)}</span></div>
    <div><span class="vehicle-card__label">Страховка</span><span class="${insClass}">${escapeHtml(formatDate(v.insuranceDeadline))}</span></div>
  </div>
  <div class="vehicle-card__actions">
    <button type="button" class="btn btn-sm btn-outline-success mh-add-btn"><i class="bi bi-plus-lg"></i> Моточас</button>
    <button type="button" class="btn btn-sm btn-outline-primary edit-btn"><i class="bi bi-pencil"></i></button>
    <button type="button" class="btn btn-sm btn-outline-danger delete-btn"><i class="bi bi-trash"></i></button>
  </div>
</article>`;
        }).join('');
        bindVehicleRowActions(cards);
    }

    function bindVehicleRowActions(root) {
        if (!root) return;
        root.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.closest('[data-id]').dataset.id;
                openModal(id);
            });
        });
        root.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.closest('[data-id]').dataset.id;
                if (confirm('Удалить это транспортное средство?')) deleteVehicle(id);
            });
        });
        root.querySelectorAll('.mh-add-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.closest('[data-id]').dataset.id;
                openMotorHoursModal(id);
            });
        });
        root.querySelectorAll('.application-input').forEach(function (inp) {
            inp.addEventListener('blur', function () {
                const id = this.dataset.id;
                if (!id) return;
                const val = this.value.trim() || null;
                apiPatch('vehicles.php?id=' + encodeURIComponent(id), { application: val }).then(function () {
                    var v = getVehicles().find(function (x) { return x.id === id; });
                    if (v) v.application = val;
                }).catch(function () {});
            });
        });
    }

    function escapeHtml(str) {
        if (str == null) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function updateDeadlineCounts() {
        document.getElementById('deadlineInsuranceCount').textContent = countDeadlinesInDays('insuranceDeadline', DAYS_DEADLINE_WARNING);
        document.getElementById('deadlineTechCount').textContent = countDeadlinesInDays('techDeadline', DAYS_DEADLINE_WARNING);
        document.getElementById('deadlineTaxCount').textContent = countDeadlinesInDays('taxDeadline', DAYS_DEADLINE_WARNING);
    }

    // ——— Отчёты ———
    let reportsModal;
    let currentReportData = null;

    function untilNextTOValue(v) {
        const c = v.motorHours != null ? Number(v.motorHours) : null;
        if (c == null || isNaN(c)) return null;
        const n1 = v.motorHoursNext1 != null ? Number(v.motorHoursNext1) : null;
        const n2 = v.motorHoursNext2 != null ? Number(v.motorHoursNext2) : null;
        const n3 = v.motorHoursNext3 != null ? Number(v.motorHoursNext3) : null;
        if (n1 != null && !isNaN(n1) && c < n1) return Math.round(n1 - c);
        if (n2 != null && !isNaN(n2) && c < n2) return Math.round(n2 - c);
        if (n3 != null && !isNaN(n3) && c < n3) return Math.round(n3 - c);
        if (n1 != null && c >= n1) return 0;
        return null;
    }

    function buildReportDeadlines() {
        const vehicles = getVehicles();
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const end = new Date(today);
        end.setDate(end.getDate() + DAYS_DEADLINE_WARNING);
        const headers = ['Наименование', 'Собственник', 'ГРНЗ', 'Тип', 'Дедлайн', 'Дней осталось'];
        const rows = [];
        vehicles.forEach(function (v) {
            [
                { key: 'insuranceDeadline', type: 'Страховка' },
                { key: 'techDeadline', type: 'Тех. осмотр' },
                { key: 'taxDeadline', type: 'Налог' }
            ].forEach(function (item) {
                const d = parseDate(v[item.key]);
                if (!d) return;
                d.setHours(0, 0, 0, 0);
                if (d < today || (d >= today && d <= end)) {
                    const days = Math.ceil((d - today) / (24 * 60 * 60 * 1000));
                    rows.push([
                        v.name || '—',
                        v.owner || '—',
                        v.grnz || '—',
                        item.type,
                        formatDate(v[item.key]),
                        days < 0 ? 'Просрочено' : String(days)
                    ]);
                }
            });
        });
        rows.sort(function (a, b) {
            const d1 = a[4], d2 = b[4];
            return String(d1).localeCompare(String(d2));
        });
        return { title: 'Дедлайны (страховка, техосмотр, налог) — ближайшие 30 дней', headers: headers, rows: rows };
    }

    function buildReportByDeadlineType(config) {
        const vehicles = getVehicles();
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const end = new Date(today);
        end.setDate(end.getDate() + DAYS_DEADLINE_WARNING);
        const headers = ['Наименование', 'Собственник', 'ГРНЗ', 'Дедлайн', 'Дней осталось'];
        const rows = [];

        vehicles.forEach(function (v) {
            const d = parseDate(v[config.key]);
            if (!d) return;
            d.setHours(0, 0, 0, 0);
            if (d < today || (d >= today && d <= end)) {
                const days = Math.ceil((d - today) / (24 * 60 * 60 * 1000));
                rows.push([
                    v.name || '—',
                    v.owner || '—',
                    v.grnz || '—',
                    formatDate(v[config.key]),
                    days < 0 ? 'Просрочено' : String(days)
                ]);
            }
        });

        rows.sort(function (a, b) {
            return String(a[3]).localeCompare(String(b[3]));
        });

        return { title: config.title, headers: headers, rows: rows };
    }

    function buildReportTax() {
        return buildReportByDeadlineType({
            key: 'taxDeadline',
            title: 'Отчет по налогу — ближайшие 30 дней'
        });
    }

    function buildReportInsurance() {
        return buildReportByDeadlineType({
            key: 'insuranceDeadline',
            title: 'Отчет по страховке — ближайшие 30 дней'
        });
    }

    function buildReportTech() {
        return buildReportByDeadlineType({
            key: 'techDeadline',
            title: 'Отчет по ТО — ближайшие 30 дней'
        });
    }

    function buildReportMotohours() {
        const vehicles = getVehicles();
        const headers = ['Наименование', 'Собственник', 'ГРНЗ', 'Текущий м/ч', 'След. ТО 1', 'До след. ТО (м/ч)'];
        const rows = [];
        vehicles.forEach(function (v) {
            const current = v.motorHours != null ? Number(v.motorHours) : null;
            const next1 = v.motorHoursNext1 != null ? Number(v.motorHoursNext1) : null;
            const until = untilNextTOValue(v);
            if (current == null && until == null) return;
            rows.push([
                v.name || '—',
                v.owner || '—',
                v.grnz || '—',
                current != null ? current : '—',
                next1 != null ? next1 : '—',
                until != null ? (until === 0 ? 'Пора ТО!' : until) : '—'
            ]);
        });
        rows.sort(function (a, b) {
            const u1 = a[5], u2 = b[5];
            if (u1 === 'Пора ТО!' && u2 !== 'Пора ТО!') return -1;
            if (u1 !== 'Пора ТО!' && u2 === 'Пора ТО!') return 1;
            if (typeof u1 === 'number' && typeof u2 === 'number') return u1 - u2;
            return 0;
        });
        return { title: 'Моточасы — до следующего ТО', headers: headers, rows: rows };
    }

    function buildReportSummary() {
        const vehicles = getVehicles();
        const headers = ['Наименование', 'Собственник', 'ГРНЗ', 'Год', 'Текущий м/ч', 'До след. ТО', 'Страховка', 'Тех. осмотр', 'Налог', 'Находится', 'Применение'];
        const rows = vehicles.map(function (v) {
            const until = untilNextTOValue(v);
            return [
                v.name || '—',
                v.owner || '—',
                v.grnz || '—',
                v.year || '—',
                v.motorHours != null ? v.motorHours : '—',
                until != null ? (until === 0 ? 'Пора ТО!' : until) : '—',
                formatDate(v.insuranceDeadline) || '—',
                formatDate(v.techDeadline) || '—',
                formatDate(v.taxDeadline) || '—',
                v.location || '—',
                v.application || '—'
            ];
        });
        return { title: 'Сводка по транспортным средствам', headers: headers, rows: rows };
    }

    function buildReportSpares() {
        const spares = getSpares().slice();
        spares.sort(function (a, b) {
            return String(a.date || '').localeCompare(String(b.date || ''), undefined, { numeric: true });
        });
        const headers = ['ТС (наименование и кому)', 'Название запчасти', 'Количество', 'Сумма (₸)', 'Дата'];
        const rows = spares.map(function (s) {
            const nameAndOwner = (s.vehicleName || 'ТС') + (s.vehicleOwner ? ' — ' + s.vehicleOwner : '');
            const amountStr = s.amount != null && !isNaN(s.amount) ? formatMoney(s.amount) : '—';
            return [
                nameAndOwner,
                s.spareName || '—',
                s.quantity != null && s.quantity !== '' ? String(s.quantity) : '—',
                amountStr,
                formatDate(s.date) || '—'
            ];
        });
        const totalAmount = spares.reduce(function (sum, s) {
            const amt = s.amount != null && !isNaN(s.amount) ? Number(s.amount) : 0;
            return sum + amt;
        }, 0);
        return {
            title: 'Отчет по запчастям' + (spares.length ? ' (записей: ' + spares.length + ', итого: ' + formatMoney(totalAmount) + ')' : ''),
            headers: headers,
            rows: rows
        };
    }

    function buildReportRent() {
        const rents = getRents().slice();
        rents.sort(function (a, b) {
            return String(a.startDate || '').localeCompare(String(b.startDate || ''), undefined, { numeric: true });
        });
        const headers = ['ТС', 'ГРНЗ', 'Арендатор', 'Начало', 'Окончание', 'Стоимость', 'Приход', 'Прибыль', 'Диз топливу', 'Статус'];
        const rows = rents.map(function (r) {
            const vehicleText = (r.vehicleName || 'ТС') + (r.vehicleGrnz ? ' (' + r.vehicleGrnz + ')' : '');
            const totalStr = r.total != null && !isNaN(r.total) ? formatMoney(r.total) : '—';
            const incomeStr = r.income != null && !isNaN(r.income) ? formatMoney(r.income) : '—';
            const profitVal = r.profit != null ? r.profit : (r.income != null && r.total != null ? r.income - r.total : null);
            const profitStr = profitVal != null ? formatMoney(profitVal) : '—';
            const dieselStr = r.dieselCost != null && !isNaN(r.dieselCost) ? formatMoney(r.dieselCost) : '—';
            return [
                vehicleText,
                r.vehicleGrnz || '—',
                r.tenant || '—',
                formatDate(r.startDate) || '—',
                formatDate(r.endDate) || '—',
                totalStr,
                incomeStr,
                profitStr,
                dieselStr,
                r.status || 'Активна'
            ];
        });
        const totalIncome = rents.reduce(function (sum, r) {
            const v = r.income != null && !isNaN(r.income) ? Number(r.income) : 0;
            return sum + v;
        }, 0);
        const totalProfit = rents.reduce(function (sum, r) {
            const v = r.profit != null ? r.profit : (r.income != null && r.total != null ? r.income - r.total : 0);
            return sum + (typeof v === 'number' && !isNaN(v) ? v : 0);
        }, 0);
        return {
            title: 'Отчёт по аренде' + (rents.length ? ' (записей: ' + rents.length + ', приход: ' + formatMoney(totalIncome) + ', прибыль: ' + formatMoney(totalProfit) + ')' : ''),
            headers: headers,
            rows: rows
        };
    }

    function renderReportTable(data) {
        const titleEl = document.getElementById('reportTitle');
        const headEl = document.getElementById('reportTableHead');
        const bodyEl = document.getElementById('reportTableBody');
        const emptyEl = document.getElementById('reportEmpty');
        const wrapEl = document.getElementById('reportTableWrap');
        if (!data || !data.headers.length) {
            titleEl.textContent = '';
            headEl.innerHTML = '';
            bodyEl.innerHTML = '';
            wrapEl.classList.add('d-none');
            emptyEl.classList.remove('d-none');
            emptyEl.textContent = 'Нет данных для отчёта.';
            return;
        }
        titleEl.textContent = data.title;
        headEl.innerHTML = '<tr>' + data.headers.map(function (h) { return '<th>' + escapeHtml(h) + '</th>'; }).join('') + '</tr>';
        bodyEl.innerHTML = data.rows.length === 0
            ? '<tr><td colspan="' + data.headers.length + '" class="text-center text-muted">Нет записей</td></tr>'
            : data.rows.map(function (row) {
                return '<tr>' + row.map(function (cell) { return '<td>' + escapeHtml(String(cell)) + '</td>'; }).join('') + '</tr>';
            }).join('');
        wrapEl.classList.remove('d-none');
        emptyEl.classList.add('d-none');
    }

    function openReportsModal() {
        currentReportData = null;
        document.getElementById('reportTitle').textContent = 'Выберите тип отчёта';
        document.getElementById('reportTableHead').innerHTML = '';
        document.getElementById('reportTableBody').innerHTML = '';
        document.getElementById('reportTableWrap').classList.remove('d-none');
        document.getElementById('reportEmpty').classList.add('d-none');
        document.getElementById('reportEmpty').textContent = 'Нет данных для отчёта.';
        document.getElementById('reportDownloadExcel').disabled = true;
        reportsModal.show();
    }

    async function runReport(reportType) {
        let data;
        try {
            data = await apiGet('reports.php?type=' + encodeURIComponent(reportType));
        } catch (e) {
            alert(e.message || 'Ошибка загрузки отчёта');
            return;
        }
        if (!data || !data.headers) return;
        currentReportData = data;
        renderReportTable(data);
        document.getElementById('reportDownloadExcel').disabled = !data || !data.rows || data.rows.length === 0;
    }

    function downloadReportExcel() {
        if (!currentReportData || typeof XLSX === 'undefined') return;
        const data = currentReportData;
        const aoa = [data.headers].concat(data.rows);
        const ws = XLSX.utils.aoa_to_sheet(aoa);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Отчёт');
        const name = 'отчет_' + new Date().toISOString().slice(0, 10) + '.xlsx';
        XLSX.writeFile(wb, name);
    }

    async function deleteVehicle(id) {
        const v = getVehicles().find(x => x.id === id);
        try {
            await apiDelete('vehicles.php?id=' + encodeURIComponent(id));
        } catch (e) {
            alert(e.message || 'Ошибка удаления');
            return;
        }
        await loadVehicles(vehicleSearchQuery);
        renderTable();
        updateDeadlineCounts();
        populateRentVehicleSelect();
    }

    let motorHoursModal;

    function getUntilNextTOText(v) {
        const current = v.motorHours != null ? Number(v.motorHours) : null;
        if (current == null || isNaN(current)) return '—';
        const n1 = v.motorHoursNext1 != null ? Number(v.motorHoursNext1) : null;
        const n2 = v.motorHoursNext2 != null ? Number(v.motorHoursNext2) : null;
        const n3 = v.motorHoursNext3 != null ? Number(v.motorHoursNext3) : null;
        if (n1 != null && !isNaN(n1) && current < n1) return Math.round(n1 - current);
        if (n2 != null && !isNaN(n2) && current < n2) return Math.round(n2 - current);
        if (n3 != null && !isNaN(n3) && current < n3) return Math.round(n3 - current);
        if (n1 != null && current >= n1) return 'Пора ТО!';
        return '—';
    }

    function formatHistoryDate(isoStr) {
        if (!isoStr) return '—';
        const d = new Date(isoStr);
        if (isNaN(d.getTime())) return isoStr;
        return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
            ' ' + d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    }

    function renderMotorHoursHistory(history) {
        const listEl = document.getElementById('mhHistoryList');
        if (!listEl) return;
        const list = Array.isArray(history) ? history : [];
        if (list.length === 0) {
            listEl.innerHTML = '<span class="text-muted">Записей пока нет</span>';
            return;
        }
        listEl.innerHTML = list.slice().reverse().map(function (h) {
            const dateStr = formatHistoryDate(h.date);
            const amount = h.amount != null ? Number(h.amount) : 0;
            const total = h.totalAfter != null ? Number(h.totalAfter) : '—';
            return '<div class="d-flex justify-content-between py-1 border-bottom border-light"><span>' + escapeHtml(dateStr) + ' <span class="text-success">+' + amount + '</span></span><span class="text-muted">→ ' + total + ' м/ч</span></div>';
        }).join('');
    }

    async function openMotorHoursModal(vehicleId) {
        const v = getVehicles().find(x => x.id === vehicleId);
        if (!v) return;
        let fullVehicle = v;
        try {
            fullVehicle = await apiGet('vehicles.php?id=' + encodeURIComponent(vehicleId));
        } catch (_) { }
        document.getElementById('mhVehicleId').value = vehicleId;
        document.getElementById('mhVehicleName').textContent = fullVehicle.name || fullVehicle.grnz || 'ТС';
        const current = fullVehicle.motorHours != null && fullVehicle.motorHours !== '' ? Number(fullVehicle.motorHours) : 0;
        document.getElementById('mhCurrentValue').textContent = current;
        document.getElementById('mhUntilTO').textContent = getUntilNextTOText(fullVehicle);
        document.getElementById('mhAddAmount').value = '1';
        renderMotorHoursHistory(fullVehicle.motorHoursHistory || []);
        motorHoursModal.show();
    }

    async function applyMotorHoursAdd() {
        const id = document.getElementById('mhVehicleId').value;
        const addEl = document.getElementById('mhAddAmount');
        const addVal = parseFloat(addEl.value);
        if (!id || isNaN(addVal) || addVal < 0) return;
        try {
            const res = await apiPost('motor_hours.php', { vehicleId: id, amount: addVal });
            document.getElementById('mhCurrentValue').textContent = res.motorHours;
            const v = getVehicles().find(x => x.id === id);
            if (v) v.motorHours = res.motorHours;
            document.getElementById('mhUntilTO').textContent = getUntilNextTOText(v || { motorHours: res.motorHours });
            renderMotorHoursHistory(res.history || []);
        } catch (e) {
            alert(e.message || 'Ошибка');
            return;
        }
        await loadVehicles(vehicleSearchQuery);
        renderTable();
        updateDeadlineCounts();
    }

    let vehicleModal;
    let vehicleForm;
    let saveVehicleBtn;
    let rentModal;

    function openModal(editId) {
        const title = document.getElementById('vehicleModalTitle');
        const form = document.getElementById('vehicleForm');
        form.reset();
        document.getElementById('vehicleId').value = editId || '';

        if (editId) {
            const v = getVehicles().find(x => x.id === editId);
            if (v) {
                title.innerHTML = '<i class="bi bi-pencil-square text-primary me-1"></i> Редактировать ТС';
                document.getElementById('vName').value = v.name || '';
                document.getElementById('vOwner').value = v.owner || '';
                document.getElementById('vGrnz').value = v.grnz || '';
                document.getElementById('vConsumption').value = v.consumptionRate || '';
                document.getElementById('vDieselFuel').value = v.dieselFuel || '';
                document.getElementById('vYear').value = v.year || '';
                document.getElementById('vMotorHoursBase').value = v.motorHoursBase != null && v.motorHoursBase !== '' ? v.motorHoursBase : '';
                document.getElementById('vMotorHoursNext1').value = v.motorHoursNext1 != null && v.motorHoursNext1 !== '' ? v.motorHoursNext1 : '';
                document.getElementById('vMotorHoursNext2').value = v.motorHoursNext2 != null && v.motorHoursNext2 !== '' ? v.motorHoursNext2 : '';
                document.getElementById('vMotorHoursNext3').value = v.motorHoursNext3 != null && v.motorHoursNext3 !== '' ? v.motorHoursNext3 : '';
                document.getElementById('vMotorHours').value = v.motorHours != null && v.motorHours !== '' ? v.motorHours : '';
                document.getElementById('vAddMotorHours').value = '';
                document.getElementById('vLocation').value = v.location || '';
                document.getElementById('vInsuranceDate').value = v.insuranceDate || '';
                document.getElementById('vInsuranceDeadline').value = v.insuranceDeadline || '';
                document.getElementById('vTechDate').value = v.techDate || '';
                document.getElementById('vTechDeadline').value = v.techDeadline || '';
                document.getElementById('vTaxDate').value = v.taxDate || '';
                document.getElementById('vTaxDeadline').value = v.taxDeadline || '';
            }
        } else {
            title.innerHTML = '<i class="bi bi-truck text-primary me-1"></i> Добавить транспортное средство';
        }
        vehicleModal.show();
    }

    async function saveVehicle() {
        const idEl = document.getElementById('vehicleId');
        const id = idEl.value.trim();
        const currentMotorHoursRaw = document.getElementById('vMotorHours').value.trim();
        const addMotorHoursRaw = document.getElementById('vAddMotorHours').value.trim();
        let motorHours = currentMotorHoursRaw !== '' ? parseFloat(currentMotorHoursRaw) : null;
        if (addMotorHoursRaw !== '') {
            const addVal = parseFloat(addMotorHoursRaw);
            if (!isNaN(addVal)) {
                motorHours = (motorHours != null && !isNaN(motorHours) ? motorHours : 0) + addVal;
            }
        }
        const motorHoursBaseRaw = document.getElementById('vMotorHoursBase').value.trim();
        const motorHoursNext1Raw = document.getElementById('vMotorHoursNext1').value.trim();
        const motorHoursNext2Raw = document.getElementById('vMotorHoursNext2').value.trim();
        const motorHoursNext3Raw = document.getElementById('vMotorHoursNext3').value.trim();
        const parseMh = (s) => { const n = s !== '' ? parseFloat(s) : null; return n != null && !isNaN(n) ? n : null; };

        let base = parseMh(motorHoursBaseRaw);
        let next1 = parseMh(motorHoursNext1Raw);
        let next2 = parseMh(motorHoursNext2Raw);
        let next3 = parseMh(motorHoursNext3Raw);
        if (motorHours != null && !isNaN(motorHours)) {
            if (base == null) base = motorHours;
            if (next1 == null) next1 = base + MOTOHOURS_TO_INTERVAL;
            if (next2 == null) next2 = base + MOTOHOURS_TO_INTERVAL * 2;
            if (next3 == null) next3 = base + MOTOHOURS_TO_INTERVAL * 3;
        }

        const item = {
            name: document.getElementById('vName').value.trim(),
            owner: document.getElementById('vOwner').value.trim(),
            grnz: document.getElementById('vGrnz').value.trim(),
            consumptionRate: document.getElementById('vConsumption').value.trim() || null,
            dieselFuel: document.getElementById('vDieselFuel').value.trim() || null,
            year: document.getElementById('vYear').value || null,
            motorHoursBase: base,
            motorHoursNext1: next1,
            motorHoursNext2: next2,
            motorHoursNext3: next3,
            motorHours: motorHours != null && !isNaN(motorHours) ? motorHours : null,
            application: document.querySelector('.application-input[data-id="' + id + '"]') ? document.querySelector('.application-input[data-id="' + id + '"]').value.trim() || null : null,
            location: document.getElementById('vLocation').value.trim() || null,
            insuranceDate: document.getElementById('vInsuranceDate').value || null,
            insuranceDeadline: document.getElementById('vInsuranceDeadline').value || null,
            techDate: document.getElementById('vTechDate').value || null,
            techDeadline: document.getElementById('vTechDeadline').value || null,
            taxDate: document.getElementById('vTaxDate').value || null,
            taxDeadline: document.getElementById('vTaxDeadline').value || null
        };
        try {
            if (id) {
                await apiPut('vehicles.php?id=' + encodeURIComponent(id), item);
            } else {
                await apiPost('vehicles.php', item);
            }
        } catch (e) {
            alert(e.message || 'Ошибка сохранения');
            return;
        }
        vehicleModal.hide();
        await loadVehicles(vehicleSearchQuery);
        renderTable();
        updateDeadlineCounts();
        populateRentVehicleSelect();
    }

    function generateId() {
        return 'v_' + Date.now() + '_' + Math.random().toString(36).slice(2, 9);
    }

    function excelDateToStr(val) {
        if (val == null || val === '') return null;
        if (typeof val === 'string') {
            val = val.trim();
            const m = val.match(/^(\d{1,2})[.\/](\d{1,2})[.\/](\d{2,4})$/);
            if (m) {
                const y = m[3].length === 2 ? 2000 + parseInt(m[3], 10) : parseInt(m[3], 10);
                return y + '-' + m[2].padStart(2, '0') + '-' + m[1].padStart(2, '0');
            }
            if (val.match(/^\d{4}-\d{2}-\d{2}/)) return val.slice(0, 10);
            return null;
        }
        if (typeof val === 'number' && val > 1000) {
            const d = new Date((val - 25569) * 86400 * 1000);
            const y = d.getUTCFullYear(), m = d.getUTCMonth() + 1, day = d.getUTCDate();
            return y + '-' + String(m).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        }
        return null;
    }

    // ——— Калькуляция аренды ———

    function parseMoneyInput(value) {
        if (value == null || value === '') return 0;
        var cleaned = String(value)
            .replace(/\s+/g, '')
            .replace(/[^0-9.,-]/g, '')
            .replace(',', '.');
        var n = parseFloat(cleaned);
        return isNaN(n) ? 0 : n;
    }

    function formatMoney(value) {
        if (!value || isNaN(value)) return '—';
        try {
            return value.toLocaleString('ru-RU') + ' ₸';
        } catch (_) {
            return value + ' ₸';
        }
    }

    function recalcRentTotal() {
        var initial = parseMoneyInput(document.getElementById('rentInitialCost') && document.getElementById('rentInitialCost').value);
        var other = parseMoneyInput(document.getElementById('rentOtherCost') && document.getElementById('rentOtherCost').value);
        var to = parseMoneyInput(document.getElementById('rentToCost') && document.getElementById('rentToCost').value);
        var salary = parseMoneyInput(document.getElementById('rentSalaryCost') && document.getElementById('rentSalaryCost').value);
        var diesel = parseMoneyInput(document.getElementById('rentDieselCost') && document.getElementById('rentDieselCost').value);
        var total = initial - other - to - salary - diesel;
        var totalEl = document.getElementById('rentTotal');
        var totalRawEl = document.getElementById('rentTotalRaw');
        if (totalEl) totalEl.textContent = total !== 0 ? formatMoney(total) : '—';
        if (totalRawEl) totalRawEl.value = total !== 0 ? total : '';

        var incomeInput = document.getElementById('rentIncome');
        var income = incomeInput ? parseMoneyInput(incomeInput.value) : 0;
        var incomeDisplay = document.getElementById('rentIncomeDisplay');
        var profitEl = document.getElementById('rentProfit');
        if (incomeDisplay) incomeDisplay.textContent = income !== 0 ? formatMoney(income) : '—';
        if (profitEl) {
            var profit = income !== 0 || total !== 0 ? income - total : null;
            profitEl.textContent = profit != null ? formatMoney(profit) : '—';
            profitEl.classList.toggle('text-success', profit != null && profit > 0);
            profitEl.classList.toggle('text-danger', profit != null && profit < 0);
        }
    }

    function populateRentVehicleSelect() {
        var select = document.getElementById('rentVehicleSelect');
        if (!select) return;
        var vehicles = getVehicles();
        var current = select.value;
        var options = '<option value="">Выберите ТС...</option>';
        vehicles.forEach(function (v) {
            var name = v.name || v.grnz || 'Без названия';
            var grnz = v.grnz ? ' (' + v.grnz + ')' : '';
            options += '<option value="' + v.id + '">' + escapeHtml(name + grnz) + '</option>';
        });
        select.innerHTML = options;
        if (current && vehicles.some(function (v) { return v.id === current; })) {
            select.value = current;
        }
    }

    function renderRentTable() {
        var tbody = document.getElementById('rentTableBody');
        if (!tbody) return;
        var rents = getRents();
        var filteredRents = rents.filter(function (r) {
            return rentMatchesQuery(r, rentSearchQuery);
        });
        if (!rents.length) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-3">Записей аренды пока нет.</td></tr>';
            return;
        }
        if (!filteredRents.length) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-3">Ничего не найдено.</td></tr>';
            return;
        }
        tbody.innerHTML = filteredRents.map(function (r) {
            var vehicleText = (r.vehicleName || 'ТС') + (r.vehicleGrnz ? ' (' + r.vehicleGrnz + ')' : '');
            var dieselCell = r.dieselCost != null ? formatMoney(r.dieselCost) : '—';
            var incomeCell = r.income != null ? formatMoney(r.income) : '—';
            var profitVal = r.profit != null ? r.profit : (r.income != null && r.total != null ? r.income - r.total : null);
            var profitCell = profitVal != null ? formatMoney(profitVal) : '—';
            var profitClass = profitVal != null && profitVal > 0 ? ' text-success' : (profitVal != null && profitVal < 0 ? ' text-danger' : '');
            return '\
<tr data-id="' + r.id + '">\
  <td>' + escapeHtml(vehicleText) + '</td>\
  <td>' + escapeHtml(r.tenant || '—') + '</td>\
  <td>' + formatDate(r.startDate) + '</td>\
  <td>' + formatDate(r.endDate) + '</td>\
  <td>' + (r.total != null ? formatMoney(r.total) : '—') + '</td>\
  <td>' + incomeCell + '</td>\
  <td class="' + profitClass + '">' + profitCell + '</td>\
  <td>' + dieselCell + '</td>\
  <td>' + escapeHtml(r.status || 'Активна') + '</td>\
  <td><button type="button" class="btn btn-sm btn-outline-danger rent-delete-btn">Удалить</button></td>\
</tr>';
        }).join('');

        tbody.querySelectorAll('.rent-delete-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.closest('tr').dataset.id;
                if (id && confirm('Удалить запись аренды?')) {
                    deleteRent(id);
                }
            });
        });
    }

    async function saveRent() {
        var vehicleSelect = document.getElementById('rentVehicleSelect');
        if (!vehicleSelect || !vehicleSelect.value) {
            alert('Выберите транспортное средство из парка.');
            return;
        }
        var vehicleId = vehicleSelect.value;
        var vehicle = getVehicles().find(function (v) { return v.id === vehicleId; });
        if (!vehicle) {
            alert('Выбранное ТС не найдено в парке.');
            return;
        }
        var tenantEl = document.getElementById('rentTenant');
        var startEl = document.getElementById('rentStartDate');
        var endEl = document.getElementById('rentEndDate');
        var totalRawEl = document.getElementById('rentTotalRaw');
        var dieselCostEl = document.getElementById('rentDieselCost');
        var incomeEl = document.getElementById('rentIncome');
        var tenant = tenantEl ? tenantEl.value.trim() : '';
        var startDate = startEl ? (startEl.value || null) : null;
        var endDate = endEl ? (endEl.value || null) : null;
        var total = totalRawEl && totalRawEl.value !== '' ? parseFloat(totalRawEl.value) : null;
        var income = incomeEl && incomeEl.value ? parseMoneyInput(incomeEl.value) : null;
        var profit = (income != null && total != null) ? income - total : (income != null || total != null ? (income || 0) - (total || 0) : null);
        var dieselCost = dieselCostEl && dieselCostEl.value ? parseMoneyInput(dieselCostEl.value) : null;
        if (!tenant) { alert('Укажите арендатора.'); return; }
        if (!startDate) { alert('Укажите дату начала аренды.'); return; }
        var item = { vehicleId: vehicleId, tenant: tenant, startDate: startDate, endDate: endDate, total: total, income: income != null && !isNaN(income) ? income : null, profit: profit != null && !isNaN(profit) ? profit : null, dieselCost: dieselCost != null && !isNaN(dieselCost) ? dieselCost : null, status: 'Активна' };
        var res = null;
        try {
            res = await apiPost('rents.php', item);
        } catch (e) {
            alert(e.message || 'Ошибка сохранения');
            return;
        }
        if (pendingTenderCommit && pendingTenderCommit.type === 'rent' && res && res.id) {
            setTenderCommitted(pendingTenderCommit.rowKey, 'rent', res.id);
            pendingTenderCommit = null;
            if (lastTenderResults && lastTenderResults.length) {
                renderTenderCompareResults(lastTenderResults, lastTenderMultiSheet);
            }
        }
        await loadRents(rentSearchQuery);
        renderRentTable();
        if (rentModal) rentModal.hide();
        resetRentForm();
        var rentTitleEl = document.getElementById('rentModalTitle');
        if (rentTitleEl) rentTitleEl.innerHTML = '<i class="bi bi-calendar-check text-primary"></i> Добавить аренду';
    }

    function resetRentForm() {
        var ids = ['rentVehicleSelect', 'rentInitialCost', 'rentOtherCost', 'rentToCost', 'rentSalaryCost', 'rentDieselCost', 'rentTenant', 'rentStartDate', 'rentEndDate', 'rentIncome'];
        ids.forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            if (el.tagName === 'SELECT') el.value = '';
            else el.value = '';
        });
        var totalEl = document.getElementById('rentTotal');
        var totalRawEl = document.getElementById('rentTotalRaw');
        var incomeDisplay = document.getElementById('rentIncomeDisplay');
        var profitEl = document.getElementById('rentProfit');
        if (totalEl) totalEl.textContent = '—';
        if (totalRawEl) totalRawEl.value = '';
        if (incomeDisplay) incomeDisplay.textContent = '—';
        if (profitEl) {
            profitEl.textContent = '—';
            profitEl.classList.remove('text-success', 'text-danger');
        }
    }

    async function deleteRent(id) {
        try {
            await apiDelete('rents.php?id=' + encodeURIComponent(id));
        } catch (e) {
            alert(e.message || 'Ошибка удаления');
            return;
        }
        await loadRents(rentSearchQuery);
        renderRentTable();
    }

    // ——— Запчасти ———

    function populateSpareVehicleSelect() {
        var select = document.getElementById('spareVehicleSelect');
        if (!select) return;
        var vehicles = getVehicles();
        var current = select.value;
        var options = '<option value="">Выберите ТС (наименование и кому)...</option>';
        vehicles.forEach(function (v) {
            var name = v.name || v.grnz || 'Без названия';
            var owner = v.owner ? ' — ' + v.owner : '';
            options += '<option value="' + v.id + '">' + escapeHtml(name + owner) + '</option>';
        });
        select.innerHTML = options;
        if (current && vehicles.some(function (v) { return v.id === current; })) {
            select.value = current;
        }
    }

    function renderSpareTable() {
        var tbody = document.getElementById('spareTableBody');
        if (!tbody) return;
        var spares = getSpares();
        var filtered = spares.filter(function (s) { return spareMatchesQuery(s, spareSearchQuery); });
        if (!spares.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Записей запчастей пока нет.</td></tr>';
            return;
        }
        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Ничего не найдено.</td></tr>';
            return;
        }
        tbody.innerHTML = filtered.map(function (s) {
            var nameAndOwner = (s.vehicleName || 'ТС') + (s.vehicleOwner ? ' — ' + s.vehicleOwner : '');
            var amountCell = s.amount != null ? formatMoney(s.amount) : '—';
            return '<tr data-id="' + s.id + '">' +
                '<td>' + escapeHtml(nameAndOwner) + '</td>' +
                '<td>' + escapeHtml(s.spareName || '—') + '</td>' +
                '<td>' + (s.quantity != null && s.quantity !== '' ? escapeHtml(String(s.quantity)) : '—') + '</td>' +
                '<td>' + amountCell + '</td>' +
                '<td>' + formatDate(s.date) + '</td>' +
                '<td>' +
                '<button type="button" class="btn btn-sm btn-outline-primary spare-edit-btn" title="Редактировать"><i class="bi bi-pencil"></i></button> ' +
                '<button type="button" class="btn btn-sm btn-outline-danger spare-delete-btn" title="Удалить"><i class="bi bi-trash"></i></button>' +
                '</td></tr>';
        }).join('');

        tbody.querySelectorAll('.spare-edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.closest('tr').dataset.id;
                if (id) openSpareModal(id);
            });
        });
        tbody.querySelectorAll('.spare-delete-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.closest('tr').dataset.id;
                if (id && confirm('Удалить запись о запчасти?')) deleteSpare(id);
            });
        });
    }

    function openSpareModal(editId, fromTenderItem) {
        if (fromTenderItem && !editId) {
            pendingTenderCommit = { rowKey: tenderRowDecisionKey(fromTenderItem), type: 'spare' };
        } else {
            pendingTenderCommit = null;
        }
        var titleEl = document.getElementById('spareModalTitle');
        if (titleEl) {
            if (editId) titleEl.textContent = 'Редактировать запчасть';
            else if (fromTenderItem) titleEl.textContent = 'Запчасть по позиции тендера';
            else titleEl.textContent = 'Добавить запчасть';
        }
        document.getElementById('spareId').value = editId || '';
        document.getElementById('spareVehicleSelect').value = '';
        var spareNameEl = document.getElementById('spareName');
        if (spareNameEl) spareNameEl.value = '';
        document.getElementById('spareQuantity').value = '1';
        document.getElementById('spareAmount').value = '';
        document.getElementById('spareDate').value = '';
        if (editId) {
            var spares = getSpares();
            var s = spares.find(function (x) { return x.id === editId; });
            if (s) {
                document.getElementById('spareId').value = s.id;
                document.getElementById('spareVehicleSelect').value = s.vehicleId || '';
                if (spareNameEl) spareNameEl.value = s.spareName || '';
                document.getElementById('spareQuantity').value = s.quantity != null ? s.quantity : 1;
                document.getElementById('spareAmount').value = s.amount != null ? String(s.amount).replace(/\s/g, '') : '';
                document.getElementById('spareDate').value = s.date || '';
            }
        } else {
            var today = new Date().toISOString().slice(0, 10);
            document.getElementById('spareDate').value = today;
            if (fromTenderItem) {
                var row = fromTenderItem.row;
                if (spareNameEl) spareNameEl.value = (row.name || '').trim();
                var q = parseTenderMoney(row.quantity);
                document.getElementById('spareQuantity').value = (q != null && !isNaN(q) && q > 0) ? q : 1;
                var amt = parseTenderMoney(row.total);
                var amtField = document.getElementById('spareAmount');
                if (amtField) amtField.value = amt != null ? String(amt) : '';
            }
        }
        populateSpareVehicleSelect();
        if (editId) {
            var sel = document.getElementById('spareVehicleSelect');
            if (sel) sel.value = (getSpares().find(function (x) { return x.id === editId; }) || {}).vehicleId || '';
        }
        if (window.spareModal) window.spareModal.show();
    }

    async function saveSpare() {
        var vehicleSelect = document.getElementById('spareVehicleSelect');
        if (!vehicleSelect || !vehicleSelect.value) {
            alert('Выберите ТС из списка (наименование и кому).');
            return;
        }
        var vehicleId = vehicleSelect.value;
        var vehicle = getVehicles().find(function (v) { return v.id === vehicleId; });
        if (!vehicle) {
            alert('Выбранное ТС не найдено.');
            return;
        }
        var editId = (document.getElementById('spareId') || {}).value.trim();
        var quantityRaw = document.getElementById('spareQuantity').value;
        var quantity = quantityRaw !== '' ? (parseFloat(quantityRaw) || 0) : null;
        var amountRaw = document.getElementById('spareAmount').value;
        var amount = amountRaw ? parseMoneyInput(amountRaw) : null;
        if (amount !== 0 && (amount == null || isNaN(amount))) amount = null;
        var date = (document.getElementById('spareDate') || {}).value || null;
        var spareName = (document.getElementById('spareName') || {}).value.trim() || '';
        var item = { vehicleId: vehicleId, spareName: spareName, quantity: quantity, amount: amount, date: date };
        var res = null;
        try {
            if (editId) {
                res = await apiPut('spares.php?id=' + encodeURIComponent(editId), item);
            } else {
                res = await apiPost('spares.php', item);
            }
        } catch (e) {
            alert(e.message || 'Ошибка сохранения');
            return;
        }
        if (!editId && pendingTenderCommit && pendingTenderCommit.type === 'spare' && res && res.id) {
            setTenderCommitted(pendingTenderCommit.rowKey, 'spare', res.id);
            pendingTenderCommit = null;
            if (lastTenderResults && lastTenderResults.length) {
                renderTenderCompareResults(lastTenderResults, lastTenderMultiSheet);
            }
        }
        await loadSpares(spareSearchQuery);
        renderSpareTable();
        if (window.spareModal) window.spareModal.hide();
    }

    async function deleteSpare(id) {
        try {
            await apiDelete('spares.php?id=' + encodeURIComponent(id));
        } catch (e) {
            alert(e.message || 'Ошибка удаления');
            return;
        }
        await loadSpares(spareSearchQuery);
        renderSpareTable();
    }

    function setupSearchHandlers() {
        var vehicleSearchInput = document.getElementById('vehicleSearchInput');
        var vehicleSearchClearBtn = document.getElementById('vehicleSearchClearBtn');
        if (vehicleSearchInput) {
            vehicleSearchInput.addEventListener('input', function () {
                vehicleSearchQuery = normalizeSearchValue(this.value);
                renderTable();
            });
        }
        if (vehicleSearchClearBtn) {
            vehicleSearchClearBtn.addEventListener('click', function () {
                vehicleSearchQuery = '';
                if (vehicleSearchInput) vehicleSearchInput.value = '';
                renderTable();
            });
        }

        var rentSearchInput = document.getElementById('rentSearchInput');
        var rentSearchClearBtn = document.getElementById('rentSearchClearBtn');
        if (rentSearchInput) {
            rentSearchInput.addEventListener('input', function () {
                rentSearchQuery = normalizeSearchValue(this.value);
                renderRentTable();
            });
        }
        if (rentSearchClearBtn) {
            rentSearchClearBtn.addEventListener('click', function () {
                rentSearchQuery = '';
                if (rentSearchInput) rentSearchInput.value = '';
                renderRentTable();
            });
        }

        var spareSearchInput = document.getElementById('spareSearchInput');
        var spareSearchClearBtn = document.getElementById('spareSearchClearBtn');
        if (spareSearchInput) {
            spareSearchInput.addEventListener('input', function () {
                spareSearchQuery = normalizeSearchValue(this.value);
                renderSpareTable();
            });
        }
        if (spareSearchClearBtn) {
            spareSearchClearBtn.addEventListener('click', function () {
                spareSearchQuery = '';
                if (spareSearchInput) spareSearchInput.value = '';
                renderSpareTable();
            });
        }
    }

    function normalizeTenderMatchString(s) {
        return String(s || '')
            .toLowerCase()
            .replace(/ё/g, 'е')
            .replace(/[^a-zа-яёії0-9]+/gi, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function tenderNameMatchesVehicle(tenderName, vehicle) {
        const b = normalizeTenderMatchString(tenderName);
        const a = normalizeTenderMatchString(vehicle.name || '');
        const g = normalizeTenderMatchString(vehicle.grnz || '');
        if (!b || b.length < 2) return false;
        if (g && g.length >= 4 && (b.indexOf(g) !== -1 || g.indexOf(b) !== -1)) return true;
        if (!a) return false;
        if (a === b) return true;
        if (a.length >= 5 && b.indexOf(a) !== -1) return true;
        if (b.length >= 5 && a.indexOf(b) !== -1) return true;
        const wordsA = a.split(' ').filter(function (w) { return w.length > 2; });
        const wordsB = b.split(' ').filter(function (w) { return w.length > 2; });
        const setA = {};
        wordsA.forEach(function (w) { setA[w] = 1; });
        let common = 0;
        wordsB.forEach(function (w) { if (setA[w]) common++; });
        if (common >= 2) return true;
        if (common === 1 && wordsB.length === 1) return true;
        if (common === 1 && wordsA.length === 1 && wordsB.length <= 3) return true;
        return false;
    }

    function findVehicleForTenderRow(tenderName, tenderGrnz, vehicles) {
        if (tenderGrnz) {
            const tg = String(tenderGrnz).trim().toLowerCase().replace(/\s+/g, '');
            for (let i = 0; i < vehicles.length; i++) {
                const v = vehicles[i];
                if (v.grnz && String(v.grnz).trim().toLowerCase().replace(/\s+/g, '') === tg) return v;
            }
        }
        for (let i = 0; i < vehicles.length; i++) {
            if (tenderNameMatchesVehicle(tenderName, vehicles[i])) return vehicles[i];
        }
        return null;
    }

    function isLikelyTenderTotalRow(name) {
        const n = normalizeTenderMatchString(name);
        if (!n) return true;
        const s = String(name || '').trim();
        if (/^(итого|всего|разом|всего\s)/i.test(s)) return true;
        if (/всего\s+по\s+смете/i.test(s)) return true;
        if (n.indexOf('итого') === 0 || n === 'всего' || n.indexOf('всего затрат') === 0) return true;
        return false;
    }

    /** Строки-заголовки разделов сметы РК (не ресурсы). */
    function isLikelyTenderSectionRow(name) {
        const s = String(name || '').trim();
        if (s.length < 2) return false;
        if (/^раздел\s+\d|^подраздел\s+[\d.]+/i.test(s)) return true;
        if (/^всего\s+по\s+(разделу|подразделу)/i.test(s)) return true;
        return false;
    }

    /**
     * Колонки типового «Локального сметного расчёта» РК: №, Шифр, Наименование работ и затрат,
     * Ед. изм., Количество, Стоимость единицы, Общая стоимость.
     */
    function mapHeaderColumnsFromRow(row) {
        let numCol = -1;
        let nameCol = -1;
        let nameScore = -1;
        let codeCol = -1;
        let qtyCol = -1;
        let unitCol = -1;
        let priceUnitCol = -1;
        let totalCol = -1;
        let rentCol = -1;
        for (let c = 0; c < row.length; c++) {
            const raw = String(row[c] != null ? row[c] : '');
            const t = raw.toLowerCase().replace(/\s+/g, ' ').trim();
            if (!t) continue;
            if (/наименование\s+работ\s+и\s+затрат/i.test(t)) {
                nameCol = c;
                nameScore = 100;
                continue;
            }
            if (/номер\s+по\s+порядку|№\s*п\/?\s*п|^\s*№\s*п\/\s*п\s*$/i.test(t) || /^п\s*\/\s*п$/i.test(t)) numCol = c;
            if (/шифр\s+позиции|шифр\s+норматива|^шифр$/i.test(t)) codeCol = c;
            if (/^количество$/i.test(t) || /^кол\s*[-–]?\s*во$/i.test(t)) qtyCol = c;
            if (/единица\s+измерения|ед\.\s*изм\.?$/i.test(t) || /^ед\.?\s*изм/i.test(t)) unitCol = c;
            if (/стоимость\s+единицы|цена\s+за\s+ед/i.test(t)) priceUnitCol = c;
            if (/общая\s+стоимость|стоимость.*тенге/i.test(t)) totalCol = c;
            else if (totalCol < 0 && /^всего$/i.test(t) && c >= 4) totalCol = c;
            const isRent = /аренд|прокат|лизинг/i.test(t);
            if (isRent && rentCol < 0) rentCol = c;
        }
        for (let c = 0; c < row.length; c++) {
            const raw = String(row[c] != null ? row[c] : '');
            const t = raw.toLowerCase().replace(/\s+/g, ' ').trim();
            if (!t) continue;
            if (/^шифр$/i.test(t) || /^№\s*$/i.test(t) || /^п\s*\/\s*п$/i.test(t)) continue;
            if (nameScore >= 100) break;
            const isName = /наимен|содержание\s+работ|техничес|оборудован|машин|механизм|ресурс(ов)?|подряд|вид\s+работ|материал|позици/i.test(raw) ||
                /наименование/i.test(t);
            let sc = 0;
            if (isName) sc = 3 + Math.min(raw.length, 40) / 10;
            if (/наименование\s+работ/i.test(t)) sc += 5;
            if (isName && sc > nameScore) {
                nameScore = sc;
                nameCol = c;
            }
        }
        if (qtyCol < 0) {
            for (let c = 0; c < row.length; c++) {
                const t = String(row[c] != null ? row[c] : '').toLowerCase().replace(/\s+/g, ' ').trim();
                if (/кол\s*[-–]?\s*во|количество|объём|объем|норма/i.test(t)) {
                    qtyCol = c;
                    break;
                }
            }
        }
        var priceCol = priceUnitCol >= 0 ? priceUnitCol : -1;
        if (priceUnitCol < 0 && totalCol < 0) {
            for (let c = 0; c < row.length; c++) {
                const t = String(row[c] != null ? row[c] : '').toLowerCase().replace(/\s+/g, ' ').trim();
                if ((/цена|стоимость|закуп|тариф|расцен|сумма/i.test(t) && !/итого|общая/i.test(t))) {
                    priceCol = c;
                    break;
                }
            }
        }
        return {
            numCol: numCol,
            nameCol: nameCol,
            codeCol: codeCol,
            qtyCol: qtyCol,
            unitCol: unitCol,
            priceUnitCol: priceUnitCol,
            totalCol: totalCol,
            priceCol: priceCol,
            rentCol: rentCol
        };
    }

    function findTenderHeaderInAoa(aoa) {
        let best = {
            headerRow: -1,
            numCol: -1,
            nameCol: 0,
            codeCol: -1,
            qtyCol: -1,
            unitCol: -1,
            priceUnitCol: -1,
            totalCol: -1,
            priceCol: -1,
            rentCol: -1,
            score: -1
        };
        for (let r = 0; r < Math.min(150, aoa.length); r++) {
            const row = aoa[r];
            if (!row) continue;
            const m = mapHeaderColumnsFromRow(row);
            let score = 0;
            if (m.nameCol >= 0) score += 6;
            if (m.qtyCol >= 0) score += 3;
            if (m.numCol >= 0) score += 1;
            if (m.codeCol >= 0) score += 2;
            if (m.unitCol >= 0) score += 2;
            if (m.priceUnitCol >= 0) score += 2;
            if (m.totalCol >= 0) score += 2;
            if (m.priceCol >= 0 && m.priceUnitCol < 0) score += 1;
            const nonEmpty = row.filter(function (x) { return String(x || '').trim() !== ''; }).length;
            score += Math.min(nonEmpty, 10) * 0.25;
            if (m.nameCol >= 0 && score > best.score) {
                best = {
                    headerRow: r,
                    numCol: m.numCol,
                    nameCol: m.nameCol,
                    codeCol: m.codeCol,
                    qtyCol: m.qtyCol,
                    unitCol: m.unitCol,
                    priceUnitCol: m.priceUnitCol,
                    totalCol: m.totalCol,
                    priceCol: m.priceCol,
                    rentCol: m.rentCol,
                    score: score
                };
            }
        }
        if (best.headerRow < 0) {
            const fallback = /наимен|содержание\s+работ|техник|оборуд|машин|ресурс|материал|позици/i;
            for (let r = 0; r < Math.min(100, aoa.length); r++) {
                const row = aoa[r];
                if (!row) continue;
                for (let c = 0; c < row.length; c++) {
                    const cell = String(row[c] || '').trim();
                    if (fallback.test(cell) && !/^шифр$/i.test(cell)) {
                        return {
                            headerRow: r,
                            numCol: -1,
                            nameCol: c,
                            codeCol: -1,
                            qtyCol: -1,
                            unitCol: -1,
                            priceUnitCol: -1,
                            totalCol: -1,
                            priceCol: -1,
                            rentCol: -1
                        };
                    }
                }
            }
        }
        return {
            headerRow: best.headerRow,
            numCol: best.numCol,
            nameCol: best.nameCol >= 0 ? best.nameCol : 0,
            codeCol: best.codeCol,
            qtyCol: best.qtyCol,
            unitCol: best.unitCol,
            priceUnitCol: best.priceUnitCol,
            totalCol: best.totalCol,
            priceCol: best.priceCol,
            rentCol: best.rentCol
        };
    }

    function rowHasDataBesidesNameCol(row, nameCol) {
        if (!row) return false;
        for (let c = 0; c < row.length; c++) {
            if (c === nameCol) continue;
            if (String(row[c] != null ? row[c] : '').trim() !== '') return true;
        }
        return false;
    }

    function extractTenderFromAoa(sheet) {
        const aoa = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '', raw: false });
        if (!aoa || !aoa.length) return [];
        const hdr = findTenderHeaderInAoa(aoa);
        const headerRow = hdr.headerRow;
        const numCol = hdr.numCol;
        let nameCol = hdr.nameCol;
        const codeCol = hdr.codeCol;
        let qtyCol = hdr.qtyCol;
        const unitCol = hdr.unitCol;
        const priceUnitCol = hdr.priceUnitCol;
        const totalCol = hdr.totalCol;
        let priceCol = hdr.priceCol;
        let rentCol = hdr.rentCol;
        const out = [];
        if (headerRow >= 0) {
            var badSamples = 0;
            for (let sr = headerRow + 1; sr < Math.min(headerRow + 18, aoa.length); sr++) {
                const srow = aoa[sr];
                if (!srow) continue;
                const sc = String(srow[nameCol] != null ? srow[nameCol] : '').trim();
                if (!sc || sc.length < 4 || /^[\d\s.,№\-–]+$/i.test(sc)) badSamples++;
            }
            if (badSamples >= 6) {
                let altBest = -1;
                let altScore = 0;
                var lens = aoa.slice(headerRow + 1, headerRow + 30).map(function (rw) { return rw ? rw.length : 0; });
                var rowLen = lens.length ? Math.max.apply(null, lens) : 0;
                for (let c = 0; c < Math.min(rowLen || 20, 15); c++) {
                    let textCells = 0;
                    for (let r = headerRow + 1; r < Math.min(headerRow + 45, aoa.length); r++) {
                        const row = aoa[r];
                        if (!row) continue;
                        const cell = String(row[c] != null ? row[c] : '').trim();
                        if (cell.length >= 10 && /[а-яё]{4,}/i.test(cell) && !/^[\d\s.,]+$/.test(cell)) textCells++;
                    }
                    if (textCells > altScore) {
                        altScore = textCells;
                        altBest = c;
                    }
                }
                if (altBest >= 0 && altScore > 4) nameCol = altBest;
            }
            let lastName = '';
            for (let r = headerRow + 1; r < aoa.length; r++) {
                const row = aoa[r];
                if (!row) continue;
                let name = String(row[nameCol] != null ? row[nameCol] : '').replace(/\s+/g, ' ').trim();
                if ((!name || name.length < 2) && lastName && rowHasDataBesidesNameCol(row, nameCol)) {
                    name = lastName;
                }
                if (!name || name.length < 2) continue;
                if (isLikelyTenderTotalRow(name) || isLikelyTenderSectionRow(name)) {
                    lastName = '';
                    continue;
                }
                if (/^[\d\s.,]+$/.test(name) && name.length < 12) continue;
                lastName = name;
                let codeStr = '';
                if (codeCol >= 0 && row[codeCol] !== undefined && String(row[codeCol]).trim() !== '') {
                    codeStr = String(row[codeCol]).trim();
                }
                let unitStr = '';
                if (unitCol >= 0 && row[unitCol] !== undefined && String(row[unitCol]).trim() !== '') {
                    unitStr = String(row[unitCol]).trim();
                }
                let qty = '';
                if (qtyCol >= 0 && row[qtyCol] !== undefined && row[qtyCol] !== '') {
                    qty = String(row[qtyCol]).trim();
                }
                if (!qty) {
                    const from = qtyCol >= 0 ? Math.max(nameCol + 1, qtyCol - 1) : nameCol + 1;
                    for (let qc = from; qc < Math.min(nameCol + 10, row.length); qc++) {
                        if (qtyCol >= 0 && qc === qtyCol) continue;
                        const q = String(row[qc] != null ? row[qc] : '').trim();
                        if (q && (/^[\d\s.,]+$/.test(q.replace(/\s/g, '')) || /^[\d]+\s*[.,]\s*[\d]+/.test(q))) {
                            qty = q;
                            break;
                        }
                    }
                }
                if (!qty && row[nameCol + 1] !== undefined && row[nameCol + 1] !== '') {
                    qty = String(row[nameCol + 1]).trim();
                }
                let priceU = '';
                let priceT = '';
                if (priceUnitCol >= 0 && row[priceUnitCol] !== undefined && String(row[priceUnitCol]).trim() !== '') {
                    priceU = String(row[priceUnitCol]).trim();
                }
                if (totalCol >= 0 && row[totalCol] !== undefined && String(row[totalCol]).trim() !== '') {
                    priceT = String(row[totalCol]).trim();
                }
                if (!priceU && priceCol >= 0 && row[priceCol] !== undefined && String(row[priceCol]).trim() !== '') {
                    priceU = String(row[priceCol]).trim();
                }
                var buyHintParts = [];
                if (unitStr) buyHintParts.push('ед: ' + unitStr);
                if (priceU) buyHintParts.push('цена ед: ' + priceU);
                if (priceT) buyHintParts.push('всего: ' + priceT);
                var buyHint = buyHintParts.join('; ');
                var rentHint = '';
                if (rentCol >= 0 && row[rentCol] !== undefined && String(row[rentCol]).trim() !== '') {
                    rentHint = String(row[rentCol]).trim();
                }
                var numStr = '';
                if (numCol >= 0 && row[numCol] !== undefined && String(row[numCol]).trim() !== '') {
                    numStr = String(row[numCol]).trim();
                }
                out.push({
                    rowNum: numStr,
                    code: codeStr,
                    name: name,
                    unit: unitStr,
                    quantity: qty,
                    priceUnit: priceU,
                    total: priceT,
                    grnz: '',
                    modeBlob: unitStr,
                    buyHint: buyHint,
                    rentHint: rentHint
                });
            }
            if (out.length) return out;
        }
        for (let r = 0; r < aoa.length; r++) {
            const row = aoa[r];
            if (!row || !row.length) continue;
            let best = '';
            for (let c = 0; c < row.length; c++) {
                const cell = String(row[c] != null ? row[c] : '').trim();
                if (!cell) continue;
                if (cell.length > best.length && /[а-яёa-z]/i.test(cell)) best = cell;
            }
            if (best.length >= 6 && !isLikelyTenderTotalRow(best) && !/^№\s*\d+$/i.test(best)) {
                out.push({
                    rowNum: '',
                    code: '',
                    name: best,
                    unit: '',
                    quantity: '',
                    priceUnit: '',
                    total: '',
                    grnz: '',
                    modeBlob: '',
                    buyHint: '',
                    rentHint: ''
                });
            }
        }
        return out;
    }

    function extractTenderRowsFromSheet(sheet) {
        const rows = XLSX.utils.sheet_to_json(sheet, { defval: '', raw: false });
        if (!rows.length) return extractTenderFromAoa(sheet);
        const keys = Object.keys(rows[0]);
        const onlyEmptyKeys = keys.every(function (k) { return /^__EMPTY/i.test(k); });
        if (onlyEmptyKeys) {
            const loose = [];
            let lastLooseName = '';
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const vals = keys.map(function (k) { return row[k]; }).map(function (v) { return v != null && v !== '' ? String(v).trim() : ''; }).filter(Boolean);
                let name = vals.reduce(function (best, cur) { return cur.length > (best || '').length ? cur : best; }, '');
                if ((!name || name.length < 2) && lastLooseName && vals.length) {
                    name = lastLooseName;
                }
                if (!name || name.length < 2 || isLikelyTenderTotalRow(name)) {
                    if (name && isLikelyTenderTotalRow(name)) lastLooseName = '';
                    continue;
                }
                lastLooseName = name;
                loose.push({
                    rowNum: '',
                    code: '',
                    name: name,
                    unit: '',
                    quantity: vals[1] || '',
                    priceUnit: '',
                    total: '',
                    grnz: '',
                    modeBlob: '',
                    buyHint: '',
                    rentHint: ''
                });
            }
            if (loose.length) return loose;
            return extractTenderFromAoa(sheet);
        }
        const nameHints = [
            'Наименование работ и затрат', 'Наименование', 'наименование', 'Наименование работ', 'Наименование работ и ресурсов', 'Техника', 'техника',
            'Машина', 'механизм', 'Оборудование', 'Ресурс', 'Позиция', 'наименование позиции',
            'Наименование и т', 'Вид работ', 'наименование оборудования', 'наименование и техническая',
            'Содержание', 'Содержание работ', 'обозначение', 'Наименование единицы'
        ];
        const qtyHints = ['Кол-во', 'Количество', 'кол', 'Норма', 'Объём', 'Объем', 'qty', 'QTY', 'объем работ'];
        const grnzHints = ['ГРНЗ', 'гос номер', 'Госномер', 'Гос. номер'];
        const modeHints = ['Способ', 'Вид поставки', 'Тип', 'условия', 'Форма', 'Источник'];
        const buyHints = [
            'Закупочная цена', 'Закуп', 'закупочная', 'Покупка', 'покупка',
            'цена ед', 'стоимость ед', 'цена поставки', 'Цена без', 'стоимость позиции', 'цена за единицу'
        ];
        const rentHints = [
            'Аренда', 'аренд', 'тариф', 'прокат', 'стоимость аренды', 'машино-час', 'машиночас',
            'стоимость маш', 'расценка'
        ];
        const numHints = ['Номер по порядку', '№ п/п', 'п/п'];
        const codeHints = ['Шифр позиции норматива', 'Шифр позиции', 'Шифр'];
        const unitHints = ['Единица измерения', 'Ед. изм.', 'Ед. изм', 'Единица'];
        const priceUnitHints = ['Стоимость единицы', 'Цена за ед.', 'Цена за ед'];
        const totalHints = ['Общая стоимость', 'Общая стоимость, тенге', 'Всего'];
        const out = [];
        let lastNameRow = '';
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            let name = getCell(row, keys, nameHints);
            if (!name || String(name).trim().length < 2) {
                const hasAnyCell = keys.some(function (k) {
                    const v = row[k];
                    return v != null && String(v).trim() !== '';
                });
                if (lastNameRow && hasAnyCell) name = lastNameRow;
            }
            if (!name || String(name).trim().length < 2) continue;
            if (isLikelyTenderTotalRow(name) || isLikelyTenderSectionRow(name)) {
                lastNameRow = '';
                continue;
            }
            lastNameRow = String(name).trim();
            const rowNum = getCell(row, keys, numHints);
            const code = getCell(row, keys, codeHints);
            const unit = getCell(row, keys, unitHints);
            const qty = getCell(row, keys, qtyHints);
            const priceU = getCell(row, keys, priceUnitHints);
            const total = getCell(row, keys, totalHints);
            const grnz = getCell(row, keys, grnzHints);
            const modeBlob = [
                getCell(row, keys, modeHints),
                getCell(row, keys, ['Примечание'])
            ].filter(Boolean).join(' ');
            var buy = getCell(row, keys, buyHints);
            const rent = getCell(row, keys, rentHints);
            if (!buy && (unit || priceU || total)) {
                const bp = [];
                if (unit) bp.push('ед: ' + unit);
                if (priceU) bp.push('цена ед: ' + priceU);
                if (total) bp.push('всего: ' + total);
                buy = bp.join('; ');
            }
            out.push({
                rowNum: rowNum || '',
                code: code || '',
                name: String(name).trim(),
                unit: unit || '',
                quantity: qty,
                priceUnit: priceU || '',
                total: total || '',
                grnz: grnz,
                modeBlob: modeBlob || unit || '',
                buyHint: buy,
                rentHint: rent
            });
        }
        if (out.length === 0) {
            return extractTenderFromAoa(sheet);
        }
        var aoaTry = extractTenderFromAoa(sheet);
        if (aoaTry.length > out.length && (out.length < 8 || aoaTry.length >= out.length + 3)) {
            return aoaTry;
        }
        return out;
    }

    function recommendFromTender(row) {
        const mb = normalizeTenderMatchString([row.modeBlob, row.unit, row.name].filter(Boolean).join(' '));
        function nonempty(v) {
            return v != null && String(v).trim() !== '' && !/^[\s\-–—]+$/.test(String(v));
        }
        const hasBuy = nonempty(row.buyHint) || nonempty(row.priceUnit) || nonempty(row.total);
        const hasRent = nonempty(row.rentHint);
        if (mb.indexOf('аренд') !== -1 || mb.indexOf('лизинг') !== -1 || mb.indexOf('прокат') !== -1) {
            return 'По смете: аренда / лизинг';
        }
        if (mb.indexOf('покуп') !== -1 || mb.indexOf('закуп') !== -1 || mb.indexOf('поставк') !== -1) {
            return 'По смете: покупка';
        }
        if (hasRent && !hasBuy) return 'Вероятно арендовать (в смете есть ставка аренды)';
        if (hasBuy && !hasRent) return 'Вероятно купить (в смете есть цена закупа)';
        if (hasRent && hasBuy) return 'В смете указаны и закуп, и аренда — выбрать вариант по тендеру';
        return 'Нет в парке — решить: купить или арендовать по условиям тендера';
    }

    function formatTenderPrices(row) {
        const parts = [];
        if (row.buyHint) {
            const b = String(row.buyHint).trim();
            if (/^ед:|цена ед:|всего:/i.test(b) || b.indexOf(';') >= 0) parts.push(b);
            else parts.push('закуп/покупка: ' + b);
        }
        if (row.rentHint) parts.push('аренда: ' + String(row.rentHint).trim());
        return parts.length ? parts.join('; ') : '—';
    }

    function tenderRowDecisionKey(r) {
        return String(r.index) + '|' + normalizeTenderMatchString(r.row.name) + '|' + String(r.row.code || '') + '|' + String(r.row.sheet || '');
    }

    function parseTenderMoney(val) {
        if (val == null || val === '') return null;
        var cleaned = String(val).replace(/\s+/g, '').replace(/\u00a0/g, '').replace(/,/g, '.');
        var n = parseFloat(cleaned.replace(/[^\d.\-]/g, ''));
        return isNaN(n) ? null : n;
    }

    function getTenderCommittedMap() {
        try {
            const raw = localStorage.getItem(TENDER_COMMITTED_STORAGE);
            const o = raw ? JSON.parse(raw) : {};
            return o && typeof o === 'object' ? o : {};
        } catch (_) {
            return {};
        }
    }

    function setTenderCommitted(rowKey, type, entityId) {
        const map = getTenderCommittedMap();
        map[rowKey] = { type: type, id: String(entityId) };
        try {
            localStorage.setItem(TENDER_COMMITTED_STORAGE, JSON.stringify(map));
        } catch (_) { /* quota */ }
    }

    function countTenderCommittedInResults(results, map) {
        let spare = 0;
        let rent = 0;
        results.forEach(function (r) {
            if (r.inPark) return;
            const k = tenderRowDecisionKey(r);
            const c = map[k];
            if (c && c.type === 'spare') spare++;
            else if (c && c.type === 'rent') rent++;
        });
        return { spare: spare, rent: rent };
    }

    function findTenderResultByIndex(idx) {
        if (!lastTenderResults) return null;
        var idxNum = typeof idx === 'number' && !isNaN(idx) ? idx : parseInt(idx, 10);
        if (isNaN(idxNum)) return null;
        for (var ti = 0; ti < lastTenderResults.length; ti++) {
            if (Number(lastTenderResults[ti].index) === idxNum) return lastTenderResults[ti];
        }
        return null;
    }

    function openSpareModalFromTender(idx) {
        var item = findTenderResultByIndex(idx);
        if (!item || item.inPark) return;
        openSpareModal(null, item);
    }

    function openRentModalFromTender(idx) {
        var item = findTenderResultByIndex(idx);
        if (!item || item.inPark) return;
        pendingTenderCommit = { rowKey: tenderRowDecisionKey(item), type: 'rent' };
        resetRentForm();
        populateRentVehicleSelect();
        var row = item.row;
        var total = parseTenderMoney(row.total);
        var initialEl = document.getElementById('rentInitialCost');
        if (initialEl) initialEl.value = total != null ? String(Math.round(total * 100) / 100) : '';
        recalcRentTotal();
        var startEl = document.getElementById('rentStartDate');
        if (startEl) startEl.value = new Date().toISOString().slice(0, 10);
        var tenantEl = document.getElementById('rentTenant');
        if (tenantEl) tenantEl.value = sessionUser && sessionUser.name ? String(sessionUser.name).trim() : '';
        var titleEl = document.getElementById('rentModalTitle');
        if (titleEl) titleEl.innerHTML = '<i class="bi bi-calendar-check text-primary"></i> Аренда по позиции тендера';
        if (rentModal) rentModal.show();
    }

    function switchMainTabById(tabButtonId) {
        var el = document.getElementById(tabButtonId);
        var Bs = typeof window !== 'undefined' ? window.bootstrap : null;
        if (el && Bs && Bs.Tab) {
            try {
                Bs.Tab.getOrCreateInstance(el).show();
            } catch (_) {
                try { el.click(); } catch (_) { /* ignore */ }
            }
        }
    }

    function renderTenderCompareResults(results, multiSheet) {
        const tbody = document.getElementById('tenderTableBody');
        const emptyEl = document.getElementById('tenderEmpty');
        const sumEl = document.getElementById('tenderSummary');
        if (!tbody) return;
        if (!results.length) {
            tbody.innerHTML = '';
            if (emptyEl) emptyEl.classList.remove('d-none');
            if (sumEl) {
                sumEl.classList.remove('d-none');
                sumEl.innerHTML = 'Файл прочитан, но позиции не распознаны. Нужна строка заголовков с колонкой <strong>«Наименование»</strong> / <strong>«Техника»</strong> (или таблица с первой строки листа без «красивых» заголовков — тогда берётся самая длинная ячейка в строке).';
            }
            return;
        }
        if (emptyEl) emptyEl.classList.add('d-none');
        lastTenderResults = results;
        lastTenderMultiSheet = multiSheet;
        const inPark = results.filter(function (r) { return r.inPark; }).length;
        const committedMap = getTenderCommittedMap();
        const cc = countTenderCommittedInResults(results, committedMap);
        const missing = results.length - inPark;
        if (sumEl) {
            sumEl.classList.remove('d-none');
            var decLine = '';
            if (missing > 0) {
                var committedN = cc.spare + cc.rent;
                decLine = ' Внесено в учёт (запчасти / аренда): <strong class="text-success">' + cc.spare + '</strong> / <strong class="text-info">' + cc.rent + '</strong>. Без записи: <strong class="text-muted">' + (missing - committedN) + '</strong>.';
            }
            sumEl.innerHTML = 'Строк в смете: <strong>' + results.length + '</strong>. В парке: <strong class="text-success">' + inPark + '</strong>. Не в парке: <strong class="text-danger">' + missing + '</strong>.' + decLine;
        }
        function tenderTableCell(v) {
            if (v == null || String(v).trim() === '') return '—';
            return escapeHtml(String(v).trim());
        }
        tbody.innerHTML = results.map(function (r) {
            const row = r.row;
            const npp = (row.rowNum !== undefined && String(row.rowNum).trim() !== '') ? tenderTableCell(row.rowNum) : String(r.index);
            const sheetNote = multiSheet && row.sheet ? ' <span class="text-muted small">(' + escapeHtml(row.sheet) + ')</span>' : '';
            const nameHtml = tenderTableCell(row.name) + sheetNote;
            const park = r.inPark ? '<span class="badge bg-success">Да</span>' : '<span class="badge bg-danger">Нет</span>';
            const matchV = r.vehicle ? escapeHtml(r.vehicle.name + (r.vehicle.owner ? ' — ' + r.vehicle.owner : '')) : '—';
            const rowKey = tenderRowDecisionKey(r);
            const committed = committedMap[rowKey];
            var actionCell = '<span class="text-muted small">—</span>';
            if (!r.inPark) {
                if (committed && committed.type === 'spare') {
                    actionCell = '<div class="small tender-committed-cell">' +
                        '<span class="text-success"><i class="bi bi-check-circle"></i> Запчасть №' + escapeHtml(committed.id) + '</span> ' +
                        '<button type="button" class="btn btn-link btn-sm p-0 tender-open-spares" data-entity-id="' + escapeHtml(committed.id) + '">Открыть</button>' +
                        '<div class="mt-1"><button type="button" class="btn btn-outline-primary btn-sm tender-dec-btn" data-tender-index="' + r.index + '" data-decision="buy" title="Добавить ещё запись">Ещё закупка</button></div></div>';
                } else if (committed && committed.type === 'rent') {
                    actionCell = '<div class="small tender-committed-cell">' +
                        '<span class="text-info"><i class="bi bi-check-circle"></i> Аренда №' + escapeHtml(committed.id) + '</span> ' +
                        '<button type="button" class="btn btn-link btn-sm p-0 tender-open-rents" data-entity-id="' + escapeHtml(committed.id) + '">Открыть</button>' +
                        '<div class="mt-1"><button type="button" class="btn btn-outline-info btn-sm tender-dec-btn" data-tender-index="' + r.index + '" data-decision="rent" title="Добавить ещё аренду">Ещё аренда</button></div></div>';
                } else {
                    actionCell = '<div class="d-flex flex-wrap gap-1 tender-decision-group" role="group">' +
                        '<button type="button" class="btn btn-outline-primary btn-sm tender-dec-btn" data-tender-index="' + r.index + '" data-decision="buy" title="Внести закупку в раздел «Запчасти»">Купить</button>' +
                        '<button type="button" class="btn btn-outline-info btn-sm tender-dec-btn" data-tender-index="' + r.index + '" data-decision="rent" title="Внести в раздел «Аренда»">Арендовать</button>' +
                        '</div>';
                }
            }
            return '<tr>' +
                '<td class="text-center text-nowrap">' + npp + '</td>' +
                '<td class="text-nowrap small">' + tenderTableCell(row.code) + '</td>' +
                '<td class="tender-col-name small">' + nameHtml + '</td>' +
                '<td class="text-nowrap small">' + tenderTableCell(row.unit) + '</td>' +
                '<td class="text-end text-nowrap small">' + tenderTableCell(row.quantity) + '</td>' +
                '<td class="text-end text-nowrap small">' + tenderTableCell(row.priceUnit) + '</td>' +
                '<td class="text-end text-nowrap small">' + tenderTableCell(row.total) + '</td>' +
                '<td class="text-center">' + park + '</td>' +
                '<td class="small">' + matchV + '</td>' +
                '<td class="text-nowrap">' + actionCell + '</td>' +
                '</tr>';
        }).join('');
    }

    async function runTenderCompare(arrayBuffer) {
        if (typeof XLSX === 'undefined') {
            throw new Error('Библиотека Excel (SheetJS) не загрузилась с интернета. Проверьте сеть и обновите страницу.');
        }
        var sumEl = document.getElementById('tenderSummary');
        if (sumEl) {
            sumEl.classList.remove('d-none');
            sumEl.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Чтение файла и сверка с парком…';
        }
        try {
            await loadVehicles(vehicleSearchQuery);
        } catch (loadErr) {
            console.warn(loadErr);
        }
        const fleet = Array.isArray(getVehicles()) ? getVehicles() : [];
        const data = new Uint8Array(arrayBuffer);
        var workbook;
        try {
            workbook = XLSX.read(data, { type: 'array' });
        } catch (readErr) {
            throw new Error('Не удалось открыть файл как Excel. Сохраните как .xlsx или .xls и попробуйте снова.');
        }
        if (!workbook.SheetNames.length) {
            throw new Error('В файле нет листов.');
        }
        const allRows = [];
        var sheetOrder = workbook.SheetNames.slice().sort(function (a, b) {
            function pri(n) {
                var l = String(n).toLowerCase();
                if (/смет|тендер|позици|ресурс|работ/i.test(l)) return 0;
                if (/лист\s*1|^sheet1$/i.test(l)) return 1;
                return 2;
            }
            return pri(a) - pri(b);
        });
        sheetOrder.forEach(function (sn) {
            const sh = workbook.Sheets[sn];
            const part = extractTenderRowsFromSheet(sh);
            part.forEach(function (r) { r.sheet = sn; allRows.push(r); });
        });
        const multiSheet = workbook.SheetNames.length > 1;
        const results = [];
        for (let i = 0; i < allRows.length; i++) {
            const row = allRows[i];
            const v = findVehicleForTenderRow(row.name, row.grnz, fleet);
            results.push({
                index: i + 1,
                row: row,
                vehicle: v,
                inPark: !!v,
                hint: v ? 'Есть в парке' : recommendFromTender(row),
                prices: formatTenderPrices(row)
            });
        }
        renderTenderCompareResults(results, multiSheet);
        var tenderTabBtn = document.getElementById('tender-tab');
        var Bs = typeof window !== 'undefined' ? window.bootstrap : null;
        if (tenderTabBtn && Bs && Bs.Tab) {
            try {
                Bs.Tab.getOrCreateInstance(tenderTabBtn).show();
            } catch (tabErr) {
                try { tenderTabBtn.click(); } catch (_) { /* ignore */ }
            }
        }
    }

    function tenderErrText(err) {
        if (err == null) return 'Неизвестная ошибка';
        if (typeof err === 'string') return err;
        if (err.message) return String(err.message);
        try { return String(err); } catch (_) { return 'Ошибка разбора'; }
    }

    function handleTenderXls(event) {
        const file = event.target.files[0];
        if (!file) return;
        const input = event.target;
        const reader = new FileReader();
        reader.onerror = function () {
            input.value = '';
            alert('Не удалось прочитать файл. Попробуйте другой файл или скопируйте смету в новый .xlsx.');
        };
        reader.onload = function () {
            var buf = reader.result;
            runTenderCompare(buf).then(function () {
                input.value = '';
            }).catch(function (err) {
                console.error(err);
                input.value = '';
                var sumEl = document.getElementById('tenderSummary');
                if (sumEl) {
                    sumEl.classList.remove('d-none');
                    sumEl.innerHTML = '<span class="text-danger">' + escapeHtml(tenderErrText(err)) + '</span>';
                }
                alert('Ошибка: ' + tenderErrText(err));
            });
        };
        reader.readAsArrayBuffer(file);
    }

    function parseDateRangeStr(str) {
        if (!str || typeof str !== 'string') return { start: null, end: null };
        const parts = str.split(/\s*[-–—]\s*/);
        if (parts.length >= 2) {
            return {
                start: excelDateToStr(parts[0].trim()),
                end: excelDateToStr(parts[1].trim())
            };
        }
        const single = excelDateToStr(str);
        return { start: single, end: single };
    }

    function getCell(row, keys, possibleNames) {
        const n = possibleNames.map(function (p) { return p.toLowerCase().trim(); });
        for (let i = 0; i < keys.length; i++) {
            const k = (keys[i] || '').toString().toLowerCase().trim();
            if (n.some(function (p) { return k === p || k.indexOf(p) >= 0 || p.indexOf(k) >= 0; })) {
                const v = row[keys[i]];
                return v != null && v !== '' ? String(v).trim() : null;
            }
        }
        return null;
    }

    async function handleImportXls(event) {
        const file = event.target.files[0];
        if (!file) return;
        event.target.value = '';

        const reader = new FileReader();
        reader.onload = async function (e) {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = typeof XLSX !== 'undefined' ? XLSX.read(data, { type: 'array' }) : null;
                if (!workbook || !workbook.SheetNames.length) {
                    alert('В файле нет листов.');
                    return;
                }
                const sheet = workbook.Sheets[workbook.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(sheet, { defval: '', raw: false });
                if (!rows.length) {
                    alert('На первом листе нет данных.');
                    return;
                }
                const keys = Object.keys(rows[0]);
                const nameAliases = ['Наименование', 'наименование'];
                const ownerAliases = ['Собственник', 'собственник'];
                const grnzAliases = ['ГРНЗ', 'грнз'];
                const consumptionAliases = ['Норма расхода', 'норма расхода'];
                const dieselFuelAliases = ['Диз топливу', 'диз топливу', 'Диз', 'диз'];
                const yearAliases = ['Год выпуска', 'Год', 'год выпуска', 'год'];
                const insuranceAliases = ['Страховка', 'страховка', 'Дата оформления страховки', 'Дедлайн страховки'];
                const techAliases = ['Дата прохождения тех. осмотра', 'Тех осмотр', 'тех осмотр', 'Дедлайн тех осмотра'];
                const taxAliases = ['Дата уплаты налога', 'налог', 'Налог', 'Дедлайн налога'];
                const locationAliases = ['Находится', 'находится'];
                const applicationAliases = ['Применение', 'применение'];

                const imported = [];

                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const name = getCell(row, keys, nameAliases) || getCell(row, keys, ['наименование']);
                    const owner = getCell(row, keys, ownerAliases) || getCell(row, keys, ['собственник']);
                    if (!name && !owner) continue;

                    const insuranceVal = getCell(row, keys, insuranceAliases);
                    const techVal = getCell(row, keys, techAliases);
                    const taxVal = getCell(row, keys, taxAliases);

                    let insuranceDate = excelDateToStr(getCell(row, keys, ['Дата оформления страховки']));
                    let insuranceDeadline = excelDateToStr(getCell(row, keys, ['Дедлайн страховки']));
                    if (insuranceVal && (insuranceVal.indexOf('-') >= 0 || insuranceVal.indexOf('–') >= 0)) {
                        const r = parseDateRangeStr(insuranceVal);
                        if (r.start) insuranceDate = r.start;
                        if (r.end) insuranceDeadline = r.end;
                    } else if (insuranceVal) {
                        insuranceDeadline = excelDateToStr(insuranceVal) || insuranceDeadline;
                    }

                    let techDate = excelDateToStr(getCell(row, keys, ['Дата прохождения тех осмотра']));
                    let techDeadline = excelDateToStr(getCell(row, keys, ['Дедлайн тех осмотра']));
                    if (techVal && (techVal.indexOf('-') >= 0 || techVal.indexOf('–') >= 0)) {
                        const r = parseDateRangeStr(techVal);
                        if (r.start) techDate = r.start;
                        if (r.end) techDeadline = r.end;
                    } else if (techVal) {
                        techDeadline = excelDateToStr(techVal) || techDeadline;
                    }

                    let taxDate = excelDateToStr(getCell(row, keys, ['Дата уплаты налога']));
                    let taxDeadline = excelDateToStr(getCell(row, keys, ['Дедлайн налога']));
                    if (taxVal && (taxVal.indexOf('-') >= 0 || taxVal.indexOf('–') >= 0)) {
                        const r = parseDateRangeStr(taxVal);
                        if (r.start) taxDate = r.start;
                        if (r.end) taxDeadline = r.end;
                    } else if (taxVal) {
                        taxDeadline = excelDateToStr(taxVal) || taxDeadline;
                    }

                    const mhParse = (val) => { const n = val !== null ? parseFloat(String(val).replace(',', '.')) : null; return n != null && !isNaN(n) ? n : null; };
                    const motorHoursAliases = ['Текущий', 'Моточас текущий', 'моточас', 'Моточасы'];
                    const motorHoursBaseAliases = ['Моточас', 'моточа', 'Моточас база'];
                    const motorHoursNextAliases = ['След. ТО 1', 'След. ТО 2', 'След. ТО 3', 'след. т'];

                    const v = {
                        name: name || '',
                        owner: owner || '',
                        grnz: getCell(row, keys, grnzAliases) || null,
                        consumptionRate: getCell(row, keys, consumptionAliases) || null,
                        dieselFuel: getCell(row, keys, dieselFuelAliases) || null,
                        year: getCell(row, keys, yearAliases) || null,
                        motorHoursBase: mhParse(getCell(row, keys, motorHoursBaseAliases)),
                        motorHoursNext1: mhParse(getCell(row, keys, ['След. ТО 1', 'след. т 1'])),
                        motorHoursNext2: mhParse(getCell(row, keys, ['След. ТО 2', 'след. т 2'])),
                        motorHoursNext3: mhParse(getCell(row, keys, ['След. ТО 3', 'след. т 3'])),
                        motorHours: mhParse(getCell(row, keys, motorHoursAliases)),
                        location: getCell(row, keys, locationAliases) || null,
                        application: getCell(row, keys, applicationAliases) || null,
                        insuranceDate: insuranceDate || null,
                        insuranceDeadline: insuranceDeadline || null,
                        techDate: techDate || null,
                        techDeadline: techDeadline || null,
                        taxDate: taxDate || null,
                        taxDeadline: taxDeadline || null
                    };
                    imported.push(v);
                }

                for (var i = 0; i < imported.length; i++) {
                    var item = imported[i];
                    delete item.id;
                    try { await apiPost('vehicles.php', item); } catch (err) { console.error(err); }
                }
                await loadVehicles(vehicleSearchQuery);
                renderTable();
                updateDeadlineCounts();
                populateRentVehicleSelect();
                alert('Импортировано записей: ' + imported.length);
            } catch (err) {
                console.error(err);
                alert('Ошибка при чтении файла: ' + (err.message || 'неверный формат'));
            }
        };
        reader.readAsArrayBuffer(file);
    }

    function downloadTemplate() {
        if (typeof XLSX === 'undefined') {
            alert('Библиотека Excel не загружена.');
            return;
        }
        var headers = [
            'Наименование',
            'Собственник',
            'ГРНЗ',
            'Норма расхода',
            'Диз топливу',
            'Год выпуска',
            'Моточас',
            'След. ТО 1',
            'След. ТО 2',
            'След. ТО 3',
            'Текущий',
            'Страховка',
            'Дата прохождения тех. осмотра',
            'Дата уплаты налога',
            'Находится',
            'Применение'
        ];
        var exampleRow = [
            'Колесный погрузчик LW550KZ / 3 куб / Жез',
            'ТОО "Райс KZ"',
            'AVD320M',
            '5-6 л/ч',
            'л',
            2022,
            2941,
            3200,
            3446,
            3696,
            5931,
            '03.05.2025-02.05.2026',
            '05.05.2025-05.05.2026',
            '05.05.2025-05.05.2026',
            'Жез',
            'погрузка, транспортировка'
        ];
        var aoa = [headers, exampleRow];
        var ws = XLSX.utils.aoa_to_sheet(aoa);
        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Автопарк');
        XLSX.writeFile(wb, 'шаблон_автопарк.xlsx');
    }

    async function showAdminPanelContent() {
        var session = sessionUser || (await getSession());
        var userEl = document.getElementById('adminCurrentUser');
        if (userEl) userEl.textContent = session ? (session.name || session.login) : '';
        var logoutBtn = document.getElementById('adminLogoutBtn');
        if (logoutBtn) {
            logoutBtn.onclick = function () {
                logout().then(function () { window.location.href = 'index.php'; });
            };
        }
        await renderAdminGeneralJournal();
        populateAdminBoardVehicleSelect();
        await renderAdminBoardJournal();
        var adminBoardSelect = document.getElementById('adminBoardVehicleSelect');
        if (adminBoardSelect) adminBoardSelect.addEventListener('change', function () { renderAdminBoardJournal(); });
        var journalBody = document.getElementById('adminGeneralJournalBody');
        if (journalBody) {
            journalBody.addEventListener('click', function (e) {
                var btn = e.target.closest('.admin-delete-log-btn');
                if (!btn) return;
                var id = btn.getAttribute('data-id');
                if (!id) return;
                if (confirm('Удалить эту запись из журнала?')) {
                    deleteAuditLogEntry(id);
                }
            });
        }
        var clearJournalBtn = document.getElementById('adminClearJournalBtn');
        if (clearJournalBtn) {
            clearJournalBtn.addEventListener('click', function () {
                if (confirm('Удалить весь журнал действий? Это действие нельзя отменить.')) {
                    clearAuditLog().then(function () {
                        renderAdminGeneralJournal();
                        renderAdminBoardJournal();
                    });
                }
            });
        }
        document.querySelectorAll('.admin-report-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var reportType = (this.getAttribute('data-report') || '').trim().toLowerCase();
                if (!reportType) {
                    alert('Не выбран тип отчёта.');
                    return;
                }
                document.querySelectorAll('.admin-report-btn').forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
                apiGet('reports.php?type=' + encodeURIComponent(reportType)).then(function (data) {
                    if (data && data.error) {
                        alert(data.error);
                        return;
                    }
                    renderAdminReport(data);
                }).catch(function (e) { alert(e.message || 'Ошибка'); });
            });
        });
        loadAdminUsers();
        var addUserForm = document.getElementById('adminAddUserForm');
        if (addUserForm) {
            addUserForm.onsubmit = function (e) {
                e.preventDefault();
                var loginEl = document.getElementById('adminNewUserLogin');
                var passEl = document.getElementById('adminNewUserPassword');
                var nameEl = document.getElementById('adminNewUserName');
                var errEl = document.getElementById('adminAddUserError');
                var okEl = document.getElementById('adminAddUserSuccess');
                if (errEl) errEl.classList.add('d-none');
                if (okEl) okEl.classList.add('d-none');
                var login = (loginEl && loginEl.value) ? loginEl.value.trim() : '';
                var password = passEl ? passEl.value : '';
                var name = (nameEl && nameEl.value) ? nameEl.value.trim() : '';
                if (!login || !password) {
                    if (errEl) { errEl.textContent = 'Укажите логин и пароль.'; errEl.classList.remove('d-none'); }
                    return;
                }
                apiPost('users.php', { action: 'create', login: login, password: password, name: name, is_admin: false }).then(function (data) {
                    if (okEl) { okEl.textContent = 'Менеджер «' + escapeHtml(login) + '» добавлен.'; okEl.classList.remove('d-none'); }
                    if (loginEl) loginEl.value = '';
                    if (passEl) passEl.value = '';
                    if (nameEl) nameEl.value = '';
                    var added = data && data.user;
                    if (added) {
                        var tbody = document.getElementById('adminUsersBody');
                        var emptyEl = document.getElementById('adminUsersEmpty');
                        if (tbody) {
                            var tr = '<tr><td>' + escapeHtml(added.login) + '</td><td>' + escapeHtml(added.name || '—') + '</td><td>Менеджер</td><td>—</td></tr>';
                            tbody.insertAdjacentHTML('beforeend', tr);
                        }
                        if (emptyEl) emptyEl.classList.add('d-none');
                    }
                    loadAdminUsers();
                }).catch(function (err) {
                    if (errEl) { errEl.textContent = err.message || 'Ошибка добавления.'; errEl.classList.remove('d-none'); }
                });
            };
        }
    }

    async function loadAdminUsers() {
        var tbody = document.getElementById('adminUsersBody');
        var emptyEl = document.getElementById('adminUsersEmpty');
        if (!tbody) return;
        try {
            var data = await apiGet('users.php');
            var users = (data && data.users) ? data.users : [];
            tbody.innerHTML = users.map(function (u) {
                var role = u.is_admin ? 'Администратор' : 'Менеджер';
                var created = (u.created_at && u.created_at.replace) ? u.created_at.replace('T', ' ').substring(0, 16) : '—';
                return '<tr><td>' + escapeHtml(u.login) + '</td><td>' + escapeHtml(u.name || '—') + '</td><td>' + escapeHtml(role) + '</td><td>' + escapeHtml(created) + '</td></tr>';
            }).join('');
            if (emptyEl) emptyEl.classList.toggle('d-none', users.length > 0);
        } catch (e) {
            if (emptyEl) { emptyEl.textContent = 'Не удалось загрузить список.'; emptyEl.classList.remove('d-none'); }
        }
    }

    async function initAdminPage() {
        var loginScreen = document.getElementById('adminLoginScreen');
        var panelScreen = document.getElementById('adminPanelScreen');
        var session = await getSession();
        if (session && (session.is_admin || session.login === 'admin')) {
            if (loginScreen) loginScreen.classList.add('d-none');
            if (panelScreen) panelScreen.classList.remove('d-none');
            await loadVehicles();
            await showAdminPanelContent();
            return;
        }
        if (loginScreen) loginScreen.classList.remove('d-none');
        if (panelScreen) panelScreen.classList.add('d-none');
        var form = document.getElementById('adminLoginForm');
        if (form) {
            form.onsubmit = function (e) {
                e.preventDefault();
                var login = (document.getElementById('adminLoginUsername') || {}).value.trim();
                var password = (document.getElementById('adminLoginPassword') || {}).value;
                var errEl = document.getElementById('adminLoginError');
                login(login, password).then(function (data) {
                    var user = data.user;
                    if (user && (user.is_admin || user.login === 'admin')) {
                        if (errEl) errEl.classList.add('d-none');
                        if (loginScreen) loginScreen.classList.add('d-none');
                        if (panelScreen) panelScreen.classList.remove('d-none');
                        loadVehicles().then(function () { return showAdminPanelContent(); });
                    } else {
                        if (errEl) {
                            errEl.textContent = user ? 'Доступ только для администратора.' : 'Неверный логин или пароль.';
                            errEl.classList.remove('d-none');
                        }
                    }
                }).catch(function (err) {
                    if (errEl) {
                        errEl.textContent = err.message || 'Неверный логин или пароль.';
                        errEl.classList.remove('d-none');
                    }
                });
            };
        }
    }

    // ——— Инициализация ———

    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('adminPage')) {
            initAdminPage();
            return;
        }
        getSession().then(function (session) {
            if (session) {
                if (session.is_admin || session.login === 'admin') {
                    window.location.href = 'admin.php';
                    return;
                }
                showApp(session);
            } else {
                showLogin();
            }
        }).catch(function () { showLogin(); });

        document.getElementById('loginForm').addEventListener('submit', function (e) {
            e.preventDefault();
            var loginVal = document.getElementById('loginUsername').value.trim();
            var password = document.getElementById('loginPassword').value;
            var errEl = document.getElementById('loginError');
            login(loginVal, password).then(function (data) {
                errEl.classList.add('d-none');
                var user = data.user;
                if (user && (user.is_admin || user.login === 'admin')) {
                    window.location.href = 'admin.php';
                    return;
                }
                showApp(user);
            }).catch(function (err) {
                errEl.textContent = err.message || 'Неверный логин или пароль.';
                errEl.classList.remove('d-none');
            });
        });

        document.getElementById('logoutBtn').addEventListener('click', function () {
            logout().then(function () { showLogin(); });
        });

        vehicleModal = new bootstrap.Modal(document.getElementById('vehicleModal'));
        rentModal = new bootstrap.Modal(document.getElementById('rentModal'));
        window.spareModal = new bootstrap.Modal(document.getElementById('spareModal'));
        motorHoursModal = new bootstrap.Modal(document.getElementById('motorHoursModal'));
        reportsModal = new bootstrap.Modal(document.getElementById('reportsModal'));

        var tenderInput = document.getElementById('tenderXlsInput');
        if (tenderInput) {
            tenderInput.addEventListener('change', handleTenderXls);
        }
        var tenderTableBodyEl = document.getElementById('tenderTableBody');
        if (tenderTableBodyEl) {
            tenderTableBodyEl.addEventListener('click', function (e) {
                var t = e.target;
                if (t && t.nodeType === 3) t = t.parentElement;
                var openSparesBtn = t && t.closest ? t.closest('.tender-open-spares') : null;
                if (openSparesBtn) {
                    e.preventDefault();
                    switchMainTabById('spares-tab');
                    return;
                }
                var openRentsBtn = t && t.closest ? t.closest('.tender-open-rents') : null;
                if (openRentsBtn) {
                    e.preventDefault();
                    switchMainTabById('rent-tab');
                    return;
                }
                var btn = t && t.closest ? t.closest('.tender-dec-btn') : null;
                if (!btn || !lastTenderResults) return;
                e.preventDefault();
                e.stopPropagation();
                var decision = btn.getAttribute('data-decision');
                var idx = parseInt(btn.getAttribute('data-tender-index'), 10);
                if (!decision || isNaN(idx)) return;
                if (decision === 'buy') openSpareModalFromTender(idx);
                else if (decision === 'rent') openRentModalFromTender(idx);
            });
        }
        var tenderClearDecisionsBtn = document.getElementById('tenderClearDecisionsBtn');
        if (tenderClearDecisionsBtn) {
            tenderClearDecisionsBtn.addEventListener('click', function () {
                if (!confirm('Сбросить привязки строк тендера к записям в системе (сами запчасти и аренды в базе не удаляются)?')) return;
                try { localStorage.removeItem(TENDER_COMMITTED_STORAGE); } catch (_) { /* ignore */ }
                if (lastTenderResults && lastTenderResults.length) {
                    renderTenderCompareResults(lastTenderResults, lastTenderMultiSheet);
                }
            });
        }
        var spareModalEl = document.getElementById('spareModal');
        if (spareModalEl) {
            spareModalEl.addEventListener('hidden.bs.modal', function () {
                if (pendingTenderCommit && pendingTenderCommit.type === 'spare') {
                    pendingTenderCommit = null;
                }
            });
        }
        vehicleForm = document.getElementById('vehicleForm');
        saveVehicleBtn = document.getElementById('saveVehicleBtn');

        document.getElementById('addVehicleBtn').addEventListener('click', function () {
            openModal(null);
        });

        document.getElementById('reportsBtn').addEventListener('click', openReportsModal);
        document.querySelectorAll('.report-type-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.report-type-btn').forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
                runReport(this.getAttribute('data-report'));
            });
        });
        document.getElementById('reportDownloadExcel').addEventListener('click', downloadReportExcel);

        document.getElementById('mhAddBtn').addEventListener('click', function () {
            applyMotorHoursAdd();
        });

        saveVehicleBtn.addEventListener('click', function () {
            const name = document.getElementById('vName').value.trim();
            const owner = document.getElementById('vOwner').value.trim();
            if (!name || !owner) {
                alert('Заполните наименование и собственника.');
                return;
            }
            saveVehicle();
        });

        var importInput = document.getElementById('importXlsInput');
        if (importInput) importInput.addEventListener('change', handleImportXls);

        var templateBtn = document.getElementById('downloadTemplateBtn');
        if (templateBtn) templateBtn.addEventListener('click', downloadTemplate);

        // События калькуляции аренды (стоимость, приход, прибыль)
        var rentIds = [
            'rentInitialCost',
            'rentOtherCost',
            'rentToCost',
            'rentSalaryCost',
            'rentDieselCost',
            'rentIncome'
        ];
        rentIds.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', recalcRentTotal);
            }
        });
        recalcRentTotal();
        setupSearchHandlers();

        var rentSaveBtn = document.getElementById('rentSaveBtn');
        if (rentSaveBtn) rentSaveBtn.addEventListener('click', saveRent);

        var addRentBtn = document.getElementById('addRentBtn');
        if (addRentBtn) {
            addRentBtn.addEventListener('click', function () {
                pendingTenderCommit = null;
                var rTitle = document.getElementById('rentModalTitle');
                if (rTitle) rTitle.innerHTML = '<i class="bi bi-calendar-check text-primary"></i> Добавить аренду';
                resetRentForm();
                populateRentVehicleSelect();
                recalcRentTotal();
                if (rentModal) rentModal.show();
            });
        }

        var addSpareBtn = document.getElementById('addSpareBtn');
        if (addSpareBtn) {
            addSpareBtn.addEventListener('click', function () {
                openSpareModal(null);
            });
        }
        var spareSaveBtn = document.getElementById('spareSaveBtn');
        if (spareSaveBtn) {
            spareSaveBtn.addEventListener('click', function () {
                saveSpare();
            });
        }

        var rentModalEl = document.getElementById('rentModal');
        if (rentModalEl) {
            rentModalEl.addEventListener('show.bs.modal', function () {
                populateRentVehicleSelect();
                recalcRentTotal();
            });
            rentModalEl.addEventListener('hidden.bs.modal', function () {
                if (pendingTenderCommit && pendingTenderCommit.type === 'rent') {
                    pendingTenderCommit = null;
                }
            });
        }

    });
})();

