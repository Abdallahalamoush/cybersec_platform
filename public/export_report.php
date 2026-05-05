<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/levels.php';
require_auth();

$user   = current_user();
$userId = $user['id'];

// ── Stats ───────────────────────────────────────────────────
$badgeCount = get_user_badge_count($pdo, $userId);
$level      = compute_user_level($badgeCount);

$st = $pdo->prepare("
    SELECT b.name, b.code, b.description, ub.awarded_at
    FROM user_badges ub
    JOIN badges b ON b.id = ub.badge_id
    WHERE ub.user_id = ?
    ORDER BY ub.awarded_at ASC
");
$st->execute([$userId]);
$badges = $st->fetchAll();

$st = $pdo->prepare("
    SELECT COUNT(*) AS sessions,
           COALESCE(AVG(score_percent), 0) AS avg_score,
           COALESCE(MAX(score_percent), 0) AS best_score
    FROM quiz_sessions
    WHERE user_id = ? AND finished_at IS NOT NULL
");
$st->execute([$userId]);
$quizStats = $st->fetch() ?: ['sessions'=>0,'avg_score'=>0,'best_score'=>0];

$st = $pdo->prepare("
    SELECT COUNT(*) AS total,
           COALESCE(SUM(correct), 0) AS correct
    FROM phishing_attempts WHERE user_id = ?
");
$st->execute([$userId]);
$phishStats = $st->fetch() ?: ['total'=>0,'correct'=>0];
$phishAcc = $phishStats['total'] > 0
    ? round(($phishStats['correct'] / $phishStats['total']) * 100) : 0;

$st = $pdo->prepare("
    SELECT COUNT(*) AS sessions,
           COALESCE(MAX(score), 0) AS best,
           COALESCE(MIN(duration_seconds), 0) AS fastest
    FROM challenge_sessions
    WHERE user_id = ? AND finished_at IS NOT NULL
");
$st->execute([$userId]);
$chStats = $st->fetch() ?: ['sessions'=>0,'best'=>0,'fastest'=>0];

$stTotal = $pdo->query("SELECT COUNT(*) AS t FROM modules WHERE is_active = 1");
$totalModules = (int)($stTotal->fetch()['t'] ?? 0);
$stDone = $pdo->prepare("SELECT COUNT(*) AS d FROM module_progress WHERE user_id = ? AND completed = 1");
$stDone->execute([$userId]);
$doneModules = (int)($stDone->fetch()['d'] ?? 0);
$progress = $totalModules > 0 ? round(($doneModules / $totalModules) * 100) : 0;

$st = $pdo->prepare("
    SELECT m.title, m.code, mp.completed, mp.completed_at
    FROM modules m
    LEFT JOIN module_progress mp ON mp.module_code = m.code AND mp.user_id = ?
    WHERE m.is_active = 1
    ORDER BY m.id ASC
");
$st->execute([$userId]);
$modulesDetail = $st->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Personal Report — <?= htmlspecialchars($user['name']) ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Helvetica Neue', Arial, sans-serif;
      background: #f5f5f7;
      color: #1a1a1a;
      padding: 32px 16px;
      line-height: 1.5;
    }
    .sheet {
      max-width: 820px;
      margin: 0 auto;
      background: #fff;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
      padding: 48px;
      border-radius: 4px;
    }
    .toolbar {
      max-width: 820px;
      margin: 0 auto 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }
    .toolbar p { font-size: 13px; color: #555; }
    .btn {
      background: #1a1a1a; color: #fff; border: none; border-radius: 6px;
      padding: 10px 20px; font-size: 14px; cursor: pointer; text-decoration: none;
      display: inline-block;
    }
    .btn:hover { background: #333; }
    .btn-light { background: #fff; color: #1a1a1a; border: 1px solid #ccc; }

    h1 { font-size: 28px; font-weight: 700; margin-bottom: 4px; letter-spacing: -0.5px; }
    h2 {
      font-size: 14px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 1.5px; color: #444; margin: 28px 0 12px;
      padding-bottom: 6px; border-bottom: 2px solid #1a1a1a;
    }
    .header-block {
      border-bottom: 3px solid #1a1a1a; padding-bottom: 20px; margin-bottom: 28px;
      display: flex; justify-content: space-between; align-items: flex-end; gap: 16px;
      flex-wrap: wrap;
    }
    .meta { font-size: 13px; color: #666; }
    .level-pill {
      display: inline-block; padding: 6px 14px; border-radius: 4px;
      font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
      color: <?= $level['color'] ?>;
      border: 2px solid <?= $level['color'] ?>;
      background: <?= $level['color'] ?>10;
    }

    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 14px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .stat-card {
      background: #fafafa; border: 1px solid #e5e5e5; padding: 14px 16px; border-radius: 6px;
    }
    .stat-card .label {
      font-size: 11px; text-transform: uppercase; letter-spacing: 1px;
      color: #777; margin-bottom: 6px;
    }
    .stat-card .value { font-size: 26px; font-weight: 700; color: #1a1a1a; line-height: 1; }
    .stat-card .sub { font-size: 11px; color: #888; margin-top: 4px; }

    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th { text-align: left; padding: 8px 10px; background: #1a1a1a; color: #fff;
         font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
    td { padding: 8px 10px; border-bottom: 1px solid #eee; }

    .badge-row {
      display: flex; align-items: center; gap: 10px; padding: 10px 0;
      border-bottom: 1px solid #eee;
    }
    .badge-row:last-child { border-bottom: none; }
    .badge-icon {
      width: 28px; height: 28px; border-radius: 4px; flex-shrink: 0;
      background: #1a1a1a; color: #fff;
      display: grid; place-items: center; font-size: 14px;
    }
    .progress-outer {
      width: 100%; height: 8px; background: #eaeaea; border-radius: 99px; overflow: hidden;
      margin-top: 6px;
    }
    .progress-inner { height: 100%; background: #1a1a1a; border-radius: 99px; }

    footer {
      margin-top: 36px; padding-top: 16px; border-top: 1px solid #ddd;
      font-size: 11px; color: #888; display: flex; justify-content: space-between;
    }

    @media print {
      body { background: #fff; padding: 0; }
      .toolbar { display: none !important; }
      .sheet { box-shadow: none; max-width: 100%; padding: 24px; border-radius: 0; }
      h1 { font-size: 22px; }
      h2 { margin-top: 18px; font-size: 12px; }
      .stat-card .value { font-size: 22px; }
      table { font-size: 12px; }
      @page { margin: 14mm; }
    }
  </style>
</head>
<body>

<div class="toolbar">
  <p>📄 Utilise <strong>Ctrl+P</strong> (ou <strong>⌘+P</strong>) puis "Enregistrer en PDF".</p>
  <div>
    <a href="/dashboard.php" class="btn btn-light">← Retour</a>
    <button class="btn" onclick="window.print()">Télécharger en PDF</button>
  </div>
</div>

<div class="sheet">

  <div class="header-block">
    <div>
      <p class="meta" style="margin-bottom:4px; text-transform:uppercase; letter-spacing:2px; font-size:10px;">
        Cyber Awareness Platform · Personal Report
      </p>
      <h1><?= htmlspecialchars($user['name']) ?></h1>
      <p class="meta"><?= htmlspecialchars($user['email']) ?></p>
    </div>
    <div style="text-align:right;">
      <span class="level-pill"><?= $level['icon'] ?> <?= $level['name'] ?></span>
      <p class="meta" style="margin-top:6px; font-size:11px;">
        Generated <?= date('d/m/Y H:i') ?>
      </p>
    </div>
  </div>

  <h2>Overall Progression</h2>
  <div class="grid-3">
    <div class="stat-card">
      <div class="label">Modules</div>
      <div class="value"><?= $doneModules ?>/<?= $totalModules ?></div>
      <div class="progress-outer"><div class="progress-inner" style="width:<?= $progress ?>%;"></div></div>
      <div class="sub"><?= $progress ?>% completed</div>
    </div>
    <div class="stat-card">
      <div class="label">Badges Earned</div>
      <div class="value"><?= $badgeCount ?></div>
      <div class="sub"><?= $level['subtitle'] ?></div>
    </div>
    <div class="stat-card">
      <div class="label">Operator Level</div>
      <div class="value" style="color:<?= $level['color'] ?>;"><?= $level['name'] ?></div>
      <div class="sub">
        <?php $next = next_level_threshold($badgeCount); ?>
        <?= $next === null ? 'Maxed out' : (($next - $badgeCount) . ' badge(s) to '.next_level_name($badgeCount)) ?>
      </div>
    </div>
  </div>

  <h2>Knowledge Assessments</h2>
  <div class="grid-3">
    <div class="stat-card">
      <div class="label">Sessions</div>
      <div class="value"><?= (int)$quizStats['sessions'] ?></div>
    </div>
    <div class="stat-card">
      <div class="label">Avg Score</div>
      <div class="value"><?= number_format((float)$quizStats['avg_score'], 1) ?>%</div>
    </div>
    <div class="stat-card">
      <div class="label">Best</div>
      <div class="value"><?= number_format((float)$quizStats['best_score'], 0) ?>%</div>
    </div>
  </div>

  <h2>Phishing Detection</h2>
  <div class="grid-2">
    <div class="stat-card">
      <div class="label">Inspections</div>
      <div class="value"><?= (int)$phishStats['total'] ?></div>
      <div class="sub"><?= (int)$phishStats['correct'] ?> correct</div>
    </div>
    <div class="stat-card">
      <div class="label">Accuracy</div>
      <div class="value"><?= $phishAcc ?>%</div>
    </div>
  </div>

  <?php if ((int)$chStats['sessions'] > 0): ?>
    <h2>Challenge Mode</h2>
    <div class="grid-3">
      <div class="stat-card">
        <div class="label">Runs</div>
        <div class="value"><?= (int)$chStats['sessions'] ?></div>
      </div>
      <div class="stat-card">
        <div class="label">Best Score</div>
        <div class="value"><?= (int)$chStats['best'] ?></div>
      </div>
      <div class="stat-card">
        <div class="label">Fastest</div>
        <div class="value"><?= (int)$chStats['fastest'] ?>s</div>
      </div>
    </div>
  <?php endif; ?>

  <h2>Modules Status</h2>
  <table>
    <thead>
      <tr>
        <th>Module</th>
        <th>Status</th>
        <th>Completed</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($modulesDetail as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['title']) ?></td>
          <td>
            <?php if ((int)($m['completed'] ?? 0) === 1): ?>
              <strong style="color:#1a8a3a;">✓ Secured</strong>
            <?php else: ?>
              <span style="color:#a07000;">Pending</span>
            <?php endif; ?>
          </td>
          <td>
            <?= !empty($m['completed_at']) ? date('d/m/Y', strtotime($m['completed_at'])) : '—' ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2>Badges Acquired</h2>
  <?php if (!$badges): ?>
    <p style="color:#888; font-size:13px;">Aucun badge décroché pour le moment.</p>
  <?php else: ?>
    <?php foreach ($badges as $b): ?>
      <div class="badge-row">
        <div class="badge-icon">★</div>
        <div style="flex:1;">
          <div style="font-weight:600; font-size:14px;"><?= htmlspecialchars($b['name']) ?></div>
          <div style="font-size:12px; color:#666;"><?= htmlspecialchars($b['description']) ?></div>
        </div>
        <div style="font-size:11px; color:#888;">
          <?= date('d/m/Y', strtotime($b['awarded_at'])) ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <footer>
    <span>Cyber Awareness Platform · v4.1 · Mois 7</span>
    <span>Report ID: <?= strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)) ?></span>
  </footer>

</div>

</body>
</html>