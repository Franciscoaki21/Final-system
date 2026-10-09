<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (!empty($_SESSION['user'])) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id, username, password_hash, full_name, role FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $found = $stmt->fetch();

    if ($found && password_verify($password, $found['password_hash'])) {
        session_regenerate_id(true);          // prevents session fixation
        $_SESSION['user'] = [
            'id' => (int) $found['id'], 'username' => $found['username'],
            'full_name' => $found['full_name'], 'role' => $found['role'],
        ];
        redirect('index.php');
    }
    $error = 'Wrong username or password.';
}
$cfg = settings($pdo);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in | Barangay <?= e($cfg['barangay']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
  <main class="login-card">
    <div class="brand">
         
            <img class="logo" src="logo.png" alt="" width="36" height="36">
      <div><strong>Barangay <?= e($cfg['barangay']) ?></strong><span>Resident Records</span></div>
    </div>
    <h1>Sign in</h1>
    <p class="muted">Staff only. Use the account given to you by the barangay administrator.</p>
    <?php if ($error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="field"><label for="username">Username</label>
        <input id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username" required autofocus></div>
      <div class="field"><label for="password">Password</label>
        <input id="password" type="password" name="password" autocomplete="current-password" required></div>
      <button class="btn primary block">Sign in</button>
    </form>
  </main>
</body>
</html>
