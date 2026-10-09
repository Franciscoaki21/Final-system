<?php
require __DIR__ . '/auth.php';

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';
$type = $_GET['type'] ?? 'all';

$where = [];
$params = [];
if ($status !== 'all' && isset(STATUSES[$status])) {
    $where[] = 'rq.status = ?';
    $params[] = $status;
}
if ($type !== 'all' && isset(REQUEST_TYPES[$type])) {
    $where[] = 'rq.type = ?';
    $params[] = $type;
}
if ($q !== '') {
    $where[] = "(CONCAT_WS(' ', rs.last_name, rs.first_name, rs.middle_name) LIKE ? OR rq.ref_no LIKE ? OR rq.purpose LIKE ? OR rq.queue_no = ?)";
    array_push($params, "%$q%", "%$q%", "%$q%", ctype_digit($q) ? (int) $q : -1);
}
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM requests rq JOIN residents rs ON rs.id = rq.resident_id $w");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$page = min(current_page(), max(1, (int) ceil($total / PER_PAGE)));
$offset = ($page - 1) * PER_PAGE;

$stmt = $pdo->prepare(
    "SELECT rq.*, rs.last_name, rs.first_name, rs.middle_name, rs.suffix, rs.purok
     FROM requests rq JOIN residents rs ON rs.id = rq.resident_id
     $w ORDER BY rq.request_date DESC, rq.queue_no DESC
     LIMIT " . PER_PAGE . " OFFSET " . $offset
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$counts = $pdo->query('SELECT status, COUNT(*) FROM requests GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$countAll = array_sum($counts);

// Builds a link that keeps the other filters
function filter_link(array $change): string
{
    return '?' . http_build_query(array_filter(array_merge($_GET, $change, ['page' => null]), fn($v) => $v !== null && $v !== ''));
}

$page_title = 'Requests';
$active = 'requests.php';
require __DIR__ . '/header.php';
?>
<div class="page-head">
  <div><h1>Requests</h1><p>Certificates and clearances, from queue to release.</p></div>
  <a class="btn primary" href="add_request.php">New request</a>
</div>

<form class="toolbar" method="get">
  <?php if ($status !== 'all'): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
  <input class="grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, reference, or queue number" aria-label="Search requests">
  <select name="type" aria-label="Filter by type">
    <option value="all">All types</option>
    <?php foreach (REQUEST_TYPES as $k => $t): ?><option value="<?= e($k) ?>"<?= $type === $k ? ' selected' : '' ?>><?= e($t['label']) ?></option><?php endforeach; ?>
  </select>
  <button class="btn">Search</button>
</form>

<div class="chips">
  <a class="chip" href="<?= e(filter_link(['status' => 'all'])) ?>" aria-pressed="<?= $status === 'all' ? 'true' : 'false' ?>">All<span class="c"><?= (int) $countAll ?></span></a>
  <?php foreach (STATUSES as $k => $label): ?>
    <a class="chip" href="<?= e(filter_link(['status' => $k])) ?>" aria-pressed="<?= $status === $k ? 'true' : 'false' ?>"><?= e($label) ?><span class="c"><?= (int) ($counts[$k] ?? 0) ?></span></a>
  <?php endforeach; ?>
</div>

<?php if (!$rows): ?>
  <div class="empty">
    <strong>No requests found</strong>
    <p><?= $countAll ? 'Try a different search or filter.' : 'Add the first request to start the queue.' ?></p>
    <?php if (!$countAll): ?><a class="btn primary" href="add_request.php">New request</a><?php endif; ?>
  </div>
<?php else: ?>
  <div class="tablewrap">
    <table>
      <thead><tr><th>Queue</th><th>Resident</th><th>Request</th><th>Filed</th><th>Fee</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><span class="qtag"><?= e(queue_no($r['queue_no'])) ?></span><span class="sub"><?= e($r['ref_no']) ?></span></td>
          <td><a class="namelink" href="view_resident.php?id=<?= (int) $r['resident_id'] ?>"><?= e(full_name($r)) ?></a><span class="sub"><?= e($r['purok']) ?></span></td>
          <td><?= e(REQUEST_TYPES[$r['type']]['label']) ?><span class="sub"><?= e($r['purpose']) ?></span></td>
          <td><?= e(fmt_short($r['request_date'])) ?></td>
          <td><?= e(fee_text($r['fee'])) ?><?= $r['or_no'] ? '<span class="sub">OR ' . e($r['or_no']) . '</span>' : '' ?></td>
          <td><?= status_pill($r['status']) ?><?= ($r['status'] === 'rejected' && $r['reason']) ? '<span class="sub">' . e($r['reason']) . '</span>' : '' ?></td>
          <td class="acts"><?= request_actions($r, 'requests.php') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination($total, PER_PAGE, $page) ?>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
