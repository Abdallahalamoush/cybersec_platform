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
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($name === '' || $email === '' || $password === '') $errors[] = "Tous les champs sont requis.";
  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";

  if (!$errors) {
    try {
      $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $errors[] = "Un compte existe déjà avec cet email.";
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users(name,email,password_hash) VALUES (?,?,?)");
        $ins->execute([$name, $email, $hash]);

        $newId = $pdo->lastInsertId();
        $sel = $pdo->prepare("SELECT id,name,email FROM users WHERE id = ?");
        $sel->execute([$newId]);
        $user = $sel->fetch();
        login_user($user);
        header('Location: /index.php');
        exit;
      }
    } catch (Exception $e) {
      $errors[] = "Erreur serveur.";
    }
  }
}

include __DIR__ . '/_partials/header.php';
?>
<div class="card">
  <div class="hx">Créer un compte</div>
  <?php foreach ($errors as $err): ?>
    <p class="badge badge-danger"><?= htmlspecialchars($err) ?></p>
  <?php endforeach; ?>

  <form method="post" class="mt-2" novalidate>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <label>Nom</label>
    <input class="input" name="name" required>
    <label>Email</label>
    <input class="input" name="email" type="email" required>
    <label>Mot de passe</label>
    <input class="input" name="password" type="password" required>
    <div class="mt-3">
      <button class="btn btn-primary" type="submit">S’inscrire</button>
      <a class="btn" href="/login.php">J’ai déjà un compte</a>
    </div>
  </form>
</div>
<?php include __DIR__ . '/_partials/footer.php'; ?>
