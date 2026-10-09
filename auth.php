<?php
// Include this at the top of every page that needs a signed-in staff member.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['user'])) {
    redirect('login.php');
}

function require_admin(): void
{
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        flash('error', 'Only an administrator can do that.');
        redirect('index.php');
    }
}
