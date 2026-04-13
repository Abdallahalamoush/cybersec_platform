<?php

function award_badge(PDO $pdo, int $userId, string $badgeCode): void {
    $stmt = $pdo->prepare("SELECT id FROM badges WHERE code = ?");
    $stmt->execute([$badgeCode]);
    $badge = $stmt->fetch();
    if (!$badge) return;

    $ins = $pdo->prepare("
        INSERT INTO user_badges (user_id, badge_id)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE awarded_at = awarded_at
    ");
    $ins->execute([$userId, (int)$badge['id']]);
}

function recalculate_user_badges(PDO $pdo, int $userId): void {

    // 1) Quiz Beginner : au moins 1 session >= 60 %
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS c FROM quiz_sessions
        WHERE user_id = ? AND score_percent >= 60
    ");
    $stmt->execute([$userId]);
    if ($stmt->fetch()['c'] > 0) award_badge($pdo, $userId, 'quiz_beginner');

    // 2) Phishing Aware : au moins 3 réponses correctes en phishing
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS c FROM phishing_attempts
        WHERE user_id = ? AND correct = 1
    ");
    $stmt->execute([$userId]);
    if ($stmt->fetch()['c'] >= 3) award_badge($pdo, $userId, 'phishing_aware');

    // 3) Password Guardian : module passwords complété
    $stmt = $pdo->prepare("
        SELECT completed FROM module_progress
        WHERE user_id = ? AND module_code = 'passwords' AND completed = 1
    ");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) award_badge($pdo, $userId, 'password_guardian');

    // 4) Social Detective : module social complété
    $stmt = $pdo->prepare("
        SELECT completed FROM module_progress
        WHERE user_id = ? AND module_code = 'social' AND completed = 1
    ");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) award_badge($pdo, $userId, 'social_detective');

    // 5) Web Sentinel : module reseaux complété  ← NOUVEAU MOIS 6
    $stmt = $pdo->prepare("
        SELECT completed FROM module_progress
        WHERE user_id = ? AND module_code = 'reseaux' AND completed = 1
    ");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) award_badge($pdo, $userId, 'web_sentinel');
}