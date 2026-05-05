<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/badges.php';
require_auth();

$user   = current_user();
$userId = $user['id'];

$questions = [
    [
        'q'       => "Lequel de ces snippets PHP est vulnérable à l'injection SQL ?",
        'choices' => [
            "\$st = \$pdo->prepare('SELECT * FROM users WHERE id = ?'); \$st->execute([\$id]);",
            "\$sql = \"SELECT * FROM users WHERE id = \" . \$_GET['id']; \$pdo->query(\$sql);",
            "\$st = \$pdo->prepare('SELECT * FROM users WHERE email = :e'); \$st->execute(['e'=>\$email]);",
            "Aucun, ils sont tous sécurisés.",
        ],
        'answer'  => 1
    ],
    [
        'q'       => "Tu affiches le pseudo d'un utilisateur sur ta page profil. Quelle protection appliquer ?",
        'choices' => [
            "Rien, le navigateur s'occupe d'échapper tout seul",
            "Concaténer directement avec echo",
            "Utiliser htmlspecialchars() avant d'afficher",
            "Convertir en majuscules pour neutraliser les balises",
        ],
        'answer'  => 2
    ],
    [
        'q'       => "À quoi sert un token CSRF dans un formulaire POST ?",
        'choices' => [
            "Chiffrer les données envoyées au serveur",
            "Vérifier que la requête provient bien d'un formulaire de ton site, pas d'un site tiers",
            "Compresser la taille de la requête",
            "Empêcher les injections SQL",
        ],
        'answer'  => 1
    ],
];

$score = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correct = 0;
    foreach ($questions as $i => $q) {
        $ans = (int)($_POST["q$i"] ?? -1);
        if ($ans === $q['answer']) $correct++;
    }
    $score = (int)round(($correct / count($questions)) * 100);

    if ($score >= 60) {
        $stmt = $pdo->prepare("
            INSERT INTO module_progress (user_id, module_code, completed, completed_at)
            VALUES (?, 'secure_dev', 1, NOW())
            ON DUPLICATE KEY UPDATE completed = 1, completed_at = NOW()
        ");
        $stmt->execute([$userId]);
        recalculate_user_badges($pdo, $userId);
    }
}

include __DIR__ . '/_partials/header.php';
?>

