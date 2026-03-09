<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/badges.php';

require_auth();

const QUIZ_QUESTIONS_PER_SESSION = 5;

function start_new_quiz(PDO $pdo): ?array {
    $stmt = $pdo->query("SELECT id FROM questions");
    $rows = $stmt->fetchAll();
    if (!$rows) {
        return null;
    }

    $allIds = array_column($rows, 'id');
    shuffle($allIds);

    $total = min(QUIZ_QUESTIONS_PER_SESSION, count($allIds));
    $questionIds = array_slice($allIds, 0, $total);

    $ins = $pdo->prepare("INSERT INTO quiz_sessions (user_id) VALUES (?)");
    $ins->execute([ current_user()['id'] ]);
    $sessionId = (int)$pdo->lastInsertId();

    $_SESSION['current_quiz'] = [
        'session_id'     => $sessionId,
        'question_ids'   => $questionIds,
        'current_index'  => 0,
        'correct_count'  => 0,
    ];

    return $_SESSION['current_quiz'];
}

function get_current_quiz(): ?array {
    return $_SESSION['current_quiz'] ?? null;
}

function clear_current_quiz(): void {
    unset($_SESSION['current_quiz']);
}

$feedback = null;
$question = null;
$finalResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start'])) {
    start_new_quiz($pdo);
}

