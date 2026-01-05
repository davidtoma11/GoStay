<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/home.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// 1. Traffic by Page
$traffic = $db->query("SELECT page, COUNT(*) as views FROM analytics GROUP BY page ORDER BY views DESC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Revenue Trend
$revenue = $db->query("SELECT MONTHNAME(created_at) as m, SUM(total_price) as t FROM reservations WHERE status='completed' GROUP BY MONTH(created_at)")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics Hub - GoStay</title>
    <link rel="stylesheet" href="../styles/crud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body style="background: #f4f7ff; padding: 20px;">
    <div class="operations-container">
        <div class="operations-header">
            <h1><i class="fa-solid fa-chart-pie"></i> Business Intelligence</h1>
            <div style="display: flex; gap: 10px;">
                <a href="export_analytics.php" class="btn btn-primary" style="background: #27ae60; border: none;"><i class="fa-solid fa-file-excel"></i> Export Analytics</a>
                <a href="hub.php" class="back-link">← Return to Hub</a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div class="crud-card" style="background: white; padding: 20px;">
                <h3>Traffic Distribution</h3>
                <canvas id="trafficChart"></canvas>
            </div>
            <div class="crud-card" style="background: white; padding: 20px;">
                <h3>Revenue Growth</h3>
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <div class="table-responsive" style="margin-top: 20px;">
            <h3>Recent Activity Log (Analytics Table)</h3>
            <table class="data-table">
                <thead><tr><th>Visitor IP</th><th>Page</th><th>Time</th></tr></thead>
                <tbody>
                    <?php 
                    $logs = $db->query("SELECT * FROM analytics ORDER BY visit_time DESC LIMIT 10")->fetchAll();
                    foreach($logs as $l): ?>
                    <tr><td><?= $l['user_ip'] ?></td><td><?= $l['page'] ?></td><td><?= $l['visit_time'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($revenue, 'm')) ?>,
                datasets: [{ label: 'Revenue (RON)', data: <?= json_encode(array_column($revenue, 't')) ?>, borderColor: '#7b2bd4', tension: 0.3, fill: true, backgroundColor: 'rgba(123, 43, 212, 0.1)' }]
            }
        });

        new Chart(document.getElementById('trafficChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($traffic, 'page')) ?>,
                datasets: [{ label: 'Views', data: <?= json_encode(array_column($traffic, 'views')) ?>, backgroundColor: '#7b2bd4' }]
            }
        });
    </script>
</body>
</html>