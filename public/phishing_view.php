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
                    'msg'  => "Correct! This email is indeed {$email['type']}."
                ];
            } else {
                $feedback = [
                    'type' => 'warn',
                    'msg'  => "Incorrect. This email was actually {$email['type']}."
                ];
            }

            $showExplanation = true;
        }
    }
}

include __DIR__ . '/_partials/header.php';
?>

<div class="card">
  <div class="hx"><?= htmlspecialchars($email['subject']) ?></div>
  <p class="subtle">From: <strong><?= htmlspecialchars($email['sender']) ?></strong></p>

  <pre class="email-body" style="background:#0b1424; padding:12px; border-radius:10px; white-space:pre-wrap;">
<?= htmlspecialchars($email['body']) ?>
  </pre>

  <?php if ($feedback): ?>
    <?php 
      $cls = $feedback['type'] === 'ok' ? 'badge badge-ok' : 'badge badge-warn';
    ?>
    <p class="<?= $cls ?>" style="margin-top:10px;"><?= htmlspecialchars($feedback['msg']) ?></p>
  <?php endif; ?>

  <?php if ($showExplanation && !empty($email['explanation'])): ?>
    <div class="card" style="margin-top:12px;">
      <div class="card-title">Explanation</div>
      <p class="subtle">
        Why is this email considered <strong><?= htmlspecialchars($email['type']) ?></strong>?
      </p>
      <ul>
        <?php foreach ($email['explanation'] as $e): ?>
          <li class="subtle"><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" class="mt-3">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <button class="btn btn-primary" name="choice" value="legitimate" type="submit">Legitimate</button>
    <button class="btn btn-danger" name="choice" value="phishing" type="submit">Phishing</button>
  </form>

  <div class="mt-3">
    <a class="btn" href="/phishing.php">Back to inbox</a>
  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
