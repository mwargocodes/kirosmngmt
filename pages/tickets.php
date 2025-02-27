<?php

require '../kirosmngmt/config/database-config.php';

//sql statements
$getUnclaimed = "SELECT * FROM modbox_tickets";
$statment = $pdo->query($getUnclaimed);
$tickets = $statment->fetchAll();
?>

<!DOCTYPE html>
<html>
    <body>
        <?php foreach ($tickets as $ticket) { ?>
        <p>
            <?= html_escape($ticket['Title']) ?>
            <?= html_escape($ticket['Author']) ?>
            <?= html_escape ($ticket['SubmissionDate']) ?>
            <?= html_escape ($ticket['Status']) ?>
        </p>
        <?php } ?>
    </body>
</html>