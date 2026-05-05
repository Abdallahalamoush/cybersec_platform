<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/levels.php';
require_auth();

$user   = current_user();
$userId = $user['id'];

// ── Badges ───────────────────────────────────────────────
$badgeStmt = $pdo->prepare("
    SELECT b.name, b.description, ub.awarded_at
    FROM user_badges ub
    JOIN badges b ON b.id = ub.badge_id
    WHERE ub.user_id = ?
    ORDER BY ub.awarded_at DESC
");
$badgeStmt->execute([$userId]);
$userBadges = $badgeStmt->fetchAll();

// ── Level ────────────────────────────────────────────────
$badgeCount = count($userBadges);
$level      = compute_user_level($badgeCount);
$nextThr    = next_level_threshold($badgeCount);
$nextName   = next_level_name($badgeCount);
$lvlProg    = progress_to_next_level($badgeCount);

// ── Quiz summary ─────────────────────────────────────────
$quizSummaryStmt = $pdo->prepare("
    SELECT COUNT(*) AS total_sessions,
           COALESCE(AVG(score_percent), 0) AS avg_score
    FROM quiz_sessions WHERE user_id = ? AND finished_at IS NOT NULL
");
$quizSummaryStmt->execute([$userId]);
$quizSummary = $quizSummaryStmt->fetch() ?: ['total_sessions'=>0,'avg_score'=>0];

$attemptsStmt = $pdo->prepare("
    SELECT COUNT(*) AS total_attempts,
           COALESCE(SUM(is_correct), 0) AS correct_attempts
    FROM attempts WHERE user_id = ?
");
$attemptsStmt->execute([$userId]);
$attempts = $attemptsStmt->fetch() ?: ['total_attempts'=>0,'correct_attempts'=>0];
$quizAccuracy = $attempts['total_attempts'] > 0
    ? ($attempts['correct_attempts'] / $attempts['total_attempts']) * 100 : 0;

// ── Phishing ─────────────────────────────────────────────
$phishSummaryStmt = $pdo->prepare("
    SELECT COUNT(*) AS total_attempts,
           COALESCE(SUM(correct), 0) AS correct_attempts
    FROM phishing_attempts WHERE user_id = ?
");
$phishSummaryStmt->execute([$userId]);
$phish = $phishSummaryStmt->fetch() ?: ['total_attempts'=>0,'correct_attempts'=>0];
$phishAccuracy = $phish['total_attempts'] > 0
    ? ($phish['correct_attempts'] / $phish['total_attempts']) * 100 : 0;

// ── Challenge stats (Mois 7) ─────────────────────────────
$chStmt = $pdo->prepare("
    SELECT COUNT(*) AS sessions,
           COALESCE(MAX(score), 0) AS best,
           COALESCE(MIN(duration_seconds), 0) AS fastest
    FROM challenge_sessions
    WHERE user_id = ? AND finished_at IS NOT NULL
");
$chStmt->execute([$userId]);
$chStats = $chStmt->fetch() ?: ['sessions'=>0,'best'=>0,'fastest'=>0];

// ── Last quiz/phishing logs ──────────────────────────────
$quizSessionsStmt = $pdo->prepare("
    SELECT total_questions, correct_answers, score_percent, started_at, finished_at
    FROM quiz_sessions WHERE user_id = ? AND finished_at IS NOT NULL
    ORDER BY started_at DESC LIMIT 5
");
$quizSessionsStmt->execute([$userId]);
$lastQuizSessions = $quizSessionsStmt->fetchAll();

$phishLastStmt = $pdo->prepare("
    SELECT email_id, user_choice, correct, attempted_at
    FROM phishing_attempts WHERE user_id = ?
    ORDER BY attempted_at DESC LIMIT 5
");
$phishLastStmt->execute([$userId]);
$lastPhishAttempts = $phishLastStmt->fetchAll();

// ── Module progress ──────────────────────────────────────
$stTotal = $pdo->query("SELECT COUNT(*) AS total FROM modules WHERE is_active = 1");
$totalModules = (int)($stTotal->fetch()['total'] ?? 0);
$stDone = $pdo->prepare("SELECT COUNT(*) AS done FROM module_progress WHERE user_id = ? AND completed = 1");
$stDone->execute([$userId]);
$doneModules = (int)($stDone->fetch()['done'] ?? 0);
$progressPercent = $totalModules > 0 ? round(($doneModules / $totalModules) * 100) : 0;

$modulesStmt = $pdo->query("SELECT id, code, title, description, level, link FROM modules WHERE is_active = 1 ORDER BY id ASC");
$modules = $modulesStmt->fetchAll();
$progressStmt = $pdo->prepare("SELECT module_code, completed, completed_at FROM module_progress WHERE user_id = ?");
$progressStmt->execute([$userId]);
$progressByCode = [];
foreach ($progressStmt->fetchAll() as $r) {
    if (!empty($r['module_code'])) $progressByCode[$r['module_code']] = $r;
}

include __DIR__ . '/_partials/header.php';
?>

<!-- ── Header with Level ── -->
<div class="card" style="padding:28px 28px; border-top:2px solid <?= $level['color'] ?>;
                          background:radial-gradient(ellipse at top left, <?= $level['color'] ?>15, transparent 60%), var(--panel);
                          box-shadow:0 0 30px <?= $level['color'] ?>15, var(--shadow);">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
    <div style="display:flex; align-items:center; gap:20px;">
      <div style="width:64px; height:64px; border-radius:12px;
                  background:<?= $level['color'] ?>15; border:2px solid <?= $level['color'] ?>;
                  display:grid; place-items:center; color:<?= $level['color'] ?>;
                  font-family:var(--display); font-weight:800; font-size:32px;
                  box-shadow:0 0 20px <?= $level['glow'] ?>;">
        <?= $level['icon'] ?>
      </div>
      <div>
        <div class="mono" style="font-size:11px; letter-spacing:3px; color:<?= $level['color'] ?>;
                                  text-transform:uppercase; margin-bottom:4px;">
          OPERATOR LEVEL // <?= strtoupper($level['name']) ?>
        </div>
        <div class="hx" style="margin-bottom:0; font-size:24px;"><?= htmlspecialchars($user['name']) ?></div>
        <p class="subtle mono" style="font-size:12px; margin-top:2px;">
          <span style="color:var(--text-dim);"><?= htmlspecialchars($level['subtitle']) ?> ·</span>
          <?= $badgeCount ?> badge<?= $badgeCount > 1 ? 's' : '' ?>
        </p>
      </div>
    </div>
    <div style="text-align:right; background:rgba(0,0,0,0.4); padding:16px 24px; border-radius:8px;
                border:1px solid rgba(255,255,255,0.05); min-width:240px;">
      <div class="mono" style="font-size:11px; color:var(--text-dim); margin-bottom:8px; letter-spacing:2px;">
        OPERATOR CLEARANCE PROGRESS
      </div>
      <div style="font-family:var(--display); font-weight:800; font-size:36px;
                  color:var(--green); text-shadow:0 0 15px rgba(0, 255, 136, 0.5); line-height:1;">
        <?= $progressPercent ?>%
      </div>
      <div style="margin-top:12px;">
        <div class="progress-track" style="height:8px;">
          <div class="progress-fill" style="width:<?= $progressPercent ?>%;
               background:linear-gradient(90deg, var(--cyan), var(--green));
               box-shadow:0 0 10px rgba(0, 255, 136, 0.5);"></div>
        </div>
        <div class="mono" style="font-size:10px; color:var(--cyan); margin-top:6px;
                                   text-align:right; letter-spacing:1px;">
          <?= $doneModules ?> / <?= $totalModules ?> MODULES
        </div>
      </div>
    </div>
  </div>

  <!-- Level progress bar -->
  <div style="margin-top:20px; padding:14px 18px; background:rgba(0,0,0,0.3);
              border:1px solid <?= $level['color'] ?>30; border-radius:8px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:8px;">
      <span class="mono" style="font-size:11px; color:<?= $level['color'] ?>; letter-spacing:2px;">
        LEVEL PROGRESSION
      </span>
      <span class="mono" style="font-size:11px; color:var(--text-muted);">
        <?php if ($nextThr === null): ?>
          <span style="color:var(--green);">★ MAXED OUT — Elite Operator</span>
        <?php else: ?>
          <?= ($nextThr - $badgeCount) ?> more badge<?= ($nextThr - $badgeCount) > 1 ? 's' : '' ?> to <strong style="color:var(--text);"><?= $nextName ?></strong>
        <?php endif; ?>
      </span>
    </div>
    <div class="progress-track" style="height:5px;">
      <div class="progress-fill" style="width:<?= $lvlProg ?>%;
           background:<?= $level['color'] ?>;
           box-shadow:0 0 8px <?= $level['glow'] ?>;"></div>
    </div>
  </div>

  <!-- Quick actions row -->
  <div style="margin-top:18px; display:flex; gap:12px; flex-wrap:wrap;">
    <a href="/challenge.php" class="btn"
       style="border-color:rgba(255,42,95,0.5); color:var(--red); background:rgba(255,42,95,0.08);
              padding:10px 18px; font-size:12px;">
      ⚡ Challenge Mode
    </a>
    <a href="/export_report.php" class="btn"
       style="border-color:rgba(0,240,255,0.5); color:var(--cyan); background:rgba(0,240,255,0.08);
              padding:10px 18px; font-size:12px;">
      📄 Export Personal Report (PDF)
    </a>
  </div>
</div>

<!-- ── Key Stats ── -->
<div class="row">
  <?php
    $kstats = [
      ['value'=>(int)$quizSummary['total_sessions'], 'label'=>'Simulations', 'sub'=>'completed', 'color'=>'var(--cyan)', 'icon'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>'],
      ['value'=>number_format($quizAccuracy,0).'%',  'label'=>'Accuracy', 'sub'=>$attempts['correct_attempts'].' correct', 'color'=>'var(--green)', 'icon'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>'],
      ['value'=>number_format($phishAccuracy,0).'%', 'label'=>'Phishing Detection', 'sub'=>$phish['total_attempts'].' inspected', 'color'=>'var(--amber)', 'icon'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>'],
      ['value'=>(int)$chStats['best'],               'label'=>'Best Challenge', 'sub'=>(int)$chStats['sessions'].' runs', 'color'=>'var(--red)', 'icon'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polyline></svg>'],
      ['value'=>$badgeCount,                          'label'=>'Badges Acquired', 'sub'=>$level['name'].' tier', 'color'=>'var(--purple)', 'icon'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15l-3 3-3-3v-4l3-3 3 3v4z"></path></svg>'],
    ];
    foreach ($kstats as $i => $s):
  ?>
    <div class="card" style="flex:1; min-width:160px; animation-delay:<?= $i*.06 ?>s; display:flex; flex-direction:column; align-items:flex-start; position:relative; overflow:hidden;">
      <div style="position:absolute; top:-20px; right:-20px; color:<?= $s['color'] ?>; opacity:0.1; transform:scale(3); pointer-events:none;"><?= $s['icon'] ?></div>
      <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
        <div style="color:<?= $s['color'] ?>; filter:drop-shadow(0 0 5px <?= $s['color'] ?>);"><?= $s['icon'] ?></div>
      </div>
      <div style="font-family:var(--display); font-weight:800; font-size:34px; color:<?= $s['color'] ?>; text-shadow:0 0 15px <?= $s['color'] ?>40; line-height:1;"><?= $s['value'] ?></div>
      <div style="font-family:var(--mono); font-size:11px; letter-spacing:2px; text-transform:uppercase; color:var(--text-dim); margin-top:8px;"><?= $s['label'] ?></div>
      <div style="font-size:12px; color:var(--text-muted); margin-top:4px;" class="mono"><?= $s['sub'] ?></div>
    </div>
  <?php endforeach; ?>
</div>

<!-- ── Badges ── -->
<div class="card">
  <div class="hx">
    Security Clearance Badges
    <span class="hx-mono"><?= count($userBadges) ?> ACQUIRED</span>
  </div>

  <?php if (!$userBadges): ?>
    <div style="text-align:center; padding:32px 0;">
      <div style="font-size:48px; margin-bottom:16px; opacity:0.2; color:var(--purple);">
        <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15l-3 3-3-3v-4l3-3 3 3v4z"></path></svg>
      </div>
      <p class="subtle mono" style="text-transform:uppercase; letter-spacing:1px;">No clearance attributes found.<br>Execute training modules to upgrade clearance.</p>
    </div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($userBadges as $b): ?>
        <div style="flex:1; min-width:240px; padding:16px 20px; border-radius:12px; border:1px solid rgba(176, 38, 255, 0.2); background:rgba(176, 38, 255, 0.05); display:flex; gap:16px; align-items:flex-start; transition:transform 0.2s, box-shadow 0.2s;"
             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(176,38,255,0.2)';"
             onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
          <div style="width:48px; height:48px; border-radius:10px; flex-shrink:0; background:rgba(176, 38, 255, 0.1); border:1px solid rgba(176, 38, 255, 0.4); display:grid; place-items:center; color:var(--purple); font-size:24px; box-shadow:0 0 10px rgba(176, 38, 255, 0.3);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15l-3 3-3-3v-4l3-3 3 3v4z"></path></svg>
          </div>
          <div>
            <div style="font-family:var(--display); font-weight:700; font-size:16px; color:#fff; text-shadow:0 0 5px rgba(255,255,255,0.3); margin-bottom:4px; text-transform:uppercase; letter-spacing:1px;">
              <?= htmlspecialchars($b['name']) ?>
            </div>
            <div style="font-size:13px; color:var(--text-muted); line-height:1.4;"><?= htmlspecialchars($b['description']) ?></div>
            <div style="font-family:var(--mono); font-size:10px; color:var(--purple); margin-top:8px; letter-spacing:1px;">
              ACQUIRED: <?= date('d M Y', strtotime($b['awarded_at'])) ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ── Module Progress ── -->
<div class="card">
  <div class="hx">
    Training Modules
    <span class="hx-mono"><?= $doneModules ?>/<?= $totalModules ?> COMPLETED</span>
  </div>

  <div style="margin-bottom:28px; background:rgba(0,0,0,0.3); padding:16px 20px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
      <span class="mono" style="font-size:11px; color:var(--text-dim); letter-spacing:2px; text-transform:uppercase;">
        Global Clearance Progress
      </span>
      <span style="font-family:var(--display); font-weight:800; font-size:20px;
                   color:<?= $progressPercent >= 80 ? 'var(--green)' : ($progressPercent >= 40 ? 'var(--amber)' : 'var(--cyan)') ?>;
                   text-shadow:0 0 10px <?= $progressPercent >= 80 ? 'rgba(0,255,136,0.5)' : ($progressPercent >= 40 ? 'rgba(255,184,0,0.5)' : 'rgba(0,240,255,0.5)') ?>;">
        <?= $progressPercent ?>%
      </span>
    </div>
    <div class="progress-track" style="height:10px;">
      <div class="progress-fill" style="width:<?= $progressPercent ?>%;
           background:linear-gradient(90deg, var(--cyan), var(--green));
           box-shadow:0 0 12px rgba(0,255,136,0.4);
           transition:width 1s cubic-bezier(0.19,1,0.22,1);"></div>
    </div>
    <div style="display:flex; justify-content:space-between; margin-top:8px;">
      <span class="mono" style="font-size:10px; color:var(--text-dim);"><?= $doneModules ?> modules secured</span>
      <span class="mono" style="font-size:10px; color:var(--text-dim);"><?= $totalModules - $doneModules ?> remaining</span>
    </div>
  </div>

  <?php if (!$modules): ?>
    <p class="subtle">No active modules found in the database.</p>
  <?php else: ?>
    <div class="row">
      <?php foreach ($modules as $i => $m):
        $code  = $m['code'] ?? '';
        $p     = ($code && isset($progressByCode[$code])) ? $progressByCode[$code] : null;
        $isDone = ($p && (int)$p['completed'] === 1);
        $lvl   = $m['level'] ?? 'beginner';
        $lvlColors = ['beginner'=>'var(--green)', 'intermediate'=>'var(--amber)', 'advanced'=>'var(--red)'];
        $lvlColor  = $lvlColors[$lvl] ?? 'var(--green)';
      ?>
        <div class="card" style="flex:1; min-width:260px; animation-delay:<?= $i*.06 ?>s;
             border-left:3px solid <?= $isDone ? 'var(--green)' : 'rgba(255,255,255,0.06)' ?>;
             transition:border-color 0.3s;">

          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
            <span style="font-family:var(--mono); font-size:11px; letter-spacing:2px;
                         color:var(--cyan); text-transform:uppercase;
                         text-shadow:0 0 5px rgba(0,240,255,0.3);">
              MODULE // <?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?>
            </span>
            <?php if ($isDone): ?>
              <span class="badge badge-ok" style="font-size:10px; padding:3px 8px;">✓ SECURED</span>
            <?php else: ?>
              <span class="badge" style="color:var(--text-dim); border-color:rgba(255,255,255,0.08);
                                         font-size:10px; padding:3px 8px;">PENDING</span>
            <?php endif; ?>
          </div>

          <div class="card-title" style="margin-bottom:6px;"><?= htmlspecialchars($m['title']) ?></div>
          <p class="subtle" style="margin:0 0 14px; font-size:13px; line-height:1.5;">
            <?= htmlspecialchars($m['description']) ?>
          </p>

          <div style="margin-bottom:14px;">
            <div class="progress-track" style="height:3px; border-radius:99px;">
              <div class="progress-fill" style="width:<?= $isDone ? 100 : 0 ?>%;
                   background:<?= $isDone ? 'var(--green)' : 'rgba(255,255,255,0.05)' ?>;
                   box-shadow:<?= $isDone ? '0 0 6px rgba(0,255,136,0.5)' : 'none' ?>;
                   transition:width 0.8s ease;"></div>
            </div>
          </div>

          <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
            <span style="font-family:var(--mono); font-size:10px; letter-spacing:2px;
                         text-transform:uppercase; color:<?= $lvlColor ?>;
                         background:rgba(255,255,255,0.04); border:1px solid <?= $lvlColor ?>40;
                         padding:3px 10px; border-radius:3px;">
              <?= $lvl ?>
            </span>
            <?php if ($isDone && !empty($p['completed_at'])): ?>
              <span class="mono" style="font-size:10px; color:var(--text-dim);">
                ✓ Completed <?= date('d/m/Y', strtotime($p['completed_at'])) ?>
              </span>
            <?php elseif (!$isDone): ?>
              <span class="mono" style="font-size:10px; color:var(--text-dim);">Not started</span>
            <?php endif; ?>
          </div>

          <a class="btn <?= $isDone ? 'btn-ghost' : 'btn-primary' ?>"
             href="<?= htmlspecialchars($m['link']) ?>"
             style="font-size:12px; padding:10px 16px; width:100%; text-align:center; display:block;">
            <?= $isDone ? '↩ Review Intel' : 'Initiate Sequence →' ?>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ── Recent Sessions ── -->
<div class="row">
  <div class="card" style="flex:2; min-width:320px;">
    <div class="hx" style="margin-bottom:20px;">
      Simulation Logs
      <a class="btn btn-primary" href="/quiz.php" style="font-size:11px; padding:8px 14px; margin-left:auto;">New Simulation</a>
    </div>

    <?php if (!$lastQuizSessions): ?>
      <p class="subtle mono">No simulation logs found.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Timestamp</th>
            <th style="text-align:center;">QTT</th>
            <th style="text-align:center;">Valid</th>
            <th>Integrity</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lastQuizSessions as $s):
            $score = (float)$s['score_percent'];
            $scoreColor = $score >= 80 ? 'var(--green)' : ($score >= 60 ? 'var(--amber)' : 'var(--red)');
          ?>
            <tr>
              <td class="mono" style="font-size:12px; color:var(--text-muted);"><?= date('d/m, H:i', strtotime($s['started_at'])) ?></td>
              <td style="text-align:center; font-family:var(--mono);"><?= (int)$s['total_questions'] ?></td>
              <td style="text-align:center; font-family:var(--mono);"><?= (int)$s['correct_answers'] ?></td>
              <td>
                <span style="font-family:var(--mono); font-size:13px; color:<?= $scoreColor ?>; font-weight:700; text-shadow:0 0 5px <?= $scoreColor ?>40;"><?= number_format($score,1) ?>%</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card" style="flex:2; min-width:320px;">
    <div class="hx" style="margin-bottom:20px;">
      Threat Detection Logs
      <a class="btn" href="/phishing.php" style="font-size:11px; padding:8px 14px; margin-left:auto;">Open Inbox</a>
    </div>

    <?php if (!$lastPhishAttempts): ?>
      <p class="subtle mono">No threat logs detected.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Timestamp</th>
            <th style="text-align:center;">Target ID</th>
            <th>Action Taken</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lastPhishAttempts as $a): ?>
            <tr>
              <td class="mono" style="font-size:12px; color:var(--text-muted);"><?= date('d/m, H:i', strtotime($a['attempted_at'])) ?></td>
              <td style="text-align:center;">
                <span style="font-family:var(--mono); font-size:11px; color:var(--cyan); background:rgba(0,240,255,0.1); padding:2px 6px; border-radius:3px;">OBJ-<?= (int)$a['email_id'] ?></span>
              </td>
              <td>
                <span style="font-family:var(--mono); font-size:11px; letter-spacing:1px; color:<?= $a['user_choice']==='phishing' ? 'var(--red)' : 'var(--cyan)' ?>;">
                  <?= strtoupper(htmlspecialchars($a['user_choice'])) ?>
                </span>
              </td>
              <td>
                <?php if ((int)$a['correct'] === 1): ?>
                  <span class="badge badge-ok" style="font-size:10px;">SUCCESS</span>
                <?php else: ?>
                  <span class="badge badge-danger" style="font-size:10px;">FAILED</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>