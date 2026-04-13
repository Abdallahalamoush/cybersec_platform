<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/badges.php';

require_auth();

$user = current_user();
$userId = $user['id'];

$questions = [
    [
        'q' => "Which cryptographic string offers the highest entropy and brute-force resistance?",
        'choices' => [
            "football123",
            "Abdallah2024",
            "t!9F#c2\$Lk@8",
            "mypassword"
        ],
        'answer' => 2
    ],
    [
        'q' => "What is the most secure protocol for credential management?",
        'choices' => [
            "Memory reuse (identical keys for all nodes)",
            "Physical localized storage (paper)",
            "Unencrypted digital sharing (texting)",
            "Encrypted cryptographic vault (password manager)"
        ],
        'answer' => 3
    ],
    [
        'q' => "Identify the acronym MFA within the context of access control.",
        'choices' => [
            "Multiple Firewall Architecture",
            "Multi-Factor Authentication",
            "Master Function Access",
            "Malicious File Analyzer"
        ],
        'answer' => 1
    ],
];

$score = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correct = 0;

    foreach ($questions as $index => $q) {
        $userAnswer = (int)($_POST["q$index"] ?? -1);
        if ($userAnswer === $q['answer']) {
            $correct++;
        }
    }

    $score = round(($correct / count($questions)) * 100);

    if ($score >= 60) {
        // mark module completed
        $stmt = $pdo->prepare("
            INSERT INTO module_progress (user_id, module_code, completed, completed_at)
            VALUES (?, 'passwords', 1, NOW())
            ON DUPLICATE KEY UPDATE completed = 1, completed_at = NOW()
        ");
        $stmt->execute([$userId]);

        // award badge
        recalculate_user_badges($pdo, $userId);
    }
}

include __DIR__ . '/_partials/header.php';
?>

