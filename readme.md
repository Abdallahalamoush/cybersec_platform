# 🛡️ Cyber Awareness Platform

> Plateforme e-learning de sensibilisation à la cybersécurité. Modules interactifs, simulations de phishing, quizzes adaptatifs et système de badges pour monter en compétences face aux menaces numériques.

---

## 📋 Sommaire

- [Présentation](#-présentation)
- [Fonctionnalités](#-fonctionnalités)
- [Stack technique](#-stack-technique)
- [Structure du projet](#-structure-du-projet)
- [Installation](#-installation)
- [Base de données](#-base-de-données)
- [Modules pédagogiques](#-modules-pédagogiques)
- [Système de badges](#-système-de-badges)
- [Sécurité](#-sécurité)
- [Panneau d'administration](#-panneau-dadministration)
- [Captures d'écran](#-aperçu-ui)

---

## 🎯 Présentation

**Cyber Awareness Platform** est une application web PHP/MySQL conçue pour former les utilisateurs aux bonnes pratiques de cybersécurité à travers une interface immersive de type *cyberpunk*. Le projet couvre quatre grands axes de sensibilisation :

- Détection d'emails de phishing
- Sécurité des mots de passe et cryptographie
- Ingénierie sociale et manipulation psychologique
- Quizzes de connaissances générales

Chaque utilisateur progresse à son rythme, accumule des badges de certification et visualise ses statistiques depuis un tableau de bord personnel ("Command Center").

---

## ✨ Fonctionnalités

### 👤 Gestion des utilisateurs
- Inscription avec politique de mot de passe renforcée (10+ caractères, majuscule, chiffre, symbole)
- Authentification sécurisée avec hachage bcrypt (`password_hash`)
- Sessions PHP avec régénération d'identifiant à la connexion
- Protection anti-bruteforce : max 5 tentatives échouées / 10 minutes par email ou IP

### 🎓 Modules d'apprentissage
| Module | Fichier | Description |
|---|---|---|
| Phishing Intel | `phishing.php` / `phishing_view.php` | Analyse d'emails interceptés, classification légitime / malveillant |
| Cryptographie & Accès | `passwords.php` / `passwords_quiz.php` | Bonnes pratiques mots de passe, MFA, gestionnaires de clés |
| Ingénierie Sociale | `social.php` / `social_quiz.php` | Vecteurs de manipulation psychologique et contre-mesures |
| Quiz Général | `quiz.php` | Sessions de questions aléatoires avec score et feedback |

### 📊 Tableau de bord
- Score global de progression (modules complétés / total)
- Statistiques : taux de précision quiz, détection phishing, sessions jouées
- Historique des 5 dernières sessions quiz et analyses phishing
- Galerie de badges acquis avec date d'obtention

### 🏅 Badges & gamification
- Attribution automatique à l'issue de chaque module ou simulation
- 3 badges disponibles (extensibles via la table `badges`)
- Calcul déclenché après chaque action utilisateur via `recalculate_user_badges()`

### 🔐 Sécurité applicative
- Tokens CSRF sur tous les formulaires POST
- Requêtes préparées PDO (protection injection SQL)
- Contrôle d'accès par rôle (`user` / `admin`) via middleware `require_admin()`
- Cookies de session : `httponly`, `samesite=Lax`

---

## 🛠️ Stack technique

| Couche | Technologie |
|---|---|
| **Backend** | PHP 8.1 |
| **Base de données** | MySQL 8.0 (PDO) |
| **Frontend** | HTML5, CSS3 vanilla, JavaScript ES6 |
| **Fonts** | JetBrains Mono, Rajdhani, Inter, Share Tech Mono |
| **Serveur local** | MAMP / Laragon (port 8889 par défaut dans `db.php`) |
| **Gestion BDD** | phpMyAdmin |

---

## 📁 Structure du projet

```
cybersec-platform/
│
├── includes/                   # Logique métier partagée
│   ├── db.php                  # Connexion PDO
│   ├── session.php             # Gestion session (login/logout/guards)
│   ├── csrf.php                # Génération et vérification token CSRF
│   ├── badges.php              # Attribution et recalcul des badges
│   ├── validators.php          # Politique de mot de passe
│   ├── rate_limit.php          # Anti-bruteforce (login_attempts)
│   ├── admin.php               # Middleware require_admin()
│   ├── phishing_emails.php     # Dataset emails de simulation
│   └── config.php              # Paramètres de l'application
│
├── public/                     # Pages accessibles
│   ├── index.php               # Page d'accueil
│   ├── login.php               # Authentification
│   ├── register.php            # Inscription
│   ├── logout.php              # Déconnexion
│   ├── dashboard.php           # Tableau de bord utilisateur
│   ├── quiz.php                # Module quiz (sessions aléatoires)
│   ├── phishing.php            # Inbox phishing (liste emails)
│   ├── phishing_view.php       # Analyse d'un email individuel
│   ├── passwords.php           # Module mots de passe (théorie)
│   ├── passwords_quiz.php      # Évaluation module mots de passe
│   ├── social.php              # Module ingénierie sociale (théorie)
│   ├── social_quiz.php         # Évaluation module ingénierie sociale
│   │
│   ├── admin/
│   │   └── modules.php         # Panneau admin — gestion des modules
│   │
│   ├── _partials/
│   │   ├── header.php          # En-tête globale (nav, session)
│   │   └── footer.php          # Pied de page + Matrix Rain canvas
│   │
│   └── assets/
│       ├── styles.css          # Thème REDLINE UI v4.0 (cyberpunk)
│       └── script.js           # Effets JS : cursor glow, matrix rain, typing
│
└── cybersec_platform.sql       # Dump complet de la base de données
```

---

## 🚀 Installation

### Prérequis

- PHP >= 8.1
- MySQL >= 8.0
- Serveur local : [MAMP](https://www.mamp.info/) ou [Laragon](https://laragon.org/)

### Étapes

**1. Cloner le dépôt**
```bash
git clone https://github.com/ton-username/cybersec-platform.git
cd cybersec-platform
```

**2. Importer la base de données**

Dans phpMyAdmin (ou via CLI) :
```sql
CREATE DATABASE cybersec_platform;
USE cybersec_platform;
SOURCE cybersec_platform.sql;
```

Ou via le terminal :
```bash
mysql -u root -p cybersec_platform < cybersec_platform.sql
```

**3. Configurer la connexion**

Éditer `includes/db.php` selon ton environnement :
```php
$host   = '127.0.0.1';
$port   = 8889;          // 3306 pour XAMPP / Laragon
$dbname = 'cybersec_platform';
$user   = 'root';
$pass   = 'root';        // Vide sur certaines configs XAMPP
```

**4. Lancer le serveur**

Avec MAMP/Laragon : placer le projet dans le répertoire `htdocs` ou `www` et démarrer les services Apache + MySQL.

Accès : [http://localhost/cybersec-platform/public/](http://localhost/cybersec-platform/public/)

---

## 🗄️ Base de données

### Tables principales

| Table | Rôle |
|---|---|
| `users` | Comptes utilisateurs (bcrypt hash) |
| `modules` | Catalogue des modules pédagogiques |
| `questions` | Questions de quiz (choix multiples A/B/C/D) |
| `quiz_sessions` | Sessions de quiz (score, timestamps) |
| `attempts` | Réponses individuelles par session |
| `phishing_attempts` | Historique des analyses d'emails |
| `badges` | Catalogue des badges disponibles |
| `user_badges` | Badges acquis par utilisateur |
| `module_progress` | Avancement par module (`completed` flag) |

> **Note :** La table `login_attempts` (utilisée par `rate_limit.php`) doit être créée manuellement si elle n'est pas présente dans le dump :
> ```sql
> CREATE TABLE login_attempts (
>   id         INT AUTO_INCREMENT PRIMARY KEY,
>   email      VARCHAR(150) NOT NULL,
>   ip         VARCHAR(45)  NOT NULL,
>   success    TINYINT(1)   NOT NULL DEFAULT 0,
>   created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
> );
> ```

---

## 📚 Modules pédagogiques

### 🎣 Phishing Intel
Simulation d'une boîte de réception avec 4 emails interceptés (2 légitimes, 2 malveillants). L'utilisateur classe chaque email et reçoit un rapport post-analyse détaillant les indicateurs de compromission.

### 🔐 Cryptographie & Accès
Théorie sur la sécurité des mots de passe (entropie, isolation des credentials, vaulting, MFA) suivie d'un quiz de 3 questions à choix multiples. Le module est marqué "complété" au passage du seuil de 60%.

### 🧠 Ingénierie Sociale
Cours sur les vecteurs de manipulation (urgence fabriquée, usurpation d'autorité, conditionnement par la peur) et les contre-mesures défensives. Évaluation par quiz de 3 questions.

### ❓ Quiz Général
Séquences de questions tirées aléatoirement depuis la table `questions`. Chaque session est tracée avec score final, timestamp et nombre de bonnes réponses. Extensible : ajouter des questions directement en base.

---

## 🏅 Système de badges

| Badge | Code | Condition |
|---|---|---|
| 🟢 **Quiz Beginner** | `quiz_beginner` | Terminer au moins 1 session quiz avec score ≥ 60% |
| 🎣 **Phishing Aware** | `phishing_aware` | Obtenir au moins 3 bonnes réponses en simulation phishing |
| 🔐 **Password Guardian** | `password_guardian` | Compléter le module "Passwords" |
| 🕵️ **Social Detective** | `social_detective` | Compléter le module "Social Engineering" |

Les badges sont attribués automatiquement via `recalculate_user_badges()` appelé après chaque soumission de formulaire pertinent.

---

## 🔒 Sécurité

### Mesures implémentées

- **CSRF** : Token aléatoire 64 caractères (hex) injecté dans chaque formulaire POST, vérifié via `hash_equals()` pour éviter les attaques timing.
- **Injection SQL** : Toutes les requêtes utilisent des `prepare()` / `execute()` PDO. Aucune concaténation directe.
- **Bruteforce** : Blocage après 5 échecs en 10 minutes (par email OU par IP), logué dans `login_attempts`.
- **Mots de passe** : Hachage `bcrypt` via `password_hash()` avec `PASSWORD_DEFAULT`. Politique : 10+ chars, majuscule, minuscule, chiffre, symbole.
- **Sessions** : `session_regenerate_id(true)` à la connexion, cookies `httponly` + `samesite=Lax`, mode strict activé.
- **Contrôle d'accès** : Guards `require_auth()` et `require_admin()` placés en tête de chaque page protégée.
- **XSS** : Toutes les sorties utilisateur sont filtrées via `htmlspecialchars()`.

---

## ⚙️ Panneau d'administration

Accessible via `/admin/modules.php` (rôle `admin` requis).

Fonctionnalités :
- Modifier le titre, la description et le niveau d'un module (`beginner` / `intermediate` / `advanced`)
- Mettre à jour le lien de destination du module
- Activer ou désactiver un module (affichage conditionnel sur la page d'accueil et le dashboard)

Pour promouvoir un utilisateur en admin, exécuter directement en base :
```sql
ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user';
UPDATE users SET role = 'admin' WHERE email = 'votre@email.com';
```

---

## 🖥️ Aperçu UI

L'interface utilise le thème **REDLINE UI v4.0** — esthétique cyberpunk haute fidélité :

- Palette : fond `#030508`, accents néon cyan `#00f0ff`, vert `#00ff88`, rouge `#ff2a5f`
- Typo : `JetBrains Mono` (code/labels), `Rajdhani` (titres display), `Inter` (corps)
- Effets : glassmorphism, cursor glow, matrix rain canvas, grid animé en arrière-plan
- Composants : cartes avec bordures néon, boutons avec effet shimmer, badges lumineux, barres de progression animées

---

## 👤 Auteur

**Abdallah** — Étudiant en Sciences du Numérique (L3), FGES / Université Catholique de Lille  
Projet réalisé dans le cadre d'un module de développement web PHP/MySQL.

---

## 📄 Licence

Ce projet est à usage académique. Toute réutilisation doit mentionner l'auteur original.
