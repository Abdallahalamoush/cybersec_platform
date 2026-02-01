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
        'q' => "Which password is strongest?",
        'choices' => [
            "football123",
            "Abdallah2024",
            "t!9F#c2$Lk@8",
            "mypassword"
        ],
        'answer' => 2
    ],
    [
        'q' => "What should you do with passwords?",
        'choices' => [
            "Reuse them so you don't forget",
            "Write them on paper",
            "Share with trusted friends",
            "Use a password manager"
        ],
        'answer' => 3
    ],
    [
        'q' => "What is MFA?",
        'choices' => [
            "Multiple Facebook Accounts",
            "Multi-Factor Authentication",
            "Master File Access",
            "Mobile Folder App"
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
    
    $stmt = $pdo->prepare("
        INSERT INTO module_progress (user_id, module_code, completed, completed_at)
        VALUES (?, 'passwords', 1, NOW())
        ON DUPLICATE KEY UPDATE completed = 1, completed_at = NOW()
    ");
    $stmt->execute([$user['id']]);

    
    recalculate_user_badges($pdo, $user['id']);
}


include __DIR__ . '/_partials/header.php';
?>

<div class="card">
  <div class="hx">Password Security Quiz</div>

  <?php if ($score !== null): ?>
    <p class="subtle">
      Your score: <strong><?= $score ?>%</strong>
    </p>

    <?php if ($score >= 60): ?>
      <p class="badge badge-ok">Great! Module completed.</p>
    <?php else: ?>
      <p class="badge badge-warn">Try again — review the lesson.</p>
    <?php endif; ?>

    <a class="btn mt-2" href="/passwords.php">Back to Module</a>
  <?php else: ?>

    <form method="post">
      <?php foreach ($questions as $i => $q): ?>
        <p><strong><?= htmlspecialchars($q['q']) ?></strong></p>

        <?php foreach ($q['choices'] as $cIndex => $choice): ?>
            <label>
              <input type="radio" name="q<?= $i ?>" value="<?= $cIndex ?>" required>
              <?= htmlspecialchars($choice) ?>
            </label><br>
        <?php endforeach; ?>

        <hr>
      <?php endforeach; ?>

      <button class="btn btn-primary mt-2" type="submit">Submit</button>
    </form>

  <?php endif; ?>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
