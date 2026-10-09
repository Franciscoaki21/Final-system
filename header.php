<?php
// Shared page header. Set $page_title and $active before including this file.
$page_title = $page_title ?? 'Barangay Records';
$active = $active ?? '';
$cfg = settings($pdo);
$me = $_SESSION['user'];

function nav_link(string $file, string $label, string $active): string
{
    $current = $file === $active ? ' aria-current="page"' : '';
    return '<a class="tab" href="' . e($file) . '"' . $current . '>' . e($label) . '</a>';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?> | Barangay <?= e($cfg['barangay']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,400;8..60,600;8..60,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="top">
  <div class="wrap">
    <div class="brand">
        
            <img class="logo" src="logo.png" alt="" width="36" height="36">
      <div><strong>Barangay <?= e($cfg['barangay']) ?></strong><span>Resident Records</span></div>
    </div>
    <nav class="tabs" aria-label="Main">
      <?= nav_link('index.php', 'Dashboard', $active) ?>
      <?= nav_link('requests.php', 'Requests', $active) ?>
      <?= nav_link('residents.php', 'Residents', $active) ?>
      <?= nav_link('reports.php', 'Reports', $active) ?>
      <?php if ($me['role'] === 'admin'): ?><?= nav_link('settings.php', 'Settings', $active) ?><?php endif; ?>
    </nav>
    <div class="top-actions">
      <a class="btn primary" href="add_request.php">New request</a>
      <a class="btn" href="account.php" title="Your account"><?= e($me['full_name']) ?></a>
      <a class="btn ghost" href="logout.php">Sign out</a>
    </div>
  </div>
</header>
<main class="wrap view">
<?php foreach (take_flash() as $f): ?>
  <div class="notice <?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
<?php endforeach; ?>
