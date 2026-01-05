<?php
// scripts/auth/signup.php

session_start();
// Prevent PHP errors from breaking JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
include_once '../config/database.php';
include_once '../models/User.php';

$input = file_get_contents("php://input");
$data = json_decode($input);

if (empty($data->action)) {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// --- ACTION 1: SIGNUP  ---
if ($data->action === 'signup') {

    if(empty($data->email) || empty($data->password) || empty($data->first_name)) {
        echo json_encode(["success" => false, "message" => "All fields required."]);
        exit;
    }

    $user->email = htmlspecialchars(strip_tags($data->email));

    // Check if user exists in DB
    if($user->emailExists()){
        echo json_encode(["success" => false, "message" => "Email already registered."]);
        exit;
    }

    $code = rand(100000, 999999);
    $firstName = htmlspecialchars(strip_tags($data->first_name));

    // Save to Session (Temporary)
    $_SESSION['signup_data'] = [
        'first_name' => $firstName,
        'last_name'  => htmlspecialchars(strip_tags($data->last_name)),
        'email'      => $user->email,
        'password'   => password_hash($data->password, PASSWORD_BCRYPT),
        'code'       => $code,
        'expires'    => time() + 300 // 5 Minutes
    ];

    // --- SEND PREMIUM EMAIL ---
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'app.gostay@gmail.com'; // CHANGE THIS
        $mail->Password   = 'ulqrfkilcnmucsoe';     // CHANGE THIS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($mail->Username, 'GoStay Welcome');
        $mail->addAddress($user->email);

        $logoPath = '../../assets/img/logo.png';
        if(file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'logo_gostay');
        }

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to GoStay - Verify Account';

        $primaryColor = '#A62FDD'; // Purple
        $bgColor = '#f4f7f6';

        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: $bgColor; margin: 0; padding: 0; }
                .container { width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-top: 40px; margin-bottom: 40px; }
                .header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 1px solid #eeeeee; }
                .content { padding: 40px 30px; color: #333333; line-height: 1.6; }
                .code-box { background-color: #f0f8ff; border: 1px dashed $primaryColor; border-radius: 6px; text-align: center; padding: 20px; margin: 30px 0; }
                .code { font-size: 36px; font-weight: bold; letter-spacing: 5px; color: $primaryColor; font-family: 'Courier New', monospace; }
                .footer { background-color: $bgColor; padding: 20px; text-align: center; font-size: 12px; color: #999999; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='cid:logo_gostay' alt='GoStay' style='max-width: 150px; height: auto; border-radius: 50px;'>
                </div>
                <div class='content'>
                    <h2 style='margin-top: 0; color: #333333; text-align: center;'>Welcome to GoStay!</h2>
                    <p>Hello <strong>$firstName</strong>,</p>
                    <p>Thank you for joining us! To activate your <strong>Client</strong> account and start booking, please verify your email address.</p>
                    
                    <p>Enter the code below in the verification window:</p>
                    
                    <div class='code-box'>
                        <span class='code'>$code</span>
                    </div>

                    <p style='font-size: 14px; color: #666; text-align: center;'>This code expires in <strong>5 minutes</strong>.</p>
                </div>
                <div class='footer'>
                    &copy; " . date("Y") . " GoStay. All rights reserved.<br>
                    Need help? <a href='#' style='color: #777;'>Contact Support</a>
                </div>
            </div>
        </body>
        </html>";

        $mail->AltBody = "Welcome to GoStay! Your verification code is: $code";

        $mail->send();

        echo json_encode([
            "success" => true, 
            "message" => "Verification code sent!",
            "debug_code" => $code 
        ]);

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Mailer Error: " . $mail->ErrorInfo]);
    }

// --- ACTION 2: VERIFY CODE (Check Session -> Insert DB) ---
} elseif ($data->action === 'verify') {

    if(!isset($_SESSION['signup_data'])) {
        echo json_encode(["success" => false, "message" => "Session expired."]);
        exit;
    }

    $sessionData = $_SESSION['signup_data'];
    $inputCode = htmlspecialchars(strip_tags($data->code));

    // Check expiry
    if(time() > $sessionData['expires']) {
        session_unset(); session_destroy();
        echo json_encode(["success" => false, "message" => "Code expired."]);
        exit;
    }

    // Verify Code matches
    if($sessionData['code'] == $inputCode) {
        
        $user->first_name = $sessionData['first_name'];
        $user->last_name  = $sessionData['last_name'];
        $user->email      = $sessionData['email'];
        $user->password   = $sessionData['password'];
        // Role is set to 'client' inside User.php create() method

        if($user->create()) {
            unset($_SESSION['signup_data']); // Clear session
            echo json_encode(["success" => true, "message" => "Account created successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database insert failed."]);
        }

    } else {
        echo json_encode(["success" => false, "message" => "Invalid verification code."]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Unknown action."]);
}
?>