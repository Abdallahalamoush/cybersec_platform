<?php require_once __DIR__ . '/../../includes/session.php'; ?>
<!doctype html>
<html lang="fr" class="body-grid">
<head>
  <meta charset="utf-8">
  <title>Cyber Awareness</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0b0f14">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<header class="header">
  <div class="container spread">
    <div class="brand">
      <div class="logo">🔒</div>
      <div>Cyber Awareness</div>
      <span class="badge">MVP • M2</span>
    </div>
    <nav class="nav">
      <a href="/index.php">Accueil</a>
      <?php if (current_user()): ?>
    <a href="/dashboard.php">Dashboard</a>
    <a href="/quiz.php">Quiz</a>
    <a href="/phishing.php">Phishing</a>
    <a href="/logout.php">Logout</a>
  <?php else: ?>

        <a href="/login.php">Login</a>
        <a href="/register.php">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container stack">
