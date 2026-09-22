<?php
// Controle de acesso adicionado; cálculo original preservado abaixo.
require dirname(__DIR__) . '/app/bootstrap.php';
$accessUser = require_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check_csrf(); }

function post_value($key, $default) {
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

function to_mb($value, $unit) {
    switch ($unit) {
        case 'KB':
            return $value / 1024;
        case 'GB':
            return $value * 1024;
        case 'TB':
            return $value * 1024 * 1024;
        case 'MB':
        default:
            return $value;
    }
}

function fmt_int($value) {
    return number_format((float) round($value), 0, ',', '.');
}

function fmt_size($mb) {
    if ($mb >= 1024 * 1024) {
        return number_format($mb / 1024 / 1024, 2, ',', '.') . ' TB';
    }
    if ($mb >= 1024) {
        return number_format($mb / 1024, 2, ',', '.') . ' GB';
    }
    return number_format($mb, 2, ',', '.') . ' MB';
}

function round_to_32($value) {
    return (int) (ceil($value / 32) * 32);
}

$dbName = trim((string) post_value('db_name', 'int-ems5mov'));
$dbPath = trim((string) post_value('db_path', '/db01/banco-source/prod-int/ems5mov'));
$logicalName = trim((string) post_value('logical_name', 'ems5mov'));
$fourglPort = (int) post_value('port_4gl', 35005);
$sql1Port = (int) post_value('port_sql1', 36005);
$sql2Port = (int) post_value('port_sql2', 46005);
$dlcBin = trim((string) post_value('dlc_bin', '/usr/dlc/bin'));
$ablUsers = max(1, (int) post_value('abl_users', 1000));
$sql1Users = max(0, (int) post_value('sql1_users', 30));
$sql2Users = max(0, (int) post_value('sql2_users', 30));
$extraUsers = max(0, (int) post_value('extra_users', 60));
$ma4gl = max(1, (int) post_value('ma_4gl', 10));
$mi4gl = max(1, (int) post_value('mi_4gl', 5));
$maSql1 = max(1, (int) post_value('ma_sql1', 10));
$miSql1 = max(1, (int) post_value('mi_sql1', 5));
$maSql2 = max(1, (int) post_value('ma_sql2', 10));
$miSql2 = max(1, (int) post_value('mi_sql2', 5));
$mpb4gl = max(1, (int) ceil($ablUsers / $ma4gl));
$mpbSql1 = max(0, (int) ceil($sql1Users / $maSql1));
$mpbSql2 = max(0, (int) ceil($sql2Users / $maSql2));
$secondaryBrokerCount = ($mpbSql1 > 0 ? 1 : 0) + ($mpbSql2 > 0 ? 1 : 0);
$mnValue = $mpb4gl + $mpbSql1 + $mpbSql2 + $secondaryBrokerCount;
$nValue = ($mpb4gl * $ma4gl) + ($mpbSql1 * $maSql1) + ($mpbSql2 * $maSql2) + $extraUsers;
$bibufs = max(1, (int) post_value('bibufs', 180));
$mm = max(1, (int) post_value('mm', 16384));
$minport4gl = max(1, (int) post_value('minport_4gl', 20000));
$maxport4gl = max($minport4gl, (int) post_value('maxport_4gl', 30000));
$minportSql1 = max(1, (int) post_value('minport_sql1', 20000));
$maxportSql1 = max($minportSql1, (int) post_value('maxport_sql1', 30000));
$minportSql2 = max(1, (int) post_value('minport_sql2', 20000));
$maxportSql2 = max($minportSql2, (int) post_value('maxport_sql2', 30000));
$pendConnTime = max(0, (int) post_value('pend_conn_time', 5));
$pinshm = isset($_POST['pinshm']) || !isset($_POST['calculate']);
$prefetchPriority = max(0, (int) post_value('prefetch_priority', 100));
$prefetchDelay = isset($_POST['prefetch_delay']) || !isset($_POST['calculate']);
$prefetchNumRecs = max(0, (int) post_value('prefetch_num_recs', 100));
$prefetchFactor = max(0, (int) post_value('prefetch_factor', 100));
$omsize = max(0, (int) post_value('omsize', 5000));
$lruskips = max(0, (int) post_value('lruskips', 100));
$tablebase = max(0, (int) post_value('tablebase', 1));
$tablerangesize = max(0, (int) post_value('tablerangesize', 5000));
$indexbase = max(0, (int) post_value('indexbase', 1));
$indexrangesize = max(0, (int) post_value('indexrangesize', 5000));
$sqlStack = max(0, (int) post_value('sql_stack', 2000));
$sqlStmtCache = max(0, (int) post_value('sql_stmt_cache', 1000));
$sqlCursors = max(0, (int) post_value('sql_cursors', 500));
$dbnotifyops = max(0, (int) post_value('dbnotifyops', 1024));
$extra4gl = trim((string) post_value('extra_4gl', ''));
$extraSql1 = trim((string) post_value('extra_sql1', ''));
$extraSql2 = trim((string) post_value('extra_sql2', ''));
$dbSize = (float) str_replace(',', '.', (string) post_value('db_size', '262'));
$dbUnit = (string) post_value('db_unit', 'GB');
$blockSizeKb = (int) post_value('block_size', 16);
$serverRam = (float) str_replace(',', '.', (string) post_value('server_ram', '512'));
$serverRamUnit = (string) post_value('server_ram_unit', 'GB');
$reservedRam = (float) str_replace(',', '.', (string) post_value('reserved_ram', '64'));
$reservedRamUnit = (string) post_value('reserved_ram_unit', 'GB');
$bufferStrategy = (string) post_value('buffer_strategy', 'whole');
$manualBuffer = (float) str_replace(',', '.', (string) post_value('manual_buffer', '240'));
$manualBufferUnit = (string) post_value('manual_buffer_unit', 'GB');
$recordsPerTransaction = max(1, (int) post_value('records_per_transaction', 100000));
$lockFactor = (float) post_value('lock_factor', 1.5);
$cpuCount = max(1, (int) post_value('cpu_count', 8));
$spinStrategy = (string) post_value('spin_strategy', 'progress_default');
$diskCount = max(1, (int) post_value('disk_count', 1));
$updateLoad = isset($_POST['update_load']) || !isset($_POST['calculate']);

$dbSizeMb = to_mb($dbSize, $dbUnit);
$serverRamMb = to_mb($serverRam, $serverRamUnit);
$reservedRamMb = to_mb($reservedRam, $reservedRamUnit);
$availableRamMb = max(0, $serverRamMb - $reservedRamMb);
$manualBufferMb = to_mb($manualBuffer, $manualBufferUnit);

if ($bufferStrategy === 'manual') {
    $targetBufferMb = $manualBufferMb;
    $strategyLabel = 'Memoria manual';
} elseif ($bufferStrategy === 'ram80') {
    $targetBufferMb = $availableRamMb * 0.8;
    $strategyLabel = '80% da RAM disponivel';
} else {
    $targetBufferMb = min($dbSizeMb, $availableRamMb * 0.9);
    $strategyLabel = 'Banco inteiro limitado a 90% da RAM disponivel';
}

$bValue = max(10, (int) floor(($targetBufferMb * 1024) / max(1, $blockSizeKb)));
$lValue = max(32, round_to_32($recordsPerTransaction * $lockFactor));
$lockMemoryMb = ($lValue * 64) / 1024 / 1024;

if ($spinStrategy === 'high_cpu') {
    $spinValue = 10000 * $cpuCount;
    $spinLabel = 'Agressivo: 10000 x CPUs';
} elseif ($spinStrategy === 'low_cpu') {
    $spinValue = 3000 * $cpuCount;
    $spinLabel = 'Conservador: 3000 x CPUs';
} else {
    $spinValue = $cpuCount === 1 ? 10000 : 6000 * $cpuCount;
    $spinLabel = 'Padrao Progress: 10000 para 1 CPU ou 6000 x CPUs';
}

$apwValue = $updateLoad ? min(9, $diskCount + 1) : min(9, $diskCount);
$apwLabel = $updateLoad ? 'Carga com muitas atualizacoes: discos + 1' : 'Carga com poucas atualizacoes: 1 por disco';

$param4glParts = [
    '-ServerType 4GL',
    '-bibufs ' . $bibufs,
    '-n ' . $nValue,
    '-Mn ' . $mnValue,
    '-Mpb ' . $mpb4gl,
    '-Ma ' . $ma4gl,
    '-Mi ' . $mi4gl,
    '-Mm ' . $mm,
    '-minport ' . $minport4gl,
    '-maxport ' . $maxport4gl,
    '-PendConnTime ' . $pendConnTime,
];
if ($pinshm) {
    $param4glParts[] = '-pinshm';
}
$param4glParts = array_merge($param4glParts, [
    '-prefetchPriority ' . $prefetchPriority,
]);
if ($prefetchDelay) {
    $param4glParts[] = '-prefetchDelay';
}
$param4glParts = array_merge($param4glParts, [
    '-prefetchNumRecs ' . $prefetchNumRecs,
    '-prefetchFactor ' . $prefetchFactor,
    '-omsize ' . $omsize,
    '-lruskips ' . $lruskips,
    '-tablebase ' . $tablebase,
    '-tablerangesize ' . $tablerangesize,
    '-indexbase ' . $indexbase,
    '-indexrangesize ' . $indexrangesize,
    '-SQLStack ' . $sqlStack,
    '-SQLStmtCache ' . $sqlStmtCache,
    '-SQLCursors ' . $sqlCursors,
    '-dbnotifyops ' . $dbnotifyops,
]);
if ($extra4gl !== '') {
    $param4glParts[] = $extra4gl;
}
$param4gl = implode(' ', $param4glParts);

$paramSql1Parts = [
    '-ServerType SQL',
    '-m3',
    '-Ma ' . $maSql1,
    '-Mi ' . $miSql1,
    '-Mpb ' . $mpbSql1,
    '-minport ' . $minportSql1,
    '-maxport ' . $maxportSql1,
    '-PendConnTime ' . $pendConnTime,
];
if ($extraSql1 !== '') {
    $paramSql1Parts[] = $extraSql1;
}
$paramSql1 = implode(' ', $paramSql1Parts);

$paramSql2Parts = [
    '-ServerType SQL',
    '-m3',
    '-Ma ' . $maSql2,
    '-Mi ' . $miSql2,
    '-Mpb ' . $mpbSql2,
    '-minport ' . $minportSql2,
    '-maxport ' . $maxportSql2,
    '-PendConnTime ' . $pendConnTime,
];
if ($extraSql2 !== '') {
    $paramSql2Parts[] = $extraSql2;
}
$paramSql2 = implode(' ', $paramSql2Parts);

$pfOutput = implode(PHP_EOL, [
    "# {$dbName}",
    "-db {$dbPath}",
    "-ld {$logicalName}",
    "-B {$bValue}",
    "-L {$lValue}",
    "-spin {$spinValue}",
    "# APW nao e parametro de .pf; iniciar {$apwValue} processo(s) com proapw",
]);

$scriptLine = implode(' ;', [
    $dbName,
    $dbPath,
    $logicalName,
    (string) $fourglPort,
    (string) $sql1Port,
    (string) $sql2Port,
    (string) $bValue,
    (string) $lValue,
    (string) $spinValue,
    (string) $apwValue,
    $param4gl,
    $paramSql1,
    $paramSql2,
]);

$proapwCommands = [];
for ($i = 1; $i <= $apwValue; $i++) {
    $proapwCommands[] = "\"{$dlcBin}/proapw\" \"{$dbPath}\"";
}

$loadScript = implode(PHP_EOL, [
    '#!/bin/bash',
    'set -euo pipefail',
    '',
    'DLCBIN="' . $dlcBin . '"',
    'DB="' . $dbPath . '"',
    'PORT4GL="' . $fourglPort . '"',
    'PORTSQL1="' . $sql1Port . '"',
    'PORTSQL2="' . $sql2Port . '"',
    '',
    'PARAM4GL=( ' . $param4gl . ' )',
    'PARAMSQL1=( ' . $paramSql1 . ' )',
    'PARAMSQL2=( ' . $paramSql2 . ' )',
    '',
    'echo "Iniciando broker primario ABL/4GL: ${DB} porta ${PORT4GL}"',
    '"${DLCBIN}/proserve" "${DB}" -S "${PORT4GL}" -B ' . $bValue . ' -L ' . $lValue . ' -spin ' . $spinValue . ' "${PARAM4GL[@]}"',
    '',
    'echo "Iniciando broker secundario SQL1: ${DB} porta ${PORTSQL1}"',
    '"${DLCBIN}/proserve" "${DB}" -S "${PORTSQL1}" "${PARAMSQL1[@]}"',
    '',
    'echo "Iniciando broker secundario SQL2: ${DB} porta ${PORTSQL2}"',
    '"${DLCBIN}/proserve" "${DB}" -S "${PORTSQL2}" "${PARAMSQL2[@]}"',
    '',
    'echo "Iniciando APW(s)"',
    'for i in $(seq 1 ' . $apwValue . '); do',
    '  echo "APW ${i}/' . $apwValue . '"',
    '  "${DLCBIN}/proapw" "${DB}"',
    'done',
    '',
    'echo "Banco iniciado com brokers ABL/SQL e APW(s)."',
]);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Calculadora de parametros Progress OpenEdge</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #0b0e11;
            --text: #edf1f5;
            --muted: #a9b3c0;
            --panel: #14191f;
            --line: #303943;
            --blue: #b8ef65;
            --cyan: #8cded8;
            --green: #b8ef65;
            --navy: #f2f6fa;
            --soft: #1b222a;
            --danger: #ff8e8e;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: var(--bg); color: var(--text);
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 16px; line-height: 1.5;
        }
        .page { width: min(1600px, calc(100% - 64px)); margin: 36px auto 64px; }
        .hero {
            display: flex; align-items: center; justify-content: space-between;
            gap: 28px; padding: 12px 0 30px; border-bottom: 1px solid var(--line);
        }
        .hero h1 {
            margin: 0; max-width: 900px; font-size: clamp(26px, 2.6vw, 40px);
            font-weight: 650; line-height: 1.18; letter-spacing: -.025em;
        }
        .hero p { margin: 14px 0 0; color: var(--muted); max-width: 840px; }
        .badge {
            flex-shrink: 0; color: var(--green); border: 1px solid #4b6036;
            border-radius: 999px; padding: 9px 16px; font-size: 14px;
            font-weight: 600; background: #182219;
        }
        .cards {
            display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px; margin: 28px 0;
        }
        .card {
            background: var(--panel); border: 1px solid var(--line);
            border-radius: 16px; padding: 22px 24px; min-width: 0;
        }
        .card:first-child { background: #c1f47a; border-color: #c1f47a; }
        .metric { color: #c6cdd5; font-size: 14px; font-weight: 600; }
        .value {
            margin-top: 16px; font-size: clamp(26px, 2.25vw, 36px);
            font-weight: 650; line-height: 1.2; color: var(--navy);
            font-variant-numeric: tabular-nums; overflow-wrap: anywhere;
            letter-spacing: -.025em;
        }
        .hint { margin-top: 10px; color: var(--muted); font-size: 14px; line-height: 1.45; }
        .card:first-child .metric, .card:first-child .hint { color: #334b20; }
        .card:first-child .value { color: #14220d; }
        .card.cyan .value { color: var(--cyan); }
        .panel {
            background: var(--panel); border: 1px solid var(--line);
            border-radius: 18px; margin-bottom: 24px; min-width: 0;
        }
        .panel-title {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 16px; padding: 24px 28px;
        }
        .panel-title h2 { margin: 0; font-size: 20px; font-weight: 600; line-height: 1.35; }
        form { padding: 0 28px 28px; }
        .form-grid {
            display: grid; grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 20px 16px; align-items: end;
        }
        label { display: grid; gap: 9px; color: #bdc6d0; font-size: 14px; font-weight: 500; min-width: 0; }
        input, select, textarea {
            width: 100%; min-width: 0; border: 1px solid #37424e;
            border-radius: 9px; background: #0e1318; color: var(--text);
            padding: 11px 12px; font: inherit; font-size: 16px;
            transition: border-color .15s, box-shadow .15s;
        }
        input, select { min-height: 46px; }
        select { padding-right: 26px; }
        input:hover, select:hover, textarea:hover { border-color: #657381; }
        input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline: 2px solid var(--green); outline-offset: 2px; border-color: var(--green);
        }
        input[readonly] { color: var(--green); background: #1c271b; border-color: #3d5030; font-weight: 600; }
        textarea { min-height: 108px; resize: vertical; line-height: 1.5; }
        .span-2 { grid-column: span 2; }
        .span-3 { grid-column: span 3; }
        .section-heading {
            grid-column: 1 / -1; margin-top: 16px; padding: 22px 0 0;
            border-top: 1px solid var(--line); color: #e8f1df;
            font-size: 16px; font-weight: 600; letter-spacing: .01em;
        }
        .check { display: flex; align-items: center; gap: 10px; min-height: 46px; color: var(--text); }
        .check input { width: 18px; height: 18px; min-height: 0; flex-shrink: 0; margin: 0; accent-color: var(--green); }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .form-grid > .actions { grid-column: 1 / -1; margin-top: 10px; padding-top: 24px; border-top: 1px solid var(--line); }
        button, .button {
            border: 1px solid transparent; border-radius: 9px; min-height: 44px;
            padding: 11px 18px; font: inherit; font-size: 14px; font-weight: 650;
            cursor: pointer; text-decoration: none; display: inline-flex;
            align-items: center; justify-content: center; line-height: 1.4;
            transition: background .15s, border-color .15s;
        }
        .primary { background: var(--green); color: #16220d; padding-inline: 30px; }
        .primary:hover { background: #d1ff92; }
        .secondary { background: #24362b; color: #c6f39b; border-color: #465c39; }
        .secondary:hover { background: #324736; }
        .ghost { background: #202831; color: #e0e7ef; border-color: #414d5a; }
        .ghost:hover { background: #303b47; }
        button:focus-visible, .button:focus-visible { outline: 2px solid var(--green); outline-offset: 3px; }
        .table-wrap { overflow-x: auto; max-width: 100%; margin: 0 28px 24px; border: 1px solid var(--line); border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; table-layout: auto; }
        th { background: #222b34; color: #d4dfeb; text-align: left; padding: 14px 16px; white-space: nowrap; font-weight: 600; }
        td { border-top: 1px solid var(--line); padding: 14px 16px; vertical-align: top; font-variant-numeric: tabular-nums; }
        tbody tr:nth-child(even) { background: var(--soft); }
        code, pre { font-family: Consolas, "Cascadia Code", "Courier New", monospace; }
        pre {
            margin: 0; white-space: pre; background: #090d11; color: #d2e7cd;
            border: 1px solid #2b3740; border-radius: 12px; padding: 20px;
            font-size: 14px; line-height: 1.7; overflow: auto; max-height: 380px; min-width: 0;
            tab-size: 4;
        }
        .split { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 18px; padding: 0 28px 24px; }
        .note { color: var(--muted); font-size: 14px; line-height: 1.75; padding: 0 28px 24px; overflow-wrap: anywhere; }
        .note code { color: #d1e9b4; background: #222d22; padding: 2px 5px; border-radius: 4px; }
        @media (max-width: 1200px) {
            .cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .form-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .hero { align-items: flex-start; flex-direction: column; gap: 18px; }
            .split { grid-template-columns: minmax(0, 1fr); }
        }
        @media (max-width: 680px) {
            .page { width: calc(100% - 28px); margin: 22px auto 36px; }
            .hero { padding-bottom: 24px; }
            .hero h1 { font-size: 28px; }
            .badge { max-width: 100%; }
            .cards { gap: 12px; margin: 20px 0; }
            .card { padding: 18px; }
            .cards, .form-grid { grid-template-columns: minmax(0, 1fr); }
            .span-2, .span-3 { grid-column: span 1; }
            .panel-title { padding: 20px 18px; }
            form { padding: 0 18px 20px; }
            .split, .note { padding: 0 18px 20px; }
            .table-wrap { margin: 0 18px 20px; }
            .actions { width: 100%; }
            .actions button { flex: 1 1 auto; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition: none !important; } }
    </style>
</head>
<body>
<main class="page">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:22px">
        <span>Olá, <?= esc($accessUser['name']) ?></span>
        <form method="post" action="<?= esc(app_url('logout.php')) ?>" style="padding:0;margin:0">
            <?= csrf_field() ?>
            <button class="ghost" type="submit">Sair</button>
        </form>
    </div>
    <section class="hero">
        <div>
            <h1>Calculadora de parametros Progress OpenEdge</h1>
            <p>Dimensionamento de carga para -B, -L, -spin e APW com saida no padrao de arquivo de parametros.</p>
        </div>
        <div class="badge">OpenEdge carga de banco</div>
    </section>

    <section class="cards" aria-label="Resultados principais">
        <div class="card">
            <div class="metric">Buffer primario</div>
            <div class="value">-B <?= fmt_int($bValue) ?></div>
            <div class="hint"><?= htmlspecialchars(fmt_size($targetBufferMb)) ?> com bloco de <?= $blockSizeKb ?> KB</div>
        </div>
        <div class="card green">
            <div class="metric">Tabela de locks</div>
            <div class="value">-L <?= fmt_int($lValue) ?></div>
            <div class="hint"><?= fmt_int($recordsPerTransaction) ?> registros x <?= number_format($lockFactor, 2, ',', '.') ?></div>
        </div>
        <div class="card cyan">
            <div class="metric">Spin lock retries</div>
            <div class="value">-spin <?= fmt_int($spinValue) ?></div>
            <div class="hint"><?= htmlspecialchars($spinLabel) ?></div>
        </div>
        <div class="card green">
            <div class="metric">Asynchronous Page Writers</div>
            <div class="value"><?= fmt_int($apwValue) ?> APW</div>
            <div class="hint"><?= htmlspecialchars($apwLabel) ?></div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-title">
            <h2>Parametros de entrada</h2>
        </div>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-grid">
                <label class="span-2">ID
                    <input name="db_name" value="<?= htmlspecialchars($dbName) ?>">
                </label>
                <label class="span-3">Diretorio
                    <input name="db_path" value="<?= htmlspecialchars($dbPath) ?>">
                </label>
                <label>Banco logico
                    <input name="logical_name" value="<?= htmlspecialchars($logicalName) ?>">
                </label>
                <label>4GL
                    <input type="number" name="port_4gl" value="<?= $fourglPort ?>">
                </label>
                <label>SQL1
                    <input type="number" name="port_sql1" value="<?= $sql1Port ?>">
                </label>
                <label>SQL2
                    <input type="number" name="port_sql2" value="<?= $sql2Port ?>">
                </label>
                <label class="span-3">Diretorio bin OpenEdge
                    <input name="dlc_bin" value="<?= htmlspecialchars($dlcBin) ?>">
                </label>
                <label>Tamanho banco
                    <input type="number" min="0" step="0.01" name="db_size" value="<?= htmlspecialchars((string) $dbSize) ?>">
                </label>
                <label>Unidade
                    <select name="db_unit">
                        <?php foreach (['MB', 'GB', 'TB'] as $unit): ?>
                            <option value="<?= $unit ?>" <?= $dbUnit === $unit ? 'selected' : '' ?>><?= $unit ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Block size
                    <select name="block_size">
                        <?php foreach ([1, 2, 4, 8, 16, 32, 64] as $size): ?>
                            <option value="<?= $size ?>" <?= $blockSizeKb === $size ? 'selected' : '' ?>><?= $size ?> KB</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>RAM servidor
                    <input type="number" min="0" step="0.01" name="server_ram" value="<?= htmlspecialchars((string) $serverRam) ?>">
                </label>
                <label>Unidade RAM
                    <select name="server_ram_unit">
                        <?php foreach (['GB', 'MB'] as $unit): ?>
                            <option value="<?= $unit ?>" <?= $serverRamUnit === $unit ? 'selected' : '' ?>><?= $unit ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Reserva SO
                    <input type="number" min="0" step="0.01" name="reserved_ram" value="<?= htmlspecialchars((string) $reservedRam) ?>">
                </label>
                <label>Unidade reserva
                    <select name="reserved_ram_unit">
                        <?php foreach (['GB', 'MB'] as $unit): ?>
                            <option value="<?= $unit ?>" <?= $reservedRamUnit === $unit ? 'selected' : '' ?>><?= $unit ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="span-2">Estrategia -B
                    <select name="buffer_strategy">
                        <option value="whole" <?= $bufferStrategy === 'whole' ? 'selected' : '' ?>>Cachear banco inteiro, limitado pela RAM</option>
                        <option value="ram80" <?= $bufferStrategy === 'ram80' ? 'selected' : '' ?>>Usar 80% da RAM disponivel</option>
                        <option value="manual" <?= $bufferStrategy === 'manual' ? 'selected' : '' ?>>Usar memoria manual</option>
                    </select>
                </label>
                <label>Memoria manual
                    <input type="number" min="0" step="0.01" name="manual_buffer" value="<?= htmlspecialchars((string) $manualBuffer) ?>">
                </label>
                <label>Unidade manual
                    <select name="manual_buffer_unit">
                        <?php foreach (['GB', 'MB'] as $unit): ?>
                            <option value="<?= $unit ?>" <?= $manualBufferUnit === $unit ? 'selected' : '' ?>><?= $unit ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Registros por transacao
                    <input type="number" min="1" name="records_per_transaction" value="<?= $recordsPerTransaction ?>">
                </label>
                <label>Fator -L
                    <select name="lock_factor">
                        <?php foreach ([1.25, 1.5, 2.0, 3.0] as $factor): ?>
                            <option value="<?= $factor ?>" <?= abs($lockFactor - $factor) < 0.001 ? 'selected' : '' ?>><?= number_format($factor, 2, ',', '.') ?>x</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>CPUs
                    <input type="number" min="1" name="cpu_count" value="<?= $cpuCount ?>">
                </label>
                <label class="span-2">Estrategia -spin
                    <select name="spin_strategy">
                        <option value="progress_default" <?= $spinStrategy === 'progress_default' ? 'selected' : '' ?>>Padrao Progress</option>
                        <option value="low_cpu" <?= $spinStrategy === 'low_cpu' ? 'selected' : '' ?>>Conservador</option>
                        <option value="high_cpu" <?= $spinStrategy === 'high_cpu' ? 'selected' : '' ?>>Agressivo</option>
                    </select>
                </label>
                <label>Discos do banco
                    <input type="number" min="1" name="disk_count" value="<?= $diskCount ?>">
                </label>
                <label class="check">
                    <input type="checkbox" name="update_load" value="1" <?= $updateLoad ? 'checked' : '' ?>>
                    Carga com muitas atualizacoes
                </label>
                <div class="section-heading">Capacidade e calculo Progress de brokers</div>
                <label>Usuarios ABL/4GL
                    <input type="number" min="1" name="abl_users" value="<?= $ablUsers ?>">
                </label>
                <label>Usuarios SQL1
                    <input type="number" min="0" name="sql1_users" value="<?= $sql1Users ?>">
                </label>
                <label>Usuarios SQL2
                    <input type="number" min="0" name="sql2_users" value="<?= $sql2Users ?>">
                </label>
                <label>Processos extras -n
                    <input type="number" min="0" name="extra_users" value="<?= $extraUsers ?>">
                </label>
                <label>Resultado -Mn
                    <input type="number" value="<?= $mnValue ?>" readonly>
                </label>
                <label>Resultado -n
                    <input type="number" value="<?= $nValue ?>" readonly>
                </label>
                <div class="section-heading">Broker ABL/4GL</div>
                <label>-bibufs
                    <input type="number" min="1" name="bibufs" value="<?= $bibufs ?>">
                </label>
                <label>-Mpb calculado
                    <input type="number" value="<?= $mpb4gl ?>" readonly>
                </label>
                <label>-Ma
                    <input type="number" min="1" name="ma_4gl" value="<?= $ma4gl ?>">
                </label>
                <label>-Mi
                    <input type="number" min="1" name="mi_4gl" value="<?= $mi4gl ?>">
                </label>
                <label>-Mm
                    <input type="number" min="1" name="mm" value="<?= $mm ?>">
                </label>
                <label>-minport 4GL
                    <input type="number" min="1" name="minport_4gl" value="<?= $minport4gl ?>">
                </label>
                <label>-maxport 4GL
                    <input type="number" min="1" name="maxport_4gl" value="<?= $maxport4gl ?>">
                </label>
                <label>-PendConnTime
                    <input type="number" min="0" name="pend_conn_time" value="<?= $pendConnTime ?>">
                </label>
                <label class="check">
                    <input type="checkbox" name="pinshm" value="1" <?= $pinshm ? 'checked' : '' ?>>
                    -pinshm
                </label>
                <label class="check">
                    <input type="checkbox" name="prefetch_delay" value="1" <?= $prefetchDelay ? 'checked' : '' ?>>
                    -prefetchDelay
                </label>
                <label>-prefetchPriority
                    <input type="number" min="0" name="prefetch_priority" value="<?= $prefetchPriority ?>">
                </label>
                <label>-prefetchNumRecs
                    <input type="number" min="0" name="prefetch_num_recs" value="<?= $prefetchNumRecs ?>">
                </label>
                <label>-prefetchFactor
                    <input type="number" min="0" name="prefetch_factor" value="<?= $prefetchFactor ?>">
                </label>
                <label>-omsize
                    <input type="number" min="0" name="omsize" value="<?= $omsize ?>">
                </label>
                <label>-lruskips
                    <input type="number" min="0" name="lruskips" value="<?= $lruskips ?>">
                </label>
                <label>-tablebase
                    <input type="number" min="0" name="tablebase" value="<?= $tablebase ?>">
                </label>
                <label>-tablerangesize
                    <input type="number" min="0" name="tablerangesize" value="<?= $tablerangesize ?>">
                </label>
                <label>-indexbase
                    <input type="number" min="0" name="indexbase" value="<?= $indexbase ?>">
                </label>
                <label>-indexrangesize
                    <input type="number" min="0" name="indexrangesize" value="<?= $indexrangesize ?>">
                </label>
                <label>-SQLStack
                    <input type="number" min="0" name="sql_stack" value="<?= $sqlStack ?>">
                </label>
                <label>-SQLStmtCache
                    <input type="number" min="0" name="sql_stmt_cache" value="<?= $sqlStmtCache ?>">
                </label>
                <label>-SQLCursors
                    <input type="number" min="0" name="sql_cursors" value="<?= $sqlCursors ?>">
                </label>
                <label>-dbnotifyops
                    <input type="number" min="0" name="dbnotifyops" value="<?= $dbnotifyops ?>">
                </label>
                <label class="span-3">Extras ABL/4GL
                    <textarea name="extra_4gl"><?= htmlspecialchars($extra4gl) ?></textarea>
                </label>
                <div class="section-heading">Broker SQL1</div>
                <label>-Mpb SQL1 calculado
                    <input type="number" value="<?= $mpbSql1 ?>" readonly>
                </label>
                <label>-Ma SQL1
                    <input type="number" min="1" name="ma_sql1" value="<?= $maSql1 ?>">
                </label>
                <label>-Mi SQL1
                    <input type="number" min="1" name="mi_sql1" value="<?= $miSql1 ?>">
                </label>
                <label>-minport SQL1
                    <input type="number" min="1" name="minport_sql1" value="<?= $minportSql1 ?>">
                </label>
                <label>-maxport SQL1
                    <input type="number" min="1" name="maxport_sql1" value="<?= $maxportSql1 ?>">
                </label>
                <label class="span-3">Extras SQL1
                    <textarea name="extra_sql1"><?= htmlspecialchars($extraSql1) ?></textarea>
                </label>
                <div class="section-heading">Broker SQL2</div>
                <label>-Mpb SQL2 calculado
                    <input type="number" value="<?= $mpbSql2 ?>" readonly>
                </label>
                <label>-Ma SQL2
                    <input type="number" min="1" name="ma_sql2" value="<?= $maSql2 ?>">
                </label>
                <label>-Mi SQL2
                    <input type="number" min="1" name="mi_sql2" value="<?= $miSql2 ?>">
                </label>
                <label>-minport SQL2
                    <input type="number" min="1" name="minport_sql2" value="<?= $minportSql2 ?>">
                </label>
                <label>-maxport SQL2
                    <input type="number" min="1" name="maxport_sql2" value="<?= $maxportSql2 ?>">
                </label>
                <label class="span-3">Extras SQL2
                    <textarea name="extra_sql2"><?= htmlspecialchars($extraSql2) ?></textarea>
                </label>
                <div class="actions span-3">
                    <button class="primary" type="submit" name="calculate" value="1">Calcular</button>
                    <button class="ghost" type="button" onclick="navigator.clipboard.writeText(document.getElementById('pf').innerText)">Copiar .pf</button>
                    <button class="secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('load-script').innerText)">Copiar script</button>
                </div>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-title">
            <h2>Linha no padrao do script de carga</h2>
            <div class="actions">
                <button class="ghost" type="button" onclick="navigator.clipboard.writeText(document.getElementById('script-line').innerText)">Copiar linha config</button>
                <button class="secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('load-script').innerText)">Copiar script carga</button>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Diretorio</th>
                    <th>Banco</th>
                    <th>4GL</th>
                    <th>SQL1</th>
                    <th>SQL2</th>
                    <th>-B</th>
                    <th>-L</th>
                    <th>-spin</th>
                    <th>APW</th>
                    <th>-n</th>
                    <th>-Mn</th>
                    <th>-Mpb 4GL</th>
                    <th>-Mpb SQL1</th>
                    <th>-Mpb SQL2</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><?= htmlspecialchars($dbName) ?></td>
                    <td><?= htmlspecialchars($dbPath) ?></td>
                    <td><?= htmlspecialchars($logicalName) ?></td>
                    <td><?= $fourglPort ?></td>
                    <td><?= $sql1Port ?></td>
                    <td><?= $sql2Port ?></td>
                    <td><?= fmt_int($bValue) ?></td>
                    <td><?= fmt_int($lValue) ?></td>
                    <td><?= fmt_int($spinValue) ?></td>
                    <td><?= fmt_int($apwValue) ?></td>
                    <td><?= fmt_int($nValue) ?></td>
                    <td><?= fmt_int($mnValue) ?></td>
                    <td><?= fmt_int($mpb4gl) ?></td>
                    <td><?= fmt_int($mpbSql1) ?></td>
                    <td><?= fmt_int($mpbSql2) ?></td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="split">
            <pre id="script-line"><?= htmlspecialchars($scriptLine) ?></pre>
            <pre id="pf"><?= htmlspecialchars($pfOutput) ?></pre>
        </div>
        <div class="panel-title">
            <h2>Script de carga/start do banco</h2>
        </div>
        <div class="split">
            <pre id="load-script"><?= htmlspecialchars($loadScript) ?></pre>
            <pre><?= htmlspecialchars("chmod +x start-" . $logicalName . ".sh\n./start-" . $logicalName . ".sh\n\n# Conferencia\npromon " . $dbPath) ?></pre>
        </div>
    </section>

    <section class="panel">
        <div class="panel-title">
            <h2>Comandos APW sugeridos</h2>
        </div>
        <div class="split">
            <pre><?= htmlspecialchars(implode(PHP_EOL, $proapwCommands)) ?></pre>
            <pre><?= htmlspecialchars("promon {$dbPath}\n# R&D > Other Displays > Performance Indicators\n# Validar resource waits para -spin\n# Validar writes/checkpoints para APW") ?></pre>
        </div>
        <div class="note">
            Formula aplicada conforme a documentacao Progress: <code>-Mpb = ceil(usuarios / -Ma)</code>,
            <code>-Mn = soma dos -Mpb + 1 para cada broker secundario -m3</code>, e
            <code>-n >= soma(-Mpb x -Ma) + processos extras</code>. Os processos extras devem cobrir PROMON,
            APW, BIW, AIW, WDOG, batch, self-service e utilitarios conectados ao banco.
        </div>
        <div class="note">
            Conforme a Progress, <code>-spin</code> controla quantas tentativas um processo faz para obter um latch antes de pausar.
            Em maquinas multiprocessadas, o padrao e <code>6000 x CPUs</code>; valores altos podem reduzir resource waits, mas podem aumentar uso de CPU.
            APW e iniciado com <code>proapw db-name</code>; a recomendacao inicial e um APW por disco do banco, mais um em aplicacoes com muitas atualizacoes.
            APW esta limitado de 0 a 9 processos e requer Enterprise database.
        </div>
    </section>
</main>
</body>
</html>
