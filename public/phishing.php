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

include __DIR__ . '/_partials/header.php';
?>
<div class="card">
    <div class="hx">Phishing Simulation — Inbox</div>
    <p class="subtle">Identify whether each email is legitimate or a phishing attempt.</p>

    <ul class="email-list">
        <?php foreach ($emails as $id => $mail): ?>
            <li class="email-item">
                <a href="/phishing_view.php?id=<?= $id ?>">
                    <strong><?= htmlspecialchars($mail['sender']) ?></strong><br>
                    <span><?= htmlspecialchars($mail['subject']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
