<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_auth();

require __DIR__ . '/../includes/phishing_emails.php';

$user = current_user();
$userId = $user['id'];


$stmt = $pdo->prepare("
    SELECT email_id,
           MAX(correct) AS ever_correct,
           COUNT(*) AS attempts_count
    FROM phishing_attempts
    WHERE user_id = ?
    GROUP BY email_id
");
$stmt->execute([$userId]);
$statusRows = $stmt->fetchAll();

$statusByEmail = [];
foreach ($statusRows as $row) {
    $statusByEmail[(int)$row['email_id']] = [
        'ever_correct'   => (int)$row['ever_correct'],
        'attempts_count' => (int)$row['attempts_count']
    ];
}

include __DIR__ . '/_partials/header.php';
?>

<div class="card">
  <div class="hx">Phishing Simulation — Inbox</div>
  <p class="subtle">
    Analyze each email and decide whether it is <strong>legitimate</strong> or a <strong>phishing</strong> attempt.
  </p>

  <div class="card" style="margin-top:12px;">
    <?php foreach ($PHISHING_EMAILS as $id => $mail): 
        $st = $statusByEmail[$id] ?? null;
    ?>
      <div class="spread" style="padding:8px 0; border-bottom:1px solid #1a2a42;">
        <div>
          <a href="/phishing_view.php?id=<?= (int)$id ?>" style="text-decoration:none; color:inherit;">
            <strong><?= htmlspecialchars($mail['sender']) ?></strong><br>
            <span class="subtle"><?= htmlspecialchars($mail['subject']) ?></span>
          </a>
        </div>
        <div style="text-align:right;">
          <?php if ($st): ?>
            <?php if ($st['ever_correct']): ?>
              <span class="badge badge-ok">Mastered</span><br>
            <?php else: ?>
              <span class="badge badge-warn">To improve</span><br>
            <?php endif; ?>
            <small class="subtle"><?= (int)$st['attempts_count'] ?> attempt(s)</small>
          <?php else: ?>
            <span class="badge">Not answered yet</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
