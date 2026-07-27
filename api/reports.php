<?php
require __DIR__ . '/init.php';
requireAuth();

$vehicleModel = new \App\Models\Vehicle($db);
$rentModel = new \App\Models\Rent($db);
$spareModel = new \App\Models\Spare($db);

$type = trim((string) ($_GET['type'] ?? ''));
if ($type === '') {
    jsonError('Укажите type: tax, insurance, tech, motohours, summary, spares, rent', 400);
}
$type = strtolower($type);

$vehicles = $vehicleModel->all('');
$today = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+30 days'));
$daysWarning = 30;

function parseDate($str) {
    if (!$str) return null;
    $t = strtotime($str);
    return $t ? date('Y-m-d', $t) : null;
}
function formatDateRu($str) {
    if (!$str) return '—';
    $t = strtotime($str);
    return $t ? date('d.m.Y', $t) : '—';
}
function daysFromToday($str) {
    if (!$str) return null;
    $t = strtotime($str);
    if (!$t) return null;
    return (int) ceil(($t - time()) / 86400);
}
function formatMoney($v) {
    if ($v === null || $v === '' || (is_numeric($v) && (float)$v == 0)) return '—';
    return number_format((float)$v, 0, ',', ' ') . ' ₸';
}

if ($type === 'tax') {
    $headers = ['Наименование', 'Собственник', 'ГРНЗ', 'Дедлайн', 'Дней осталось'];
    $rows = [];
    foreach ($vehicles as $v) {
        $d = parseDate($v['taxDeadline'] ?? null);
        if (!$d || ($d >= $today && $d <= $endDate) || $d < $today) {
            $days = daysFromToday($d);
            $rows[] = [$v['name'] ?? '—', $v['owner'] ?? '—', $v['grnz'] ?? '—', formatDateRu($d), $days < 0 ? 'Просрочено' : (string)$days];
        }
    }
    usort($rows, fn($a, $b) => strcmp($a[3], $b[3]));
    jsonResponse(['title' => 'Отчет по налогу — ближайшие 30 дней', 'headers' => $headers, 'rows' => $rows]);
    exit;
}

if ($type === 'insurance') {
    $headers = ['Наименование', 'Собственник', 'ГРНЗ', 'Дедлайн', 'Дней осталось'];
    $rows = [];
    foreach ($vehicles as $v) {
        $d = parseDate($v['insuranceDeadline'] ?? null);
        if (!$d || ($d >= $today && $d <= $endDate) || $d < $today) {
            $days = daysFromToday($d);
            $rows[] = [$v['name'] ?? '—', $v['owner'] ?? '—', $v['grnz'] ?? '—', formatDateRu($d), $days < 0 ? 'Просрочено' : (string)$days];
        }
    }
    usort($rows, fn($a, $b) => strcmp($a[3], $b[3]));
    jsonResponse(['title' => 'Отчет по страховке — ближайшие 30 дней', 'headers' => $headers, 'rows' => $rows]);
    exit;
}

if ($type === 'tech') {
    $headers = ['Наименование', 'Собственник', 'ГРНЗ', 'Дедлайн', 'Дней осталось'];
    $rows = [];
    foreach ($vehicles as $v) {
        $d = parseDate($v['techDeadline'] ?? null);
        if (!$d || ($d >= $today && $d <= $endDate) || $d < $today) {
            $days = daysFromToday($d);
            $rows[] = [$v['name'] ?? '—', $v['owner'] ?? '—', $v['grnz'] ?? '—', formatDateRu($d), $days < 0 ? 'Просрочено' : (string)$days];
        }
    }
    usort($rows, fn($a, $b) => strcmp($a[3], $b[3]));
    jsonResponse(['title' => 'Отчет по ТО — ближайшие 30 дней', 'headers' => $headers, 'rows' => $rows]);
    exit;
}

if ($type === 'motohours') {
    $headers = ['Наименование', 'Собственник', 'ГРНЗ', 'Текущий м/ч', 'След. ТО 1', 'До след. ТО (м/ч)'];
    $rows = [];
    foreach ($vehicles as $v) {
        $c = $v['motorHours'] !== null ? (float)$v['motorHours'] : null;
        $n1 = $v['motorHoursNext1'] !== null ? (float)$v['motorHoursNext1'] : null;
        $until = null;
        if ($c !== null) {
            if ($n1 !== null && $c < $n1) $until = (int)round($n1 - $c);
            elseif ($n1 !== null && $c >= $n1) $until = 0;
        }
        if ($c === null && $until === null) continue;
        $rows[] = [
            $v['name'] ?? '—',
            $v['owner'] ?? '—',
            $v['grnz'] ?? '—',
            $c !== null ? $c : '—',
            $n1 !== null ? $n1 : '—',
            $until !== null ? ($until === 0 ? 'Пора ТО!' : $until) : '—'
        ];
    }
    usort($rows, function ($a, $b) {
        $u1 = $a[5]; $u2 = $b[5];
        if ($u1 === 'Пора ТО!' && $u2 !== 'Пора ТО!') return -1;
        if ($u1 !== 'Пора ТО!' && $u2 === 'Пора ТО!') return 1;
        if (is_numeric($u1) && is_numeric($u2)) return $u1 - $u2;
        return 0;
    });
    jsonResponse(['title' => 'Моточасы — до следующего ТО', 'headers' => $headers, 'rows' => $rows]);
    exit;
}

