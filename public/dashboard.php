<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_auth();

$user = current_user();
$userId = $user['id'];

// ---------- Badges ----------
$badgeStmt = $pdo->prepare("
    SELECT b.name, b.description, ub.awarded_at
    FROM user_badges ub
    JOIN badges b ON b.id = ub.badge_id
    WHERE ub.user_id = ?
    ORDER BY ub.awarded_at ASC
");
$badgeStmt->execute([$userId]);
$userBadges = $badgeStmt->fetchAll();


// ---------- Résumé des sessions de quiz ----------
$quizSummaryStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_sessions,
        COALESCE(AVG(score_percent), 0) AS avg_score
    FROM quiz_sessions
    WHERE user_id = ?
");
$quizSummaryStmt->execute([$userId]);
$quizSummary = $quizSummaryStmt->fetch() ?: ['total_sessions' => 0, 'avg_score' => 0];

// dernier 5 sessions
$quizSessionsStmt = $pdo->prepare("
    SELECT total_questions, correct_answers, score_percent, started_at, finished_at
    FROM quiz_sessions
    WHERE user_id = ?
    ORDER BY started_at DESC
    LIMIT 5
");
$quizSessionsStmt->execute([$userId]);
$lastQuizSessions = $quizSessionsStmt->fetchAll();

// ---------- Resume des Quiz attempts  (questions) ----------
$attemptsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_attempts,
        COALESCE(SUM(is_correct), 0) AS correct_attempts
    FROM attempts
    WHERE user_id = ?
");
$attemptsStmt->execute([$userId]);
$attempts = $attemptsStmt->fetch() ?: ['total_attempts' => 0, 'correct_attempts' => 0];

$quizAccuracy = 0;
if ($attempts['total_attempts'] > 0) {
    $quizAccuracy = ($attempts['correct_attempts'] / $attempts['total_attempts']) * 100;
}

// ---------- Resume Phishing simulation ----------
$phishSummaryStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_attempts,
        COALESCE(SUM(correct), 0) AS correct_attempts
    FROM phishing_attempts
    WHERE user_id = ?
");
$phishSummaryStmt->execute([$userId]);
$phish = $phishSummaryStmt->fetch() ?: ['total_attempts' => 0, 'correct_attempts' => 0];

$phishAccuracy = 0;
if ($phish['total_attempts'] > 0) {
    $phishAccuracy = ($phish['correct_attempts'] / $phish['total_attempts']) * 100;
}

// Les derniers 5 phishing attempts
$phishLastStmt = $pdo->prepare("
    SELECT email_id, user_choice, correct, attempted_at
    FROM phishing_attempts
    WHERE user_id = ?
    ORDER BY attempted_at DESC
    LIMIT 5
");
$phishLastStmt->execute([$userId]);
$lastPhishAttempts = $phishLastStmt->fetchAll();

include __DIR__ . '/_partials/header.php';
?>

<div class="card">
  <div class="hx">Dashboard — <?= htmlspecialchars($user['name']) ?></div>
  <p class="subtle">Overview of your training progress on the platform.</p>
</div>

<div class="row">

  <!-- Resume Quiz  -->
  <div class="card" style="flex:1; min-width:260px">
    <div class="card-title">Quiz — Summary</div>
    <p class="subtle">
      Total quiz sessions: <strong><?= (int)$quizSummary['total_sessions'] ?></strong><br>
      Average score: <strong><?= number_format($quizSummary['avg_score'], 1) ?> %</strong><br>
      Questions answered: <strong><?= (int)$attempts['total_attempts'] ?></strong><br>
      Correct answers: <strong><?= (int)$attempts['correct_attempts'] ?></strong><br>
      Accuracy: <strong><?= number_format($quizAccuracy, 1) ?> %</strong>
    </p>
    <a class="btn mt-2" href="/quiz.php">Start a new quiz</a>
  </div>

  <!-- Phishing simulation  -->
  <div class="card" style="flex:1; min-width:260px">
    <div class="card-title">Phishing Simulation — Summary</div>
    <p class="subtle">
      Total emails analyzed: <strong><?= (int)$phish['total_attempts'] ?></strong><br>
      Correct classifications: <strong><?= (int)$phish['correct_attempts'] ?></strong><br>
      Accuracy: <strong><?= number_format($phishAccuracy, 1) ?> %</strong>
    </p>
    <a class="btn mt-2" href="/phishing.php">Go to simulation</a>
  </div>

  <div class="card">
  <div class="hx">Badges</div>

  <?php if (!$userBadges): ?>
    <p class="subtle">No badges unlocked yet. Keep training to earn them!</p>
  <?php else: ?>
    <ul>
      <?php foreach ($userBadges as $b): ?>
        <li class="subtle">
          <strong><?= htmlspecialchars($b['name']) ?></strong><br>
          <?= htmlspecialchars($b['description']) ?><br>
          <small>Awarded at: <?= htmlspecialchars($b['awarded_at']) ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

</div>


<div class="card">
  <div class="hx">Recent quiz sessions</div>
  <?php if (!$lastQuizSessions): ?>
    <p class="subtle">No quiz sessions yet.</p>
  <?php else: ?>
    <table style="width:100%; border-collapse:collapse; font-size:14px;">
      <thead>
        <tr>
          <th style="border-bottom:1px solid #1a2a42; text-align:left; padding:6px;">Date</th>
          <th style="border-bottom:1px solid #1a2a42; text-align:left; padding:6px;">Questions</th>
          <th style="border-bottom:1px solid #1a2a42; text-align:left; padding:6px;">Correct</th>
          <th style="border-bottom:1px solid #1a2a42; text-align:left; padding:6px;">Score</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lastQuizSessions as $s): ?>
          <tr>
            <td style="padding:6px;"><?= htmlspecialchars($s['started_at']) ?></td>
            <td style="padding:6px;"><?= (int)$s['total_questions'] ?></td>
            <td style="padding:6px;"><?= (int)$s['correct_answers'] ?></td>
            <td style="padding:6px;"><?= number_format($s['score_percent'], 1) ?> %</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="card">
  <div class="hx">Recent phishing attempts</div>
  <?php if (!$lastPhishAttempts): ?>
    <p class="subtle">No phishing simulation activity yet.</p>
  <?php else: ?>
    <table style="width:100%; border-collapse:collapse; font-size:14px;">
      <thead>
        <tr>
          <th style="border-bottom:1px solid #1a2a42; text-align:left; padding:6px;">Email ID</th>
          <th style="border-bottom:1px solid #1a2a42; text-align:left; padding:6px;">Your choice</th>
          <th style="border-bottom:1px solid #1a2a42; text-align:left; padding:6px;">Result</th>
          <th style="border-bottom:1px solid #1a2a42; text-align:left; padding:6px;">Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lastPhishAttempts as $a): ?>
          <tr>
            <td style="padding:6px;"><?= (int)$a['email_id'] ?></td>
            <td style="padding:6px;"><?= htmlspecialchars($a['user_choice']) ?></td>
            <td style="padding:6px;">
              <?php if ($a['correct']): ?>
                <span class="badge badge-ok">Correct</span>
              <?php else: ?>
                <span class="badge badge-warn">Incorrect</span>
              <?php endif; ?>
            </td>
            <td style="padding:6px;"><?= htmlspecialchars($a['attempted_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
