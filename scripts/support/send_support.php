<?php
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // SMTP Settings
    $smtp_host = 'smtp.gmail.com'; 
    $smtp_user = 'app.gostay@gmail.com'; 
    $smtp_pass = 'ulqrfkilcnmucsoe'; 
    $smtp_port = 587;
    $admin_email = 'app.gostay@gmail.com'; 

    // Inputs
    $fullname = htmlspecialchars($_POST['fullname']);
    $email_client = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']); // Gets what user typed
    $problem_type = htmlspecialchars($_POST['problem_type']);
    $message = nl2br(htmlspecialchars($_POST['message']));
    
    $order_ref = htmlspecialchars($_POST['order_ref']); 
    $urgency = htmlspecialchars($_POST['urgency']);
    $contact_method = htmlspecialchars($_POST['contact_method']);

    // Assets
    $primaryColor = '#7b2bd4';
    $bgColor = '#f4f7f6';
    $logoPath = '../../assets/img/logo.png'; 

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp_port;

        $mail->setFrom($smtp_user, 'GoStay Support');
        $mail->addAddress($admin_email); 
        $mail->addReplyTo($email_client, $fullname); 

        if(file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'logo_gostay'); 
        }

        $mail->isHTML(true);
        $mail->Subject = "Ticket: $problem_type ($fullname)";
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Helvetica', sans-serif; background: $bgColor; padding: 0; margin: 0; }
                .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; }
                .header { background: #fff; padding: 20px; text-align: center; border-bottom: 1px solid #eee; }
                .content { padding: 30px; color: #333; }
                .info { background: #f9f9f9; border-left: 4px solid $primaryColor; padding: 15px; margin: 20px 0; }
                .footer { background: $bgColor; padding: 15px; text-align: center; font-size: 12px; color: #888; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'><img src='cid:logo_gostay' width='120'></div>
                <div class='content'>
                    <h2>New Support Ticket</h2>
                    <p><strong>From:</strong> $fullname</p>
                    <div class='info'>
                        Ref: $order_ref <br>
                        Phone: $phone <br>
                        Issue: $problem_type <br>
                        Urgency: $urgency
                    </div>
                    <p><strong>Message:</strong><br>$message</p>
                </div>
                <div class='footer'>Automated Message</div>
            </div>
        </body>
        </html>";

        $mail->AltBody = strip_tags($message);

        $mail->send();
        
        header("Location: contact.php?status=success");
        exit;

    } catch (Exception $e) {
        header("Location: contact.php?status=error");
        exit;
    }

} else {
    header("Location: contact.php");
    exit;
}
?>