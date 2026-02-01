<?php

function client_ip(): string {
    // basic approach for local project
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function is_rate_limited(PDO $pdo, string $email, string $ip): bool {
    // rule: max 5 failed attempts per 10 minutes (per email OR per IP)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM login_attempts
        WHERE success = 0
          AND created_at >= (NOW() - INTERVAL 10 MINUTE)
          AND (email = ? OR ip = ?)
    ");
    $stmt->execute([$email, $ip]);
    $row = $stmt->fetch();
    return $row && (int)$row['c'] >= 5;
}

function log_login_attempt(PDO $pdo, string $email, string $ip, int $success): void {
    $stmt = $pdo->prepare("
        INSERT INTO login_attempts (email, ip, success)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$email, $ip, $success]);
}
