<?php
session_start();
header('Content-Type: application/json');

include_once '../config/database.php';
include_once '../models/User.php';
include_once '../utils/tracker.php';

// HTTP Request Spoofing (Allow only POST requests)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Get JSON input
$data = json_decode(file_get_contents("php://input"));

// CSRF Check (Verify token from session)
if (!isset($data->csrf_token) || $data->csrf_token !== $_SESSION['csrf_token']) {
    echo json_encode(["success" => false, "message" => "Session expired or invalid token"]);
    exit;
}

if(!empty($data->email) && !empty($data->password)) {
    
    // Sanitize input (Defense in depth)
    $user->email = htmlspecialchars(strip_tags($data->email));
    
    // Check if user exists
    if($user->emailExists()) {
        
        // Verify Hash (Compare input vs DB hash)
        if(password_verify($data->password, $user->password)) {
            
            // Session Fixation (Regenerate ID to prevent theft)
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user->id;
            $_SESSION['role'] = $user->role; 
            $_SESSION['user_name'] = $user->first_name . ' ' . $user->last_name;
            
            $redirect = "home.php"; // Default redirect

            echo json_encode([
                "success" => true, 
                "message" => "Login successful",
                "redirect" => $redirect
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid credentials"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid credentials"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Fill all fields"]);
}
?>