if ($type === 'summary') {
    $headers = ['Наименование', 'Собственник', 'ГРНЗ', 'Год', 'Текущий м/ч', 'До след. ТО', 'Страховка', 'Тех. осмотр', 'Налог', 'Находится', 'Применение'];
    $rows = [];
    foreach ($vehicles as $v) {
        $c = $v['motorHours'] ?? null;
        $n1 = $v['motorHoursNext1'] ?? null;
        $until = null;
        if ($c !== null && $n1 !== null) {
            if ($c < $n1) $until = (int)round((float)$n1 - (float)$c);
            else $until = 0;
        }
        $rows[] = [
            $v['name'] ?? '—', $v['owner'] ?? '—', $v['grnz'] ?? '—',
            $v['year'] ?? '—', $c ?? '—', $until !== null ? ($until === 0 ? 'Пора ТО!' : $until) : '—',
            formatDateRu($v['insuranceDeadline'] ?? null), formatDateRu($v['techDeadline'] ?? null), formatDateRu($v['taxDeadline'] ?? null),
            $v['location'] ?? '—', $v['application'] ?? '—'
        ];
    }
    jsonResponse(['title' => 'Сводка по транспортным средствам', 'headers' => $headers, 'rows' => $rows]);
    exit;
}

if ($type === 'spares') {
    $spares = $spareModel->all('');
    usort($spares, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));
    $headers = ['ТС (наименование и кому)', 'Название запчасти', 'Количество', 'Сумма (₸)', 'Дата'];
    $total = 0;
    $rows = [];
    foreach ($spares as $s) {
        $nameAndOwner = ($s['vehicleName'] ?? 'ТС') . (isset($s['vehicleOwner']) && $s['vehicleOwner'] ? ' — ' . $s['vehicleOwner'] : '');
        $amount = $s['amount'] !== null ? (float)$s['amount'] : 0;
        $total += $amount;
        $rows[] = [$nameAndOwner, $s['spareName'] ?? '—', $s['quantity'] ?? '—', formatMoney($s['amount']), formatDateRu($s['date'] ?? null)];
    }
    $title = 'Отчет по запчастям' . (count($spares) ? ' (записей: ' . count($spares) . ', итого: ' . formatMoney($total) . ')' : '');
    jsonResponse(['title' => $title, 'headers' => $headers, 'rows' => $rows]);
    exit;
}

if ($type === 'rent') {
    $rents = $rentModel->all('');
    usort($rents, fn($a, $b) => strcmp($a['startDate'] ?? '', $b['startDate'] ?? ''));
    $headers = ['ТС', 'ГРНЗ', 'Арендатор', 'Начало', 'Окончание', 'Стоимость', 'Приход', 'Прибыль', 'Диз топливу', 'Статус'];
    $totalIncome = $totalProfit = 0;
    $rows = [];
    foreach ($rents as $r) {
        $vehicleText = ($r['vehicleName'] ?? 'ТС') . (isset($r['vehicleGrnz']) && $r['vehicleGrnz'] ? ' (' . $r['vehicleGrnz'] . ')' : '');
        $profit = $r['profit'] ?? (isset($r['income'], $r['total']) ? (float)$r['income'] - (float)$r['total'] : null);
        if ($r['income'] !== null) $totalIncome += (float)$r['income'];
        if ($profit !== null) $totalProfit += (float)$profit;
        $rows[] = [
            $vehicleText, $r['vehicleGrnz'] ?? '—', $r['tenant'] ?? '—',
            formatDateRu($r['startDate']), formatDateRu($r['endDate']),
            formatMoney($r['total']), formatMoney($r['income']), formatMoney($profit), formatMoney($r['dieselCost'] ?? null), $r['status'] ?? 'Активна'
        ];
    }
    $title = 'Отчёт по аренде' . (count($rents) ? ' (записей: ' . count($rents) . ', приход: ' . formatMoney($totalIncome) . ', прибыль: ' . formatMoney($totalProfit) . ')' : '');
    jsonResponse(['title' => $title, 'headers' => $headers, 'rows' => $rows]);
    exit;
}

jsonError('Неизвестный тип отчёта', 400);
