<?php
// Receives the add_request.php form. Gives the request today's next queue number.
require __DIR__ . '/auth.php';
require_post('add_request.php');

$rid = (int) ($_POST['resident_id'] ?? 0);
$type = $_POST['type'] ?? '';
$purpose = trim((string) ($_POST['purpose'] ?? ''));

$errors = [];
$stmt = $pdo->prepare('SELECT COUNT(*) FROM residents WHERE id = ?');
$stmt->execute([$rid]);
if (!$stmt->fetchColumn()) {
    $errors['resident'] = 'Choose a resident first.';
}
if (!isset(REQUEST_TYPES[$type])) {
    $errors['type'] = 'Choose the certificate the resident needs.';
}
if ($purpose === '') {
    $errors['purpose'] = 'Enter the purpose.';
} elseif (text_len($purpose) > 150) {
    $errors['purpose'] = 'Use 150 characters or fewer.';
}

if ($errors) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = ['type' => $type, 'purpose' => $purpose];
    redirect('add_request.php' . ($rid ? '?resident_id=' . $rid : ''));
}

try {
    $pdo->beginTransaction();

    // Lock today's rows so two staff members cannot get the same queue number.
    $today = date('Y-m-d');
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(queue_no), 0) + 1 FROM requests WHERE request_date = ? FOR UPDATE');
    $stmt->execute([$today]);
    $queueNo = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('INSERT INTO requests (queue_no, request_date, resident_id, type, purpose, fee, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$queueNo, $today, $rid, $type, $purpose, REQUEST_TYPES[$type]['fee'], $_SESSION['user']['id']]);

    $id = (int) $pdo->lastInsertId();
    $ref = sprintf('BRG-%s-%04d', date('Y'), $id);
    $pdo->prepare('UPDATE requests SET ref_no = ? WHERE id = ?')->execute([$ref, $id]);

    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'The request could not be saved. Try again.');
    redirect('add_request.php?resident_id=' . $rid);
}

flash('success', 'Added to the queue as No. ' . queue_no($queueNo) . ' (' . $ref . ').');
redirect('requests.php?status=pending');
