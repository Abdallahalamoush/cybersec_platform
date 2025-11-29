<?php
require_once __DIR__ . '/../includes/session.php';
include __DIR__ . '/_partials/header.php';
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
      <a class="btn" href="/logout.php">Se déconnecter</a>
    </div>
  <?php else: ?>
    <p class="subtle">Crée un compte pour suivre tes progrès et débloquer des badges.</p>
    <div class="mt-2">
      <a class="btn btn-primary" href="/register.php">Créer un compte</a>
      <a class="btn" href="/login.php">Se connecter</a>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="hx">Parcours</div>
  <div class="row">

    <!-- Module 1 : Quiz (Phishing base) -->
    <div class="card" style="flex:1; min-width:260px">
      <div class="card-title">Quiz • Phishing (Base)</div>
      <p class="subtle">Test tes connaissances sur les liens suspects, domaines trompeurs et urgences artificielles.</p>
      <?php if (current_user()): ?>
        <a class="btn mt-2" href="/quiz.php">Lancer le quiz</a>
      <?php else: ?>
        <a class="btn mt-2" href="/login.php">Se connecter</a>
      <?php endif; ?>
    </div>

    <!-- Module 2 : Simulation de phishing -->
    <div class="card" style="flex:1; min-width:260px">
      <div class="card-title">Simulation de phishing</div>
      <p class="subtle">Analyse une boîte mail simulée avec de vrais exemples d’emails légitimes et frauduleux.</p>
      <?php if (current_user()): ?>
        <a class="btn mt-2" href="/phishing.php">Démarrer la simulation</a>
      <?php else: ?>
        <a class="btn mt-2" href="/login.php">Se connecter</a>
      <?php endif; ?>
    </div>

    <!-- Module 3 : Mots de passe (à venir) -->
    <div class="card" style="flex:1; min-width:260px">
      <div class="card-title">Mots de passe • Bientôt</div>
      <p class="subtle">Comprendre la force d’un mot de passe, les bonnes pratiques et l’authentification multifactorielle.</p>
      <span class="badge badge-warn">À venir</span>
    </div>

  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
