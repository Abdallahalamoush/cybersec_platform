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
        $error = "Invalid CSRF Token.";
    } else {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = (string)($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $pass === '') {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid Email format.";
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
                $error = "An account with this email already exists.";
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);

                $ins = $pdo->prepare("
                    INSERT INTO users (name, email, password_hash, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $ins->execute([$name, $email, $hash]);

                $success = "Account successfully created. Ready for authentication.";
            }
        }
    }
}

include __DIR__ . '/_partials/header.php';
?>

<div style="display:flex; justify-content:center; align-items:center; min-height:65vh;">
  <div class="card" style="width:100%; max-width:480px; border-top:2px solid var(--purple); box-shadow:0 10px 40px rgba(176, 38, 255, 0.1);">
    
    <div style="text-align:center; margin-bottom:24px;">
      <div style="display:inline-grid; place-items:center; width:48px; height:48px; border-radius:12px; background:rgba(176, 38, 255, 0.1); border:1px solid rgba(176, 38, 255, 0.3); color:var(--purple); font-size:24px; margin-bottom:12px; box-shadow:0 0 15px rgba(176, 38, 255, 0.2);">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5c-2.2 0-4 1.8-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
      </div>
      <div class="hx" style="justify-content:center; margin-bottom:4px; text-shadow:0 0 15px rgba(176, 38, 255, 0.4);">Network Registration</div>
      <p class="subtle mono" style="font-size:12px; letter-spacing:1px; text-transform:uppercase;">Initialize your operator profile</p>
    </div>

    <?php if ($error): ?>
      <p class="badge badge-danger" style="display:block; text-align:center; margin-bottom:16px; padding:10px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <div style="text-align:center;">
        <p class="badge badge-ok" style="display:inline-block; margin-bottom:24px; padding:10px 16px; font-size:13px;"><?= htmlspecialchars($success) ?></p>
        <a class="btn btn-primary" href="/login.php" style="width:100%; justify-content:center; padding:12px;">Proceed to Authentication</a>
      </div>
    <?php else: ?>
      <form method="post" class="mt-2">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

        <div class="mb-2">
          <label>Codename (Name)</label>
          <input class="input" type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="Neo">
        </div>

        <div class="mb-2">
          <label>Operator Communication Route (Email)</label>
          <input class="input" type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="neo@matrix.net">
        </div>

        <div class="mb-2">
          <label>Secure Passphrase</label>
          <input class="input" type="password" name="password" required placeholder="Choose a strong password">
        </div>

        <div style="background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.05); padding:12px; border-radius:6px; margin-top:10px; margin-bottom:20px;">
          <p class="mono" style="font-size:10px; color:var(--text-dim); text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">Security Requirements:</p>
          <p class="subtle" style="font-size:12px; line-height:1.5;">
            Must contain 10+ characters, including at least 1 uppercase letter, 1 lowercase letter, 1 number, and 1 special symbol.
          </p>
        </div>

        <button class="btn btn-primary" style="width:100%; justify-content:center; padding:12px;" type="submit">Establish Connection</button>
        
        <div style="text-align:center; margin-top:24px;">
          <a class="btn btn-ghost" href="/login.php" style="font-size:12px;">← Return to Authorized Access</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
