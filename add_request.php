<?php
// Step 1: find the resident. Step 2: choose the certificate and purpose. The form posts to insert_request.php.
require __DIR__ . '/auth.php';

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

$resident = null;
$rid = (int) ($_GET['resident_id'] ?? 0);
if ($rid > 0) {
    $stmt = $pdo->prepare('SELECT * FROM residents WHERE id = ?');
    $stmt->execute([$rid]);
    $resident = $stmt->fetch() ?: null;
}

$q = trim($_GET['q'] ?? '');
$matches = [];
if (!$resident && $q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM residents WHERE CONCAT_WS(' ', last_name, first_name, middle_name, purok) LIKE ? ORDER BY last_name, first_name LIMIT 8");
    $stmt->execute(['%' . $q . '%']);
    $matches = $stmt->fetchAll();
}

$page_title = 'New request';
$active = 'requests.php';
require __DIR__ . '/header.php';
?>
<div class="page-head"><div><h1>New request</h1><p>The request joins today's queue as soon as you save it.</p></div></div>

<?php if (!$resident): ?>
  <div class="panel form-panel">
    <h2>Who is asking?</h2>
    <?php if (isset($errors['resident'])): ?><div class="notice error"><?= e($errors['resident']) ?></div><?php endif; ?>
    <form method="get" class="toolbar nomargin">
      <input class="grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Search by name or purok" aria-label="Search residents" autofocus>
      <button class="btn primary">Search</button>
    </form>
    <?php if ($q !== ''): ?>
      <div class="pick-list">
        <?php foreach ($matches as $m): ?>
          <a class="pick-item" href="add_request.php?resident_id=<?= (int) $m['id'] ?>">
            <strong><?= e(full_name($m)) ?></strong><span><?= e($m['purok']) ?>, <?= age_from($m['birth_date']) ?> years old</span>
          </a>
        <?php endforeach; ?>
        <?php if (!$matches): ?><p class="muted small">No resident matches "<?= e($q) ?>".</p><?php endif; ?>
      </div>
    <?php endif; ?>
    <p class="mt"><a href="add_resident.php">Resident not listed? Add a new resident</a></p>
  </div>
<?php else: ?>
  <form method="post" action="insert_request.php" class="panel form-panel">
    <?= csrf_field() ?>
    <input type="hidden" name="resident_id" value="<?= (int) $resident['id'] ?>">
    <div class="picked">
      <div><strong><?= e(full_name($resident)) ?></strong><span><?= e($resident['purok']) ?>, <?= age_from($resident['birth_date']) ?> years old</span></div>
      <a class="btn sm" href="add_request.php">Change</a>
    </div>

    <div class="field mt">
      <span class="lbl">What does the resident need?</span>
      <?php $chosen = $old['type'] ?? 'residency'; foreach (REQUEST_TYPES as $k => $t): ?>
        <label class="opt"><input type="radio" name="type" value="<?= e($k) ?>"<?= $chosen === $k ? ' checked' : '' ?>>
          <span><strong><?= e($t['label']) ?></strong><small>Fee: <?= e(fee_text($t['fee'])) ?></small></span></label>
      <?php endforeach; ?>
      <?= isset($errors['type']) ? '<span class="field-error">' . e($errors['type']) . '</span>' : '' ?>
    </div>

    <div class="field">
      <label for="purpose">Purpose</label>
      <input id="purpose" name="purpose" list="purposes" value="<?= e($old['purpose'] ?? '') ?>" maxlength="150" placeholder="For example, employment"<?= isset($errors['purpose']) ? ' class="invalid"' : '' ?>>
      <datalist id="purposes"><?php foreach (PURPOSES as $p): ?><option value="<?= e($p) ?>"><?php endforeach; ?></datalist>
      <?= isset($errors['purpose']) ? '<span class="field-error">' . e($errors['purpose']) . '</span>' : '' ?>
    </div>

    <div class="form-actions">
      <a class="btn" href="requests.php">Cancel</a>
      <button class="btn primary">Add to queue</button>
    </div>
  </form>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
