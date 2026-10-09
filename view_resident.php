<?php
require __DIR__ . '/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM residents WHERE id = ?');
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) {
    flash('error', 'That resident was not found.');
    redirect('residents.php');
}

$stmt = $pdo->prepare('SELECT * FROM requests WHERE resident_id = ? ORDER BY request_date DESC, queue_no DESC');
$stmt->execute([$id]);
$history = $stmt->fetchAll();

$page_title = full_name($r);
$active = 'residents.php';
require __DIR__ . '/header.php';
?>
<div class="page-head">
  <div><h1><?= e(full_name($r)) ?></h1><p><?= age_from($r['birth_date']) ?> years old, <?= e($r['sex']) ?>, <?= e($r['purok']) ?></p></div>
  <div class="toolbar nomargin">
    <a class="btn primary" href="add_request.php?resident_id=<?= $id ?>">New request</a>
    <a class="btn" href="update_resident.php?id=<?= $id ?>">Edit</a>
    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
      <form method="post" action="delete_resident.php" class="inline" onsubmit="return confirm('Delete this resident? This cannot be undone.');">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
        <button class="btn danger">Delete</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="grid-2">
  <div class="panel">
    <h2>Details</h2>
    <dl class="kv">
      <dt>Birth date</dt><dd><?= e(fmt_date($r['birth_date'])) ?></dd>
      <dt>Civil status</dt><dd><?= e($r['civil_status']) ?></dd>
      <dt>Address</dt><dd><?= e(($r['street'] !== '' ? $r['street'] . ', ' : '') . $r['purok']) ?></dd>
      <dt>Household number</dt><dd><?= e($r['household_no'] !== '' ? $r['household_no'] : 'Not set') ?></dd>
      <dt>Occupation</dt><dd><?= e($r['occupation'] !== '' ? $r['occupation'] : 'Not set') ?></dd>
      <dt>Contact number</dt><dd><?= e($r['contact_no'] !== '' ? $r['contact_no'] : 'Not set') ?></dd>
    </dl>
  </div>
  <div class="panel">
    <h2>Request history</h2>
    <?php if ($history): ?>
      <ul class="hist">
        <?php foreach ($history as $h): ?>
          <li><span><?= e(REQUEST_TYPES[$h['type']]['label']) ?><span class="small muted"> filed <?= e(fmt_short($h['request_date'])) ?></span></span><?= status_pill($h['status']) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted">No requests yet.</p>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
