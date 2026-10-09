<?php
// Shared helpers and constants used by every page.
date_default_timezone_set('Asia/Manila');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const REQUEST_TYPES = [
    'residency' => ['label' => 'Certificate of Residency', 'short' => 'Residency', 'fee' => 50],
    'indigency' => ['label' => 'Certificate of Indigency', 'short' => 'Indigency', 'fee' => 0],
    'clearance' => ['label' => 'Barangay Clearance',       'short' => 'Clearance', 'fee' => 100],
];
const STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'released' => 'Released', 'rejected' => 'Rejected'];
const PUROKS = ['Sitio Centro', 'Sitio Proper', 'Sitio Moog', 'Sitio Agsalen Centro', 'Sitio Agsalen East', 'Sitio Ibaba East', 'Sitio Ibaba West'];
const CIVIL_STATUSES = ['Single', 'Married', 'Widowed', 'Separated'];
const SEXES = ['Female', 'Male'];
const PURPOSES = ['Employment', 'Scholarship', 'Medical assistance', 'School enrollment', 'Passport application',
                  'Bank requirements', 'Business permit', 'Burial assistance', 'Legal assistance'];
const PER_PAGE = 15;

/* ---------- output and navigation ---------- */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- CSRF protection for every POST form ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['csrf'] ?? '';
    $expected = $_SESSION['csrf'] ?? '';
    if ($expected === '' || !is_string($sent) || !hash_equals($expected, $sent)) {
        http_response_code(400);
        die('This form has expired. Go back, refresh the page, and try again.');
    }
}

function require_post(string $fallback = 'index.php'): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        redirect($fallback);
    }
    verify_csrf();
}

/* ---------- formatting ---------- */
function full_name(array $r): string
{
    return $r['last_name'] . ', ' . $r['first_name']
        . ($r['middle_name'] !== '' ? ' ' . $r['middle_name'] : '')
        . ($r['suffix'] !== '' ? ' ' . $r['suffix'] : '');
}

function display_name(array $r): string
{
    return $r['first_name'] . ($r['middle_name'] !== '' ? ' ' . $r['middle_name'] : '')
        . ' ' . $r['last_name'] . ($r['suffix'] !== '' ? ' ' . $r['suffix'] : '');
}

function age_from(string $birth): int
{
    return (new DateTime($birth))->diff(new DateTime('today'))->y;
}

function peso($amount): string
{
    return '₱' . number_format((float) $amount, 0);
}

function fee_text($amount): string
{
    return (float) $amount > 0 ? peso($amount) : 'Free';
}

function fmt_date(?string $date): string
{
    return $date ? date('F j, Y', strtotime($date)) : '';
}

function fmt_short(?string $date): string
{
    return $date ? date('M j, Y', strtotime($date)) : '';
}

function ordinal(int $n): string
{
    $suffix = ['th', 'st', 'nd', 'rd'];
    $v = $n % 100;
    return $n . ($suffix[($v - 20) % 10] ?? $suffix[$v] ?? $suffix[0]);
}

function queue_no($n): string
{
    return str_pad((string) $n, 3, '0', STR_PAD_LEFT);
}

function status_pill(string $status): string
{
    return '<span class="pill ' . e($status) . '">' . e(STATUSES[$status] ?? $status) . '</span>';
}

function render_bars(array $items): string
{
    $max = max(array_merge([1], array_values($items)));
    $html = '<div class="bars">';
    foreach ($items as $label => $value) {
        $pct = round($value / $max * 100);
        $html .= '<div class="bar"><span>' . e($label) . '</span><div class="track"><div class="fill" style="width:'
            . $pct . '%"></div></div><span class="v">' . (int) $value . '</span></div>';
    }
    return $html . '</div>';
}

/* ---------- pagination ---------- */
function current_page(): int
{
    return max(1, (int) ($_GET['page'] ?? 1));
}

function pagination(int $total, int $perPage, int $page): string
{
    $pages = (int) ceil($total / $perPage);
    if ($pages <= 1) {
        return '';
    }
    $html = '<nav class="pager" aria-label="Pages">';
    for ($p = 1; $p <= $pages; $p++) {
        $qs = http_build_query(array_merge($_GET, ['page' => $p]));
        $html .= $p === $page
            ? '<span class="current" aria-current="page">' . $p . '</span>'
            : '<a href="?' . e($qs) . '">' . $p . '</a>';
    }
    return $html . '</nav>';
}

