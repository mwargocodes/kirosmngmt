<?php
require_once 'database-conn.php';

//this file will outline functions to control staff notices across the site. 
//this includes the notices.php bulletin.

function getRecentStaffNotices(PDO $pdo, int $limit = 5): array {
    $stmt = $pdo->prepare("SELECT n.*, a.username 
                           FROM staff_notices n 
                           JOIN accounts a ON n.author_id = a.id 
                           ORDER BY n.created_at DESC 
                           LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStaffNoticesPage(PDO $pdo, int $page = 1, int $perPage = 10): array {
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT n.*, a.username 
                           FROM staff_notices n 
                           JOIN accounts a ON n.author_id = a.id 
                           ORDER BY n.created_at DESC 
                           LIMIT :perPage OFFSET :offset");
    $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countStaffNotices(PDO $pdo): int {
    return (int)$pdo->query("SELECT COUNT(*) FROM staff_notices")->fetchColumn();
}

function addStaffNotice(PDO $pdo, int $authorID, string $title, string $content): bool {
    $stmt = $pdo->prepare("INSERT INTO staff_notices (author_id, title, content) 
                           VALUES (:authorID, :title, :content)");
    return $stmt->execute([
        'authorID' => $authorID,
        'title' => $title,
        'content' => $content
    ]);
}

//functionality for admins to delete notices

function deleteStaffNotice(PDO $pdo, int $noticeID): bool {
    $stmt = $pdo->prepare("DELETE FROM staff_notices WHERE noticeID = :noticeID");
    return $stmt->execute(['noticeID' => $noticeID]);
}

// mark notices read when viewing page

function markNoticesRead(PDO $pdo, int $userID): void {
    $stmt = $pdo->query("SELECT noticeID FROM staff_notices");
    $all = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($all as $noticeID) {
        $insert = $pdo->prepare("INSERT IGNORE INTO staff_notice_reads (noticeID, userID) VALUES (:n, :u)");
        $insert->execute(['n' => $noticeID, 'u' => $userID]);
    }
}

function hasUnreadNotices(PDO $pdo, int $userID): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) 
                           FROM staff_notices n
                           WHERE NOT EXISTS (
                               SELECT 1 FROM staff_notice_reads r 
                               WHERE r.noticeID = n.noticeID AND r.userID = :uid
                           )");
    $stmt->execute(['uid' => $userID]);
    return $stmt->fetchColumn() > 0;
}
