<?php
if (!isset($db)) {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
}

try {
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $current_page = basename($_SERVER['PHP_SELF']); 
    
    $stmt_track = $db->prepare("INSERT INTO analytics (user_ip, page) VALUES (?, ?)");
    $stmt_track->execute([$user_ip, $current_page]);
} catch (PDOException $e) {
  
}
?>