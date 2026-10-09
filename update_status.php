<?php
// Moves a request through the workflow: pending -> approved -> released, or pending -> rejected.
// GET shows the reject/release form. POST performs the change.
require __DIR__ . '/auth.php';

function safe_back(?string $back): string
{
    return in_array($back, ['index.php', 'requests.php'], true) ? $back : 'requests.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $back = safe_back($_POST['back'] ?? '');
    $userId = $_SESSION['user']['id'];

    $stmt = $pdo->prepare('SELECT * FROM requests WHERE id = ?');
    $stmt->execute([$id]);
    $req = $stmt->fetch();
    if (!$req) {
        flash('error', 'That request was not found.');
        redirect($back);
    }

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE requests SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
        $stmt->execute([$userId, $id]);
        flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount() ? 'Approved. The certificate is ready to print.' : 'This request was already processed.');
        redirect($back);
    }

    if ($action === 'reject') {
        $reason = trim((string) ($_POST['reason'] ?? ''));
        if ($reason === '' || text_len($reason) > 255) {
            flash('error', 'Enter a reason (255 characters or fewer) so the resident knows what to fix.');
            redirect('update_status.php?action=reject&id=' . $id . '&back=' . $back);
        }
        $stmt = $pdo->prepare("UPDATE requests SET status = 'rejected', reason = ?, approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
        $stmt->execute([$reason, $userId, $id]);
        flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount() ? 'Rejected.' : 'This request was already processed.');
        redirect($back);
    }

    if ($action === 'release') {
        $orNo = trim((string) ($_POST['or_no'] ?? ''));
        if ((float) $req['fee'] > 0 && !preg_match('/^[A-Za-z0-9\-]{1,30}$/', $orNo)) {
            flash('error', 'Enter the official receipt number (letters, numbers, and dashes only).');
            redirect('update_status.php?action=release&id=' . $id . '&back=' . $back);
        }
        $stmt = $pdo->prepare("UPDATE requests SET status = 'released', or_no = ?, released_at = NOW() WHERE id = ? AND status = 'approved'");
        $stmt->execute([(float) $req['fee'] > 0 ? $orNo : null, $id]);
        flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount() ? 'Released.' : 'This request was already processed.');
        redirect($back);
    }

    redirect($back);
}

// ---- GET: show the form for reject / release ----
$id = (int) ($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';
$back = safe_back($_GET['back'] ?? '');

$stmt = $pdo->prepare('SELECT rq.*, rs.last_name, rs.first_name, rs.middle_name, rs.suffix FROM requests rq JOIN residents rs ON rs.id = rq.resident_id WHERE rq.id = ?');
$stmt->execute([$id]);
$req = $stmt->fetch();

$ok = $req && (($action === 'reject' && $req['status'] === 'pending') || ($action === 'release' && $req['status'] === 'approved'));
if (!$ok) {
    flash('error', 'That request cannot be changed this way.');
    redirect($back);
}

$page_title = $action === 'reject' ? 'Reject request' : 'Mark as released';
$active = 'requests.php';
require __DIR__ . '/header.php';
?>
<div class="page-head"><div><h1><?= e($page_title) ?></h1>
  <p><?= e(REQUEST_TYPES[$req['type']]['label']) ?> for <?= e(display_name($req)) ?>, queue No. <?= e(queue_no($req['queue_no'])) ?></p></div></div>

<form method="post" action="update_status.php" class="panel form-panel">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $id ?>">
  <input type="hidden" name="action" value="<?= e($action) ?>">
  <input type="hidden" name="back" value="<?= e($back) ?>">

  <?php if ($action === 'reject'): ?>
    <div class="field"><label for="reason">Reason</label>
      <textarea id="reason" name="reason" maxlength="255" placeholder="Tell the resident what to fix or bring" autofocus></textarea></div>
  <?php else: ?>
    <p class="mb"><?= (float) $req['fee'] > 0 ? 'Collect ' . e(peso($req['fee'])) . ' before handing over the certificate.' : 'No fee for this certificate.' ?></p>
    <?php if ((float) $req['fee'] > 0): ?>
      <div class="field"><label for="or_no">Official receipt number</label>
        <input id="or_no" name="or_no" maxlength="30" inputmode="numeric" autofocus></div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="form-actions">
    <a class="btn" href="<?= e($back) ?>">Cancel</a>
    <button class="btn <?= $action === 'reject' ? 'danger' : 'primary' ?>"><?= $action === 'reject' ? 'Reject request' : 'Mark as released' ?></button>
  </div>
</form>
<?php require __DIR__ . '/footer.php'; ?>
