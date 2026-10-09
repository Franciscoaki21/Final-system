<?php
// Downloads residents or requests as a CSV file that opens in Excel.
require __DIR__ . '/auth.php';

$type = $_GET['type'] ?? '';
if (!in_array($type, ['residents', 'requests'], true)) {
    redirect('reports.php');
}

// Stops spreadsheet programs from running a cell that starts with = + - @
function csv_safe($value): string
{
    $s = (string) $value;
    return $s !== '' && strpos('=+-@', $s[0]) !== false ? "'" . $s : $s;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $type . '-' . date('Y-m-d') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");   // UTF-8 byte order mark so Excel reads it correctly

if ($type === 'residents') {
    fputcsv($out, ['Last name', 'First name', 'Middle name', 'Suffix', 'Birth date', 'Sex', 'Civil status', 'Purok', 'Street', 'Occupation', 'Contact number', 'Household number']);
    foreach ($pdo->query('SELECT * FROM residents ORDER BY last_name, first_name') as $r) {
        fputcsv($out, array_map('csv_safe', [$r['last_name'], $r['first_name'], $r['middle_name'], $r['suffix'], $r['birth_date'], $r['sex'],
            $r['civil_status'], $r['purok'], $r['street'], $r['occupation'], $r['contact_no'], $r['household_no']]));
    }
} else {
    fputcsv($out, ['Reference', 'Queue number', 'Date filed', 'Resident', 'Type', 'Purpose', 'Status', 'Fee', 'OR number', 'Released on', 'Rejection reason']);
    $rows = $pdo->query('SELECT rq.*, rs.last_name, rs.first_name, rs.middle_name, rs.suffix
                         FROM requests rq JOIN residents rs ON rs.id = rq.resident_id ORDER BY rq.request_date, rq.queue_no');
    foreach ($rows as $r) {
        fputcsv($out, array_map('csv_safe', [$r['ref_no'], queue_no($r['queue_no']), $r['request_date'], full_name($r),
            REQUEST_TYPES[$r['type']]['label'], $r['purpose'], STATUSES[$r['status']], $r['fee'], $r['or_no'], $r['released_at'], $r['reason']]));
    }
}
fclose($out);
