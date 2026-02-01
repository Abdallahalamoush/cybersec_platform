<?php
// includes/session.php
if (session_status() !== PHP_SESSION_ACTIVE) {
  ini_set('session.use_strict_mode', 1);
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false, 
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

function login_user(array $user): void {
  session_regenerate_id(true);
  $_SESSION['user'] = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
  ];
}
function logout_user(): void {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}
function current_user(): ?array {
  return $_SESSION['user'] ?? null;
}
function require_guest() {
  if (current_user()) { header('Location: /index.php'); exit; }
}

function require_auth() {
  if (!current_user()) { header('Location: /login.php'); exit; }
}

