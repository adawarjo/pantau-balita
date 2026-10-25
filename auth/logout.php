<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

// Logout hanya melalui POST + CSRF agar tidak bisa dipicu tautan pihak lain
if (is_post()) {
    csrf_verify();
    logout_user();
    set_flash('success', 'Anda telah keluar.');
}
redirect('auth/login.php');
