<?php
session_start();
header('Content-Type: application/json');

include_once '../config/database.php';
include_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();

if(!$db) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$user = new User($db);
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if(!empty($data->first_name) && !empty($data->last_name) && 
   !empty($data->email) && !empty($data->password)) {
    
    // Set user data
    $user->first_name = $data->first_name;
    $user->last_name = $data->last_name;
    $user->email = $data->email;
    $user->password = $data->password;

    // Email validation
    if(!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Please enter a valid email address"]);
        exit;
    }

    // Password strength check
    if(strlen($data->password) < 6) {
        echo json_encode(["success" => false, "message" => "Password must be at least 6 characters long"]);
        exit;
    }

    // Check if email is already registered
    if($user->emailExists()) {
        echo json_encode(["success" => false, "message" => "This email is already registered"]);
        exit;
    }

    // Create user account
    if($user->register()) {
        // Set session variables
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['role'] = $user->role;
        $_SESSION['first_name'] = $user->first_name;
        $_SESSION['last_name'] = $user->last_name;
        $_SESSION['user_name'] = $user->first_name . ' ' . $user->last_name;
        
        echo json_encode([
            "success" => true, 
            "message" => "Account created successfully!",
            "redirect" => "../scripts/crud/hub.php"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to create account. Please try again."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Please fill in all required fields"]);
}
?>