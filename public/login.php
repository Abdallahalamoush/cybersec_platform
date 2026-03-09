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
        $error = "Invalid CSRF Token.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass  = (string)($_POST['password'] ?? '');
        $ip    = client_ip();

        if ($email === '' || $pass === '') {
            $error = "Email and Password are required.";
        } else {
            // Rate limit check BEFORE verifying password
            if (is_rate_limited($pdo, $email, $ip)) {
                $error = "Too many attempts. Retry in 10 minutes.";
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
                    $error = "Invalid Credentials.";
                }
            }
        }
    }
}

include __DIR__ . '/_partials/header.php';
?>

<div style="display:flex; justify-content:center; align-items:center; min-height:60vh;">
  <div class="card" style="width:100%; max-width:440px; border-top:2px solid var(--cyan); box-shadow:0 10px 40px rgba(0, 240, 255, 0.1);">
    
    <div style="text-align:center; margin-bottom:24px;">
      <div style="display:inline-grid; place-items:center; width:48px; height:48px; border-radius:12px; background:rgba(0, 240, 255, 0.1); border:1px solid rgba(0, 240, 255, 0.3); color:var(--cyan); font-size:24px; margin-bottom:12px; box-shadow:0 0 15px rgba(0, 240, 255, 0.2);">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
      </div>
      <div class="hx" style="justify-content:center; margin-bottom:4px;">Secure Authorization</div>
      <p class="subtle mono" style="font-size:12px; letter-spacing:1px; text-transform:uppercase;">Enter credentials to access systems</p>
    </div>

    <?php if ($error): ?>
      <p class="badge badge-danger" style="display:block; text-align:center; margin-bottom:16px; padding:10px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" class="mt-2">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

      <div class="mb-2">
        <label>Operator ID (Email)</label>
        <input class="input" type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="operator@redline.dev">
      </div>

      <div class="mb-2">
        <label>Passphrase</label>
        <input class="input" type="password" name="password" required placeholder="••••••••••••">
      </div>

      <button class="btn btn-primary" style="width:100%; justify-content:center; padding:12px; margin-top:16px;" type="submit">Initialize Sequence</button>
      
      <div style="text-align:center; margin-top:24px;">
        <a class="btn btn-ghost" href="/register.php" style="font-size:12px;">Request Access Clearance →</a>
      </div>
    </form>

    <div style="margin-top:24px; padding-top:16px; border-top:1px dashed rgba(255,255,255,0.1); text-align:center;">
      <p style="font-family:var(--mono); font-size:10px; color:var(--text-dim); text-transform:uppercase; letter-spacing:1px;">
        <span style="color:var(--amber);">WARNING:</span> Anti-bruteforce active. Max 5 failures / 10m.
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
