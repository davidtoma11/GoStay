<?php
session_start();


if (!isset($_SESSION['user_id'])) { 
    header("Location: ../../index.php"); 
    exit; 
}

// Database Connection
require_once '..//config/database.php';
include_once '../utils/tracker.php';
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);


$full_name_db = $user['first_name'] . ' ' . $user['last_name'];
$email_db = $user['email'];
$phone_db = ''; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GoStay - Support</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/home.css"> <style>
        .success-view { text-align: center; padding: 40px 20px; animation: fadeIn 0.5s ease; }
        .success-icon { font-size: 5rem; color: #4CAF50; margin-bottom: 20px; }
        .success-title { font-size: 2rem; margin-bottom: 10px; color: #fff; }
        .success-text { font-size: 1.1rem; opacity: 0.9; margin-bottom: 30px; color: rgba(255,255,255,0.8); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <nav class="main-nav">
        <div class="nav-icons">
            <a href="../home.php" title="Home"><i class="fa-solid fa-house"></i></a>
            <a href="../auth/logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </nav>

    <div class="content-wrapper">
        
        <header class="hero-section" style="padding-bottom: 20px;">
            <div class="hero-logo" style="font-size: 4rem;">GoStay</div>
            <h1 class="hero-headline" style="font-size: 2.5rem; margin-bottom: 20px;">Support Center</h1>
        </header>

        <section class="contact-section">
            <div class="contact-card wide-card">
                
                <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                    
                    <div class="success-view">
                        <i class="fa-solid fa-circle-check success-icon"></i>
                        <h2 class="success-title">Message Sent!</h2>
                        <p class="success-text">Your request has been submitted.<br>Check your email for confirmation.</p>
                        <a href="../pages/home.php" class="btn contact-btn-custom" style="display: inline-block; width: auto; padding: 0 40px;">Return Home</a>
                    </div>

                <?php elseif (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
                    
                     <div class="success-view">
                        <i class="fa-solid fa-circle-exclamation success-icon" style="color: #ff4444;"></i>
                        <h2 class="success-title">Error</h2>
                        <p class="success-text">We couldn't send your message. Please try again.</p>
                        <a href="contact.php" class="btn contact-btn-custom">Try Again</a>
                    </div>

                <?php else: ?>

                    <form action="send_support.php" method="POST" class="support-form">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Order Reference</label>
                                <input type="text" name="order_ref" placeholder="#GS-2024-...">
                            </div>
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="fullname" required value="<?php echo htmlspecialchars($full_name_db); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" required value="<?php echo htmlspecialchars($email_db); ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone_db); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Issue Type</label>
                            <select name="problem_type" class="glass-select">
                                <option value="New Order">Booking Inquiry</option>
                                <option value="Payment">Payment & Billing</option>
                                <option value="Cancellation">Cancellation</option>
                                <option value="Technical">Technical Issue</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="message" rows="5" required placeholder="Describe your issue..."></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="radio-label">Urgency</label>
                                <div class="radio-group">
                                    <label class="radio-container">High <input type="radio" name="urgency" value="High"><span class="checkmark"></span></label>
                                    <label class="radio-container">Medium <input type="radio" name="urgency" value="Medium" checked><span class="checkmark"></span></label>
                                    <label class="radio-container">Low <input type="radio" name="urgency" value="Low"><span class="checkmark"></span></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="radio-label">Contact Method</label>
                                <div class="radio-group">
                                    <label class="radio-container">Email <input type="radio" name="contact_method" value="Email" checked><span class="checkmark"></span></label>
                                    <label class="radio-container">Phone <input type="radio" name="contact_method" value="Phone"><span class="checkmark"></span></label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn contact-btn-custom">Submit Request</button>
                    </form>

                <?php endif; ?>
                
            </div>
        </section>
    </div>


</body>
</html>