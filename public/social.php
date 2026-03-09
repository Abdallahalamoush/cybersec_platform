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

<div class="card" style="max-width:800px; margin:0 auto; padding:0; overflow:hidden;">
  <!-- Header -->
  <div style="padding:32px 32px 24px; border-bottom:1px solid rgba(255,255,255,0.05); background:radial-gradient(ellipse at top left, rgba(255, 107, 107, 0.1), transparent 60%);">
    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
      <div>
        <div class="hx" style="margin-bottom:8px; display:flex; align-items:center; gap:12px;">
          <div style="width:40px; height:40px; border-radius:8px; background:rgba(255, 107, 107, 0.1); border:1px solid rgba(255, 107, 107, 0.3); display:grid; place-items:center; color:var(--red);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
          Social Engineering Tactics
        </div>
        <div class="mono subtle" style="font-size:12px; letter-spacing:1px; text-transform:uppercase;">Training Module // HUMINT</div>
      </div>
      
      <?php if ($completed): ?>
        <div class="status-indicator" style="background:rgba(0,255,136,0.1); border-color:rgba(0,255,136,0.3);">
          <span class="status-dot online"></span>
          <span class="mono" style="font-size:10px; color:var(--green); letter-spacing:1px;">SECURED</span>
        </div>
      <?php else: ?>
        <div class="status-indicator">
          <span class="status-dot" style="background:var(--amber); box-shadow:0 0 8px var(--amber);"></span>
          <span class="mono" style="font-size:10px; color:var(--amber); letter-spacing:1px;">PENDING</span>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Content -->
  <div style="padding:32px;">
    
    <div style="margin-bottom:32px;">
      <p style="font-size:16px; line-height:1.7; color:var(--text-bright); margin-bottom:24px;">
        The human element is consistently the weakest link in any security apparatus. <strong style="color:var(--red);">Social Engineering</strong> bypasses technical controls by manipulating psychological triggers—exploiting trust, fear, and cognitive biases to extract sensitive data or unauthorized access.
      </p>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
        
        <!-- Malicious Playbook -->
        <div style="background:rgba(255,107,107,0.02); border:1px solid rgba(255,107,107,0.1); border-top:2px solid var(--red); padding:24px; border-radius:8px;">
          <div class="hx" style="font-size:14px; margin-bottom:16px; color:var(--red); text-shadow:none; display:flex; align-items:center; gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg> Target Manipulation Vectors
          </div>
          <ul style="margin:0; padding-left:20px; color:var(--text-muted); font-size:14px; line-height:1.6; display:flex; flex-direction:column; gap:12px;">
            <li><strong style="color:var(--text-bright);">Manufactured Urgency:</strong> "Execute immediately or lose access parameters." Limits analytical reasoning.</li>
            <li><strong style="color:var(--text-bright);">Authority Spoofing:</strong> Falsified origin pretending to be C-level execs or IT administrators.</li>
            <li><strong style="color:var(--text-bright);">Fear Conditioning:</strong> "Your security has been breached!" Paralysis via panic.</li>
            <li><strong style="color:var(--text-bright);">Familiarity Exploitation:</strong> Impersonating known contacts to inherit implied trust.</li>
            <li><strong style="color:var(--text-bright);">Reciprocity / Reward:</strong> Offering unearned benefits or "free" resources to induce compliance.</li>
          </ul>
        </div>

        <!-- Defense Protocols -->
        <div style="background:rgba(0,255,136,0.02); border:1px solid rgba(0,255,136,0.1); border-top:2px solid var(--green); padding:24px; border-radius:8px;">
          <div class="hx" style="font-size:14px; margin-bottom:16px; color:var(--green); text-shadow:none; display:flex; align-items:center; gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> Defensive Countermeasures
          </div>
          <ul style="margin:0; padding-left:20px; color:var(--text-muted); font-size:14px; line-height:1.6; display:flex; flex-direction:column; gap:12px;">
            <li><strong style="color:var(--text-bright);">Asynchronous Verification:</strong> Never authenticate through provided channels. Manually navigate to known-good endpoints.</li>
            <li><strong style="color:var(--text-bright);">Zero Trust Communication:</strong> Assume external communications are hostile until verified via secondary channels.</li>
            <li><strong style="color:var(--text-bright);">Credential Sanity:</strong> No authentic administrative entity will ever request raw passwords or 2FA tokens.</li>
            <li><strong style="color:var(--text-bright);">Emotional Decoupling:</strong> Recognize when an interaction is designed to induce panic or urgency. Pause and assess.</li>
          </ul>
        </div>

      </div>
    </div>

    <!-- Assessment CTA -->
    <div style="padding:24px; background:rgba(255,107,107,0.03); border:1px solid rgba(255,107,107,0.1); border-radius:8px; text-align:center;">
      <p class="mono" style="font-size:12px; color:var(--red); text-transform:uppercase; letter-spacing:1px; margin-bottom:16px;">
        Awaiting Validation Sequence
      </p>
      <p class="subtle" style="font-size:14px; margin-bottom:24px;">
        When ready, initiate the assessment to validate your defensive reasoning against manipulation tactics.
      </p>
      
      <a class="btn btn-primary" href="/social_quiz.php" style="padding:12px 32px; font-size:14px; text-transform:uppercase; letter-spacing:1px; background:rgba(255,107,107,0.1); border-color:var(--red); color:var(--red); box-shadow:0 0 10px rgba(255,107,107,0.2);">
        Initialize Assessment
      </a>
      
      <?php if ($completed): ?>
        <div style="margin-top:16px;">
          <span class="badge badge-ok" style="font-size:11px;">MODULE PREVIOUSLY SECURED</span>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
