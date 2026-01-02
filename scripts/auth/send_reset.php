<?php
// scripts/auth/send_reset.php

require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

session_start();
header('Content-Type: application/json');

include_once '../config/database.php';
include_once '../models/User.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email)) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    // Sanitize input
    $user->email = htmlspecialchars(strip_tags($data->email));

    // Check if user exists
    if ($user->emailExists()) {
        $code = rand(100000, 999999);

        // Save code to database
        if ($user->setResetToken($code)) {
            
            // --- SEND EMAIL VIA GMAIL (SMTP) ---
            $mail = new PHPMailer(true);

            try {
                // Server Settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'app.gostay@gmail.com'; 
                $mail->Password   = 'ulqrfkilcnmucsoe';  
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom($mail->Username, 'GoStay Security');
                $mail->addAddress($user->email);

                
                $logoPath = '../../assets/img/logo.png'; 
                
                if(file_exists($logoPath)) {
                    $mail->addEmbeddedImage($logoPath, 'logo_gostay'); 
                }

                $mail->isHTML(true);
                $mail->Subject = 'Reset Your GoStay Password';

                $primaryColor = '#A62FDD';
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
                        .btn-link { color: $primaryColor; text-decoration: none; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <img src='cid:logo_gostay' alt='GoStay' style='max-width: 150px; height: auto; border-radius: 50px;'>
                        </div>
                        <div class='content'>
                            <h2 style='margin-top: 0; color: #333333;'>Password Reset Request</h2>
                            <p>Hello,</p>
                            <p>We received a request to reset the password for your <strong>GoStay</strong> account. If you didn't make this request, you can safely ignore this email.</p>
                            
                            <p>To proceed, please enter the following code in the verification window:</p>
                            
                            <div class='code-box'>
                                <span class='code'>$code</span>
                            </div>

                            <p style='font-size: 14px; color: #666;'>This code will expire in <strong>5 minutes</strong>.</p>
                        </div>
                        <div class='footer'>
                            &copy; " . date("Y") . " GoStay. All rights reserved.<br>
                            Need help? <a href='#' style='color: #777;'>Contact Support</a>
                        </div>
                    </div>
                </body>
                </html>
                ";

                $mail->AltBody = "Hello, your reset code is: $code. It expires in 5 minutes.";

                $mail->send();
                
                // Success Response
                echo json_encode([
                    "success" => true,
                    "message" => "Email sent successfully!"
                ]);

            } catch (Exception $e) {
                echo json_encode([
                    "success" => false, 
                    "message" => "Error sending email: " . $mail->ErrorInfo
                ]);
            }

        } else {
            echo json_encode(["success" => false, "message" => "Database error: Could not save token."]);
        }
    } else {
        echo json_encode(["success" => true, "message" => "If the email exists, a code has been sent."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Please enter an email address."]);
}
?>