<div class="card" style="max-width:820px; margin:0 auto; padding:0; overflow:hidden;">

  <!-- Header -->
  <div style="padding:32px 32px 24px; border-bottom:1px solid rgba(255,255,255,0.05);
              background:radial-gradient(ellipse at top right, rgba(176,38,255,0.12), transparent 60%);">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
      <div>
        <div class="hx" style="margin-bottom:8px; display:flex; align-items:center; gap:12px;">
          <div style="width:40px; height:40px; border-radius:8px; background:rgba(176,38,255,0.1);
                      border:1px solid rgba(176,38,255,0.3); display:grid; place-items:center;
                      color:var(--purple);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="14 2 18 6 7 17 3 17 3 13 14 2"></polygon>
              <line x1="3" y1="22" x2="21" y2="22"></line>
            </svg>
          </div>
          Assessment: Développement Sécurisé
        </div>
        <div class="mono subtle" style="font-size:12px; letter-spacing:1px; text-transform:uppercase;">
          Knowledge Validation // SEC-DEV
        </div>
      </div>
      <div style="padding:6px 14px; border-radius:6px; border:1px solid rgba(176,38,255,0.3);
                  background:rgba(176,38,255,0.08); display:flex; align-items:center; gap:8px;">
        <span style="width:8px; height:8px; border-radius:50%; background:var(--purple);
                     box-shadow:0 0 8px var(--purple);"></span>
        <span class="mono" style="font-size:10px; color:var(--purple); letter-spacing:1px;">
          <?= $score !== null ? 'EVALUATED' : 'ACTIVE' ?>
        </span>
      </div>
    </div>
  </div>

  <div style="padding:32px;">

    <?php if ($score !== null): ?>

      <div style="text-align:center; padding:32px; background:rgba(0,0,0,0.2);
                  border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
        <p class="mono subtle" style="font-size:12px; letter-spacing:2px;
                                      text-transform:uppercase; margin-bottom:8px;">
          Evaluation Score
        </p>
        <div style="font-family:var(--display); font-size:64px; font-weight:800; line-height:1;
                    margin-bottom:16px;
                    color:<?= $score >= 60 ? 'var(--green)' : 'var(--red)' ?>;
                    text-shadow:0 0 20px <?= $score >= 60 ? 'var(--green)' : 'var(--red)' ?>50;">
          <?= $score ?>%
        </div>

        <?php if ($score >= 60): ?>
          <div style="display:inline-block; padding:8px 18px; background:rgba(0,255,136,0.1);
                      border:1px solid rgba(0,255,136,0.3); border-radius:4px;
                      color:var(--green); font-size:14px; letter-spacing:1px; margin-bottom:24px;">
            VALIDATION SUCCESS // CODE DEFENDER UNLOCKED
          </div>
        <?php else: ?>
          <div style="display:inline-block; padding:8px 18px; background:rgba(255,42,95,0.1);
                      border:1px solid rgba(255,42,95,0.3); border-radius:4px;
                      color:var(--red); font-size:14px; letter-spacing:1px; margin-bottom:24px;">
            VALIDATION FAILED // RETRAINING REQUIRED
          </div>
        <?php endif; ?>

        <div>
          <a class="btn btn-primary" href="/secure_dev.php"
             style="padding:10px 24px; font-size:13px; letter-spacing:1px;">
            Return to Training Database
          </a>
        </div>
      </div>

    <?php else: ?>

      <form method="post">
        <?php foreach ($questions as $i => $q): ?>
          <div style="margin-bottom:28px; background:rgba(0,0,0,0.2);
                      border:1px solid rgba(255,255,255,0.05); border-radius:8px; padding:24px;">
            <div style="display:flex; gap:14px; margin-bottom:18px;">
              <div class="mono" style="color:var(--purple); font-size:14px; padding-top:2px;">
                [Q<?= $i + 1 ?>]
              </div>
              <p style="font-family:var(--display); font-weight:600; font-size:16px;
                        line-height:1.5; color:var(--text); margin:0;">
                <?= htmlspecialchars($q['q']) ?>
              </p>
            </div>
            <div style="display:flex; flex-direction:column; gap:10px; padding-left:32px;">
              <?php foreach ($q['choices'] as $ci => $choice): ?>
                <label class="choice"
                  style="position:relative; display:flex; align-items:flex-start; padding:12px 16px;
                         background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05);
                         border-radius:6px; cursor:pointer; transition:all 0.2s;">
                  <input type="radio" name="q<?= $i ?>" value="<?= $ci ?>" required
                         style="position:absolute; opacity:0; width:0; height:0;">
                  <div class="choice-marker"
                    style="width:18px; height:18px; border-radius:50%;
                           border:1px solid rgba(255,255,255,0.2); margin-right:12px;
                           display:grid; place-items:center; transition:all 0.2s; flex-shrink:0;
                           margin-top:2px;">
                    <div class="inner-dot"
                      style="width:8px; height:8px; border-radius:50%;
                             background:transparent; transition:all 0.2s;"></div>
                  </div>
                  <div style="flex:1; font-size:14px; color:var(--text); font-family:var(--mono);
                              line-height:1.5; word-break:break-word;">
                    <?= htmlspecialchars($choice) ?>
                  </div>
                  <style>
                    label.choice:hover { background:rgba(176,38,255,0.05);
                      border-color:rgba(176,38,255,0.3); transform:translateX(5px); }
                    label.choice input:checked + .choice-marker {
                      border-color:var(--purple); box-shadow:0 0 10px rgba(176,38,255,0.5); }
                    label.choice input:checked + .choice-marker .inner-dot { background:var(--purple); }
                    label.choice:has(input:checked) { border-color:var(--purple);
                      background:rgba(176,38,255,0.05); }
                  </style>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <div style="text-align:right; border-top:1px dashed rgba(255,255,255,0.1); padding-top:22px;">
          <button class="btn btn-primary" type="submit"
                  style="padding:12px 32px; font-size:14px; letter-spacing:1px;
                         text-transform:uppercase;
                         background:rgba(176,38,255,0.15); border-color:rgba(176,38,255,0.6);
                         color:var(--purple);">
            Transmit Answers
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 style="margin-left:8px;">
              <line x1="22" y1="2" x2="11" y2="13"></line>
              <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
          </button>
        </div>
      </form>

    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>