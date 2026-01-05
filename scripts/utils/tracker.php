<?php

if (isset($db)) {
    try {
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $current_page = basename($_SERVER['PHP_SELF']); 
        
        $stmt_track = $db->prepare("INSERT INTO analytics (user_ip, page) VALUES (?, ?)");
        $stmt_track->execute([$user_ip, $current_page]);
    } catch (PDOException $e) {
    }
}
?>