<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoStay - Contact Support</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../styles/home.css">
</head>
<body>

    <nav class="main-nav">
        <div class="nav-icons">
            <a href="../home.php" title="Home"><i class="fa-solid fa-house"></i></a>
            <a href="auth/logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </nav>

    <div class="content-wrapper">
        
        <header class="hero-section" style="padding-bottom: 20px;">
            <div class="hero-logo" style="font-size: 4rem;">GoStay</div>
            <h1 class="hero-headline" style="font-size: 2.5rem; margin-bottom: 20px;">Support Request</h1>
        </header>

        <section class="contact-section">
            <div class="contact-card wide-card">
                
                <form action="send_support.php" method="POST" enctype="multipart/form-data" class="support-form">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Order Reference No</label>
                            <input type="text" name="order_ref" placeholder="e.g. #GS-2024-889">
                        </div>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="fullname" required placeholder="John Doe">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" required placeholder="name@example.com">
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" placeholder="(000) 000-0000">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>I'm having a problem with:</label>
                        <select name="problem_type" class="glass-select">
                            <option value="New Order">New Booking / Order</option>
                            <option value="Payment">Billing or Charges</option>
                            <option value="Cancellation">Cancellation</option>
                            <option value="Technical">Technical Issue</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Describe Your Problem</label>
                        <textarea name="message" rows="5" required placeholder="Please describe your issue in detail..."></textarea>
                    </div>

                    <div class="form-group">
                        <label style="margin-bottom: 10px; display:block;">Urgency Level</label>
                        <div class="radio-group">
                            <label class="radio-container">Today
                                <input type="radio" name="urgency" value="Today">
                                <span class="checkmark"></span>
                            </label>
                            <label class="radio-container">Next 48 Hours
                                <input type="radio" name="urgency" value="48h" checked>
                                <span class="checkmark"></span>
                            </label>
                            <label class="radio-container">Not Urgent
                                <input type="radio" name="urgency" value="Low">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="margin-bottom: 10px; display:block;">Preferred Contact Method</label>
                        <div class="radio-group">
                            <label class="radio-container">Email
                                <input type="radio" name="contact_method" value="Email" checked>
                                <span class="checkmark"></span>
                            </label>
                            <label class="radio-container">Phone
                                <input type="radio" name="contact_method" value="Phone">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn contact-btn-custom">Submit Request</button>

                </form>
            </div>
        </section>

    </div>

    <div class="floating-logo"></div> 

</body>
</html>