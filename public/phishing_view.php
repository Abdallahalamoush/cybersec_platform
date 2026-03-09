<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/badges.php';

require_auth();

require __DIR__ . '/../includes/phishing_emails.php';

$user = current_user();
$userId = $user['id'];

$id = (int)($_GET['id'] ?? 0);
if (!isset($PHISHING_EMAILS[$id])) {
    header("Location: /phishing.php");
    exit;
}

$email = $PHISHING_EMAILS[$id];
$feedback = null;
$showExplanation = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $feedback = ['type' => 'error', 'msg' => 'Invalid CSRF token'];
    } else {
        $choice = $_POST['choice'] ?? '';
        if ($choice === 'phishing' || $choice === 'legitimate') {
            $correct = ($choice === $email['type']) ? 1 : 0;

           
            $stmt = $pdo->prepare("
                INSERT INTO phishing_attempts (user_id, email_id, user_choice, correct)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $id,
                $choice,
                $correct
            ]);
            
            recalculate_user_badges($pdo, $userId);


            if ($correct) {
                $feedback = [
                    'type' => 'ok',
                    'msg'  => "ANALYSIS CORRECT // Payload identified as {$email['type']}."
                ];
            } else {
                $feedback = [
                    'type' => 'warn',
                    'msg'  => "ANALYSIS FAILED // Payload was actually {$email['type']}."
                ];
            }

            $showExplanation = true;
        }
    }
}

include __DIR__ . '/_partials/header.php';
?>

<div class="card" style="max-width:800px; margin:0 auto; padding:0; overflow:hidden;">
  <!-- Header / Email Metadata -->
  <div style="padding:24px 32px; border-bottom:1px solid rgba(255,255,255,0.05); background:radial-gradient(circle at top right, rgba(255, 184, 0, 0.05), transparent 70%);">
    
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px;">
      <div class="hx" style="margin:0; font-size:20px;">Intercepted Communication View</div>
      <div class="mono" style="font-size:11px; color:var(--text-dim); text-transform:uppercase; letter-spacing:1px; background:rgba(0,0,0,0.3); padding:4px 8px; border-radius:4px; border:1px solid rgba(255,255,255,0.05);">
        ID: OBJ-<?= str_pad($id, 3, '0', STR_PAD_LEFT) ?>
      </div>
    </div>

    <div style="background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.05); border-radius:8px; padding:16px; font-family:var(--mono); font-size:13px;">
      <div style="display:grid; grid-template-columns:80px 1fr; gap:8px; align-items:baseline; margin-bottom:8px;">
        <span style="color:var(--text-dim); text-transform:uppercase; letter-spacing:1px; font-size:11px;">FROM:</span>
        <span style="color:var(--text-bright);"><?= htmlspecialchars($email['sender']) ?></span>
      </div>
      <div style="display:grid; grid-template-columns:80px 1fr; gap:8px; align-items:baseline;">
        <span style="color:var(--text-dim); text-transform:uppercase; letter-spacing:1px; font-size:11px;">SUBJECT:</span>
        <span style="color:var(--amber);"><?= htmlspecialchars($email['subject']) ?></span>
      </div>
    </div>
  </div>

  <!-- Email Body -->
  <div style="padding:32px;">
    <div style="position:relative;">
      <!-- Decorative bracket -->
      <div style="position:absolute; left:-16px; top:0; bottom:0; width:4px; border-left:2px solid rgba(255,184,0,0.3); border-top:2px solid rgba(255,184,0,0.3); border-bottom:2px solid rgba(255,184,0,0.3);"></div>
      
      <pre class="email-body" style="background:rgba(0,0,0,0.2); padding:24px; border-radius:8px; border:1px solid rgba(255,255,255,0.03); white-space:pre-wrap; font-family:var(--mono); font-size:14px; line-height:1.6; color:var(--text-bright); overflow-x:auto;">
<?= htmlspecialchars($email['body']) ?>
      </pre>
    </div>

    <!-- Feedback Section -->
    <?php if ($feedback): ?>
      <div style="margin-top:32px; text-align:center;">
        <?php 
          $cls = $feedback['type'] === 'ok' ? 'badge badge-ok' : 'badge badge-danger';
        ?>
        <span class="<?= $cls ?>" style="font-size:13px; padding:10px 16px; display:inline-block; box-shadow:0 0 15px rgba(0,0,0,0.3); text-transform:uppercase; letter-spacing:1px;">
          <?= htmlspecialchars($feedback['msg']) ?>
        </span>
      </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <?php if (!$showExplanation): ?>
      <div style="margin-top:32px; padding-top:24px; border-top:1px dashed rgba(255,255,255,0.1);">
        <p class="mono subtle text-center" style="font-size:11px; text-transform:uppercase; letter-spacing:1px; margin-bottom:16px;">Provide Threat Assessment:</p>
        <form method="post" style="display:flex; gap:16px; justify-content:center;">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <button class="btn" style="border-color:var(--green); color:var(--green); background:rgba(0,255,136,0.05); padding:12px 24px;" name="choice" value="legitimate" type="submit">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Legit Payload
          </button>
          <button class="btn" style="border-color:var(--red); color:var(--red); background:rgba(255,107,107,0.05); padding:12px 24px;" name="choice" value="phishing" type="submit">
             MALICIOUS (PHISHING)
          </button>
        </form>
      </div>
    <?php endif; ?>

    <!-- Explanation Section -->
    <?php if ($showExplanation && !empty($email['explanation'])): ?>
      <div class="card" style="margin-top:24px; border-top:2px solid var(--cyan); background:rgba(0,240,255,0.02);">
        <div class="hx" style="font-size:16px; margin-bottom:12px; color:var(--cyan); text-shadow:0 0 5px rgba(0,240,255,0.3);">Post-Analysis Report</div>
        <p class="subtle mono" style="font-size:12px; margin-bottom:16px; text-transform:uppercase; letter-spacing:1px;">
          Classification Rationale: <strong style="color:<?= $email['type'] === 'phishing' ? 'var(--red)' : 'var(--green)' ?>"><?= strtoupper(htmlspecialchars($email['type'])) ?></strong>
        </p>
        <ul style="margin:0; padding-left:20px; color:var(--text-muted); font-size:14px; line-height:1.6;">
          <?php foreach ($email['explanation'] as $e): ?>
            <li style="margin-bottom:8px;"><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="text-center" style="margin-top:<?= $showExplanation ? '32px' : '24px' ?>;">
      <a class="btn btn-ghost" href="/phishing.php" style="font-size:12px;">← Return to Inbox</a>
    </div>

  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
