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

if(!empty($data->email) && !empty($data->password)) {
    
    if($user->login($data->email, $data->password)) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['role'] = $user->role;
        $_SESSION['first_name'] = $user->first_name;
        $_SESSION['last_name'] = $user->last_name;
        $_SESSION['user_name'] = $user->first_name . ' ' . $user->last_name;
        
        echo json_encode([
            "success" => true, 
            "message" => "Login successful!",
            "redirect" => "../scripts/dashboard.php"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Please fill in all fields"]);
}
?>