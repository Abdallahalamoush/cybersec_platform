<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_auth();

$user = current_user();
$userId = $user['id'];

// check completion
$check = $pdo->prepare("
    SELECT completed
    FROM module_progress
    WHERE user_id = ? AND module_code = 'social'
");
$check->execute([$userId]);
$row = $check->fetch();
$completed = ($row && (int)$row['completed'] === 1);

include __DIR__ . '/_partials/header.php';
?>

<div class="card">
  <div class="hx">Social Engineering — Learning Module</div>

  <p class="subtle">
    Social engineering is when an attacker manipulates a person instead of hacking a system.
    The goal is usually to make you click, share information, or trust the attacker.
  </p>

  <div class="card" style="margin-top:12px;">
    <div class="card-title">Common manipulation techniques</div>
    <ul class="subtle">
      <li><strong>Urgency:</strong> “Do it now or you lose access.”</li>
      <li><strong>Authority:</strong> “I’m IT / your boss / bank security.”</li>
      <li><strong>Fear:</strong> “Your account is compromised!”</li>
      <li><strong>Trust:</strong> pretending to be a colleague or service.</li>
      <li><strong>Reward:</strong> “You won a gift / refund.”</li>
    </ul>
  </div>

  <div class="card" style="margin-top:12px;">
    <div class="card-title">How to defend yourself</div>
    <ul class="subtle">
      <li>Pause and verify before acting (especially with urgency).</li>
      <li>Check sender identity independently (not using their link).</li>
      <li>Never share passwords or MFA codes.</li>
      <li>When in doubt, contact the company using the official website.</li>
    </ul>
  </div>

  <?php if ($completed): ?>
    <p class="badge badge-ok mt-2">✔ Module completed</p>
  <?php endif; ?>

  <a class="btn btn-primary mt-2" href="/social_quiz.php">Take the Social Engineering Quiz</a>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
