<?php
/**
 * CampusRide - UIU Student Portal
 * File: config/auth.php
 * Authentication & Session Guard for `campusride` database
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Ensures user is authenticated. If not, redirects to login.php
 */
function requireAuth(): array {
    if (!isset($_SESSION['user']) || empty($_SESSION['user']['user_id'])) {
        header('Location: login.php?msg=' . urlencode('Please log in to access this page.'));
        exit();
    }
    return $_SESSION['user'];
}

/**
 * Returns the currently logged in student, or null if guest
 */
function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Returns the current logged-in student user_id
 */
function getCurrentUserId(): int {
    return (int)($_SESSION['user']['user_id'] ?? 0);
}

/**
 * Hashes password using SHA2-256 (matches MySQL SHA2(password, 256))
 */
function hashStudentPassword(string $password): string {
    return hash('sha256', $password);
}

/**
 * Verifies plain password against stored SHA2-256 hash
 */
function verifyStudentPassword(string $plainPassword, string $storedHash): bool {
    return hash_equals($storedHash, hash('sha256', $plainPassword));
}

/**
 * Refreshes $_SESSION['user'] data from the database
 */
function refreshUserSession(): void {
    if (isset($_SESSION['user']['user_id'])) {
        $db = (new Database())->getConnection();
        if ($db) {
            $stmt = $db->prepare("SELECT * FROM `USER` WHERE user_id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user']['user_id']]);
            $updated = $stmt->fetch();
            if ($updated) {
                $_SESSION['user'] = $updated;
            }
        }
    }
}