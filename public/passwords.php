<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_auth();

$user = current_user();
$userId = $user['id'];

$completed = false;

// verifier si l'utilisateur a déjà complété le module
$check = $pdo->prepare("
    SELECT completed 
    FROM module_progress 
    WHERE user_id = ? AND module_code = 'passwords'
");
$check->execute([$userId]);
$row = $check->fetch();

if ($row && $row['completed']) {
    $completed = true;
}

include __DIR__ . '/_partials/header.php';
?>

<div class="card">
  <div class="hx">Password Security — Learning Module</div>

  <p class="subtle">
    Understanding password security is one of the most important skills in cybersecurity.
  </p>

  <ul>
    <li>Use long passwords (12+ characters).</li>
    <li>Never reuse the same password across sites.</li>
    <li>Use a password manager to store them safely.</li>
    <li>Enable Multi-Factor Authentication whenever possible.</li>
  </ul>

  <p class="subtle mt-2">
    When you're ready, take the short quiz below to validate your understanding.
  </p>

  <?php if ($completed): ?>
    <p class="badge badge-ok mt-2">
      ✔ You have already completed this module.
    </p>
  <?php endif; ?>

  <a class="btn btn-primary mt-2" href="/passwords_quiz.php">
    Take the Password Quiz
  </a>

</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
