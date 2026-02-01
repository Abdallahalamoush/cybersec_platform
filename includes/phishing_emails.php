<?php


$PHISHING_EMAILS = [
    1 => [
        'sender' => 'PayPal Support <no-reply@paypa1-security.com>',
        'subject' => 'Your account has been limited',
        'body' =>
"Dear customer,

We have detected unusual activity on your PayPal account.

To restore full access, please confirm your identity using the following secure link:
http://paypal-verification-security.com/login

Failure to act within 24 hours will result in permanent account limitation.
",
        'type' => 'phishing',
        'explanation' => [
            'The domain uses "paypa1" instead of "paypal" (number 1 instead of letter L).',
            'The link is not an official PayPal domain (should be something like paypal.com).',
            'The email creates fake urgency (“within 24 hours”) to push you to click.'
        ]
    ],
    2 => [
        'sender' => 'Freebox <support@free.fr>',
        'subject' => 'Votre facture Freebox est disponible',
        'body' =>
"Bonjour,

Votre facture Freebox du mois est disponible dans votre espace abonné.
Vous pouvez la consulter en vous connectant depuis le site officiel Free.

Cordialement,
Le service client Free",
        'type' => 'legitimate',
        'explanation' => [
            'The sender domain looks correct: support@free.fr.',
            'The email asks you to log in from the official website, not via a strange link.',
            'The tone is neutral and not excessively urgent or threatening.'
        ]
    ],
    3 => [
        'sender' => 'Microsoft Security <no-reply@account.microsoft.com>',
        'subject' => 'Unusual sign-in activity',
        'body' =>
"We detected an unusual sign-in attempt to your Microsoft account.

If this was you, you can ignore this message.
If not, we recommend that you review your recent activity and secure your account:

https://account.microsoft.com/security

Thank you,
Microsoft Security",
        'type' => 'legitimate',
        'explanation' => [
            'The link points to an official Microsoft domain.',
            'The email gives options and does not force you to click immediately.',
            'The sender domain matches a real Microsoft domain pattern.'
        ]
    ],
    4 => [
        'sender' => 'Netflix Billing <support@netfliix-billing.com>',
        'subject' => 'Payment failed — update required',
        'body' =>
"Hello,

Your last payment could not be processed. Your Netflix account will be suspended.

Update your payment details here:
http://netflix-update-payments.com

Thank you,
Netflix Billing Team",
        'type' => 'phishing',
        'explanation' => [
            'The domain "netfliix-billing.com" is suspicious (typo in “Netflix”).',
            'The link is not the official Netflix domain (netflix.com).',
            'Strong threat / urgency is used to push a quick reaction.'
        ]
    ],
];
