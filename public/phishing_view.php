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
        $feedback = [
            'type' => 'error',
            'msg'  => 'Invalid CSRF token'
        ];
    } else {
        $choice = $_POST['choice'] ?? '';

        if ($choice === 'phishing' || $choice === 'legitimate') {
            $correct = ($choice === $email['type']) ? 1 : 0;

            $stmt = $pdo->prepare("
                INSERT INTO phishing_attempts (user_id, email_id, user_choice, correct)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $id, $choice, $correct]);

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
      <div class="hx" style="margin:0; font-size:20px;">
        Intercepted Communication View
      </div>

      <div class="mono" style="font-size:11px; color:var(--text-dim); text-transform:uppercase; letter-spacing:1px; background:rgba(0,0,0,0.3); padding:4px 8px; border-radius:4px; border:1px solid rgba(255,255,255,0.05);">
        ID: OBJ-<?= str_pad($id, 3, '0', STR_PAD_LEFT) ?>
      </div>
    </div>

    <div style="background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.05); border-radius:8px; padding:16px; font-family:var(--mono); font-size:13px;">
      <div style="display:grid; grid-template-columns:80px 1fr; gap:8px; align-items:baseline; margin-bottom:8px;">
        <span style="color:var(--text-dim); text-transform:uppercase; letter-spacing:1px; font-size:11px;">
          FROM:
        </span>
        <span style="color:var(--text);">
          <?= htmlspecialchars($email['sender']) ?>
        </span>
      </div>

      <div style="display:grid; grid-template-columns:80px 1fr; gap:8px; align-items:baseline;">
        <span style="color:var(--text-dim); text-transform:uppercase; letter-spacing:1px; font-size:11px;">
          SUBJECT:
        </span>
        <span style="color:var(--amber);">
          <?= htmlspecialchars($email['subject']) ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Email Body -->
  <div style="padding:32px;">
    <div style="position:relative;">
      <div style="position:absolute; left:-16px; top:0; bottom:0; width:4px; border-left:2px solid rgba(255,184,0,0.3); border-top:2px solid rgba(255,184,0,0.3); border-bottom:2px solid rgba(255,184,0,0.3);"></div>

      <pre class="email-body" style="background:rgba(0,0,0,0.2); padding:24px; border-radius:8px; border:1px solid rgba(255,255,255,0.03); white-space:pre-wrap; font-family:var(--mono); font-size:14px; line-height:1.6; color:var(--text); overflow-x:auto;"><?= htmlspecialchars($email['body']) ?></pre>
    </div>

    <!-- Attachment block -->
    <?php if (!empty($email['attachment'])):
        $att = $email['attachment'];
        $isFake = !empty($att['fake']);
    ?>
      <div id="attachment-block"
           style="margin-top:20px; background:rgba(0,0,0,0.4);
                  border:1px solid rgba(255,184,0,0.3);
                  border-radius:8px; padding:14px 16px;
                  display:flex; align-items:center; gap:14px;
                  cursor:pointer; transition:all 0.2s;"
           <?php if ($isFake): ?>
             onclick="document.getElementById('fake-pdf-overlay').style.display='flex';"
           <?php endif; ?>>

        <div style="width:42px; height:48px; flex-shrink:0; background:linear-gradient(135deg, #ff2a5f, #b026ff);
                    border-radius:4px; display:grid; place-items:center; color:#fff;
                    font-family:var(--mono); font-weight:700; font-size:11px;
                    box-shadow:0 0 12px rgba(255,42,95,0.3);">
          PDF
        </div>

        <div style="flex:1; min-width:0;">
          <div style="font-family:var(--mono); font-size:13px; color:var(--text);
                      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            <?= htmlspecialchars($att['name']) ?>
          </div>

          <div class="mono subtle" style="font-size:10px; letter-spacing:1px; margin-top:2px;">
            <?= htmlspecialchars($att['size'] ?? '') ?> · <?= $isFake ? 'CLICK TO PREVIEW' : 'ATTACHMENT' ?>
          </div>
        </div>

        <?php if ($isFake): ?>
          <div style="color:var(--amber); font-size:11px; font-family:var(--mono);">
            ▶ OPEN
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Feedback -->
    <?php if ($feedback): ?>
      <div style="margin-top:32px; text-align:center;">
        <?php $cls = $feedback['type'] === 'ok' ? 'badge badge-ok' : 'badge badge-danger'; ?>

        <span class="<?= $cls ?>" style="font-size:13px; padding:10px 16px; display:inline-block; box-shadow:0 0 15px rgba(0,0,0,0.3); text-transform:uppercase; letter-spacing:1px;">
          <?= htmlspecialchars($feedback['msg']) ?>
        </span>
      </div>
    <?php endif; ?>

    <!-- Action buttons -->
    <?php if (!$showExplanation): ?>
      <div style="margin-top:32px; padding-top:24px; border-top:1px dashed rgba(255,255,255,0.1);">
        <p class="mono subtle" style="text-align:center; font-size:11px; text-transform:uppercase;
                                       letter-spacing:1px; margin-bottom:16px;">
          Provide Threat Assessment:
        </p>

        <form method="post" style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

          <button class="btn"
                  style="border-color:var(--green); color:var(--green); background:rgba(0,255,136,0.05); padding:12px 24px;"
                  name="choice"
                  value="legitimate"
                  type="submit">
            Legit Payload
          </button>

          <button class="btn"
                  style="border-color:var(--red); color:var(--red); background:rgba(255,42,95,0.05); padding:12px 24px;"
                  name="choice"
                  value="phishing"
                  type="submit">
            MALICIOUS (PHISHING)
          </button>
        </form>
      </div>
    <?php endif; ?>

    <!-- Explanation -->
    <?php if ($showExplanation && !empty($email['explanation'])): ?>
      <div class="card" style="margin-top:24px; border-top:2px solid var(--cyan); background:rgba(0,240,255,0.02);">
        <div class="hx" style="font-size:16px; margin-bottom:12px; color:var(--cyan); text-shadow:0 0 5px rgba(0,240,255,0.3);">
          Post-Analysis Report
        </div>

        <p class="subtle mono" style="font-size:12px; margin-bottom:16px; text-transform:uppercase; letter-spacing:1px;">
          Classification Rationale:
          <strong style="color:<?= $email['type'] === 'phishing' ? 'var(--red)' : 'var(--green)' ?>">
            <?= strtoupper(htmlspecialchars($email['type'])) ?>
          </strong>
        </p>

        <ul style="margin:0; padding-left:20px; color:var(--text-muted); font-size:14px; line-height:1.6;">
          <?php foreach ($email['explanation'] as $e): ?>
            <li style="margin-bottom:8px;">
              <?= htmlspecialchars($e) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div style="text-align:center; margin-top:<?= $showExplanation ? '32px' : '24px' ?>;">
      <a class="btn btn-ghost" href="/phishing.php" style="font-size:12px;">
        ← Return to Inbox
      </a>
    </div>
  </div>
</div>

<!-- Fake PDF Overlay -->
<?php if (!empty($email['attachment']['fake'])): ?>
<div id="fake-pdf-overlay"
     style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.88); backdrop-filter:blur(6px); align-items:center; justify-content:center; padding:20px;">

  <!-- Stage 1: Fake PDF -->
  <div id="fake-pdf-stage1"
       style="background:#f5f5f0; color:#1a1a1a; width:100%; max-width:640px; max-height:90vh; overflow-y:auto; border-radius:6px; box-shadow:0 12px 40px rgba(0,0,0,0.5); position:relative; font-family:Georgia, serif;">

    <!-- Fake PDF toolbar -->
    <div style="background:#3a3a3a; color:#fff; padding:8px 14px; display:flex;
                justify-content:space-between; align-items:center; font-family:Arial, sans-serif;
                font-size:12px; position:sticky; top:0; z-index:2;">
      <span>
        📄 <?= htmlspecialchars($email['attachment']['name']) ?>
      </span>

      <button onclick="document.getElementById('fake-pdf-overlay').style.display='none';"
              style="background:transparent; color:#fff; border:none; font-size:16px;
                     cursor:pointer; padding:0 6px;">
        ✕
      </button>
    </div>

    <!-- Fake PDF content -->
    <div style="padding:32px 40px;">
      <div style="border-bottom:2px solid #999; padding-bottom:14px; margin-bottom:20px;
                  display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
          <div style="font-size:11px; letter-spacing:2px; color:#999; text-transform:uppercase; margin-bottom:4px;">
            Document officiel
          </div>

          <h2 style="font-size:22px; margin:0; color:#1a1a1a; font-family:Arial, sans-serif;">
            <?= $id === 7 ? 'Bulletin de salaire — Avril 2026' : 'Bon de livraison' ?>
          </h2>
        </div>

        <div style="background:#cc0000; color:#fff; padding:4px 8px; font-size:9px;
                    font-weight:700; letter-spacing:1px;">
          URGENT
        </div>
      </div>

      <p style="font-size:13px; line-height:1.6; color:#333; margin-bottom:16px;">
        <?php if ($id === 7): ?>
          Cher collaborateur, votre bulletin de salaire est généré.
          <strong>Action requise :</strong> mise à jour de vos coordonnées bancaires.
        <?php else: ?>
          Votre colis n°JD014600006281351046 est en attente.
          <strong>Confirmez vos coordonnées</strong> pour finaliser la livraison.
        <?php endif; ?>
      </p>

      <!-- Fake trap form -->
      <div style="background:#fff; border:1px solid #ccc; padding:18px; border-radius:4px;
                  font-family:Arial, sans-serif;">

        <div style="font-size:11px; letter-spacing:1px; color:#666; text-transform:uppercase;
                    margin-bottom:12px;">
          ⚠ Formulaire interactif
        </div>

        <label style="display:block; font-size:11px; color:#444; margin-bottom:4px; font-weight:bold; font-family:Arial,sans-serif;">
          Nom complet
        </label>
        <input id="fp-name"
               type="text"
               placeholder="Jean Dupont"
               style="width:100%; padding:8px 10px; border:1px solid #aaa; border-radius:3px;
                      font-size:13px; margin-bottom:12px; font-family:inherit;">

        <label style="display:block; font-size:11px; color:#444; margin-bottom:4px; font-weight:bold; font-family:Arial,sans-serif;">
          Numéro de carte bancaire
        </label>
        <input id="fp-card"
               type="text"
               placeholder="1234 5678 9012 3456"
               maxlength="19"
               style="width:100%; padding:8px 10px; border:1px solid #aaa; border-radius:3px;
                      font-size:13px; margin-bottom:12px; font-family:inherit;">

        <div style="display:flex; gap:10px;">
          <div style="flex:1;">
            <label style="display:block; font-size:11px; color:#444; margin-bottom:4px; font-weight:bold; font-family:Arial,sans-serif;">
              Expiration
            </label>
            <input id="fp-exp"
                   type="text"
                   placeholder="MM/AA"
                   style="width:100%; padding:8px 10px; border:1px solid #aaa; border-radius:3px;
                          font-size:13px; font-family:inherit;">
          </div>

          <div style="flex:1;">
            <label style="display:block; font-size:11px; color:#444; margin-bottom:4px; font-weight:bold; font-family:Arial,sans-serif;">
              CVV
            </label>
            <input id="fp-cvv"
                   type="text"
                   placeholder="123"
                   maxlength="4"
                   style="width:100%; padding:8px 10px; border:1px solid #aaa; border-radius:3px;
                          font-size:13px; font-family:inherit;">
          </div>
        </div>

        <button id="fp-submit"
                style="margin-top:16px; background:#cc0000; color:#fff; border:none;
                       padding:10px 20px; border-radius:3px; font-size:13px; cursor:pointer;
                       font-weight:bold; width:100%;">
          Valider
        </button>
      </div>

      <p style="font-size:10px; color:#999; margin-top:14px; font-style:italic;">
        Ce document est confidentiel. Toute diffusion est strictement interdite.
      </p>
    </div>
  </div>

  <!-- Stage 2: Educational Reveal -->
  <div id="fake-pdf-stage2"
       style="display:none; background:rgba(3,5,8,0.98); border:2px solid var(--red); width:100%; max-width:540px; border-radius:12px; padding:36px 32px; text-align:center; color:var(--text); font-family:var(--sans); box-shadow:0 0 60px rgba(255,42,95,0.4);">

    <div style="font-size:64px; line-height:1; margin-bottom:16px; color:var(--red);
                text-shadow:0 0 30px rgba(255,42,95,0.6);">
      ⚠
    </div>

    <div class="hx" style="justify-content:center; margin-bottom:14px; color:var(--red);
                            text-shadow:0 0 12px rgba(255,42,95,0.5);">
      GAME OVER // Tu viens de te faire avoir.
    </div>

    <p class="subtle" style="font-size:14px; line-height:1.6; margin-bottom:20px;">
      Si ce mail était réel, tes données bancaires viendraient d'arriver
      sur le serveur d'un attaquant. Bonne nouvelle : c'était une simulation.
    </p>

    <div style="background:rgba(255,42,95,0.05); border-left:3px solid var(--red);
                padding:14px 18px; text-align:left; border-radius:4px; margin-bottom:24px;">
      <div class="mono" style="font-size:10px; color:var(--red); letter-spacing:2px;
                                 margin-bottom:8px;">
        À RETENIR
      </div>

      <ul style="margin:0; padding-left:18px; color:var(--text-muted); font-size:13px;
                 line-height:1.7;">
        <li>Aucun document légitime ne demande ton numéro de carte / CVV.</li>
        <li>Un PDF interactif qui réclame des credentials = piège.</li>
        <li>Vérifie toujours le domaine de l'expéditeur avant d'ouvrir.</li>
      </ul>
    </div>

    <button onclick="document.getElementById('fake-pdf-overlay').style.display='none';"
            class="btn btn-primary"
            style="padding:12px 28px; background:rgba(255,42,95,0.15);
                   border-color:var(--red); color:var(--red);">
      Fermer la simulation
    </button>
  </div>
</div>

<script>
(function() {
  const submitBtn = document.getElementById('fp-submit');

  if (!submitBtn) return;

  submitBtn.addEventListener('click', function(e) {
    e.preventDefault();

    document.getElementById('fake-pdf-stage1').style.display = 'none';
    document.getElementById('fake-pdf-stage2').style.display = 'block';
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const overlay = document.getElementById('fake-pdf-overlay');

      if (overlay && overlay.style.display !== 'none') {
        overlay.style.display = 'none';
      }
    }
  });
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/_partials/footer.php'; ?>