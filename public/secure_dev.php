<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_auth();

$user   = current_user();
$userId = $user['id'];

$check = $pdo->prepare("
    SELECT completed FROM module_progress
    WHERE user_id = ? AND module_code = 'secure_dev'
");
$check->execute([$userId]);
$row       = $check->fetch();
$completed = ($row && (int)$row['completed'] === 1);

include __DIR__ . '/_partials/header.php';
?>

<div class="card" style="max-width:820px; margin:0 auto; padding:0; overflow:hidden;">

  <!-- Header -->
  <div style="padding:32px 32px 24px; border-bottom:1px solid rgba(255,255,255,0.05);
              background:radial-gradient(ellipse at top left, rgba(176,38,255,0.12), transparent 60%);">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
      <div>
        <div class="hx" style="margin-bottom:8px; display:flex; align-items:center; gap:12px;">
          <div style="width:40px; height:40px; border-radius:8px; background:rgba(176,38,255,0.1);
                      border:1px solid rgba(176,38,255,0.3); display:grid; place-items:center;
                      color:var(--purple);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="16 18 22 12 16 6"></polyline>
              <polyline points="8 6 2 12 8 18"></polyline>
            </svg>
          </div>
          Développement Sécurisé
        </div>
        <div class="mono subtle" style="font-size:12px; letter-spacing:1px; text-transform:uppercase;">
          Training Module // SEC-DEV
        </div>
      </div>

      <?php if ($completed): ?>
        <div style="background:rgba(0,255,136,0.1); border:1px solid rgba(0,255,136,0.3);
                    padding:6px 14px; border-radius:6px; display:flex; align-items:center; gap:8px;">
          <span style="width:8px; height:8px; border-radius:50%; background:var(--green);
                       box-shadow:0 0 8px var(--green); flex-shrink:0;"></span>
          <span class="mono" style="font-size:10px; color:var(--green); letter-spacing:1px;">SECURED</span>
        </div>
      <?php else: ?>
        <div style="background:rgba(255,184,0,0.1); border:1px solid rgba(255,184,0,0.3);
                    padding:6px 14px; border-radius:6px; display:flex; align-items:center; gap:8px;">
          <span style="width:8px; height:8px; border-radius:50%; background:var(--amber);
                       box-shadow:0 0 8px var(--amber); flex-shrink:0;"></span>
          <span class="mono" style="font-size:10px; color:var(--amber); letter-spacing:1px;">PENDING</span>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Content -->
  <div style="padding:32px;">

    <p style="font-size:16px; line-height:1.7; color:var(--text); margin-bottom:28px;">
      Le code que tu écris est ta première ligne de défense. Une seule requête SQL non préparée,
      un seul <code>echo</code> non échappé, et un attaquant peut prendre le contrôle de ton application.
      Ce module couvre les <strong style="color:var(--purple);">trois failles fondamentales</strong>
      à neutraliser dès la phase de dev.
    </p>

    <!-- SQL Injection -->
    <div style="background:rgba(255,42,95,0.03); border:1px solid rgba(255,42,95,0.15);
                border-left:3px solid var(--red); border-radius:8px; padding:24px; margin-bottom:20px;">
      <div class="hx" style="font-size:15px; margin-bottom:12px; color:var(--red); text-shadow:none;">
        ① SQL Injection
      </div>
      <p class="subtle" style="font-size:14px; line-height:1.6; margin-bottom:16px;">
        Concaténer une entrée utilisateur dans une requête SQL = porte ouverte à
        l'extraction de la base entière. Un simple <code>' OR 1=1 --</code> bypass
        toute authentification naïve.
      </p>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        <div style="background:rgba(255,42,95,0.08); border:1px solid rgba(255,42,95,0.3);
                    padding:14px; border-radius:6px;">
          <div class="mono" style="font-size:10px; color:var(--red); letter-spacing:1px; margin-bottom:8px;">
            ✗ VULNERABLE
          </div>
          <pre style="font-family:var(--mono); font-size:12px; color:#ffb4b4;
                      white-space:pre-wrap; margin:0; line-height:1.5;">$sql = "SELECT * FROM users
WHERE email = '$email'
AND password = '$pass'";</pre>
        </div>
        <div style="background:rgba(0,255,136,0.08); border:1px solid rgba(0,255,136,0.3);
                    padding:14px; border-radius:6px;">
          <div class="mono" style="font-size:10px; color:var(--green); letter-spacing:1px; margin-bottom:8px;">
            ✓ SECURE
          </div>
          <pre style="font-family:var(--mono); font-size:12px; color:#a7f3d0;
                      white-space:pre-wrap; margin:0; line-height:1.5;">$st = $pdo-&gt;prepare(
  "SELECT * FROM users
   WHERE email = ?"
);
$st-&gt;execute([$email]);</pre>
        </div>
      </div>
      <p class="subtle" style="font-size:13px; margin-top:14px; line-height:1.6;">
        <strong style="color:var(--text);">Règle :</strong> jamais de concat. Toujours
        <code>prepare()</code> + <code>execute([...])</code>. PDO escape les paramètres pour toi.
      </p>
    </div>

    <!-- XSS -->
    <div style="background:rgba(255,184,0,0.03); border:1px solid rgba(255,184,0,0.15);
                border-left:3px solid var(--amber); border-radius:8px; padding:24px; margin-bottom:20px;">
      <div class="hx" style="font-size:15px; margin-bottom:12px; color:var(--amber); text-shadow:none;">
        ② Cross-Site Scripting (XSS)
      </div>
      <p class="subtle" style="font-size:14px; line-height:1.6; margin-bottom:16px;">
        Afficher une chaîne utilisateur sans l'échapper laisse l'attaquant injecter
        du JavaScript qui s'exécute dans le navigateur des autres visiteurs (vol de
        cookies, redirections, key-logging).
      </p>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        <div style="background:rgba(255,184,0,0.08); border:1px solid rgba(255,184,0,0.3);
                    padding:14px; border-radius:6px;">
          <div class="mono" style="font-size:10px; color:var(--amber); letter-spacing:1px; margin-bottom:8px;">
            ✗ VULNERABLE
          </div>
          <pre style="font-family:var(--mono); font-size:12px; color:#fde68a;
                      white-space:pre-wrap; margin:0; line-height:1.5;">echo "Hello, " . $_GET['name'];

