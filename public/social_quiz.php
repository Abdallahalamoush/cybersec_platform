<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/badges.php';
require_auth();

$user = current_user();
$userId = $user['id'];

$questions = [
    [
        'q' => "A message says: “Your account will be locked in 10 minutes. Click here.” What technique is it?",
        'choices' => ["Authority", "Urgency", "Reward", "Trust"],
        'answer' => 1
    ],
    [
        'q' => "Someone calls claiming to be IT and asks for your password. What is the best response?",
        'choices' => [
            "Give it if they sound professional",
            "Ask them to email you",
            "Refuse and contact IT via official channel",
            "Share only part of the password"
        ],
        'answer' => 2
    ],
    [
        'q' => "What should you NEVER share, even with a colleague?",
        'choices' => ["Your email", "Your MFA code", "Your name", "Your department"],
        'answer' => 1
    ],
];

$score = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correct = 0;
    foreach ($questions as $i => $q) {
        $userAnswer = (int)($_POST["q$i"] ?? -1);
        if ($userAnswer === $q['answer']) $correct++;
    }

    $score = round(($correct / count($questions)) * 100);

    if ($score >= 60) {
        // mark module completed
        $stmt = $pdo->prepare("
            INSERT INTO module_progress (user_id, module_code, completed, completed_at)
            VALUES (?, 'social', 1, NOW())
            ON DUPLICATE KEY UPDATE completed = 1, completed_at = NOW()
        ");
        $stmt->execute([$userId]);

        // award badge
        recalculate_user_badges($pdo, $userId);
    }
}

include __DIR__ . '/_partials/header.php';
?>

<div class="card">
  <div class="hx">Social Engineering Quiz</div>

  <?php if ($score !== null): ?>
    <p class="subtle">Your score: <strong><?= $score ?>%</strong></p>

    <?php if ($score >= 60): ?>
      <p class="badge badge-ok">Great! Module completed + badge unlocked.</p>
    <?php else: ?>
      <p class="badge badge-warn">Try again — review the lesson.</p>
    <?php endif; ?>

    <a class="btn mt-2" href="/social.php">Back to module</a>

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
