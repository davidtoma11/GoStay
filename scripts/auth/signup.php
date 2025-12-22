<?php
session_start();
header('Content-Type: application/json');

include_once '../config/database.php';
include_once '../models/User.php';

// HTTP Request Spoofing (Allow only POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Get raw posted data
$data = json_decode(file_get_contents("php://input"));

// CSRF (Verify token from session)
if (!isset($data->csrf_token) || $data->csrf_token !== $_SESSION['csrf_token']) {
    echo json_encode(["success" => false, "message" => "Invalid security token"]);
    exit;
}

//reCAPTCHA Verification
$recaptcha_secret = '6LedkzIsAAAAAFuncToey09T9Xeolw_ZMo37nWP5';
if (isset($data->recaptcha_response)) {
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$data->recaptcha_response}");
    $responseKeys = json_decode($verify);
    if(!$responseKeys->success) {
        echo json_encode(["success" => false, "message" => "Robot verification failed"]);
        exit;
    }
}


// Validate inputs
if(!empty($data->first_name) && !empty($data->last_name) && 
   !empty($data->email) && !empty($data->password)) {
    
    // Set user properties
    $user->first_name = $data->first_name;
    $user->last_name = $data->last_name;
    $user->email = $data->email;
    $user->password = $data->password; // Will be hashed in User.php
    $user->role = 'client'; // Default role

    // Validate email format
    if(!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Invalid email format"]);
        exit;
    }

    // Check existing email
    if($user->emailExists()) {
        echo json_encode(["success" => false, "message" => "Email already registered"]);
        exit;
    }

    // Create account
    if($user->register()) {
        echo json_encode(["success" => true, "message" => "Account created successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Registration failed"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
}
?>