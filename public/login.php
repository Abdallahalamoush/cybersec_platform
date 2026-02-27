<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/rate_limit.php';

if (current_user()) {
    header("Location: /dashboard.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = "Token CSRF invalide.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass  = (string)($_POST['password'] ?? '');
        $ip    = client_ip();

        if ($email === '' || $pass === '') {
            $error = "Email et mot de passe sont obligatoires.";
        } else {
            // Rate limit check BEFORE verifying password
            if (is_rate_limited($pdo, $email, $ip)) {
                $error = "Trop de tentatives. Réessaye dans 10 minutes.";
            } else {
                $st = $pdo->prepare("SELECT id, name, email, role, password_hash FROM users WHERE email = ?");
                $st->execute([$email]);
                $user = $st->fetch();

                if ($user && password_verify($pass, $user['password_hash'])) {
                    // success log
                    log_login_attempt($pdo, $email, $ip, 1);

                    // login
                    $_SESSION['user'] = [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                    'role'  => $user['role'] ?? 'user'
                    ];
                    header("Location: /dashboard.php");
                    exit;
                } else {
                    // failure log
                    log_login_attempt($pdo, $email, $ip, 0);
                    $error = "Identifiants incorrects.";
                }
            }
        }
    }
}

include __DIR__ . '/_partials/header.php';
?>

<div class="card">
  <div class="hx">Se connecter</div>

  <?php if ($error): ?>
    <p class="badge badge-danger"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="post" class="mt-2">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

    <label class="subtle">Email</label>
    <input class="input" type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

    <label class="subtle mt-2">Mot de passe</label>
    <input class="input" type="password" name="password" required>

    <button class="btn btn-primary mt-2" type="submit">Connexion</button>
    <a class="btn mt-2" href="/register.php">Créer un compte</a>
  </form>

  <p class="subtle mt-2">
    Protection anti brute-force : max 5 échecs / 10 minutes (email ou IP).
  </p>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
