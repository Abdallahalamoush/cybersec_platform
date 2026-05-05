<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/badges.php';

require_auth();

const CHALLENGE_QUESTIONS = 5;
const CHALLENGE_TIMER     = 60; // seconds

$user   = current_user();
$userId = $user['id'];

// ── AJAX submit ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit') {
    header('Content-Type: application/json');

    if (!csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid CSRF']);
        exit;
    }

    $duration = max(1, min((int)($_POST['duration'] ?? CHALLENGE_TIMER), CHALLENGE_TIMER));
    $answers  = json_decode($_POST['answers'] ?? '[]', true);
    if (!is_array($answers)) $answers = [];

    $correct = 0;
    $total   = 0;

    foreach ($answers as $a) {
        $qid    = (int)($a['qid'] ?? 0);
        $choice = (string)($a['choice'] ?? '');
        if (!$qid || !in_array($choice, ['A','B','C','D'], true)) continue;

        $st = $pdo->prepare("SELECT correct_choice FROM questions WHERE id = ?");
        $st->execute([$qid]);
        $row = $st->fetch();
        if (!$row) continue;

        $total++;
        if ($row['correct_choice'] === $choice) $correct++;
    }

    $accuracy   = $total > 0 ? ($correct / $total) * 100 : 0;
    $speedBonus = max(0, CHALLENGE_TIMER - $duration);
    $finalScore = (int)round($accuracy + $speedBonus);

    $stmt = $pdo->prepare("
        INSERT INTO challenge_sessions
            (user_id, started_at, finished_at, total_questions, correct_answers, duration_seconds, score)
        VALUES (?, (NOW() - INTERVAL ? SECOND), NOW(), ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $duration, $total, $correct, $duration, $finalScore]);

    recalculate_user_badges($pdo, $userId);

    echo json_encode([
        'correct'  => $correct,
        'total'    => $total,
        'duration' => $duration,
        'score'    => $finalScore,
        'accuracy' => round($accuracy),
    ]);
    exit;
}

