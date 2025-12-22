<?php
session_start();
// Generate CSRF Token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoStay - Authentication</title>
    <link rel="stylesheet" href="../styles/login.css">
    
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <script>
        const csrfToken = "<?php echo $_SESSION['csrf_token']; ?>";
    </script>
</head>

<body>
    <div class="page-container">
        <section class="hero">
            <h1 class="hero-brand">GoStay</h1>
            <h2 class="hero-title">Your Next Escape</h2>
            <p class="hero-subtitle">Manage your reservations / listings</p>
            <p class="hero-description">Access your dashboard to modify dates, communicate with hosts, and track your expenses. Our platform offers a seamless interface designed to make your travel management simple and stress-free.</p>
        </section>

        <aside class="login-panel">
            <form id="loginForm" class="auth-form active">
                <h3 class="login-heading">USER LOGIN</h3>
                <div class="form-body">
                    <div class="field input-with-icon">
                        <img class="left-icon" src="../assets/img/email-icon.png" alt="email">
                        <input type="text" id="loginEmail" name="email" placeholder="Email" required>
                    </div>

                    <div class="field input-with-icon">
                        <img class="left-icon" src="../assets/img/password-icon.png" alt="pass">
                        <input id="loginPassword" type="password" name="password" placeholder="Password" required>
                        <button type="button" class="toggle-visibility" data-target="loginPassword">
                            <img class="eye-icon" src="../assets/img/toggle-password.png" alt="show">
                        </button>
                    </div>

                    <div class="links">
                        <a class="link-forgot" href="#">Forgot password?</a>
                        <a class="link-signup" href="#" id="showSignup">Don't have an account?</a>
                    </div>
                </div>
                <div class="actions">
                    <button type="submit" class="btn">Log In</button>
                </div>
                <div id="loginMessage" style="color: red; text-align: center; margin-top: 10px;"></div>
            </form>

            <form id="signupForm" class="auth-form" style="display: none;">
                <h3 class="login-heading">CREATE ACCOUNT</h3>
                <div class="form-body">
                    <div class="field input-with-icon">
                        <img class="left-icon" src="../assets/img/surname-icon.png" alt="first">
                        <input type="text" id="regFirstName" name="first_name" placeholder="First Name" required>
                    </div>

                    <div class="field input-with-icon">
                        <img class="left-icon" src="../assets/img/name-icon.png" alt="last">
                        <input type="text" id="regLastName" name="last_name" placeholder="Last Name" required>
                    </div>

                    <div class="field input-with-icon">
                        <img class="left-icon" src="../assets/img/email-icon.png" alt="email">
                        <input type="email" id="regEmail" name="email" placeholder="Email Address" required>
                    </div>

                    <div class="field input-with-icon">
                        <img class="left-icon" src="../assets/img/password-icon.png" alt="pass">
                        <input id="signupPassword" type="password" name="password" placeholder="Password" required>
                        <button type="button" class="toggle-visibility" data-target="signupPassword">
                            <img class="eye-icon" src="../assets/img/toggle-password.png" alt="show">
                        </button>
                    </div>

                    <div class="captcha-wrapper">
                        <div class="g-recaptcha" data-sitekey="6LedkzIsAAAAACz3Fe0q4XXEeT5gkaX91m9crDiQ" style="margin-top: 15px; transform:scale(0.85); transform-origin:0 0;"></div>
                    </div>

                    <div class="links">
                        <a class="link-login" href="#" id="showLogin">Already have an account?</a>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn">Sign Up</button>
                </div>
                <div id="signupMessage" style="color: red; text-align: center; margin-top: 10px;"></div>
            </form>
        </aside>
    </div>

    <div class="logo"></div>

    <script src="../styles/login.js"></script>
</body>
</html>