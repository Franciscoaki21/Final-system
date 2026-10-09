<?php
// Only an administrator can delete, and only if the resident has no requests on file.
require __DIR__ . '/auth.php';
require_admin();
require_post('residents.php');

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM requests WHERE resident_id = ?');
$stmt->execute([$id]);
if ($stmt->fetchColumn() > 0) {
    flash('error', 'This resident has requests on file, so the record is kept to protect the request history.');
    redirect('view_resident.php?id=' . $id);
}

try {
    $pdo->prepare('DELETE FROM residents WHERE id = ?')->execute([$id]);
    flash('success', 'Resident deleted.');
} catch (PDOException $ex) {
    flash('error', 'The resident could not be deleted.');
}
redirect('residents.php');
