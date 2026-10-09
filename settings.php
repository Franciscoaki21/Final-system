<?php
// Barangay details printed on every certificate. Administrators only.
require __DIR__ . '/auth.php';
require_admin();

$fields = [
    'barangay'  => ['Barangay name', 'Maligaya'],
    'locality'  => ['City or municipality', 'Municipality of Bayanihan'],
    'province'  => ['Province', 'Province of Sampaguita'],
    'captain'   => ['Punong Barangay', 'Hon. Juan D. Bayani'],
    'secretary' => ['Barangay Secretary', 'Ana L. Dimagiba'],
];

$errors = [];
$values = settings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($fields as $key => [$label]) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
        if ($values[$key] === '') {
            $errors[$key] = 'Enter the ' . text_lower($label) . '.';
        } elseif (text_len($values[$key]) > 100) {
            $errors[$key] = 'Use 100 characters or fewer.';
        }
    }
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        foreach ($fields as $key => $unused) {
            $stmt->execute([$key, $values[$key]]);
        }
        flash('success', 'Settings saved.');
        redirect('settings.php');
    }
}

$page_title = 'Settings';
$active = 'settings.php';
require __DIR__ . '/header.php';
?>
<div class="page-head"><div><h1>Barangay settings</h1><p>These details appear on every certificate.</p></div></div>
<form method="post" class="panel form-panel" novalidate>
  <?= csrf_field() ?>
  <?php foreach ($fields as $key => [$label, $hint]): ?>
    <div class="field">
      <label for="<?= e($key) ?>"><?= e($label) ?></label>
      <input id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($values[$key] ?? '') ?>" placeholder="<?= e($hint) ?>" maxlength="100"<?= isset($errors[$key]) ? ' class="invalid"' : '' ?>>
      <?= isset($errors[$key]) ? '<span class="field-error">' . e($errors[$key]) . '</span>' : '' ?>
    </div>
  <?php endforeach; ?>
  <div class="form-actions"><button class="btn primary">Save settings</button></div>
</form>
<?php require __DIR__ . '/footer.php'; ?>
