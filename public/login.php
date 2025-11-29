<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_guest();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) {
    $errors[] = "Token CSRF invalide.";
  }
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') $errors[] = "Tous les champs sont requis.";
  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";

  if (!$errors) {
    $stmt = $pdo->prepare("SELECT id,name,email,password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
      login_user($user);
      header('Location: /index.php');
      exit;
    } else {
      $errors[] = "Identifiants incorrects.";
    }
  }
}

include __DIR__ . '/_partials/header.php';
?>
<div class="card">
  <div class="hx">Connexion</div>
  <?php foreach ($errors as $err): ?>
    <p class="badge badge-danger"><?= htmlspecialchars($err) ?></p>
  <?php endforeach; ?>

  <form method="post" class="mt-2" novalidate>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <label>Email</label>
    <input class="input" name="email" type="email" required>
    <label>Mot de passe</label>
    <input class="input" name="password" type="password" required>
    <div class="mt-3">
      <button class="btn btn-primary" type="submit">Se connecter</button>
      <a class="btn" href="/register.php">Créer un compte</a>
    </div>
  </form>
</div>
<?php include __DIR__ . '/_partials/footer.php'; ?>
