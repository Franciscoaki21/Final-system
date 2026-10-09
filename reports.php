<?php
require __DIR__ . '/auth.php';

$period = $_GET['period'] ?? 'month';
if (!in_array($period, ['month', 'year', 'all'], true)) {
    $period = 'month';
}
$labels = ['month' => 'This month', 'year' => 'This year', 'all' => 'All time'];

// Date filter for the requests tables
if ($period === 'month') {
    $cond = "DATE_FORMAT(request_date, '%Y-%m') = ?";
    $arg = [date('Y-m')];
} elseif ($period === 'year') {
    $cond = 'YEAR(request_date) = ?';
    $arg = [date('Y')];
} else {
    $cond = '1 = 1';
    $arg = [];
}

$run = function (string $sql) use ($pdo, $arg) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($arg);
    return $stmt;
};

$byStatus = $run("SELECT status, COUNT(*) FROM requests WHERE $cond GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$byType = $run("SELECT type, COUNT(*) FROM requests WHERE $cond GROUP BY type")->fetchAll(PDO::FETCH_KEY_PAIR);
$fees = (float) $run("SELECT COALESCE(SUM(fee), 0) FROM requests WHERE $cond AND status = 'released'")->fetchColumn();
$totalRequests = array_sum($byStatus);

// Resident statistics (not affected by the period)
$byPurok = $pdo->query('SELECT purok, COUNT(*) FROM residents GROUP BY purok')->fetchAll(PDO::FETCH_KEY_PAIR);
$ageRow = $pdo->query(
    "SELECT SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 18) AS minors,
            SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 18 AND 59) AS adults,
            SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= 60) AS seniors
     FROM residents"
)->fetch();
$bySex = $pdo->query('SELECT sex, COUNT(*) FROM residents GROUP BY sex')->fetchAll(PDO::FETCH_KEY_PAIR);
$residentTotal = (int) $pdo->query('SELECT COUNT(*) FROM residents')->fetchColumn();

$purokItems = [];
foreach (PUROKS as $p) { $purokItems[$p] = (int) ($byPurok[$p] ?? 0); }

$page_title = 'Reports';
$active = 'reports.php';
require __DIR__ . '/header.php';
?>
<div class="page-head">
  <div><h1>Reports</h1><p>Numbers for planning and for the monthly report.</p></div>
  <form method="get" class="toolbar nomargin no-print">
    <select name="period" aria-label="Report period" onchange="this.form.submit()">
      <?php foreach ($labels as $k => $l): ?><option value="<?= e($k) ?>"<?= $period === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select>
    <a class="btn" href="export.php?type=requests">Export requests (CSV)</a>
    <a class="btn" href="export.php?type=residents">Export residents (CSV)</a>
    <button type="button" class="btn" onclick="window.print()">Print</button>
  </form>
</div>

<div class="stats stats-row">
  <div class="stat"><b><?= $totalRequests ?></b><span>Requests filed (<?= e(strtolower($labels[$period])) ?>)</span></div>
  <div class="stat"><b><?= (int) ($byStatus['released'] ?? 0) ?></b><span>Released</span></div>
  <div class="stat"><b><?= (int) ($byStatus['pending'] ?? 0) ?></b><span>Still pending</span></div>
  <div class="stat"><b><?= e(peso($fees)) ?></b><span>Fees collected</span></div>
</div>

<div class="grid-eq">
  <div class="panel"><h2>Requests by type</h2>
    <?php $items = []; foreach (REQUEST_TYPES as $k => $t) { $items[$t['short']] = (int) ($byType[$k] ?? 0); } echo render_bars($items); ?></div>
  <div class="panel"><h2>Requests by status</h2>
    <?php $items = []; foreach (STATUSES as $k => $l) { $items[$l] = (int) ($byStatus[$k] ?? 0); } echo render_bars($items); ?></div>
</div>
<div class="grid-eq mt2">
  <div class="panel"><h2>Residents by purok</h2><?= render_bars($purokItems) ?></div>
  <div class="panel"><h2>Residents by age and sex</h2>
    <?= render_bars(['Under 18' => (int) $ageRow['minors'], '18 to 59' => (int) $ageRow['adults'], '60 and over' => (int) $ageRow['seniors']]) ?>
    <div class="spacer"></div>
    <?= render_bars(['Female' => (int) ($bySex['Female'] ?? 0), 'Male' => (int) ($bySex['Male'] ?? 0)]) ?>
    <p class="small muted mt">Total on record: <?= $residentTotal ?> residents</p>
  </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
