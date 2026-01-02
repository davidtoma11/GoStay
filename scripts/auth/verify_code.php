<?php
session_start();
header('Content-Type: application/json');

include_once '../config/database.php';
include_once '../models/User.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->code)) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $user->email = htmlspecialchars(strip_tags($data->email));
    
    $query = "SELECT reset_expires_at FROM users WHERE email = :email AND reset_code = :code LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $user->email);
    $stmt->bindParam(":code", $data->code);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (new DateTime() > new DateTime($row['reset_expires_at'])) {
            echo json_encode(["success" => false, "message" => "Code expired."]);
        } else {
            echo json_encode(["success" => true, "message" => "Valid code."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid code."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Please enter the code."]);
}
?>