/* ---------- small reusable forms ---------- */
function approve_form(int $id, string $back): string
{
    return '<form method="post" action="update_status.php" class="inline">' . csrf_field()
        . '<input type="hidden" name="id" value="' . $id . '">'
        . '<input type="hidden" name="action" value="approve">'
        . '<input type="hidden" name="back" value="' . e($back) . '">'
        . '<button class="btn sm primary">Approve</button></form>';
}

function request_actions(array $r, string $back): string
{
    $id = (int) $r['id'];
    $html = '';
    if ($r['status'] === 'pending') {
        $html .= approve_form($id, $back)
            . '<a class="btn sm" href="update_status.php?action=reject&amp;id=' . $id . '&amp;back=' . e($back) . '">Reject</a>';
    } elseif ($r['status'] === 'approved') {
        $html .= '<a class="btn sm" href="certificate.php?id=' . $id . '" target="_blank">View certificate</a>'
            . '<a class="btn sm primary" href="update_status.php?action=release&amp;id=' . $id . '&amp;back=' . e($back) . '">Mark as released</a>';
    } elseif ($r['status'] === 'released') {
        $html .= '<a class="btn sm" href="certificate.php?id=' . $id . '" target="_blank">View certificate</a>';
    }
    return $html;
}

/* ---------- settings (barangay name, officials) ---------- */
function settings(PDO $pdo): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = $pdo->query('SELECT name, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        $cache += ['barangay' => 'Barangay', 'locality' => '', 'province' => '', 'captain' => '', 'secretary' => ''];
    }
    return $cache;
}

/* ---------- resident form validation (used by add and update) ---------- */
function clean_resident_input(array $in): array
{
    $g = function (string $key) use ($in): string {
        return trim((string) ($in[$key] ?? ''));
    };
    $d = [
        'last_name' => $g('last_name'), 'first_name' => $g('first_name'), 'middle_name' => $g('middle_name'),
        'suffix' => $g('suffix'), 'birth_date' => $g('birth_date'), 'sex' => $g('sex'),
        'civil_status' => $g('civil_status'), 'purok' => $g('purok'), 'street' => $g('street'),
        'occupation' => $g('occupation'), 'contact_no' => $g('contact_no'), 'household_no' => $g('household_no'),
    ];
    $errors = [];
    $required = [
        'last_name' => 'Enter the last name.', 'first_name' => 'Enter the first name.',
        'birth_date' => 'Enter the birth date.', 'sex' => 'Select the sex.',
        'civil_status' => 'Select the civil status.', 'purok' => 'Select the purok.',
    ];
    foreach ($required as $key => $message) {
        if ($d[$key] === '') {
            $errors[$key] = $message;
        }
    }
    if ($d['birth_date'] !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $d['birth_date']);
        if (!$dt || $dt->format('Y-m-d') !== $d['birth_date']) {
            $errors['birth_date'] = 'Enter a valid birth date.';
        } elseif ($dt > new DateTime('today')) {
            $errors['birth_date'] = 'Birth date cannot be in the future.';
        }
    }
    if ($d['sex'] !== '' && !in_array($d['sex'], SEXES, true)) {
        $errors['sex'] = 'Select the sex.';
    }
    if ($d['civil_status'] !== '' && !in_array($d['civil_status'], CIVIL_STATUSES, true)) {
        $errors['civil_status'] = 'Select the civil status.';
    }
    if ($d['purok'] !== '' && !in_array($d['purok'], PUROKS, true)) {
        $errors['purok'] = 'Select the purok.';
    }
    $limits = ['last_name' => 60, 'first_name' => 60, 'middle_name' => 60, 'suffix' => 10, 'street' => 100,
               'occupation' => 100, 'household_no' => 20];
    foreach ($limits as $key => $max) {
        if (text_len($d[$key]) > $max) {
            $errors[$key] = 'Use ' . $max . ' characters or fewer.';
        }
    }
    if ($d['contact_no'] !== '' && !preg_match('/^[0-9 +\-()]{1,20}$/', $d['contact_no'])) {
        $errors['contact_no'] = 'Use digits, spaces, +, -, or parentheses only (20 characters at most).';
    }
    return [$d, $errors];
}

/* ---------- text helpers (use mbstring when it is enabled, which XAMPP does by default) ---------- */
function text_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function text_upper(string $s): string
{
    return function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s);
}

function text_lower(string $s): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
}
