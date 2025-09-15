<?php
require_once 'database-conn.php';

// Fetch mods and admins
$stmt = $pdo->prepare("SELECT id, displayName, accountRole FROM accounts WHERE accountRole IN ('mod', 'ok') ORDER BY displayName ASC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$users) {
    echo "<p>No moderators or admins found.</p>";
    exit;
}

// Role badge HTML generator
function roleBadge($role) {
    switch ($role) {
        case 'ok':
            return '<span class="badge bg-danger">Admin</span>';
        case 'mod':
            return '<span class="badge bg-primary text-dark">Mod</span>';
        default:
            return '';
    }
}
?>

<ul class="list-group list-group-flush">
<?php foreach ($users as $u): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center bg-dark text-white border-secondary">
        <a href="profile.php?id=<?= htmlspecialchars($u['id']) ?>" class="text-white text-decoration-none fw-semibold">
            <?= htmlspecialchars($u['displayName']) ?>
        </a>
        <?= roleBadge($u['accountRole']) ?>
    </li>
<?php endforeach; ?>
</ul>
