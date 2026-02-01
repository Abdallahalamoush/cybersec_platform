<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/badges.php';

require_auth(); // réservé aux utilisateurs connectés

// Nombre de questions par session de quiz
const QUIZ_QUESTIONS_PER_SESSION = 5;

function start_new_quiz(PDO $pdo): ?array {
    // Récupérer tous les IDs de questions
    $stmt = $pdo->query("SELECT id FROM questions");
    $rows = $stmt->fetchAll();
    if (!$rows) {
        return null; // aucun quiz possible
    }

    $allIds = array_column($rows, 'id');
    shuffle($allIds);

    $total = min(QUIZ_QUESTIONS_PER_SESSION, count($allIds));
    $questionIds = array_slice($allIds, 0, $total);

    // Créer une nouvelle session de quiz en DB
    $ins = $pdo->prepare("INSERT INTO quiz_sessions (user_id) VALUES (?)");
    $ins->execute([ current_user()['id'] ]);
    $sessionId = (int)$pdo->lastInsertId();

    // Stocker l'état dans $_SESSION
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

// Si on demande explicitement un nouveau quiz (?start=1)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start'])) {
    start_new_quiz($pdo);
}

// Charger l'état courant
$currentQuiz = get_current_quiz();

// Soumission d'une réponse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $feedback = ['type' => 'error', 'msg' => 'Token CSRF invalide.'];
    } else {
        $currentQuiz = get_current_quiz();
        if (!$currentQuiz) {
            $feedback = ['type' => 'error', 'msg' => 'Session de quiz introuvable.'];
        } else {
            $sessionId = (int)$currentQuiz['session_id'];
            $questionIds = $currentQuiz['question_ids'];
            $currentIndex = $currentQuiz['current_index'];
            $totalQuestions = count($questionIds);

            $questionId = (int)($_POST['question_id'] ?? 0);
            $choice     = $_POST['choice'] ?? '';

            if (!$questionId || !in_array($choice, ['A','B','C','D'], true)) {
                $feedback = ['type' => 'error', 'msg' => 'Soumission invalide.'];
            } else {
                // Charger la question pour vérifier
                $st = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
                $st->execute([$questionId]);
                $q = $st->fetch();

                if (!$q) {
                    $feedback = ['type' => 'error', 'msg' => 'Question introuvable.'];
                } else {
                    $is_correct = ($choice === $q['correct_choice']) ? 1 : 0;

                    // Enregistrer la tentative dans attempts
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

                    // Mettre à jour le score en session
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
                        ? "✅ Bonne réponse !"
                        : "❌ Mauvaise réponse. La bonne réponse était {$q['correct_choice']} : " .
                          htmlspecialchars($labels[$q['correct_choice']] ?? '');

                    $feedback = [
                        'type' => $is_correct ? 'ok' : 'warn',
                        'msg'  => $msg
                    ];

                    // Passer à la question suivante
                    $currentQuiz['current_index']++;
                    $_SESSION['current_quiz'] = $currentQuiz;

                    // Si on a terminé toutes les questions
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

                        // 🔥 Recalculate badges after quiz session
                        recalculate_user_badges($pdo, current_user()['id']);

                        clear_current_quiz();
                    }
                }
            }
        }
    }
} else {
    // GET : si pas de session en cours, tenter d'en démarrer une
    if (!$currentQuiz) {
        $currentQuiz = start_new_quiz($pdo);
    }
}

// Si on a encore une session et pas de résultat final, charger la question courante
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

<div class="card">
  <div class="hx">Quiz — Session</div>

  <?php if ($finalResult): ?>
    <!-- Écran final -->
    <p class="subtle">
      Session terminée. Votre score :
      <strong><?= (int)$finalResult['correct'] ?>/<?= (int)$finalResult['total'] ?></strong>
      (<?= number_format($finalResult['score'], 1) ?> %)
    </p>
    <div class="mt-3">
      <a class="btn btn-primary" href="/quiz.php?start=1">Recommencer un quiz</a>
      <a class="btn" href="/index.php">Retour à l’accueil</a>
    </div>

  <?php elseif (!$question): ?>
    <p>Aucune question disponible pour le moment.</p>
    <a class="btn" href="/index.php">Retour</a>

  <?php else: ?>
    <?php
      $currentIndex = $currentQuiz['current_index'] + 1;
      $totalQuestions = count($currentQuiz['question_ids']);
    ?>

    <?php if ($feedback): ?>
      <?php
        $cls = $feedback['type']==='ok' ? 'badge badge-ok'
             : ($feedback['type']==='warn' ? 'badge badge-warn' : 'badge badge-danger');
      ?>
      <p class="<?= $cls ?>"><?= htmlspecialchars($feedback['msg']) ?></p>
    <?php else: ?>
      <p class="subtle">Réponds à chaque question. Le score sera affiché à la fin de la session.</p>
    <?php endif; ?>

    <p class="subtle mt-1">
      Question <?= $currentIndex ?> / <?= $totalQuestions ?>
    </p>

    <form method="post" class="mt-2">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="question_id" value="<?= (int)$question['id'] ?>">

      <p style="font-weight:700; font-size:18px">
        <?= htmlspecialchars($question['question_text']) ?>
      </p>

      <?php if (!empty($question['choice_a'])): ?>
      <label class="choice">
        <input type="radio" name="choice" value="A" required>
        <div><strong>A.</strong> <?= htmlspecialchars($question['choice_a']) ?></div>
      </label>
      <?php endif; ?>

      <?php if (!empty($question['choice_b'])): ?>
      <label class="choice">
        <input type="radio" name="choice" value="B" required>
        <div><strong>B.</strong> <?= htmlspecialchars($question['choice_b']) ?></div>
      </label>
      <?php endif; ?>

      <?php if (!empty($question['choice_c'])): ?>
      <label class="choice">
        <input type="radio" name="choice" value="C" required>
        <div><strong>C.</strong> <?= htmlspecialchars($question['choice_c']) ?></div>
      </label>
      <?php endif; ?>

      <?php if (!empty($question['choice_d'])): ?>
      <label class="choice">
        <input type="radio" name="choice" value="D" required>
        <div><strong>D.</strong> <?= htmlspecialchars($question['choice_d']) ?></div>
      </label>
      <?php endif; ?>

      <div class="mt-3">
        <button class="btn btn-primary" type="submit">Valider</button>
      </div>
    </form>
  <?php endif; ?>
</div>

<div class="card" style="opacity:.95">
  <div class="hx">Historique rapide</div>
  <p class="subtle">
    Chaque réponse est enregistrée dans <code>attempts</code> avec un
    <code>session_id</code>. Le résumé de la session est stocké dans
    <code>quiz_sessions</code>.
  </p>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
