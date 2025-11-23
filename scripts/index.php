<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoStay - Authentication</title>
    <link rel="stylesheet" href="../styles/login.css">
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
            <!-- Login Form -->
            <form id="loginForm" class="auth-form active" method="post">
                <h3 class="login-heading">USER LOGIN</h3>

                <div class="form-body">
                    <div class="field input-with-icon">
                        <img class="left-icon" src="../assets/img/email-icon.png" alt="email icon">
                        <!-- Numele trebuie să fie "email" -->
                        <input type="text" name="email" placeholder="Email" required>
                    </div>

                    <div class="field input-with-icon">
                        <img class="left-icon" src="../assets/img/password-icon.png" alt="password icon">
                        <!-- Numele trebuie să fie "password" -->
                        <input id="loginPassword" type="password" name="password" placeholder="Password" required>
                        <button type="button" class="toggle-visibility" data-target="loginPassword">
                            <img class="eye-icon" src="../assets/img/toggle-password.png" alt="show/hide">
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
            </form>

            <!-- Signup Form -->
            <form id="signupForm" class="auth-form" action="../php/signup_process.php" method="POST" style="display: none;">
                <h3 class="login-heading">CREATE ACCOUNT</h3>
                <div class="form-body">
                    <div class="field input-with-icon">
                        <img class="left-icon" src="../assets/img/surname-icon.png" alt="first name icon">
                        <input type="text" name="first_name" placeholder="First Name" required>
                    </div>

                    <div class="field input-with-icon">
                        <img class="left-icon" src="../assets/img/name-icon.png" alt="last name icon">
                        <input type="text" name="last_name" placeholder="Last Name" required>
                    </div>

                    <div class="field input-with-icon">
                        <img class="left-icon" src="../assets/img/email-icon.png" alt="email icon">
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>

                    <div class="field input-with-icon">
                        <img class="left-icon" src="../assets/img/password-icon.png" alt="password icon">
                        <input id="signupPassword" type="password" name="password" placeholder="Password" required>
                        <button type="button" class="toggle-visibility" data-target="signupPassword">
                            <img class="eye-icon" src="../assets/img/toggle-password.png" alt="show/hide">
                        </button>
                    </div>

                    <div class="field input-with-icon">
                        <img class="left-icon" src="../assets/img/password-icon.png" alt="confirm password icon">
                        <input id="confirmPassword" type="password" name="confirm_password" placeholder="Confirm Password" required>
                        <button type="button" class="toggle-visibility" data-target="confirmPassword">
                            <img class="eye-icon" src="../assets/img/toggle-password.png" alt="show/hide">
                        </button>
                    </div>

                    <div class="links">
                        <a class="link-login" href="#" id="showLogin">Already have an account?</a>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn">Sign Up</button>
                </div>
            </form>
        </aside>
    </div>

    <div class="logo"></div>

    <script src="../styles/login.js"></script>
</body>

</html>