// ── GET — load fresh random questions ────────────────────
$stmt = $pdo->prepare("
    SELECT id, question_text, choice_a, choice_b, choice_c, choice_d
    FROM questions
    ORDER BY RAND()
    LIMIT ?
");
$stmt->bindValue(1, CHALLENGE_QUESTIONS, PDO::PARAM_INT);
$stmt->execute();
$questions = $stmt->fetchAll();

// Stats perso
$st = $pdo->prepare("
    SELECT MAX(score) AS best,
           MIN(duration_seconds) AS fastest,
           COUNT(*) AS played
    FROM challenge_sessions
    WHERE user_id = ? AND finished_at IS NOT NULL
");
$st->execute([$userId]);
$personalBest = $st->fetch() ?: ['best' => 0, 'fastest' => 0, 'played' => 0];

include __DIR__ . '/_partials/header.php';
?>

<div class="card" style="max-width:820px; margin:0 auto; padding:0; overflow:hidden;">

  <!-- Header -->
  <div style="padding:32px; border-bottom:1px solid rgba(255,255,255,0.05);
              background:radial-gradient(ellipse at top right, rgba(255,42,95,0.12), transparent 60%);">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;">
      <div>
        <div class="hx" style="margin-bottom:8px; display:flex; align-items:center; gap:12px;">
          <div style="width:40px; height:40px; border-radius:8px; background:rgba(255,42,95,0.1);
                      border:1px solid rgba(255,42,95,0.3); display:grid; place-items:center; color:var(--red);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polyline>
            </svg>
          </div>
          Challenge Mode
        </div>
        <p class="subtle" style="font-size:14px; max-width:520px; margin:0;">
          <?= CHALLENGE_QUESTIONS ?> questions. <?= CHALLENGE_TIMER ?> secondes au compteur.
          Plus tu réponds vite, plus le bonus de vitesse est élevé.
          <strong style="color:var(--red);">Speed Demon</strong> débloque à ≥ 80 % en moins de 45s.
        </p>
      </div>
      <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <div style="background:rgba(0,0,0,0.4); padding:10px 14px; border-radius:6px;
                    border:1px solid rgba(255,255,255,0.05); text-align:center; min-width:90px;">
          <div class="mono" style="font-size:9px; color:var(--text-dim); letter-spacing:2px;">BEST</div>
          <div style="font-family:var(--display); font-weight:800; font-size:20px; color:var(--green);">
            <?= (int)($personalBest['best'] ?? 0) ?>
          </div>
        </div>
        <div style="background:rgba(0,0,0,0.4); padding:10px 14px; border-radius:6px;
                    border:1px solid rgba(255,255,255,0.05); text-align:center; min-width:90px;">
          <div class="mono" style="font-size:9px; color:var(--text-dim); letter-spacing:2px;">FASTEST</div>
          <div style="font-family:var(--display); font-weight:800; font-size:20px; color:var(--cyan);">
            <?= ($personalBest['fastest'] ?? 0) > 0 ? (int)$personalBest['fastest'].'s' : '—' ?>
          </div>
        </div>
        <div style="background:rgba(0,0,0,0.4); padding:10px 14px; border-radius:6px;
                    border:1px solid rgba(255,255,255,0.05); text-align:center; min-width:90px;">
          <div class="mono" style="font-size:9px; color:var(--text-dim); letter-spacing:2px;">RUNS</div>
          <div style="font-family:var(--display); font-weight:800; font-size:20px; color:var(--purple);">
            <?= (int)($personalBest['played'] ?? 0) ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Body -->
  <div style="padding:32px;">

    <?php if (count($questions) < 1): ?>
      <p class="subtle mono" style="text-align:center; padding:40px 0;">
        ERROR // Question pool empty. Contact admin.
      </p>
    <?php else: ?>

    <!-- Pre-start screen -->
    <div id="ch-intro" style="text-align:center; padding:24px;">
      <div style="font-family:var(--display); font-size:48px; font-weight:800; color:var(--red);
                  text-shadow:0 0 20px rgba(255,42,95,0.5); line-height:1; margin-bottom:12px;">
        READY ?
      </div>
      <p class="subtle" style="margin-bottom:24px;">
        Le timer démarre dès que tu cliques. Pas de pause, pas de retour en arrière.
      </p>
      <button id="ch-start-btn" class="btn btn-primary"
              style="padding:16px 40px; font-size:14px; letter-spacing:2px;
                     background:rgba(255,42,95,0.15); border-color:rgba(255,42,95,0.6);
                     color:var(--red); box-shadow:0 0 20px rgba(255,42,95,0.3);">
        ⚡ INITIATE CHALLENGE
      </button>
    </div>

    <!-- Game area -->
    <div id="ch-game" style="display:none;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <span class="mono" style="font-size:11px; color:var(--cyan); letter-spacing:2px;">
          QUESTION <span id="ch-q-current">1</span> / <?= count($questions) ?>
        </span>
        <div id="ch-timer" style="font-family:var(--display); font-weight:800; font-size:32px;
                                  color:var(--green); text-shadow:0 0 15px rgba(0,255,136,0.5);
                                  letter-spacing:2px;">
          <?= CHALLENGE_TIMER ?>s
        </div>
      </div>

      <div class="progress-track" style="height:6px; margin-bottom:24px;">
        <div id="ch-progress-bar" class="progress-fill"
             style="width:100%; background:linear-gradient(90deg, var(--green), var(--amber), var(--red));
                    box-shadow:0 0 10px var(--green); transition:width 1s linear;"></div>
      </div>

      <div id="ch-question-box"></div>
    </div>

    <!-- Results screen -->
    <div id="ch-results" style="display:none; text-align:center; padding:24px;">
      <p class="mono subtle" style="font-size:12px; letter-spacing:2px; margin-bottom:8px;">FINAL SCORE</p>
      <div id="ch-final-score" style="font-family:var(--display); font-size:72px; font-weight:800;
                                       line-height:1; margin-bottom:16px;
                                       color:var(--green); text-shadow:0 0 25px rgba(0,255,136,0.6);">
        0
      </div>
      <p id="ch-final-detail" class="subtle" style="font-size:15px; margin-bottom:24px;"></p>
      <div id="ch-speed-demon" style="display:none; margin-bottom:20px;">
        <span class="badge badge-ok" style="font-size:12px; padding:8px 16px;">
          ⚡ SPEED DEMON BADGE UNLOCKED
        </span>
      </div>
      <div style="display:flex; gap:14px; justify-content:center; flex-wrap:wrap;">
        <a class="btn btn-primary" href="/challenge.php">↻ Run It Back</a>
        <a class="btn" href="/dashboard.php">Command Center</a>
      </div>
    </div>

    <?php endif; ?>

  </div>
</div>

<script>
(function() {
  const QUESTIONS = <?= json_encode(array_values($questions), JSON_UNESCAPED_UNICODE) ?>;
  const TIMER     = <?= CHALLENGE_TIMER ?>;
  const CSRF      = <?= json_encode(csrf_token()) ?>;

  if (!QUESTIONS || QUESTIONS.length === 0) return;

  const startBtn   = document.getElementById('ch-start-btn');
  const intro      = document.getElementById('ch-intro');
  const game       = document.getElementById('ch-game');
  const results    = document.getElementById('ch-results');
  const qBox       = document.getElementById('ch-question-box');
  const qCurrent   = document.getElementById('ch-q-current');
  const timerEl    = document.getElementById('ch-timer');
  const progressEl = document.getElementById('ch-progress-bar');

  let currentIdx = 0;
  let answers    = [];
  let startTime  = 0;
  let timerId    = null;
  let timeLeft   = TIMER;
  let submitted  = false;

  function renderQuestion() {
    if (currentIdx >= QUESTIONS.length) { finish(); return; }

    const q = QUESTIONS[currentIdx];
    qCurrent.textContent = (currentIdx + 1);

    const choices = ['A','B','C','D'].map(letter => {
      const text = q['choice_' + letter.toLowerCase()];
      if (!text) return '';
      return `
        <button type="button" class="ch-choice" data-letter="${letter}"
          style="display:flex; align-items:center; width:100%; gap:14px;
                 padding:14px 18px; margin-bottom:10px; border-radius:8px;
                 background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.1);
                 color:var(--text); font-family:var(--sans); font-size:14px;
                 text-align:left; cursor:pointer; transition:all 0.15s;">
          <span style="width:28px; height:28px; flex-shrink:0; border-radius:6px;
                       border:1px solid rgba(255,255,255,0.2); display:grid; place-items:center;
                       font-family:var(--mono); font-weight:700; font-size:12px;
                       color:var(--text-dim);">${letter}</span>
          <span style="flex:1;">${escapeHtml(text)}</span>
        </button>`;
    }).join('');

    qBox.innerHTML = `
      <div style="background:rgba(0,0,0,0.3); padding:22px; border-radius:8px;
                  border-left:3px solid var(--red); margin-bottom:24px;">
        <p style="font-family:var(--display); font-weight:600; font-size:17px;
                  line-height:1.5; color:var(--text); margin:0;">
          ${escapeHtml(q.question_text)}
        </p>
      </div>
      <div>${choices}</div>
    `;

    document.querySelectorAll('.ch-choice').forEach(el => {
      el.addEventListener('mouseenter', () => {
        el.style.background = 'rgba(0,240,255,0.05)';
        el.style.borderColor = 'rgba(0,240,255,0.3)';
        el.style.transform = 'translateX(4px)';
      });
      el.addEventListener('mouseleave', () => {
        el.style.background = 'rgba(255,255,255,0.03)';
        el.style.borderColor = 'rgba(255,255,255,0.1)';
        el.style.transform = 'none';
      });
      el.addEventListener('click', () => {
        const letter = el.dataset.letter;
        answers.push({ qid: q.id, choice: letter });
        currentIdx++;
        renderQuestion();
      });
    });
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    })[c]);
  }

  function tick() {
    timeLeft--;
    if (timeLeft <= 0) {
      timerEl.textContent = '0s';
      progressEl.style.width = '0%';
      finish();
      return;
    }
    timerEl.textContent = timeLeft + 's';
    timerEl.style.color = timeLeft > 30 ? 'var(--green)' : (timeLeft > 15 ? 'var(--amber)' : 'var(--red)');
    timerEl.style.textShadow = '0 0 15px ' + timerEl.style.color;
    progressEl.style.width = (timeLeft / TIMER * 100) + '%';
  }

  function finish() {
    if (submitted) return;
    submitted = true;
    if (timerId) clearInterval(timerId);

    const elapsed = Math.min(TIMER, Math.max(1, Math.round((Date.now() - startTime) / 1000)));

    const fd = new FormData();
    fd.append('action', 'submit');
    fd.append('csrf', CSRF);
    fd.append('duration', elapsed);
    fd.append('answers', JSON.stringify(answers));

    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        game.style.display = 'none';
        results.style.display = 'block';

        const scoreEl  = document.getElementById('ch-final-score');
        const detailEl = document.getElementById('ch-final-detail');
        const sdEl     = document.getElementById('ch-speed-demon');

        scoreEl.textContent = data.score;
        const color = data.score >= 120 ? 'var(--green)' : (data.score >= 80 ? 'var(--amber)' : 'var(--red)');
        scoreEl.style.color = color;
        scoreEl.style.textShadow = '0 0 25px ' + color;
        detailEl.innerHTML =
          `<strong>${data.correct}/${data.total}</strong> correct · `
          + `<strong>${data.duration}s</strong> elapsed · `
          + `accuracy <strong>${data.accuracy}%</strong>`;

        if (data.accuracy >= 80 && data.duration <= 45) {
          sdEl.style.display = 'block';
        }
      })
      .catch(() => {
        game.style.display = 'none';
        results.style.display = 'block';
        document.getElementById('ch-final-score').textContent = '?';
        document.getElementById('ch-final-detail').textContent =
          'Submission failed. Reload and retry.';
      });
  }

  startBtn.addEventListener('click', () => {
    intro.style.display = 'none';
    game.style.display  = 'block';
    startTime = Date.now();
    timerId = setInterval(tick, 1000);
    renderQuestion();
  });
})();
</script>

<?php include __DIR__ . '/_partials/footer.php'; ?>