// payload : ?name=&lt;script&gt;
//   fetch('/steal?c='+document.cookie)
// &lt;/script&gt;</pre>
        </div>
        <div style="background:rgba(0,255,136,0.08); border:1px solid rgba(0,255,136,0.3);
                    padding:14px; border-radius:6px;">
          <div class="mono" style="font-size:10px; color:var(--green); letter-spacing:1px; margin-bottom:8px;">
            ✓ SECURE
          </div>
          <pre style="font-family:var(--mono); font-size:12px; color:#a7f3d0;
                      white-space:pre-wrap; margin:0; line-height:1.5;">echo "Hello, " . htmlspecialchars(
  $_GET['name'] ?? '',
  ENT_QUOTES,
  'UTF-8'
);</pre>
        </div>
      </div>
      <p class="subtle" style="font-size:13px; margin-top:14px; line-height:1.6;">
        <strong style="color:var(--text);">Règle :</strong> tout output utilisateur passe par
        <code>htmlspecialchars()</code>. Pour les attributs HTML, ajoute <code>ENT_QUOTES</code>.
      </p>
    </div>

    <!-- CSRF -->
    <div style="background:rgba(0,240,255,0.03); border:1px solid rgba(0,240,255,0.15);
                border-left:3px solid var(--cyan); border-radius:8px; padding:24px; margin-bottom:28px;">
      <div class="hx" style="font-size:15px; margin-bottom:12px; color:var(--cyan); text-shadow:none;">
        ③ Cross-Site Request Forgery (CSRF)
      </div>
      <p class="subtle" style="font-size:14px; line-height:1.6; margin-bottom:14px;">
        L'attaquant force le navigateur d'un utilisateur connecté à exécuter une action
        non voulue (transfert d'argent, changement de mot de passe). La défense :
        un token aléatoire dans chaque formulaire, vérifié côté serveur.
      </p>
      <pre style="font-family:var(--mono); font-size:12px; color:#a5b4fc;
                  background:rgba(0,0,0,0.5); padding:14px; border-radius:6px;
                  white-space:pre-wrap; margin:0; line-height:1.6;">// Génération
$_SESSION['csrf'] = bin2hex(random_bytes(32));

// Vérification (timing-safe)
if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    die('CSRF token invalid');
}</pre>
      <p class="subtle" style="font-size:13px; margin-top:14px; line-height:1.6;">
        <strong style="color:var(--text);">Bonus :</strong> active <code>SameSite=Lax</code>
        sur tes cookies de session — c'est déjà le cas dans <code>includes/session.php</code>.
      </p>
    </div>

    <!-- Memo card -->
    <div style="background:rgba(0,0,0,0.3); border:1px dashed rgba(255,255,255,0.1);
                padding:18px 22px; border-radius:8px; margin-bottom:28px;">
      <div class="mono" style="font-size:11px; color:var(--cyan); letter-spacing:2px;
                                text-transform:uppercase; margin-bottom:10px;">
        Cheat Sheet — 3 réflexes
      </div>
      <ul style="margin:0; padding-left:20px; color:var(--text-muted); font-size:14px;
                 line-height:1.8;">
        <li><strong style="color:var(--text);">Input</strong> : préparer toutes les requêtes SQL</li>
        <li><strong style="color:var(--text);">Output</strong> : échapper tout HTML rendu</li>
        <li><strong style="color:var(--text);">State change</strong> : exiger un token CSRF</li>
      </ul>
    </div>

    <!-- CTA -->
    <div style="padding:24px; background:rgba(176,38,255,0.04);
                border:1px solid rgba(176,38,255,0.2); border-radius:8px; text-align:center;">
      <p class="mono" style="font-size:12px; color:var(--purple); text-transform:uppercase;
                             letter-spacing:1px; margin-bottom:12px;">
        Awaiting Validation Sequence
      </p>
      <p class="subtle" style="font-size:14px; margin-bottom:22px;">
        Identifie les patterns vulnérables et débloque le badge <strong style="color:var(--purple);">Code Defender</strong>.
      </p>
      <a class="btn btn-primary" href="/secure_dev_quiz.php"
         style="padding:12px 32px; font-size:14px; text-transform:uppercase; letter-spacing:1px;
                background:rgba(176,38,255,0.15); border-color:rgba(176,38,255,0.6);
                color:var(--purple); box-shadow:0 0 15px rgba(176,38,255,0.3);">
        Initialize Assessment →
      </a>
      <?php if ($completed): ?>
        <div style="margin-top:14px;">
          <span class="badge badge-ok" style="font-size:11px;">MODULE PREVIOUSLY SECURED</span>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>