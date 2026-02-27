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

// Last badge (new)
$lastBadgeStmt = $pdo->prepare("
    SELECT b.name, b.description, ub.awarded_at
    FROM user_badges ub
    JOIN badges b ON b.id = ub.badge_id
    WHERE ub.user_id = ?
    ORDER BY ub.awarded_at DESC
    LIMIT 1
");
$lastBadgeStmt->execute([$userId]);
$lastBadge = $lastBadgeStmt->fetch();

// ---------- Quiz sessions summary ----------
$quizSummaryStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_sessions,
        COALESCE(AVG(score_percent), 0) AS avg_score
    FROM quiz_sessions
    WHERE user_id = ?
");
$quizSummaryStmt->execute([$userId]);
$quizSummary = $quizSummaryStmt->fetch() ?: ['total_sessions' => 0, 'avg_score' => 0];

// last 5 sessions
$quizSessionsStmt = $pdo->prepare("
    SELECT total_questions, correct_answers, score_percent, started_at, finished_at
    FROM quiz_sessions
    WHERE user_id = ?
    ORDER BY started_at DESC
    LIMIT 5
");
$quizSessionsStmt->execute([$userId]);
$lastQuizSessions = $quizSessionsStmt->fetchAll();

// last 3 sessions (new: recent activity)
$recentQuizStmt = $pdo->prepare("
    SELECT total_questions, correct_answers, score_percent, started_at
    FROM quiz_sessions
    WHERE user_id = ?
    ORDER BY started_at DESC
    LIMIT 3
");
$recentQuizStmt->execute([$userId]);
$recentQuiz = $recentQuizStmt->fetchAll();

// ---------- Quiz attempts summary (questions) ----------
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
if ((int)$attempts['total_attempts'] > 0) {
    $quizAccuracy = ($attempts['correct_attempts'] / $attempts['total_attempts']) * 100;
}

// ---------- Phishing simulation summary ----------
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
if ((int)$phish['total_attempts'] > 0) {
    $phishAccuracy = ($phish['correct_attempts'] / $phish['total_attempts']) * 100;
}

// last 5 phishing attempts
$phishLastStmt = $pdo->prepare("
    SELECT email_id, user_choice, correct, attempted_at
    FROM phishing_attempts
    WHERE user_id = ?
    ORDER BY attempted_at DESC
    LIMIT 5
");
$phishLastStmt->execute([$userId]);
$lastPhishAttempts = $phishLastStmt->fetchAll();

// last 3 phishing attempts (new: recent activity)
$recentPhishStmt = $pdo->prepare("
    SELECT email_id, correct, attempted_at
    FROM phishing_attempts
    WHERE user_id = ?
    ORDER BY attempted_at DESC
    LIMIT 3
");
$recentPhishStmt->execute([$userId]);
$recentPhish = $recentPhishStmt->fetchAll();

// ---------- Global module progress ----------
$stTotal = $pdo->query("
    SELECT COUNT(*) AS total
    FROM modules
    WHERE is_active = 1
");
$totalModules = (int)($stTotal->fetch()['total'] ?? 0);

$stDone = $pdo->prepare("
    SELECT COUNT(*) AS done
    FROM module_progress
    WHERE user_id = ? AND completed = 1
");
$stDone->execute([$userId]);
$doneModules = (int)($stDone->fetch()['done'] ?? 0);

$progressPercent = $totalModules > 0 ? round(($doneModules / $totalModules) * 100) : 0;

// ---------- Per-module progress (new) ----------
$modulesStmt = $pdo->query("
    SELECT id, code, title, description, level, link
    FROM modules
    WHERE is_active = 1
    ORDER BY id ASC
");
$modules = $modulesStmt->fetchAll();

$progressStmt = $pdo->prepare("
    SELECT module_code, completed, completed_at
    FROM module_progress
    WHERE user_id = ?
");
$progressStmt->execute([$userId]);
$progressRows = $progressStmt->fetchAll();

$progressByCode = [];
foreach ($progressRows as $r) {
    if (!empty($r['module_code'])) {
        $progressByCode[$r['module_code']] = $r;
    }
}

include __DIR__ . '/_partials/header.php';
?>

<div class="card">
  <div class="hx">Dashboard — <?= htmlspecialchars($user['name']) ?></div>
  <p class="subtle">Overview of your training progress on the platform.</p>
</div>

<div class="row">

  <!-- Quiz summary -->
  <div class="card" style="flex:1; min-width:260px">
    <div class="card-title">Quiz — Summary</div>
    <p class="subtle">
      Total quiz sessions: <strong><?= (int)$quizSummary['total_sessions'] ?></strong><br>
      Average score: <strong><?= number_format((float)$quizSummary['avg_score'], 1) ?> %</strong><br>
      Questions answered: <strong><?= (int)$attempts['total_attempts'] ?></strong><br>
      Correct answers: <strong><?= (int)$attempts['correct_attempts'] ?></strong><br>
      Accuracy: <strong><?= number_format((float)$quizAccuracy, 1) ?> %</strong>
    </p>
    <a class="btn mt-2" href="/quiz.php">Start a new quiz</a>
  </div>

  <!-- Phishing summary -->
  <div class="card" style="flex:1; min-width:260px">
    <div class="card-title">Phishing Simulation — Summary</div>
    <p class="subtle">
      Total emails analyzed: <strong><?= (int)$phish['total_attempts'] ?></strong><br>
      Correct classifications: <strong><?= (int)$phish['correct_attempts'] ?></strong><br>
      Accuracy: <strong><?= number_format((float)$phishAccuracy, 1) ?> %</strong>
    </p>
    <a class="btn mt-2" href="/phishing.php">Go to simulation</a>
  </div>

  <!-- Last badge (new) -->
  <div class="card" style="flex:1; min-width:260px">
    <div class="card-title">Last badge earned</div>
    <?php if (!$lastBadge): ?>
      <p class="subtle">No badge yet. Keep training to unlock your first badge!</p>
    <?php else: ?>
      <p class="subtle">
        <strong><?= htmlspecialchars($lastBadge['name']) ?></strong><br>
        <?= htmlspecialchars($lastBadge['description']) ?><br>
        <small>Awarded at: <?= htmlspecialchars($lastBadge['awarded_at']) ?></small>
      </p>
    <?php endif; ?>
  </div>

  <!-- Badges list (existing) -->
  <div class="card" style="flex:1; min-width:260px">
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

<!-- Global progression (existing, unchanged) -->
<div class="card">
  <div class="hx">Progression globale</div>

  <p class="subtle">
    <?= $doneModules ?> modules complétés / <?= $totalModules ?>
  </p>

  <div style="
      width:100%;
      background:#1a1f2b;
      border-radius:8px;
      overflow:hidden;
      height:18px;
      margin-top:8px;
  ">
    <div style="
        width:<?= (int)$progressPercent ?>%;
        background:linear-gradient(90deg,#00f5ff,#00ff90);
        height:100%;
        transition:0.4s;
    "></div>
  </div>

  <p class="subtle mt-1">
    <?= (int)$progressPercent ?>% terminé
  </p>
</div>

<!-- Per-module progress (new) -->
<div class="card">
  <div class="hx">Module progress</div>

  <?php if (!$modules): ?>
    <p class="subtle">No active modules found.</p>
  <?php else: ?>
    <div class="row">
      <?php foreach ($modules as $m): ?>
        <?php
          $code = $m['code'] ?? '';
          $p = ($code && isset($progressByCode[$code])) ? $progressByCode[$code] : null;
          $isDone = ($p && (int)$p['completed'] === 1);
        ?>
        <div class="card" style="flex:1; min-width:260px">
          <div class="card-title"><?= htmlspecialchars($m['title']) ?></div>
          <p class="subtle"><?= htmlspecialchars($m['description']) ?></p>

          <?php if ($isDone): ?>
            <span class="badge badge-ok">✅ Completed</span>
            <?php if (!empty($p['completed_at'])): ?>
              <div class="subtle mt-1"><small>Completed at: <?= htmlspecialchars($p['completed_at']) ?></small></div>
            <?php endif; ?>
          <?php else: ?>
            <span class="badge badge-warn">⏳ To do</span>
          <?php endif; ?>

          <div class="mt-2">
            <a class="btn btn-primary" href="<?= htmlspecialchars($m['link']) ?>">Open</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Recent quiz sessions (existing) -->
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
            <td style="padding:6px;"><?= number_format((float)$s['score_percent'], 1) ?> %</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<!-- Recent phishing attempts (existing) -->
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
              <?php if ((int)$a['correct'] === 1): ?>
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

<!-- Recent activity (new, compact) -->
<div class="card">
  <div class="hx">Recent activity (quick view)</div>

  <div class="row">
    <div class="card" style="flex:1; min-width:260px">
      <div class="card-title">Last 3 quiz sessions</div>
      <?php if (!$recentQuiz): ?>
        <p class="subtle">No recent quiz sessions.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($recentQuiz as $q): ?>
            <li class="subtle">
              <?= htmlspecialchars($q['started_at']) ?> —
              <strong><?= number_format((float)$q['score_percent'], 1) ?>%</strong>
              (<?= (int)$q['correct_answers'] ?>/<?= (int)$q['total_questions'] ?>)
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="card" style="flex:1; min-width:260px">
      <div class="card-title">Last 3 phishing attempts</div>
      <?php if (!$recentPhish): ?>
        <p class="subtle">No recent phishing attempts.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($recentPhish as $p): ?>
            <li class="subtle">
              <?= htmlspecialchars($p['attempted_at']) ?> —
              Email #<?= (int)$p['email_id'] ?> —
              <?php if ((int)$p['correct'] === 1): ?>
                <span class="badge badge-ok">Correct</span>
              <?php else: ?>
                <span class="badge badge-warn">Incorrect</span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>