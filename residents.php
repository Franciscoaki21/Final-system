<?php
require __DIR__ . '/auth.php';

$q = trim($_GET['q'] ?? '');
$purok = $_GET['purok'] ?? '';

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "CONCAT_WS(' ', rs.last_name, rs.first_name, rs.middle_name, rs.street, rs.household_no, rs.contact_no) LIKE ?";
    $params[] = '%' . $q . '%';
}
if ($purok !== '' && in_array($purok, PUROKS, true)) {
    $where[] = 'rs.purok = ?';
    $params[] = $purok;
}
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM residents rs $w");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$all = (int) $pdo->query('SELECT COUNT(*) FROM residents')->fetchColumn();

$page = min(current_page(), max(1, (int) ceil($total / PER_PAGE)));
$offset = ($page - 1) * PER_PAGE;

$stmt = $pdo->prepare(
    "SELECT rs.*, (SELECT COUNT(*) FROM requests rq WHERE rq.resident_id = rs.id) AS req_count
     FROM residents rs $w
     ORDER BY rs.last_name, rs.first_name
     LIMIT " . PER_PAGE . " OFFSET " . $offset
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$page_title = 'Residents';
$active = 'residents.php';
require __DIR__ . '/header.php';
?>
<div class="page-head">
  <div><h1>Residents</h1><p>The household and resident registry.</p></div>
  <a class="btn primary" href="add_resident.php">Add resident</a>
</div>

<form class="toolbar" method="get">
  <input class="grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, street, household, or contact" aria-label="Search residents">
  <select name="purok" aria-label="Filter by purok">
    <option value="">All puroks</option>
    <?php foreach (PUROKS as $p): ?><option<?= $purok === $p ? ' selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?>
  </select>
  <button class="btn">Search</button>
  <?php if ($q !== '' || $purok !== ''): ?><a class="btn ghost" href="residents.php">Clear</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
  <div class="empty">
    <strong><?= $all ? 'No residents match' : 'No residents yet' ?></strong>
    <p><?= $all ? 'Try a different search or purok.' : 'Add a resident so you can start issuing certificates.' ?></p>
    <?php if (!$all): ?><a class="btn primary" href="add_resident.php">Add resident</a><?php endif; ?>
  </div>
<?php else: ?>
  <p class="small muted mb">Showing <?= count($rows) ?> of <?= $total ?> residents</p>
  <div class="tablewrap">
    <table>
      <thead><tr><th>Name</th><th>Age and sex</th><th>Address</th><th>Household</th><th>Civil status</th><th>Contact</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a class="namelink" href="view_resident.php?id=<?= (int) $r['id'] ?>"><?= e(full_name($r)) ?></a>
              <span class="sub"><?= (int) $r['req_count'] ?> request<?= $r['req_count'] == 1 ? '' : 's' ?> on file</span></td>
          <td><?= age_from($r['birth_date']) ?>, <?= e($r['sex']) ?></td>
          <td><?= e($r['purok']) ?><span class="sub"><?= e($r['street']) ?></span></td>
          <td><?= e($r['household_no']) ?></td>
          <td><?= e($r['civil_status']) ?></td>
          <td><?= e($r['contact_no'] !== '' ? $r['contact_no'] : 'None') ?></td>
          <td class="acts"><a class="btn sm" href="add_request.php?resident_id=<?= (int) $r['id'] ?>">New request</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination($total, PER_PAGE, $page) ?>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
