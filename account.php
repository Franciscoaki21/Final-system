<?php
// Every staff member can change their own password here.
require __DIR__ . '/auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['new'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user']['id']]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($current, $hash)) {
        $errors['current'] = 'That is not your current password.';
    }
    if (strlen($new) < 8) {
        $errors['new'] = 'Use at least 8 characters.';
    }
    if ($new !== $confirm) {
        $errors['confirm'] = 'The two passwords do not match.';
    }
    if (!$errors) {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user']['id']]);
        flash('success', 'Password changed.');
        redirect('account.php');
    }
}

$page_title = 'Your account';
$active = '';
require __DIR__ . '/header.php';
?>
<div class="page-head"><div><h1>Your account</h1>
  <p><?= e($me['full_name']) ?>, signed in as <?= e($me['username']) ?> (<?= e($me['role']) ?>)</p></div></div>
<form method="post" class="panel form-panel" novalidate>
  <h2>Change password</h2>
  <?= csrf_field() ?>
  <div class="field"><label for="current">Current password</label>
    <input id="current" type="password" name="current" autocomplete="current-password"<?= isset($errors['current']) ? ' class="invalid"' : '' ?>>
    <?= isset($errors['current']) ? '<span class="field-error">' . e($errors['current']) . '</span>' : '' ?></div>
  <div class="field"><label for="new">New password</label>
    <input id="new" type="password" name="new" autocomplete="new-password"<?= isset($errors['new']) ? ' class="invalid"' : '' ?>>
    <?= isset($errors['new']) ? '<span class="field-error">' . e($errors['new']) . '</span>' : '' ?></div>
  <div class="field"><label for="confirm">Confirm new password</label>
    <input id="confirm" type="password" name="confirm" autocomplete="new-password"<?= isset($errors['confirm']) ? ' class="invalid"' : '' ?>>
    <?= isset($errors['confirm']) ? '<span class="field-error">' . e($errors['confirm']) . '</span>' : '' ?></div>
  <div class="form-actions"><button class="btn primary">Change password</button></div>
</form>
<?php require __DIR__ . '/footer.php'; ?>
