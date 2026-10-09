<?php
// Printable certificate. Opens in its own tab; use the Print button (or Ctrl+P).
require __DIR__ . '/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT rq.*, rs.last_name, rs.first_name, rs.middle_name, rs.suffix, rs.birth_date, rs.civil_status, rs.purok, rs.street
                       FROM requests rq JOIN residents rs ON rs.id = rq.resident_id WHERE rq.id = ?');
$stmt->execute([$id]);
$r = $stmt->fetch();

if (!$r || !in_array($r['status'], ['approved', 'released'], true)) {
    flash('error', 'Only approved or released requests have a certificate.');
    redirect('requests.php');
}

$cfg = settings($pdo);
$name = text_upper(display_name($r));
$age = age_from($r['birth_date']);
$address = ($r['street'] !== '' ? $r['street'] . ', ' : '') . $r['purok'] . ', Barangay ' . $cfg['barangay'] . ', ' . $cfg['locality'] . ', ' . $cfg['province'];
$issued = strtotime($r['approved_at'] ?: $r['created_at']);
$issuedLine = 'Issued this ' . ordinal((int) date('j', $issued)) . ' day of ' . date('F, Y', $issued)
    . ' at Barangay ' . $cfg['barangay'] . ', ' . $cfg['locality'] . ', ' . $cfg['province'] . '.';
$title = REQUEST_TYPES[$r['type']]['label'];
$who = '<b>' . e($name) . '</b>, ' . $age . ' years old, ' . e(text_lower($r['civil_status'])) . ', ';
$closing = 'upon the request of the above-named person for <b>' . e($r['purpose']) . '</b> and for whatever legal purpose it may serve.';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> | <?= e($r['ref_no']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,400;8..60,600;8..60,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body class="cert-page">
  <div class="cert-toolbar no-print">
    <a class="btn" href="requests.php">Back to requests</a>
    <button class="btn primary" onclick="window.print()">Print certificate</button>
  </div>

  <div class="cert-sheet">
    <div class="cert-head">
  <img src="logo.png" alt="" class="cert-logo">
  <div class="sm">Republic of the Philippines</div>
  <div class="sm"><?= e($cfg['province']) ?></div>
  <div class="sm"><?= e($cfg['locality']) ?></div>
  <div class="brgy">BARANGAY <?= e(text_upper($cfg['barangay'])) ?></div>
  <div class="sm">Office of the Punong Barangay</div>
</div>
    <h1 class="cert-title"><?= e(text_upper($title)) ?></h1>
    <p>TO WHOM IT MAY CONCERN:</p>

    <?php if ($r['type'] === 'residency'): ?>
      <p class="indent">This is to certify that <?= $who ?>is a bonafide resident of <?= e($address) ?>.</p>
    <?php elseif ($r['type'] === 'indigency'): ?>
      <p class="indent">This is to certify that <?= $who ?>is a resident of <?= e($address) ?>, and belongs to an indigent family in this barangay.</p>
    <?php else: ?>
      <p class="indent">This is to certify that <?= $who ?>is a resident of <?= e($address) ?>, and has no derogatory record or pending case filed in this barangay as of the date of issuance.</p>
    <?php endif; ?>
    <p class="indent">This <?= $r['type'] === 'clearance' ? 'clearance' : 'certification' ?> is issued <?= $closing ?></p>
    <p class="indent"><?= e($issuedLine) ?></p>

    <div class="cert-sig">
      <div class="line"></div>
      <strong><?= e(text_upper($cfg['captain'])) ?></strong>
      <div>Punong Barangay</div>
    </div>
    <div class="cert-foot">
      <span>Reference no. <?= e($r['ref_no']) ?></span>
      <span>Fee: <?= e(fee_text($r['fee'])) ?><?= $r['or_no'] ? ', OR no. ' . e($r['or_no']) : '' ?></span>
      <span>Prepared by <?= e($cfg['secretary']) ?>, Barangay Secretary</span>
    </div>
  </div>
</body>
</html>