$currentQuiz = get_current_quiz();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $feedback = ['type' => 'error', 'msg' => 'Invalid CSRF Token.'];
    } else {
        $currentQuiz = get_current_quiz();
        if (!$currentQuiz) {
            $feedback = ['type' => 'error', 'msg' => 'Quiz session not found.'];
        } else {
            $sessionId = (int)$currentQuiz['session_id'];
            $questionIds = $currentQuiz['question_ids'];
            $currentIndex = $currentQuiz['current_index'];
            $totalQuestions = count($questionIds);

            $questionId = (int)($_POST['question_id'] ?? 0);
            $choice     = $_POST['choice'] ?? '';

            if (!$questionId || !in_array($choice, ['A','B','C','D'], true)) {
                $feedback = ['type' => 'error', 'msg' => 'Invalid submission.'];
            } else {
                $st = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
                $st->execute([$questionId]);
                $q = $st->fetch();

                if (!$q) {
                    $feedback = ['type' => 'error', 'msg' => 'Question not found.'];
                } else {
                    $is_correct = ($choice === $q['correct_choice']) ? 1 : 0;

                    $ins = $pdo->prepare("
                        INSERT INTO attempts (user_id, question_id, chosen_choice, is_correct, session_id)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $ins->execute([
                        current_user()['id'],
                        $questionId,
                        $choice,
                        $is_correct,
                        $sessionId
                    ]);

                    if ($is_correct) {
                        $currentQuiz['correct_count']++;
                    }

                    $labels = [
                        'A' => $q['choice_a'],
                        'B' => $q['choice_b'],
                        'C' => $q['choice_c'],
                        'D' => $q['choice_d'],
                    ];

                    $msg = $is_correct
                        ? "MATCH // Answer Correct."
                        : "MISMATCH // Correct value was {$q['correct_choice']}: " . htmlspecialchars($labels[$q['correct_choice']] ?? '');

                    $feedback = [
                        'type' => $is_correct ? 'ok' : 'warn',
                        'msg'  => $msg
                    ];

                    $currentQuiz['current_index']++;
                    $_SESSION['current_quiz'] = $currentQuiz;

                    if ($currentQuiz['current_index'] >= $totalQuestions) {
                        $correct = $currentQuiz['correct_count'];
                        $scorePercent = $totalQuestions > 0 ? ($correct / $totalQuestions) * 100 : 0;

                        $upd = $pdo->prepare("
                            UPDATE quiz_sessions
                            SET total_questions = ?, correct_answers = ?, score_percent = ?, finished_at = NOW()
                            WHERE id = ?
                        ");
                        $upd->execute([$totalQuestions, $correct, $scorePercent, $sessionId]);

                        $finalResult = [
                            'total'   => $totalQuestions,
                            'correct' => $correct,
                            'score'   => $scorePercent
                        ];

                        recalculate_user_badges($pdo, current_user()['id']);

                        clear_current_quiz();
                    }
                }
            }
        }
    }
} else {
    if (!$currentQuiz) {
        $currentQuiz = start_new_quiz($pdo);
    }
}

if (!$finalResult && $currentQuiz) {
    $questionIds = $currentQuiz['question_ids'];
    $currentIndex = $currentQuiz['current_index'];
    $totalQuestions = count($questionIds);

    if ($currentIndex < $totalQuestions) {
        $qid = $questionIds[$currentIndex];
        $st = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
        $st->execute([$qid]);
        $question = $st->fetch();
    }
}

include __DIR__ . '/_partials/header.php';
?>

<div class="card" style="max-width:700px; margin:0 auto;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid rgba(255,255,255,0.05);">
    <div class="hx" style="margin:0;">Knowledge Assessment Sequence</div>
    <div class="status-indicator">
      <span class="status-dot online"></span>
      <span class="mono" style="font-size:10px; color:var(--cyan); letter-spacing:1px; text-transform:uppercase;">Session <?= $currentQuiz ? htmlspecialchars($currentQuiz['session_id']) : 'Complete' ?> Active</span>
    </div>
  </div>

  <?php if ($finalResult): ?>
    <!-- Écran final -->
    <div style="text-align:center; padding:40px 20px;">
      <div style="font-family:var(--display); font-size:64px; font-weight:800; line-height:1; margin-bottom:16px; color:<?= $finalResult['score'] >= 80 ? 'var(--green)' : ($finalResult['score'] >= 50 ? 'var(--amber)' : 'var(--red)') ?>; text-shadow:0 0 20px <?= $finalResult['score'] >= 80 ? 'var(--green)' : ($finalResult['score'] >= 50 ? 'var(--amber)' : 'var(--red)') ?>50;">
        <?= number_format($finalResult['score'], 1) ?>%
      </div>
      <p class="subtle mono" style="font-size:14px; text-transform:uppercase; margin-bottom:8px;">
        Assessment Complete // Score Validated
      </p>
      <p style="font-size:16px; margin-bottom:32px; color:var(--text-bright);">
        Integrity Level: <strong><?= (int)$finalResult['correct'] ?> / <?= (int)$finalResult['total'] ?></strong> Data Points Matched.
      </p>
      <div style="display:flex; gap:16px; justify-content:center;">
        <a class="btn btn-primary" href="/quiz.php?start=1">Initialize New Sequence</a>
        <a class="btn" href="/dashboard.php">Return to Command Center</a>
      </div>
    </div>

  <?php elseif (!$question): ?>
    <div style="padding:40px; text-align:center;">
      <p class="subtle mono" style="margin-bottom:24px;">ERROR: Sequence database empty. No queries available.</p>
      <a class="btn" href="/dashboard.php">Abort Sequence</a>
    </div>

  <?php else: ?>
    <?php
      $currentIndex = $currentQuiz['current_index'] + 1;
      $totalQuestions = count($currentQuiz['question_ids']);
      $progressPct = ($currentIndex / $totalQuestions) * 100;
    ?>

    <?php if ($feedback): ?>
      <?php
        $cls = $feedback['type']==='ok' ? 'badge badge-ok'
             : ($feedback['type']==='warn' ? 'badge badge-warn' : 'badge badge-danger');
      ?>
      <div style="margin-bottom:24px; text-align:center;">
        <span class="<?= $cls ?>" style="font-size:13px; padding:10px 16px; display:inline-block; box-shadow:0 0 15px rgba(0,0,0,0.5);"><?= htmlspecialchars($feedback['msg']) ?></span>
      </div>
    <?php endif; ?>

    <!-- Progress bar -->
    <div style="margin-bottom:24px;">
      <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
        <span class="mono" style="font-size:11px; color:var(--cyan); letter-spacing:1px; text-transform:uppercase;">Query <?= $currentIndex ?> of <?= $totalQuestions ?></span>
        <span class="mono" style="font-size:11px; color:var(--text-dim);"><?= round($progressPct) ?>% LOADED</span>
      </div>
      <div class="progress-track">
        <div class="progress-fill" style="width:<?= $progressPct ?>%; background:var(--cyan); box-shadow:0 0 10px var(--cyan);"></div>
      </div>
    </div>

    <form method="post" class="mt-2" style="position:relative; z-index:2;">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="question_id" value="<?= (int)$question['id'] ?>">

      <div style="background:rgba(0,0,0,0.3); padding:24px; border-radius:8px; border-left:3px solid var(--purple); margin-bottom:24px;">
        <p style="font-family:var(--display); font-weight:600; font-size:18px; line-height:1.5; color:var(--text-bright); margin:0;">
          <?= nl2br(htmlspecialchars($question['question_text'])) ?>
        </p>
      </div>

      <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:32px;">
        <?php foreach (['A', 'B', 'C', 'D'] as $opt): 
          $key = 'choice_' . strtolower($opt);
          if (empty($question[$key])) continue;
        ?>
        <label class="choice" style="position:relative; display:flex; align-items:center; padding:16px 20px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.1); border-radius:8px; cursor:pointer; transition:all 0.2s;">
          <input type="radio" name="choice" value="<?= $opt ?>" required style="position:absolute; opacity:0; width:0; height:0;">
          <div class="choice-marker" style="width:24px; height:24px; border-radius:4px; border:1px solid rgba(255,255,255,0.2); margin-right:16px; display:grid; place-items:center; font-family:var(--mono); font-size:12px; font-weight:700; color:var(--text-dim); transition:all 0.2s;">
            <?= $opt ?>
          </div>
          <div style="flex:1; font-size:15px; color:var(--text-bright); line-height:1.4;">
            <?= htmlspecialchars($question[$key]) ?>
          </div>
          <style>
            label.choice:hover { background:rgba(0, 240, 255, 0.05); border-color:rgba(0, 240, 255, 0.3); transform:translateX(5px); }
            label.choice input:checked + .choice-marker { background:var(--cyan); border-color:var(--cyan); color:#000; box-shadow:0 0 10px rgba(0,240,255,0.5); }
            label.choice input:checked ~ div { color:var(--cyan); }
            label.choice:has(input:checked) { border-color:var(--cyan); background:rgba(0, 240, 255, 0.05); }
          </style>
        </label>
        <?php endforeach; ?>
      </div>

      <div style="text-align:right;">
        <button class="btn btn-primary" type="submit" style="padding:12px 24px; font-size:14px; letter-spacing:1px;">
          Submit Response <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left:8px;"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </button>
      </div>
    </form>
  <?php endif; ?>
</div>

<div style="max-width:700px; margin:24px auto 0; text-align:center;">
    <p class="mono subtle" style="font-size:10px; text-transform:uppercase; letter-spacing:1px;">
        Assessment data is securely logged in [attempts] table with corresponding [session_id].
    </p>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
