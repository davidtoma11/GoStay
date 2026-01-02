<?php
session_start();
header('Content-Type: application/json');

include_once '../config/database.php';
include_once '../models/User.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->code) && !empty($data->password)) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $user->email = htmlspecialchars(strip_tags($data->email));
    
    // Attempt reset
    $result = $user->resetPassword($data->code, $data->password);

    if ($result === "success") {
        echo json_encode(["success" => true, "message" => "Password changed! Please Login."]);
    } elseif ($result === "expired") {
        echo json_encode(["success" => false, "message" => "Code expired. Request a new one."]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid code."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Fill all fields"]);
}