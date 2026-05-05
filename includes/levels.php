<?php
// includes/levels.php
// Système de niveaux basé sur les badges cumulés.
// Recruit (0)  →  Bronze (1-2)  →  Silver (3-4)  →  Gold (5+)

function get_user_badge_count(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM user_badges WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int)($stmt->fetch()['c'] ?? 0);
}

function compute_user_level(int $badgeCount): array {
    if ($badgeCount >= 5) {
        return [
            'name'     => 'Gold',
            'code'     => 'gold',
            'color'    => '#FFD700',
            'glow'     => 'rgba(255,215,0,0.5)',
            'min'      => 5,
            'icon'     => '★',
            'subtitle' => 'Elite Operator',
        ];
    }
    if ($badgeCount >= 3) {
        return [
            'name'     => 'Silver',
            'code'     => 'silver',
            'color'    => '#C0C0C0',
            'glow'     => 'rgba(192,192,192,0.5)',
            'min'      => 3,
            'icon'     => '◆',
            'subtitle' => 'Senior Operator',
        ];
    }
    if ($badgeCount >= 1) {
        return [
            'name'     => 'Bronze',
            'code'     => 'bronze',
            'color'    => '#CD7F32',
            'glow'     => 'rgba(205,127,50,0.5)',
            'min'      => 1,
            'icon'     => '▲',
            'subtitle' => 'Junior Operator',
        ];
    }
    return [
        'name'     => 'Recruit',
        'code'     => 'recruit',
        'color'    => '#475569',
        'glow'     => 'rgba(71,85,105,0.4)',
        'min'      => 0,
        'icon'     => '○',
        'subtitle' => 'Awaiting Clearance',
    ];
}

function next_level_threshold(int $badgeCount): ?int {
    if ($badgeCount < 1) return 1;
    if ($badgeCount < 3) return 3;
    if ($badgeCount < 5) return 5;
    return null;
}

function next_level_name(int $badgeCount): ?string {
    if ($badgeCount < 1) return 'Bronze';
    if ($badgeCount < 3) return 'Silver';
    if ($badgeCount < 5) return 'Gold';
    return null;
}

function progress_to_next_level(int $badgeCount): int {
    if ($badgeCount >= 5) return 100;
    if ($badgeCount >= 3) return (int)round((($badgeCount - 3) / 2) * 100);
    if ($badgeCount >= 1) return (int)round((($badgeCount - 1) / 2) * 100);
    return 0;
}