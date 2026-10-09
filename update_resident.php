<?php
// GET  ?id=5  shows the edit form. POST saves the changes to that resident.
require __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    [$data, $errors] = clean_resident_input($_POST);

    if (!$errors) {
        $dup = $pdo->prepare('SELECT COUNT(*) FROM residents WHERE last_name = ? AND first_name = ? AND birth_date = ? AND id <> ?');
        $dup->execute([$data['last_name'], $data['first_name'], $data['birth_date'], $id]);
        if ($dup->fetchColumn() > 0) {
            $errors['last_name'] = 'Another resident with this name and birth date is already in the registry.';
        }
    }
    if ($errors) {
        $_SESSION['old'] = $data;
        $_SESSION['errors'] = $errors;
        redirect('update_resident.php?id=' . $id);
    }

    $data['id'] = $id;
    $sql = 'UPDATE residents SET last_name = :last_name, first_name = :first_name, middle_name = :middle_name, suffix = :suffix,
            birth_date = :birth_date, sex = :sex, civil_status = :civil_status, purok = :purok, street = :street,
            occupation = :occupation, contact_no = :contact_no, household_no = :household_no WHERE id = :id';
    $pdo->prepare($sql)->execute($data);

    flash('success', 'Resident updated.');
    redirect('view_resident.php?id=' . $id);
}

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM residents WHERE id = ?');
$stmt->execute([$id]);
$resident = $stmt->fetch();
if (!$resident) {
    flash('error', 'That resident was not found.');
    redirect('residents.php');
}

$r = $_SESSION['old'] ?? $resident;
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old'], $_SESSION['errors']);

$form_action = 'update_resident.php';
$submit_label = 'Save changes';
$resident_id = $id;

$page_title = 'Edit resident';
$active = 'residents.php';
require __DIR__ . '/header.php';
?>
<div class="page-head"><div><h1>Edit <?= e(full_name($resident)) ?></h1></div></div>
<?php if ($errors): ?><div class="notice error" role="alert">Fix the highlighted fields and save again.</div><?php endif; ?>
<?php require __DIR__ . '/resident_form.php'; ?>
<?php require __DIR__ . '/footer.php'; ?>
