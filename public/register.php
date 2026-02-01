<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validators.php';

if (current_user()) {
    header("Location: /dashboard.php");
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = "Token CSRF invalide.";
    } else {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = (string)($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $pass === '') {
            $error = "Tous les champs sont obligatoires.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Email invalide.";
        } else {
            // Password policy
            $pwErrors = validate_password_policy($pass);
            if ($pwErrors) {
                $error = implode(" ", $pwErrors);
            }
        }

        if (!$error) {
            // Check if email already exists
            $st = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $st->execute([$email]);
            if ($st->fetch()) {
                $error = "Un compte existe déjà avec cet email.";
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);

                $ins = $pdo->prepare("
                    INSERT INTO users (name, email, password_hash, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $ins->execute([$name, $email, $hash]);

                $success = "Compte créé avec succès. Tu peux te connecter.";
            }
        }
    }
}

include __DIR__ . '/_partials/header.php';
?>

<div class="card">
  <div class="hx">Créer un compte</div>

  <?php if ($error): ?>
    <p class="badge badge-danger"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <?php if ($success): ?>
    <p class="badge badge-ok"><?= htmlspecialchars($success) ?></p>
    <a class="btn btn-primary mt-2" href="/login.php">Se connecter</a>
  <?php else: ?>
    <form method="post" class="mt-2">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

      <label class="subtle">Nom</label>
      <input class="input" type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

      <label class="subtle mt-2">Email</label>
      <input class="input" type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

      <label class="subtle mt-2">Mot de passe</label>
      <input class="input" type="password" name="password" required>

      <p class="subtle mt-1">
        Règles: 10+ caractères, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial.
      </p>

      <button class="btn btn-primary mt-2" type="submit">Créer le compte</button>
      <a class="btn mt-2" href="/login.php">J’ai déjà un compte</a>
    </form>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
