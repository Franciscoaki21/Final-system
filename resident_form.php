<?php
// Form fields shared by add_resident.php and update_resident.php.
// Expects: $form_action, $submit_label, $r (current values), $errors, optional $resident_id.
function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<span class="field-error">' . e($errors[$key]) . '</span>' : '';
}
function input_class(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' class="invalid"' : '';
}
?>
<form method="post" action="<?= e($form_action) ?>" class="panel form-panel" novalidate>
  <?= csrf_field() ?>
  <?php if (!empty($resident_id)): ?><input type="hidden" name="id" value="<?= (int) $resident_id ?>"><?php endif; ?>

  <div class="row2">
    <div class="field"><label for="last_name">Last name</label>
      <input id="last_name" name="last_name" value="<?= e($r['last_name'] ?? '') ?>" maxlength="60"<?= input_class($errors, 'last_name') ?>><?= field_error($errors, 'last_name') ?></div>
    <div class="field"><label for="first_name">First name</label>
      <input id="first_name" name="first_name" value="<?= e($r['first_name'] ?? '') ?>" maxlength="60"<?= input_class($errors, 'first_name') ?>><?= field_error($errors, 'first_name') ?></div>
  </div>
  <div class="row2">
    <div class="field"><label for="middle_name">Middle name</label>
      <input id="middle_name" name="middle_name" value="<?= e($r['middle_name'] ?? '') ?>" maxlength="60"<?= input_class($errors, 'middle_name') ?>><?= field_error($errors, 'middle_name') ?></div>
    <div class="field"><label for="suffix">Suffix (Jr., Sr., III)</label>
      <input id="suffix" name="suffix" value="<?= e($r['suffix'] ?? '') ?>" maxlength="10"<?= input_class($errors, 'suffix') ?>><?= field_error($errors, 'suffix') ?></div>
  </div>
  <div class="row2">
    <div class="field"><label for="birth_date">Birth date</label>
      <input id="birth_date" type="date" name="birth_date" value="<?= e($r['birth_date'] ?? '') ?>" max="<?= e(date('Y-m-d')) ?>"<?= input_class($errors, 'birth_date') ?>><?= field_error($errors, 'birth_date') ?></div>
    <div class="field"><label for="sex">Sex</label>
      <select id="sex" name="sex"<?= input_class($errors, 'sex') ?>>
        <option value="">Select</option>
        <?php foreach (SEXES as $o): ?><option<?= ($r['sex'] ?? '') === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
      </select><?= field_error($errors, 'sex') ?></div>
  </div>
  <div class="row2">
    <div class="field"><label for="civil_status">Civil status</label>
      <select id="civil_status" name="civil_status"<?= input_class($errors, 'civil_status') ?>>
        <option value="">Select</option>
        <?php foreach (CIVIL_STATUSES as $o): ?><option<?= ($r['civil_status'] ?? '') === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
      </select><?= field_error($errors, 'civil_status') ?></div>
    <div class="field"><label for="purok">Purok</label>
      <select id="purok" name="purok"<?= input_class($errors, 'purok') ?>>
        <option value="">Select</option>
        <?php foreach (PUROKS as $o): ?><option<?= ($r['purok'] ?? '') === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
      </select><?= field_error($errors, 'purok') ?></div>
  </div>
  <div class="field"><label for="street">Street or lot</label>
    <input id="street" name="street" value="<?= e($r['street'] ?? '') ?>" maxlength="100"<?= input_class($errors, 'street') ?>><?= field_error($errors, 'street') ?></div>
  <div class="row2">
    <div class="field"><label for="occupation">Occupation</label>
      <input id="occupation" name="occupation" value="<?= e($r['occupation'] ?? '') ?>" maxlength="100"<?= input_class($errors, 'occupation') ?>><?= field_error($errors, 'occupation') ?></div>
    <div class="field"><label for="contact_no">Contact number</label>
      <input id="contact_no" name="contact_no" value="<?= e($r['contact_no'] ?? '') ?>" inputmode="tel" maxlength="20"<?= input_class($errors, 'contact_no') ?>><?= field_error($errors, 'contact_no') ?></div>
  </div>
  <div class="field"><label for="household_no">Household number</label>
    <input id="household_no" name="household_no" value="<?= e($r['household_no'] ?? '') ?>" maxlength="20"<?= input_class($errors, 'household_no') ?>><?= field_error($errors, 'household_no') ?></div>

  <div class="form-actions">
    <a class="btn" href="residents.php">Cancel</a>
    <button class="btn primary"><?= e($submit_label) ?></button>
  </div>
</form>
