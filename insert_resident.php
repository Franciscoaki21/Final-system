<?php
// Receives the add_resident.php form and inserts a new row.
require __DIR__ . '/auth.php';
require_post('add_resident.php');

[$data, $errors] = clean_resident_input($_POST);

if (!$errors) {
    $dup = $pdo->prepare('SELECT COUNT(*) FROM residents WHERE last_name = ? AND first_name = ? AND birth_date = ?');
    $dup->execute([$data['last_name'], $data['first_name'], $data['birth_date']]);
    if ($dup->fetchColumn() > 0) {
        $errors['last_name'] = 'A resident with this name and birth date is already in the registry.';
    }
}

if ($errors) {
    $_SESSION['old'] = $data;
    $_SESSION['errors'] = $errors;
    redirect('add_resident.php');
}

$sql = 'INSERT INTO residents (last_name, first_name, middle_name, suffix, birth_date, sex, civil_status, purok, street, occupation, contact_no, household_no)
        VALUES (:last_name, :first_name, :middle_name, :suffix, :birth_date, :sex, :civil_status, :purok, :street, :occupation, :contact_no, :household_no)';
$pdo->prepare($sql)->execute($data);

flash('success', 'Resident saved.');
redirect('view_resident.php?id=' . (int) $pdo->lastInsertId());
