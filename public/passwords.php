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

<div class="card" style="max-width:800px; margin:0 auto; padding:0; overflow:hidden;">
  <!-- Header -->
  <div style="padding:32px 32px 24px; border-bottom:1px solid rgba(255,255,255,0.05); background:radial-gradient(ellipse at top left, rgba(0, 240, 255, 0.1), transparent 60%);">
    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
      <div>
        <div class="hx" style="margin-bottom:8px; display:flex; align-items:center; gap:12px;">
          <div style="width:40px; height:40px; border-radius:8px; background:rgba(0, 240, 255, 0.1); border:1px solid rgba(0, 240, 255, 0.3); display:grid; place-items:center; color:var(--cyan);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          </div>
          Cryptography & Access Control
        </div>
        <div class="mono subtle" style="font-size:12px; letter-spacing:1px; text-transform:uppercase;">Training Module // PASSWORDS</div>
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
        Understanding password security and cryptographic principles is one of the most fundamental skills for any operator. Weak credentials are the primary vector for unauthorized system infiltration.
      </p>

      <div class="hx" style="font-size:16px; margin-bottom:16px; color:var(--cyan); text-shadow:none;">Core Security Protocols:</div>
      
      <div style="display:grid; gap:16px;">
        <div style="display:flex; gap:16px; align-items:flex-start; background:rgba(255,255,255,0.02); padding:16px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
          <div style="color:var(--cyan); padding-top:2px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
          </div>
          <div>
            <strong style="display:block; color:var(--text-bright); margin-bottom:4px; font-family:var(--display); letter-spacing:0.5px;">Entropy Generation</strong>
            <span class="subtle" style="font-size:14px; line-height:1.5;">Utilize high-entropy strings (12+ characters, mixed casing, symbols). Length exponentially increases brute-force resistance.</span>
          </div>
        </div>

        <div style="display:flex; gap:16px; align-items:flex-start; background:rgba(255,255,255,0.02); padding:16px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
          <div style="color:var(--cyan); padding-top:2px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
          </div>
          <div>
            <strong style="display:block; color:var(--text-bright); margin-bottom:4px; font-family:var(--display); letter-spacing:0.5px;">Credential Isolation</strong>
            <span class="subtle" style="font-size:14px; line-height:1.5;">Never reuse keys across different systems. A compromise in one nodes should not grant lateral movement access.</span>
          </div>
        </div>

        <div style="display:flex; gap:16px; align-items:flex-start; background:rgba(255,255,255,0.02); padding:16px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
          <div style="color:var(--cyan); padding-top:2px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
          </div>
          <div>
            <strong style="display:block; color:var(--text-bright); margin-bottom:4px; font-family:var(--display); letter-spacing:0.5px;">Secure Vaulting</strong>
            <span class="subtle" style="font-size:14px; line-height:1.5;">Employ encrypted password managers to generate and store credentials. The human brain is not a secure storage medium for cryptographic keys.</span>
          </div>
        </div>

        <div style="display:flex; gap:16px; align-items:flex-start; background:rgba(255,255,255,0.02); padding:16px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
          <div style="color:var(--cyan); padding-top:2px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
          </div>
          <div>
            <strong style="display:block; color:var(--text-bright); margin-bottom:4px; font-family:var(--display); letter-spacing:0.5px;">Multi-Factor Authentication (MFA)</strong>
            <span class="subtle" style="font-size:14px; line-height:1.5;">Always require a second authentication factor (token, authenticator app, hardware key) to mitigate stolen credentials.</span>
          </div>
        </div>
      </div>
    </div>

    <div style="padding:24px; background:rgba(0,240,255,0.03); border:1px solid rgba(0,240,255,0.1); border-radius:8px; text-align:center;">
      <p class="mono" style="font-size:12px; color:var(--cyan); text-transform:uppercase; letter-spacing:1px; margin-bottom:16px;">
        Awaiting Validation Sequence
      </p>
      <p class="subtle" style="font-size:14px; margin-bottom:24px;">
        When ready, initiate the assessment to validate your understanding of access control protocols.
      </p>
      
      <a class="btn btn-primary" href="/passwords_quiz.php" style="padding:12px 32px; font-size:14px; text-transform:uppercase; letter-spacing:1px;">
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
