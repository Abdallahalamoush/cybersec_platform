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

<div class="card" style="max-width:900px; margin:0 auto; padding:0; overflow:hidden;">
  <!-- Header section -->
  <div style="padding:32px 32px 24px; border-bottom:1px solid rgba(255,255,255,0.05); background:radial-gradient(ellipse at top right, rgba(255, 184, 0, 0.1), transparent 60%);">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px;">
      <div>
        <div class="hx" style="margin-bottom:8px; display:flex; align-items:center; gap:12px;">
          <div style="width:40px; height:40px; border-radius:8px; background:rgba(255, 184, 0, 0.1); border:1px solid rgba(255, 184, 0, 0.3); display:grid; place-items:center; color:var(--amber);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
          </div>
          Threat Detection Inbox
        </div>
        <p class="subtle" style="font-size:14px; max-width:600px;">
          Analyze each intercepted communication and classify it as <strong>legitimate</strong> or a <strong>malicious payload</strong>.
        </p>
      </div>
      <div class="status-indicator" style="background:rgba(255,184,0,0.1); border-color:rgba(255,184,0,0.3);">
        <span class="status-dot" style="background:var(--amber); box-shadow:0 0 8px var(--amber);"></span>
        <span class="mono" style="font-size:10px; color:var(--amber); letter-spacing:1px; text-transform:uppercase;"><?= count($PHISHING_EMAILS) ?> Intercepts</span>
      </div>
    </div>
  </div>

  <!-- Inbox List -->
  <div style="background:rgba(0,0,0,0.2);">
    <?php foreach ($PHISHING_EMAILS as $id => $mail): 
        $st = $statusByEmail[$id] ?? null;
        $isMastered = $st && $st['ever_correct'];
        $isAttempted = (bool)$st;
    ?>
      <a href="/phishing_view.php?id=<?= (int)$id ?>" style="display:block; text-decoration:none; color:inherit; border-bottom:1px solid rgba(255,255,255,0.03); transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'; this.style.paddingLeft='16px';" onmouseout="this.style.background='transparent'; this.style.paddingLeft='0';">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:20px 32px; gap:24px;">
          
          <div style="display:flex; align-items:center; gap:20px; flex:1;">
            <!-- Status Icon -->
            <div style="width:12px; height:12px; border-radius:50%; flex-shrink:0; 
              <?php if (!$isAttempted): ?>
                background:var(--cyan); box-shadow:0 0 8px var(--cyan);
              <?php elseif ($isMastered): ?>
                background:var(--green); box-shadow:0 0 8px var(--green);
              <?php else: ?>
                background:var(--red); box-shadow:0 0 8px var(--red);
              <?php endif; ?>
            "></div>

            <div style="min-width:0; flex:1;">
              <div style="display:flex; align-items:baseline; gap:12px; margin-bottom:4px;">
                <strong style="font-size:15px; color:var(--text-bright); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($mail['sender']) ?></strong>
                <span class="mono subtle" style="font-size:10px; text-transform:uppercase; letter-spacing:1px;">OBJ-<?= str_pad($id, 3, '0', STR_PAD_LEFT) ?></span>
              </div>
              <div style="font-size:13px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                <?= htmlspecialchars($mail['subject']) ?>
              </div>
            </div>
          </div>

          <!-- Status Badge -->
          <div style="text-align:right; flex-shrink:0;">
            <?php if ($st): ?>
              <?php if ($isMastered): ?>
                <span class="badge badge-ok" style="font-size:10px; padding:4px 8px;">SECURED</span>
              <?php else: ?>
                <span class="badge badge-danger" style="font-size:10px; padding:4px 8px;">VULNERABLE</span>
              <?php endif; ?>
              <div class="mono subtle" style="font-size:10px; margin-top:6px; text-transform:uppercase; letter-spacing:1px;"><?= (int)$st['attempts_count'] ?> SCANS</div>
            <?php else: ?>
              <span class="badge" style="font-size:10px; padding:4px 8px; color:var(--cyan); border-color:var(--cyan); background:rgba(0,240,255,0.1);">UNANALYZED</span>
            <?php endif; ?>
          </div>
          
          <div style="color:var(--text-dim);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