<div class="card" style="max-width:800px; margin:0 auto; padding:0; overflow:hidden;">
  <!-- Header -->
  <div style="padding:32px 32px 24px; border-bottom:1px solid rgba(255,255,255,0.05); background:radial-gradient(ellipse at top right, rgba(0, 240, 255, 0.1), transparent 60%);">
    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
      <div>
        <div class="hx" style="margin-bottom:8px; display:flex; align-items:center; gap:12px;">
          <div style="width:40px; height:40px; border-radius:8px; background:rgba(0, 240, 255, 0.1); border:1px solid rgba(0, 240, 255, 0.3); display:grid; place-items:center; color:var(--cyan);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="14 2 18 6 7 17 3 17 3 13 14 2"></polygon><line x1="3" y1="22" x2="21" y2="22"></line></svg>
          </div>
          Assessment: Cryptography
        </div>
        <div class="mono subtle" style="font-size:12px; letter-spacing:1px; text-transform:uppercase;">Knowledge Validation // PASSWORDS</div>
      </div>
      
      <div class="status-indicator">
        <span class="status-dot <?php echo ($score !== null) ? ($score >= 60 ? 'online' : '') : 'online'; ?>" style="<?php echo ($score !== null && $score < 60) ? 'background:var(--red); box-shadow:0 0 8px var(--red);' : ''; ?>"></span>
        <span class="mono" style="font-size:10px; color:<?php echo ($score !== null) ? ($score >= 60 ? 'var(--green)' : 'var(--red)') : 'var(--cyan)'; ?>; letter-spacing:1px; text-transform:uppercase;">
          <?php echo ($score !== null) ? 'EVALUATED' : 'ACTIVE'; ?>
        </span>
      </div>
    </div>
  </div>

  <div style="padding:32px;">
    <?php if ($score !== null): ?>
      
      <div style="text-align:center; padding:24px; background:rgba(0,0,0,0.2); border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
        <p class="mono subtle" style="font-size:12px; letter-spacing:2px; text-transform:uppercase; margin-bottom:8px;">Evaluation Score</p>
        <div style="font-family:var(--display); font-size:64px; font-weight:800; line-height:1; margin-bottom:16px; color:<?= $score >= 60 ? 'var(--green)' : 'var(--red)' ?>; text-shadow:0 0 20px <?= $score >= 60 ? 'var(--green)50' : 'var(--red)50' ?>;">
          <?= $score ?>%
        </div>

        <?php if ($score >= 60): ?>
          <div style="display:inline-block; padding:8px 16px; background:rgba(0,255,136,0.1); border:1px solid rgba(0,255,136,0.3); border-radius:4px; color:var(--green); font-size:14px; letter-spacing:1px; margin-bottom:24px;">
            VALIDATION SUCCESS // MODULE SECURED
          </div>
        <?php else: ?>
          <div style="display:inline-block; padding:8px 16px; background:rgba(255,107,107,0.1); border:1px solid rgba(255,107,107,0.3); border-radius:4px; color:var(--red); font-size:14px; letter-spacing:1px; margin-bottom:24px;">
            VALIDATION FAILED // RETRAINING REQUIRED
          </div>
        <?php endif; ?>

        <div>
          <a class="btn btn-primary" href="/passwords.php" style="padding:10px 24px; font-size:13px; letter-spacing:1px;">Return to Training Database</a>
        </div>
      </div>

    <?php else: ?>

      <form method="post">
        <?php foreach ($questions as $i => $q): ?>
          
          <div style="margin-bottom:32px; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.05); border-radius:8px; padding:24px;">
            <div style="display:flex; gap:16px; margin-bottom:20px;">
              <div class="mono" style="color:var(--cyan); font-size:14px; padding-top:2px;">[Q<?= $i + 1 ?>]</div>
              <p style="font-family:var(--display); font-weight:600; font-size:16px; line-height:1.5; color:var(--text-bright); margin:0;">
                <?= htmlspecialchars($q['q']) ?>
              </p>
            </div>

            <div style="display:flex; flex-direction:column; gap:10px; padding-left:36px;">
              <?php foreach ($q['choices'] as $cIndex => $choice): ?>
                <label class="choice" style="position:relative; display:flex; align-items:center; padding:12px 16px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); border-radius:6px; cursor:pointer; transition:all 0.2s;">
                  <input type="radio" name="q<?= $i ?>" value="<?= $cIndex ?>" required style="position:absolute; opacity:0; width:0; height:0;">
                  <div class="choice-marker" style="width:18px; height:18px; border-radius:50%; border:1px solid rgba(255,255,255,0.2); margin-right:12px; display:grid; place-items:center; transition:all 0.2s;">
                    <div class="inner-dot" style="width:8px; height:8px; border-radius:50%; background:transparent; transition:all 0.2s;"></div>
                  </div>
                  <div style="flex:1; font-size:14px; color:var(--text-bright);">
                    <?= htmlspecialchars($choice) ?>
                  </div>
                  <style>
                    label.choice:hover { background:rgba(0, 240, 255, 0.05); border-color:rgba(0, 240, 255, 0.3); transform:translateX(5px); }
                    label.choice input:checked + .choice-marker { border-color:var(--cyan); box-shadow:0 0 10px rgba(0,240,255,0.5); }
                    label.choice input:checked + .choice-marker .inner-dot { background:var(--cyan); }
                    label.choice input:checked ~ div { color:var(--cyan); }
                    label.choice:has(input:checked) { border-color:var(--cyan); background:rgba(0, 240, 255, 0.05); }
                  </style>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

        <?php endforeach; ?>

        <div style="text-align:right; border-top:1px dashed rgba(255,255,255,0.1); padding-top:24px;">
          <button class="btn btn-primary" type="submit" style="padding:12px 32px; font-size:14px; letter-spacing:1px; text-transform:uppercase;">
            Transmit Answers <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left:8px;"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
          </button>
        </div>
      </form>

    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
