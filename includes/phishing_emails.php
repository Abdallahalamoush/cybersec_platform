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

    5 => [
        'sender'  => 'Crédit Agricole <securite@credit-agricole-verification.net>',
        'subject' => 'Action requise — Vérification de votre compte',
        'body'    =>
"Madame, Monsieur,

Suite à une activité inhabituelle, nous avons temporairement limité l'accès
à votre espace en ligne Crédit Agricole.

Pour lever cette restriction, veuillez remplir le formulaire de vérification
sécurisé en cliquant sur le lien ci-dessous :

https://credit-agricole-verification.net/confirmer-identite

Vous devrez renseigner votre numéro de carte, date d'expiration et CVV.

Passé 48h, votre compte sera suspendu définitivement.

Cordialement,
Le Service Sécurité Crédit Agricole",
        'type'        => 'phishing',
        'explanation' => [
            'Le domaine "credit-agricole-verification.net" est frauduleux — le vrai domaine est credit-agricole.fr.',
            'Aucune banque ne demande votre CVV par email ou via un lien. C\'est un signal d\'alarme absolu.',
            'L\'urgence artificielle ("48h", "suspendu définitivement") est une technique classique de pression psychologique.',
        ]
    ],

    6 => [
        'sender'  => 'DHL Express <noreply@dhl-delivery-confirm.com>',
        'subject' => 'Votre colis est en attente — Document requis',
        'body'    =>
"Bonjour,

Votre colis n°JD014600006281351046 est retenu en entrepôt.

Pour finaliser la livraison, veuillez ouvrir le bon de livraison en pièce jointe
(BON_LIVRAISON_5842.pdf) et confirmer votre adresse complète.

Sans retour de votre part sous 24h, le colis sera retourné à l'expéditeur.

Équipe DHL Express",
        'type'        => 'phishing',
        'explanation' => [
            'Le domaine expéditeur "dhl-delivery-confirm.com" n\'est pas officiel — DHL utilise dhl.com.',
            'Les transporteurs n\'envoient jamais de pièce jointe PDF à ouvrir pour confirmer une adresse. Ce fichier pourrait être un malware.',
            'Le numéro de suivi au format inhabituel et l\'urgence ("24h, retourné") sont des indicateurs de phishing.',
        ]
    ],

    // ── NOUVEAU MOIS 7 — Faux PDF interactif (pédagogique) ──
    7 => [
        'sender'  => 'Service RH <rh@entreprise-corp-secure.com>',
        'subject' => 'Bulletin de salaire — Action requise avant le 30/04',
        'body'    =>
"Bonjour,

Vous trouverez en pièce jointe votre bulletin de salaire du mois en cours.

⚠ Une mise à jour de vos coordonnées bancaires est nécessaire pour le prochain virement.
Merci d'ouvrir le PDF ci-joint et de remplir le formulaire intégré.

Sans réponse sous 48h, votre prochain salaire ne pourra pas être versé.

Cordialement,
Service Ressources Humaines",
        'type'        => 'phishing',
        'attachment'  => [
            'name' => 'BULLETIN_SALAIRE_AVRIL_2026.pdf',
            'size' => '1.2 MB',
            'fake' => true,
        ],
        'explanation' => [
            'Le domaine "entreprise-corp-secure.com" est générique et suspect — un vrai service RH utilise le domaine officiel de l\'entreprise.',
            'Aucun service RH légitime ne demande de coordonnées bancaires via un PDF interactif. Les changements RIB passent par un portail interne sécurisé.',
            'L\'urgence ("48h", "salaire pas versé") est une technique de manipulation classique : ralentit ta réflexion, accélère ton clic.',
        ]
    ],
];
