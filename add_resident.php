<?php
// Shows the empty form. The form posts to insert_resident.php.
require __DIR__ . '/auth.php';

$r = $_SESSION['old'] ?? [];
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old'], $_SESSION['errors']);

$form_action = 'insert_resident.php';
$submit_label = 'Save resident';

$page_title = 'Add resident';
$active = 'residents.php';
require __DIR__ . '/header.php';
?>
<div class="page-head"><div><h1>Add resident</h1><p>Enter the details exactly as they appear on a valid ID.</p></div></div>
<?php if ($errors): ?><div class="notice error" role="alert">Fix the highlighted fields and save again.</div><?php endif; ?>
<?php require __DIR__ . '/resident_form.php'; ?>
<?php require __DIR__ . '/footer.php'; ?>
