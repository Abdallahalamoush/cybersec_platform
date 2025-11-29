<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_auth();

$emails = [
    1 => [
        'sender' => 'PayPal Support <no-reply@paypa1-security.com>',
        'subject' => 'Your account has been limited',
        'body' => "Dear customer,\n\nWe've detected unusual activity...\nPlease confirm your identity here:\nhttp://paypal-verification-security.com/login",
        'type' => 'phishing'
    ],
    2 => [
        'sender' => 'Freebox <support@free.fr>',
        'subject' => 'Your latest bill is ready',
        'body' => "Bonjour,\nVotre facture Freebox du mois est disponible dans votre espace client.",
        'type' => 'legitimate'
    ],
    3 => [
        'sender' => 'Microsoft Security <secure@microsoft.com>',
        'subject' => 'Unusual sign-in detected',
        'body' => "A sign-in attempt was detected from a new location.\nIf this wasn't you, secure your account here:\nhttps://aka.ms/security-check",
        'type' => 'phishing'
    ]
];

$id = (int)($_GET['id'] ?? 0);
if (!isset($emails[$id])) {
    header("Location: /phishing.php");
    exit;
}

$email = $emails[$id];
$feedback = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $feedback = ['type' => 'error', 'msg' => 'Invalid CSRF token'];
    } else {
        $choice = $_POST['choice'] ?? '';
        if ($choice === 'phishing' || $choice === 'legitimate') {
            $correct = ($choice === $email['type']) ? 1 : 0;

            // enrigstrement en bdd
            $stmt = $pdo->prepare("
                INSERT INTO phishing_attempts (user_id, email_id, user_choice, correct)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                current_user()['id'],
                $id,
                $choice,
                $correct
            ]);

            $feedback = [
                'type' => $correct ? 'ok' : 'warn',
                'msg'  => $correct ? 
                    "Correct! This email is indeed {$email['type']}." :
                    "Incorrect. This email was actually {$email['type']}."
            ];
        }
    }
}

include __DIR__ . '/_partials/header.php';
?>
<div class="card">
    <div class="hx"><?= htmlspecialchars($email['subject']) ?></div>
    <p class="subtle">From: <strong><?= htmlspecialchars($email['sender']) ?></strong></p>

    <pre class="email-body"><?= htmlspecialchars($email['body']) ?></pre>

    <?php if ($feedback): ?>
        <?php 
            $cls = $feedback['type'] === 'ok' ? 'badge badge-ok' : 'badge badge-warn';
        ?>
        <p class="<?= $cls ?>"><?= htmlspecialchars($feedback['msg']) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <button class="btn btn-primary" name="choice" value="legitimate" type="submit">Legitimate</button>
        <button class="btn btn-danger" name="choice" value="phishing" type="submit">Phishing</button>
    </form>

    <div class="mt-3">
        <a class="btn" href="/phishing.php">Back to inbox</a>
    </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
