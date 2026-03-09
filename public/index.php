<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

include __DIR__ . '/_partials/header.php';

try {
    $stmt = $pdo->query("
        SELECT id, title, description, link, level
        FROM modules
        WHERE is_active = 1
        ORDER BY id ASC
    ");
    $modules = $stmt->fetchAll();
} catch (Exception $e) {
    $modules = [];
}

$levelIcons  = ['beginner' => '◎', 'intermediate' => '◑', 'advanced' => '●'];
$levelColors = ['beginner' => 'var(--green)', 'intermediate' => 'var(--amber)', 'advanced' => 'var(--red)'];
?>

<!-- ── Hero ── -->
<div class="card" style="padding:48px 40px; overflow:hidden; position:relative; display:flex; flex-direction:column; justify-content:center; min-height:400px; background:radial-gradient(circle at top right, rgba(0, 240, 255, 0.1), transparent 50%), var(--panel);">

  <!-- Background decoration -->
  <div style="
    position:absolute; right:-60px; top:-60px;
    width:400px; height:400px; border-radius:50%;
    background:radial-gradient(circle, rgba(176, 38, 255, 0.1) 0%, transparent 70%);
    pointer-events:none;
  "></div>
  
  <div style="
    position:absolute; right:40px; top:40px;
    font-size:120px; color:rgba(0, 255, 136, 0.03);
    line-height:1; pointer-events:none; user-select:none;
    filter: drop-shadow(0 0 20px rgba(0, 255, 136, 0.2));
  ">
    <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
    </svg>
  </div>

  <div style="position:relative; z-index:2;">
    <div class="hero-tag">
      <span class="status-dot online"></span>
      PLATFORM ACTIVE // V4.0
    </div>

    <h1 class="hero-title" style="margin-bottom:16px;">
      Master <span>Cyber Security</span><br>Awareness
    </h1>

    <p style="max-width:550px; font-size:16px; line-height:1.7; margin-bottom:32px; color:var(--text-muted); text-shadow:0 0 10px rgba(0,0,0,0.8);">
      Interactive modules, phishing simulations, and adaptive quizzes designed to
      build real-world threat awareness. Upgrade your security clearance and earn badges.
    </p>

    <?php if (current_user()): ?>
      <p style="font-family:var(--mono); font-size:13px; color:var(--text-dim); margin-bottom:20px; letter-spacing:1px; text-transform:uppercase;">
        INITIALIZING SESSION FOR OPERATOR: <span style="color:var(--cyan); text-shadow:0 0 8px rgba(0,240,255,0.5);"><?= htmlspecialchars(strtoupper(current_user()['name'])) ?></span>
      </p>
      <div style="display:flex; gap:16px; flex-wrap:wrap;">
        <a class="btn btn-primary" href="/quiz.php">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
          Start Simulation
        </a>
        <a class="btn" href="/dashboard.php">Command Center</a>
        <a class="btn" href="/phishing.php">Phishing Intel</a>
      </div>
    <?php else: ?>
      <div style="display:flex; gap:16px; flex-wrap:wrap;">
        <a class="btn btn-primary" href="/register.php">Initialize Access</a>
        <a class="btn" href="/login.php">Authenticate</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── Stats row ── -->
<div class="row">
  <?php
    $stats = [
      ['value' => '4',     'label' => 'Active Modules',       'color' => 'var(--cyan)'],
      ['value' => '3+',    'label' => 'Badges to Earn',        'color' => 'var(--purple)'],
      ['value' => '100%',  'label' => 'Free Access',           'color' => 'var(--green)'],
    ];
    foreach ($stats as $s):
  ?>
    <div class="card" style="flex:1; min-width:160px; text-align:center; padding:24px 20px; display:flex; flex-direction:column; justify-content:center;">
      <div style="
        font-family:var(--display); font-weight:800;
        font-size:36px;
        color: <?= $s['color'] ?>;
        text-shadow: 0 0 15px <?= $s['color'] ?>;
        line-height:1.1;
      "><?= $s['value'] ?></div>
      <div style="font-family:var(--mono); font-size:11px; letter-spacing:2px; text-transform:uppercase; color:var(--text-dim); margin-top:8px;"><?= $s['label'] ?></div>
    </div>
  <?php endforeach; ?>
</div>

<!-- ── Modules ── -->
<div class="card">
  <div class="hx">
    Training Modules
    <span class="hx-mono"><?= count($modules) ?> AVAILABLE</span>
  </div>

  <?php if (!$modules): ?>
    <p class="subtle">No active modules available at the moment.</p>
  <?php else: ?>
    <div class="row">
      <?php foreach ($modules as $i => $m):
        $lvl   = $m['level'] ?? 'beginner';
        $icon  = $levelIcons[$lvl]  ?? '◎';
        $color = $levelColors[$lvl] ?? 'var(--green)';
        $link  = !empty($m['link']) ? $m['link'] : '#';
      ?>
        <div class="card" style="flex:1; min-width:260px; animation-delay:<?= $i * .1 ?>s;">

          <!-- Module number -->
          <div style="
            font-family:var(--mono);
            font-size:11px; letter-spacing:3px; color:var(--cyan);
            margin-bottom:12px; text-shadow:0 0 5px rgba(0,240,255,0.3);
          ">MODULE // <?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></div>

          <div class="card-title"><?= htmlspecialchars($m['title']) ?></div>

          <p class="subtle" style="margin:10px 0 18px;">
            <?= htmlspecialchars($m['description']) ?>
          </p>

          <?php if (!empty($lvl)): ?>
            <span style="
              display:inline-flex; align-items:center; gap:8px;
              font-family:var(--mono);
              font-size:11px; letter-spacing:2px; text-transform:uppercase;
              color:<?= $color ?>; background:rgba(255,255,255,0.05);
              border:1px solid <?= $color ?>; padding:4px 12px; border-radius:4px;
              margin-bottom:20px; box-shadow:0 0 10px <?= $color ?>40;
            "><?= $icon ?> <?= $lvl ?></span>
          <?php endif; ?>

          <div style="margin-top:auto;">
            <?php if (current_user()): ?>
              <a class="btn btn-primary" href="<?= htmlspecialchars($link) ?>" style="font-size:13px; padding:10px 18px; width:100%;">
                Execute Component →
              </a>
            <?php else: ?>
              <a class="btn" href="/login.php" style="font-size:13px; padding:10px 18px; width:100%; text-align:center;">Auth Required</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ── Mission ── -->
<div class="card">
  <div class="hx">Mission Parameters</div>
  <div class="row" style="gap:24px;">
    <?php
      $pillars = [
        ['icon'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>','title'=>'Phishing Intel','desc'=>'Learn to spot malicious payloads through realistic simulations.'],
        ['icon'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>','title'=>'Cryptography & Access', 'desc'=>'Understand encryption principles and credential secure management.'],
        ['icon'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>','title'=>'Social Engineering','desc'=>'Recognize psychological manipulation vulnerabilities.'],
      ];
      foreach ($pillars as $p):
    ?>
      <div style="flex:1; min-width:240px; display:flex; gap:16px; align-items:flex-start;">
        <div style="
          width:48px; height:48px; border-radius:12px; flex-shrink:0;
          background:rgba(0, 240, 255, 0.1); border:1px solid rgba(0, 240, 255, 0.3);
          display:grid; place-items:center; color:var(--cyan);
          box-shadow: 0 0 15px rgba(0, 240, 255, 0.2);
        "><?= $p['icon'] ?></div>
        <div>
          <div style="font-family:var(--display); font-weight:700; font-size:16px; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px; color:#fff; text-shadow:0 0 10px rgba(255,255,255,0.2);">
            <?= $p['title'] ?>
          </div>
          <p class="subtle" style="font-size:14px; line-height:1.6;"><?= $p['desc'] ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>