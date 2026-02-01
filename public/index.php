<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

include __DIR__ . '/_partials/header.php';

// Fetch active modules from DB
try {
    $stmt = $pdo->query("
        SELECT id, title, description, link, level
        FROM modules
        WHERE is_active = 1
        ORDER BY id ASC
    ");
    $modules = $stmt->fetchAll();
} catch (Exception $e) {
    $modules = [];
}
?>

<div class="card">
  <div class="hx">Bienvenue</div>

  <?php if (current_user()): ?>
    <p class="subtle">
      Connecté en tant que <strong><?= htmlspecialchars(current_user()['name']) ?></strong>
      (<?= htmlspecialchars(current_user()['email']) ?>)
    </p>

    <div class="mt-2">
      <a class="btn btn-primary" href="/quiz.php">Commencer le Quiz</a>
      <a class="btn" href="/dashboard.php">Dashboard</a>
      <a class="btn" href="/logout.php">Se déconnecter</a>
    </div>

  <?php else: ?>
    <p class="subtle">
      Crée un compte pour suivre tes progrès et débloquer des badges.
    </p>

    <div class="mt-2">
      <a class="btn btn-primary" href="/register.php">Créer un compte</a>
      <a class="btn" href="/login.php">Se connecter</a>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="hx">Parcours</div>

  <?php if (!$modules): ?>
    <p class="subtle">
      Aucun module disponible pour le moment.
    </p>
  <?php else: ?>
    <div class="row">
      <?php foreach ($modules as $m): ?>
        <div class="card" style="flex:1; min-width:260px">
          <div class="card-title"><?= htmlspecialchars($m['title']) ?></div>

          <p class="subtle">
            <?= htmlspecialchars($m['description']) ?>
          </p>

          <?php if (!empty($m['level'])): ?>
            <span class="badge">Niveau : <?= htmlspecialchars($m['level']) ?></span>
          <?php endif; ?>

          <div class="mt-2">
            <?php if (current_user()): ?>
              <?php
                // If link missing, fallback to "#"
                $link = !empty($m['link']) ? $m['link'] : '#';
              ?>
              <a class="btn btn-primary" href="<?= htmlspecialchars($link) ?>">Ouvrir</a>
            <?php else: ?>
              <a class="btn" href="/login.php">Se connecter</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="hx">Objectif</div>
  <p class="subtle">
    Cette plateforme vise à sensibiliser aux menaces courantes (phishing, mots de passe faibles, ingénierie sociale)
    via des modules pédagogiques, des quiz interactifs et des badges.
  </p>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
