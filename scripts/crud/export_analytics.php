<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit("Access Denied");

$database = new Database();
$db = $database->getConnection();

$filename = "GoStay_Analytics_" . date('Y-m-d') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

echo "\xEF\xBB\xBF"; // UTF-8 BOM for characters

$q_traffic = $db->query("SELECT page, COUNT(*) as v FROM analytics GROUP BY page ORDER BY v DESC")->fetchAll(PDO::FETCH_ASSOC);
$q_logs = $db->query("SELECT * FROM analytics ORDER BY visit_time DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);

$style = "style='background: #7b2bd4; color: white; font-weight: bold; border: 1px solid #000;'";
?>

<h3>Platform Traffic Summary</h3>
<table border="1">
    <tr><th <?= $style ?>>Page Filename</th><th <?= $style ?>>Total Impressions</th></tr>
    <?php foreach($q_traffic as $t): ?>
    <tr><td><?= htmlspecialchars($t['page']) ?></td><td><?= $t['v'] ?></td></tr>
    <?php endforeach; ?>
</table>

<br>

<h3>Detailed Visitor History (Last 500)</h3>
<table border="1">
    <tr><th <?= $style ?>>IP Address</th><th <?= $style ?>>Accessed Page</th><th <?= $style ?>>Visit Timestamp</th></tr>
    <?php foreach($q_logs as $l): ?>
    <tr><td><?= $l['user_ip'] ?></td><td><?= htmlspecialchars($l['page']) ?></td><td><?= $l['visit_time'] ?></td></tr>
    <?php endforeach; ?>
</table>