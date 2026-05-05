<?php require_once __DIR__ . '/../../includes/session.php';
$cp = basename($_SERVER['PHP_SELF'], '.php');
?>
<!doctype html>
<html lang="en" class="body-grid">
<head>
  <meta charset="utf-8">
  <title>Cyber Awareness // Secure Access</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#030508">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700&family=Rajdhani:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Share+Tech+Mono&family=Syne:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>

<header class="header">
  <div class="container spread" style="padding-top:16px; padding-bottom:16px;">

    <a href="/index.php" class="brand">
      <div class="logo">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
      </div>
      <span>Cyber Awareness</span>
      <span class="badge">v4.1.0</span>
    </a>

    <nav class="nav">
      <a href="/index.php"    <?= $cp==='index'     ? 'class="nav-active"' : '' ?>>Home</a>
      <?php if (current_user()): ?>
        <?php if ((current_user()['role'] ?? 'user') === 'admin'): ?>
          <a href="/admin/modules.php" <?= $cp==='modules' ? 'class="nav-active"' : '' ?>>Admin</a>
        <?php endif; ?>
        <a href="/dashboard.php" <?= $cp==='dashboard' ? 'class="nav-active"' : '' ?>>Command Center</a>
        <a href="/quiz.php"      <?= $cp==='quiz'      ? 'class="nav-active"' : '' ?>>Simulations</a>
        <a href="/challenge.php" <?= $cp==='challenge' ? 'class="nav-active"' : '' ?> style="color:var(--red);">⚡ Challenge</a>
        <a href="/phishing.php"  <?= $cp==='phishing'  ? 'class="nav-active"' : '' ?>>Phishing</a>
        <a href="/logout.php" style="color:var(--red); text-shadow:0 0 5px rgba(255,42,95,0.5); margin-left:8px;">Disconnect</a>
      <?php else: ?>
        <a href="/login.php"    <?= $cp==='login'    ? 'class="nav-active"' : '' ?>>Authenticate</a>
        <a href="/register.php" class="btn btn-primary" style="padding:8px 16px; font-size:13px; margin-left:8px;">Initialize</a>
      <?php endif; ?>
    </nav>
  </div>

  <div class="header-status">
    <div class="container spread" style="padding-top:4px; padding-bottom:4px;">
      <span class="status-pill">
        <span class="status-dot"></span>
        NETWORK SECURE // SYSTEMS NOMINAL
      </span>
      <span style="font-family:var(--mono); font-size:11px; letter-spacing:2px; color:var(--text-dim); text-transform:uppercase;">
        <?php if (current_user()): ?>
          ACTIVE ID — <span style="color:var(--cyan); text-shadow:0 0 5px rgba(0,240,255,0.5);"><?= htmlspecialchars(strtoupper(current_user()['name'])) ?></span>
        <?php else: ?>
          UNIDENTIFIED HOST SESSION
        <?php endif; ?>
      </span>
    </div>
  </div>
</header>

<main class="container stack">