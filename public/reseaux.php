<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_auth();

$user   = current_user();
$userId = $user['id'];

$check = $pdo->prepare("
    SELECT completed FROM module_progress
    WHERE user_id = ? AND module_code = 'reseaux'
");
$check->execute([$userId]);
$row       = $check->fetch();
$completed = ($row && (int)$row['completed'] === 1);

include __DIR__ . '/_partials/header.php';
?>

<div class="card" style="max-width:800px; margin:0 auto; padding:0; overflow:hidden;">

  <!-- Header -->
  <div style="padding:32px 32px 24px; border-bottom:1px solid rgba(255,255,255,0.05);
              background:radial-gradient(ellipse at top left, rgba(0,184,255,0.1), transparent 60%);">
    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
      <div>
        <div class="hx" style="margin-bottom:8px; display:flex; align-items:center; gap:12px;">
          <div style="width:40px; height:40px; border-radius:8px; background:rgba(0,184,255,0.1);
                      border:1px solid rgba(0,184,255,0.3); display:grid; place-items:center;
                      color:var(--cyan);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle>
              <circle cx="18" cy="19" r="3"></circle>
              <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
              <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
            </svg>
          </div>
          Réseaux sociaux & Fake News
        </div>
        <div class="mono subtle" style="font-size:12px; letter-spacing:1px; text-transform:uppercase;">
          Training Module // OSINT-SOCIAL
        </div>
      </div>

      <?php if ($completed): ?>
        <div style="background:rgba(0,255,136,0.1); border:1px solid rgba(0,255,136,0.3);
                    padding:6px 14px; border-radius:6px; display:flex; align-items:center; gap:8px;">
          <span class="status-dot" style="background:var(--green); box-shadow:0 0 8px var(--green);
                width:8px; height:8px; border-radius:50%; flex-shrink:0;"></span>
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
      Les plateformes sociales sont devenues le principal terrain de chasse des cybercriminels.
      <strong style="color:var(--cyan);">Faux profils, arnaques marketplace et fake news</strong>
      exploitent notre confiance numérique pour soutirer argent, données personnelles ou répandre
      la désinformation à grande échelle.
    </p>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:28px;">

      <!-- Threat Vectors -->
      <div style="background:rgba(0,184,255,0.02); border:1px solid rgba(0,184,255,0.1);
                  border-top:2px solid var(--cyan); padding:24px; border-radius:8px;">
        <div class="hx" style="font-size:14px; margin-bottom:16px; color:var(--cyan);
                                text-shadow:none; display:flex; align-items:center; gap:8px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2
               2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
          Vecteurs d'attaque
        </div>
        <ul style="margin:0; padding-left:18px; color:var(--text-muted); font-size:14px;
                   line-height:1.7; display:flex; flex-direction:column; gap:10px;">
          <li><strong style="color:var(--text);">Catfishing :</strong> faux profil romantique
              ou professionnel pour établir la confiance avant d'escroquer.</li>
          <li><strong style="color:var(--text);">Arnaque marketplace :</strong> vendeur fantôme,
              faux acheteur (chèque en bois), demande de paiement hors plateforme.</li>
          <li><strong style="color:var(--text);">Fake news :</strong> articles piégés, titres
              trompeurs, manipulations d'image ou de vidéo (deepfake).</li>
          <li><strong style="color:var(--text);">DM piégé :</strong> lien malveillant reçu via
              message direct d'un compte compromis ou cloné.</li>
        </ul>
      </div>

      <!-- Defense -->
      <div style="background:rgba(0,255,136,0.02); border:1px solid rgba(0,255,136,0.1);
                  border-top:2px solid var(--green); padding:24px; border-radius:8px;">
        <div class="hx" style="font-size:14px; margin-bottom:16px; color:var(--green);
                                text-shadow:none; display:flex; align-items:center; gap:8px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          Contre-mesures
        </div>
        <ul style="margin:0; padding-left:18px; color:var(--text-muted); font-size:14px;
                   line-height:1.7; display:flex; flex-direction:column; gap:10px;">
          <li><strong style="color:var(--text);">Reverse image search :</strong> vérifier
              l'authenticité d'un profil via Google Images ou TinEye.</li>
          <li><strong style="color:var(--text);">Paiement sécurisé :</strong> ne jamais payer
              hors des plateformes officielles. Méfiance vis-à-vis des virements.</li>
          <li><strong style="color:var(--text);">Fact-checking :</strong> croiser les sources
              (AFP Factuel, Les Décodeurs) avant de partager une information.</li>
          <li><strong style="color:var(--text);">Vigilance DM :</strong> ne jamais cliquer sur
              un lien reçu en message privé sans vérification préalable.</li>
        </ul>
      </div>
    </div>

    <!-- Assessment CTA -->
    <div style="padding:24px; background:rgba(0,184,255,0.03);
                border:1px solid rgba(0,184,255,0.15); border-radius:8px; text-align:center;">
      <p class="mono" style="font-size:12px; color:var(--cyan); text-transform:uppercase;
                             letter-spacing:1px; margin-bottom:12px;">
        Validation requise pour débloquer le badge
      </p>
      <p class="subtle" style="font-size:14px; margin-bottom:22px;">
        Testez vos connaissances sur les arnaques sociales et la désinformation.
      </p>
      <a class="btn btn-primary" href="/reseaux_quiz.php"
         style="padding:12px 32px; font-size:14px; text-transform:uppercase; letter-spacing:1px;">
        Initiate Assessment →
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