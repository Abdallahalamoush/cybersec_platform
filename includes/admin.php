<?php
require_once __DIR__ . '/session.php';

function require_admin(): void {
    $u = current_user();
    if (!$u || ($u['role'] ?? 'user') !== 'admin') {
        http_response_code(403);
        die("Accès refusé.");
    }
}