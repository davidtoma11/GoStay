<?php
// Include PHPMailer classes manually
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- SMTP Settings ---
    $smtp_host = 'smtp.gmail.com'; 
    $smtp_user = 'app.gostay@gmail.com'; 
    $smtp_pass = 'ulqrfkilcnmucsoe';     
    $smtp_port = 587;
    $admin_email = 'app.gostay@gmail.com';   

    // --- Form Data ---
    $fullname = htmlspecialchars($_POST['fullname']);
    $email_client = htmlspecialchars($_POST['email']);
    $order_ref = htmlspecialchars($_POST['order_ref']) ?: 'N/A';
    $phone = htmlspecialchars($_POST['phone']) ?: 'N/A';
    $problem_type = htmlspecialchars($_POST['problem_type']);
    $message = nl2br(htmlspecialchars($_POST['message']));
    $urgency = htmlspecialchars($_POST['urgency']);
    $contact_method = htmlspecialchars($_POST['contact_method']);

    // --- Visual Settings ---
    $primaryColor = '#7b2bd4';
    $bgColor = '#f4f7f6';
    $logoPath = '../../assets/img/logo.png'; 

    $mail = new PHPMailer(true);

    try {
        // Server Configuration
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp_port;

        // Recipients
        $mail->setFrom($smtp_user, 'GoStay Support System');
        $mail->addAddress($admin_email); 
        $mail->addReplyTo($email_client, $fullname); 

        // Embed Logo
        if(file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'logo_gostay'); 
        }

        // Email Body (Styled)
        $mail->isHTML(true);
        $mail->Subject = "Support Ticket: $problem_type ($fullname)";
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: $bgColor; margin: 0; padding: 0; }
                .container { width: 100%; max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
                .header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 1px solid #eeeeee; }
                .content { padding: 40px 30px; color: #333333; line-height: 1.6; }
                .info-box { background-color: #f9f9f9; border-left: 4px solid $primaryColor; padding: 15px; margin: 20px 0; border-radius: 4px; }
                .data-row { margin-bottom: 8px; font-size: 14px; }
                .label { font-weight: bold; color: #555; width: 120px; display: inline-block; }
                .message-area { margin-top: 20px; padding: 15px; background: #f0f8ff; border-radius: 6px; font-style: italic; color: #444; }
                .footer { background-color: $bgColor; padding: 20px; text-align: center; font-size: 12px; color: #999999; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='cid:logo_gostay' alt='GoStay' style='max-width: 150px; height: auto;'>
                </div>
                <div class='content'>
                    <h2 style='margin-top: 0; color: #333;'>New Support Request</h2>
                    <p>Hello Admin, a new ticket has been submitted via the platform.</p>
                    
                    <div class='info-box'>
                        <div class='data-row'><span class='label'>Client:</span> $fullname</div>
                        <div class='data-row'><span class='label'>Email:</span> <a href='mailto:$email_client' style='color:$primaryColor; text-decoration:none;'>$email_client</a></div>
                        <div class='data-row'><span class='label'>Phone:</span> $phone</div>
                        <div class='data-row'><span class='label'>Order Ref:</span> $order_ref</div>
                        <div class='data-row'><span class='label'>Issue Type:</span> $problem_type</div>
                        <div class='data-row'><span class='label'>Urgency:</span> $urgency</div>
                        <div class='data-row'><span class='label'>Contact via:</span> $contact_method</div>
                    </div>

                    <p><strong>Message Description:</strong></p>
                    <div class='message-area'>
                        \"$message\"
                    </div>
                </div>
                <div class='footer'>
                    &copy; " . date("Y") . " GoStay System. Automated Message.
                </div>
            </div>
        </body>
        </html>";

        $mail->AltBody = "New Support Request from $fullname ($email_client). Issue: $problem_type. Message: " . strip_tags($message);

        $mail->send();
        
        echo "<script>alert('Support request sent successfully!'); window.location.href='../home.php';</script>";

    } catch (Exception $e) {
        echo "<script>alert('Error sending email: {$mail->ErrorInfo}'); window.history.back();</script>";
    }

} else {
    header("Location: contact.php");
    exit;
}
?>