<?php

function award_badge(PDO $pdo, int $userId, string $badgeCode): void {
    // Find badge by code
    $stmt = $pdo->prepare("SELECT id FROM badges WHERE code = ?");
    $stmt->execute([$badgeCode]);
    $badge = $stmt->fetch();

    if (!$badge) {
        return;
    }

    $badgeId = (int)$badge['id'];

   // À insérer uniquement si l'utilisateur ne le possède pas encore
    $ins = $pdo->prepare("
        INSERT INTO user_badges (user_id, badge_id)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE awarded_at = awarded_at
    ");
    $ins->execute([$userId, $badgeId]);
}

function recalculate_user_badges(PDO $pdo, int $userId): void {
   // 1) Niveau débutant : avoir réussi au moins une session de quiz avec un score supérieur ou égal à 60 %.
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM quiz_sessions
        WHERE user_id = ? AND score_percent >= 60
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row && $row['c'] > 0) {
        award_badge($pdo, $userId, 'quiz_beginner');
    }
// 2) Sensibilisation au phishing : au moins 3 tentatives de phishing réussies
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM phishing_attempts
        WHERE user_id = ? AND correct = 1
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row && $row['c'] >= 3) {
        award_badge($pdo, $userId, 'phishing_aware');
    }

    // 3) Protection des mots de passe : module « mots de passe » terminé
    $stmt = $pdo->prepare("
        SELECT completed
        FROM module_progress
        WHERE user_id = ? AND module_code = 'passwords' AND completed = 1
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        award_badge($pdo, $userId, 'password_guardian');
    }

    // 4) Social Detective: module 'social' completed
    $stmt = $pdo->prepare("
        SELECT completed
        FROM module_progress
        WHERE user_id = ? AND module_code = 'social' AND completed = 1
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        award_badge($pdo, $userId, 'social_detective');
    }

}
