<?php
require __DIR__ . '/auth.php';

$today = date('Y-m-d');
$month = date('Y-m');

$residentCount = (int) $pdo->query('SELECT COUNT(*) FROM residents')->fetchColumn();

// Everyone still waiting, oldest first. The first row is "next in line".
$queue = $pdo->query(
    "SELECT rq.*, rs.last_name, rs.first_name, rs.middle_name, rs.suffix, rs.purok
     FROM requests rq JOIN residents rs ON rs.id = rq.resident_id
     WHERE rq.status = 'pending'
     ORDER BY rq.request_date, rq.queue_no"
)->fetchAll();
$next = $queue[0] ?? null;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status = 'released' AND DATE(released_at) = ?");
$stmt->execute([$today]);
$releasedToday = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(fee), 0) FROM requests WHERE status = 'released' AND DATE_FORMAT(released_at, '%Y-%m') = ?");
$stmt->execute([$month]);
$feesMonth = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT type, COUNT(*) AS c FROM requests WHERE DATE_FORMAT(request_date, '%Y-%m') = ? GROUP BY type");
$stmt->execute([$month]);
$byType = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$page_title = 'Dashboard';
$active = 'index.php';
require __DIR__ . '/header.php';
?>
<div class="page-head">
  <div><h1>Today at the barangay hall</h1><p><?= e(date('l, F j, Y')) ?></p></div>
</div>

<div class="grid-dash">
  <div class="ticket">
    <div class="ticket-main">
      <span class="kicker">Next in line</span>
      <?php if ($next): ?>
        <span class="who"><?= e(display_name($next)) ?></span>
        <span class="what"><?= e(REQUEST_TYPES[$next['type']]['label']) ?> for <?= e($next['purpose']) ?></span>
        <div class="acts">
          <?= approve_form((int) $next['id'], 'index.php') ?>
          <a class="btn" href="update_status.php?action=reject&amp;id=<?= (int) $next['id'] ?>&amp;back=index.php">Reject</a>
        </div>
      <?php else: ?>
        <span class="who">Nobody is waiting</span>
        <span class="what">New requests will appear here in the order they arrive.</span>
        <div class="acts"><a class="btn primary" href="add_request.php">New request</a></div>
      <?php endif; ?>
    </div>
    <div class="ticket-stub">
      <span class="lbl">Queue number</span>
      <span class="qno"><?= $next ? e(queue_no($next['queue_no'])) : '—' ?></span>
      <span class="wait"><?= $next ? (count($queue) - 1) . ' more waiting' : 'Queue is clear' ?></span>
    </div>
  </div>

  <div class="stats">
    <div class="stat"><b><?= $residentCount ?></b><span>Residents on record</span></div>
    <div class="stat"><b><?= count($queue) ?></b><span>Waiting in queue</span></div>
    <div class="stat"><b><?= $releasedToday ?></b><span>Released today</span></div>
    <div class="stat"><b><?= e(peso($feesMonth)) ?></b><span>Fees collected this month</span></div>
  </div>
</div>

<div class="grid-2">
  <div class="panel">
    <h2>Waiting in queue</h2>
    <?php if ($queue): ?>
      <ul class="qlist">
        <?php foreach (array_slice($queue, 0, 8) as $r): ?>
          <li>
            <span class="qtag"><?= e(queue_no($r['queue_no'])) ?></span>
            <div class="grow">
              <strong><?= e(full_name($r)) ?></strong>
              <span><?= e(REQUEST_TYPES[$r['type']]['short']) ?>, <?= e($r['purpose']) ?><?= $r['request_date'] !== $today ? ', filed ' . e(fmt_short($r['request_date'])) : '' ?></span>
            </div>
            <?= request_actions($r, 'index.php') ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php if (count($queue) > 8): ?><p class="small muted mt">Showing 8 of <?= count($queue) ?>. <a href="requests.php?status=pending">See all pending requests</a>.</p><?php endif; ?>
    <?php else: ?>
      <p class="muted">No pending requests.</p>
    <?php endif; ?>
  </div>

  <div class="panel">
    <h2>Requests this month</h2>
    <?php
      $items = [];
      foreach (REQUEST_TYPES as $key => $t) { $items[$t['short']] = (int) ($byType[$key] ?? 0); }
      if (array_sum($items) > 0) { echo render_bars($items); } else { echo '<p class="muted">No requests yet this month.</p>'; }
    ?>
  </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
