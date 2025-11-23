<?php
include_once 'database.php';

$database = new Database();
$db = $database->getConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Status</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: 'Roboto', sans-serif;
            background: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .status-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.9);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        
        .connection-badge {
            display: inline-block;
            padding: 8px 20px;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        
        .status-details {
            color: #2c3e50;
            line-height: 1.6;
        }
        
        .status-details strong {
            color: #7b2bd4;
        }
    </style>
</head>
<body>
    <div class="status-card">
        <?php if($db): ?>
            <div class="connection-badge">✅ DATABASE CONNECTED</div>
            <div class="status-details">
                <strong>Database:</strong> gostay<br>
                <strong>Server:</strong> localhost:3306<br>
                <strong>User:</strong> root
            </div>
        <?php else: ?>
            <div class="connection-badge" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">❌ CONNECTION FAILED</div>
            <div class="status-details">
                Unable to connect to database
            </div>
        <?php endif; ?>
    </div>
</body>
</html>