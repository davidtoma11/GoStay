<?php
session_start();
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
    <link rel="stylesheet" href="../../styles/login.css">
    
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
                
                <input type="hidden" id="csrf_token_input" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-body">
                    <div class="field input-with-icon">
                        <img class="left-icon" src="../../assets/img/email-icon.png" alt="email">
                        <input type="text" id="loginEmail" name="email" placeholder="Email" required>
                    </div>
                    <div class="field input-with-icon">
                        <img class="left-icon" src="../../assets/img/password-icon.png" alt="pass">
                        <input id="loginPassword" type="password" name="password" placeholder="Password" required>
                        <button type="button" class="toggle-visibility" data-target="loginPassword">
                            <img class="eye-icon" src="../../assets/img/toggle-password.png" alt="show">
                        </button>
                    </div>
                    <div class="links">
                        <a class="link-forgot" href="#" id="showForgot">Forgot password?</a>
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
                        <img class="left-icon" src="../../assets/img/surname-icon.png" alt="first">
                        <input type="text" id="regFirstName" name="first_name" placeholder="First Name" required>
                    </div>
                    <div class="field input-with-icon">
                        <img class="left-icon" src="../../assets/img/name-icon.png" alt="last">
                        <input type="text" id="regLastName" name="last_name" placeholder="Last Name" required>
                    </div>
                    <div class="field input-with-icon">
                        <img class="left-icon" src="../../assets/img/email-icon.png" alt="email">
                        <input type="email" id="regEmail" name="email" placeholder="Email Address" required>
                    </div>
                    
                    <div class="field input-with-icon">
                        <img class="left-icon" src="../../assets/img/password-icon.png" alt="pass">
                        <input id="signupPassword" type="password" name="password" placeholder="Password" required>
                        <button type="button" class="toggle-visibility" data-target="signupPassword">
                            <img class="eye-icon" src="../../assets/img/toggle-password.png" alt="show">
                        </button>
                    </div>

                    <div class="field input-with-icon">
                        <img class="left-icon" src="../../assets/img/password-icon.png" alt="pass">
                        <input id="signupConfirmPassword" type="password" name="confirm_password" placeholder="Confirm Password" required>
                    </div>

                    <div class="captcha-wrapper">
                        <div class="g-recaptcha" data-sitekey="6LedkzIsAAAAACz3Fe0q4XXEeT5gkaX91m9crDiQ" style="margin-top: 15px; transform:scale(0.85); transform-origin:0 0;"></div>
                    </div>
                    <div class="links">
                        <a class="link-login showLoginBtn" href="#">Already have an account?</a>
                    </div>
                </div>
                <div class="actions">
                    <button type="submit" class="btn">Sign Up</button>
                </div>
                <div id="signupMessage" style="color: red; text-align: center; margin-top: 10px;"></div>
            </form>

            <form id="signupVerifyForm" class="auth-form" style="display: none;">
                <h3 class="login-heading">VERIFY ACCOUNT</h3>
                <p style="text-align:center; color:#666; margin-bottom:0.5rem;">Check your email for the activation code.</p>
                
                <div style="text-align:center; font-weight:bold; color:var(--primary); margin-bottom:1rem; font-size:1.2rem;">
                    Time left: <span id="signupTimerDisplay">05:00</span>
                </div>

                <div class="form-body">
                    <div class="field input-with-icon">
                        <img class="left-icon" src="../../assets/img/password-icon.png" alt="code">
                        <input type="text" id="signupCodeInput" name="code" placeholder="Activation Code" required maxlength="6" style="letter-spacing: 5px; text-align: center; font-weight: bold;">
                    </div>
                </div>
                <div class="actions">
                    <button type="submit" class="btn">Activate Account</button>
                </div>
                <div id="signupVerifyMessage" style="color: red; text-align: center; margin-top: 10px;"></div>
            </form>

            <form id="forgotForm" class="auth-form" style="display: none;">
                <h3 class="login-heading">RESET PASSWORD</h3>
                <p style="text-align:center; color:#666; margin-bottom:1rem;">Step 1/3: Enter email to receive code.</p>
                <div class="form-body">
                    <div class="field input-with-icon">
                        <img class="left-icon" src="../../assets/img/email-icon.png" alt="email">
                        <input type="email" id="forgotEmail" name="email" placeholder="Your Email Address" required>
                    </div>
                    <div class="links">
                        <a href="#" class="showLoginBtn">Back to Login</a>
                    </div>
                </div>
                <div class="actions">
                    <button type="submit" class="btn">Send Code</button>
                </div>
                <div id="forgotMessage" style="color: red; text-align: center; margin-top: 10px;"></div>
            </form>

            <form id="verifyCodeForm" class="auth-form" style="display: none;">
                <h3 class="login-heading">VERIFY CODE</h3>
                <p style="text-align:center; color:#666; margin-bottom:0.5rem;">Step 2/3: Enter the 6-digit code.</p>
                
                <div style="text-align:center; font-weight:bold; color:var(--primary); margin-bottom:1rem; font-size:1.2rem;">
                    Time left: <span id="timerDisplay">05:00</span>
                </div>

                <div class="form-body">
                    <div class="field input-with-icon">
                        <img class="left-icon" src="../../assets/img/password-icon.png" alt="code">
                        <input type="text" id="verifyCodeInput" name="code" placeholder="Enter 6-Digit Code" required maxlength="6" style="letter-spacing: 5px; text-align: center; font-weight: bold;">
                    </div>
                    
                    <div class="links" style="justify-content: center;">
                        <a href="#" id="resendCodeBtn" style="color:var(--text-secondary); pointer-events: none; opacity: 0.5;">Resend Code</a>
                    </div>
                </div>
                <div class="actions">
                    <button type="submit" class="btn">Verify Code</button>
                </div>
                <div id="verifyMessage" style="color: red; text-align: center; margin-top: 10px;"></div>
            </form>

            <form id="newPasswordForm" class="auth-form" style="display: none;">
                <h3 class="login-heading">NEW PASSWORD</h3>
                <p style="text-align:center; color:#666; margin-bottom:1rem;">Step 3/3: Create a new password.</p>
                <div class="form-body">
                    <div class="field input-with-icon">
                        <img class="left-icon" src="../../assets/img/password-icon.png" alt="pass">
                        <input id="newPassInput" type="password" name="password" placeholder="New Password" required>
                        <button type="button" class="toggle-visibility" data-target="newPassInput">
                            <img class="eye-icon" src="../../assets/img/toggle-password.png" alt="show">
                        </button>
                    </div>

                    <div class="field input-with-icon">
                        <img class="left-icon" src="../../assets/img/password-icon.png" alt="pass">
                        <input id="confirmPassInput" type="password" name="confirm_password" placeholder="Confirm Password" required>
                    </div>
                </div>
                <div class="actions">
                    <button type="submit" class="btn">Set Password</button>
                </div>
                <div id="newPassMessage" style="color: red; text-align: center; margin-top: 10px;"></div>
            </form>

        </aside>
    </div>

    <div class="logo"></div>

    <script src="../../styles/login.js"></script>
</body>
</html>