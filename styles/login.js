document.addEventListener('DOMContentLoaded', function() {
    console.log("Login script loaded.");

    // --- ELEMENT REFERENCES ---
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const forgotForm = document.getElementById('forgotForm');
    const verifyCodeForm = document.getElementById('verifyCodeForm');
    const newPasswordForm = document.getElementById('newPasswordForm');

    // Variables for Reset Flow
    let resetEmail = "";
    let resetCode = "";
    let countdown; 
    
    // --- TIMER: 5 MINUTE (300 secunde) ---
    const TIMER_DURATION = 300; 

    // --- HELPER: Hide All Forms ---
    function hideAllForms() {
        document.querySelectorAll('.auth-form').forEach(f => {
            f.style.display = 'none';
            f.classList.remove('active');
        });
        clearInterval(countdown);
    }

    // --- NAVIGATION HANDLERS ---

    // 1. Forgot Password Link
    const showForgotBtn = document.getElementById('showForgot');
    if(showForgotBtn) {
        showForgotBtn.addEventListener('click', function(e) {
            e.preventDefault();
            hideAllForms();
            if(forgotForm) {
                forgotForm.style.display = 'block';
                forgotForm.classList.add('active');
            }
        });
    }

    // 2. Sign Up Link
    const showSignupBtn = document.getElementById('showSignup');
    if(showSignupBtn) {
        showSignupBtn.addEventListener('click', function(e) {
            e.preventDefault();
            hideAllForms();
            if(signupForm) {
                signupForm.style.display = 'block';
                signupForm.classList.add('active');
            }
        });
    }

    // 3. Back to Login (REPARAT AICI)
    document.body.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('showLoginBtn')) {
            e.preventDefault();
            hideAllForms();
            if(loginForm) {
                loginForm.style.display = 'block';
                loginForm.classList.add('active');
            }
        } // Acolada de la IF se inchide aici
    }); // Paranteza de la Listener se inchide aici

    // --- TIMER LOGIC ---
    function startTimer(duration, display) {
        let timer = duration, minutes, seconds;
        const resendBtn = document.getElementById('resendCodeBtn');
        
        // Asiguram ca butonul e activ vizual
        if(resendBtn) {
            resendBtn.style.pointerEvents = "auto"; 
            resendBtn.style.opacity = "1";
            resendBtn.style.color = "#007bff"; 
            resendBtn.style.cursor = "pointer";
            resendBtn.textContent = "Resend Code";
        }

        clearInterval(countdown);
        
        // Functie interna pentru update vizual
        function updateDisplay() {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            if(display) display.textContent = minutes + ":" + seconds;
        }

        updateDisplay();

        countdown = setInterval(function () {
            if (--timer < 0) {
                clearInterval(countdown);
                if(display) display.textContent = "EXPIRED";
            } else {
                updateDisplay();
            }
        }, 1000);
    }

    // --- FORM SUBMISSIONS ---

    // 1. STEP 1: Send Email
    if(forgotForm) {
        forgotForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = document.getElementById('forgotEmail');
            resetEmail = emailInput.value;
            
            const btn = this.querySelector('.btn');
            const originalText = btn.textContent;
            btn.disabled = true; btn.textContent = "Sending...";

            fetch('auth/send_reset.php', { 
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: resetEmail })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // DEBUG POPUP
                    if(data.debug_code) {
                        alert("DEBUG: Codul este " + data.debug_code);
                        console.log("CODE:", data.debug_code);
                    }
                    
                    showMessage("Code sent!", "success");

                    setTimeout(() => {
                        hideAllForms();
                        if(verifyCodeForm) {
                            verifyCodeForm.style.display = 'block';
                            verifyCodeForm.classList.add('active');
                            const display = document.querySelector('#timerDisplay');
                            startTimer(TIMER_DURATION, display);
                        }
                    }, 1000);
                } else {
                    showMessage(data.message, "error");
                }
            })
            .catch(err => {
                console.error(err);
                showMessage("Network error.", "error");
            })
            .finally(() => {
                btn.disabled = false; btn.textContent = originalText;
            });
        });
    }

    // 2. RESEND CODE BUTTON HANDLER
    document.body.addEventListener('click', function(e) {
        if(e.target && e.target.id === 'resendCodeBtn') {
            e.preventDefault();
            console.log("Resend Button Clicked");

            const btn = e.target;
            // Nu il dezactivam complet, doar vizual cat timp incarca
            btn.style.pointerEvents = "none";
            btn.textContent = "Sending...";
            btn.style.opacity = "0.7";

            fetch('auth/send_reset.php', { 
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: resetEmail })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    if(data.debug_code) {
                         alert("DEBUG: Noul cod este " + data.debug_code);
                         console.log("NEW CODE:", data.debug_code);
                    }
                    
                    // --- RESETEAZA TIMER-UL ---
                    const display = document.querySelector('#timerDisplay');
                    startTimer(TIMER_DURATION, display); 
                    
                    showMessage("New code sent!", "success");
                } else {
                    showMessage(data.message, "error");
                }
            })
            .finally(() => {
                btn.style.pointerEvents = "auto";
                btn.textContent = "Resend Code";
                btn.style.opacity = "1";
            });
        }
    });

    // 3. STEP 2: Verify Code
    if(verifyCodeForm) {
        verifyCodeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const codeInput = document.getElementById('verifyCodeInput');
            resetCode = codeInput.value;
            
            const btn = this.querySelector('.btn');
            const originalText = btn.textContent;
            btn.disabled = true; btn.textContent = "Verifying...";

            fetch('auth/verify_code.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: resetEmail, code: resetCode })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    hideAllForms();
                    if(newPasswordForm) {
                        newPasswordForm.style.display = 'block';
                        newPasswordForm.classList.add('active');
                    }
                } else {
                    showMessage(data.message, "error");
                }
            })
            .catch(err => showMessage("Network error.", "error"))
            .finally(() => { btn.disabled = false; btn.textContent = originalText; });
        });
    }

    // 4. STEP 3: Set New Password
    if(newPasswordForm) {
        newPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const p1 = document.getElementById('newPassInput').value;
            const p2 = document.getElementById('confirmPassInput').value;

            if(p1 !== p2) {
                showMessage("Passwords do not match!", "error");
                return;
            }
            if(p1.length < 6) {
                showMessage("Password too short (min 6 chars)", "error");
                return;
            }

            const btn = this.querySelector('.btn');
            const originalText = btn.textContent;
            btn.disabled = true; btn.textContent = "Updating...";

            fetch('auth/reset_confirm.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: resetEmail, code: resetCode, password: p1 })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, "success");
                    setTimeout(() => {
                        hideAllForms();
                        if(loginForm) {
                            loginForm.style.display = 'block';
                            loginForm.classList.add('active');
                        }
                    }, 2000);
                } else {
                    showMessage(data.message, "error");
                }
            })
            .catch(err => showMessage("Network error.", "error"))
            .finally(() => { btn.disabled = false; btn.textContent = originalText; });
        });
    }

    // --- LOGIN & SIGNUP HANDLERS ---
    if(loginForm) loginForm.addEventListener('submit', e => { e.preventDefault(); submitLoginForm(loginForm); });
    if(signupForm) signupForm.addEventListener('submit', e => { e.preventDefault(); submitSignupForm(signupForm); });

    // Toggle Visibility
    document.querySelectorAll('.toggle-visibility').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const pwd = document.getElementById(targetId);
            if(pwd) pwd.type = pwd.type === 'password' ? 'text' : 'password';
        });
    });
});

// --- HELPER FUNCTIONS ---

function submitLoginForm(form) {
    const submitBtn = form.querySelector('.btn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Processing...'; submitBtn.disabled = true;

    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const token = (typeof csrfToken !== 'undefined') ? csrfToken : '';

    fetch('auth/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password, csrf_token: token })
    })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            showMessage(res.message, 'success');
            if(res.redirect) setTimeout(() => window.location.href = res.redirect, 1000);
        } else showMessage(res.message, 'error');
    })
    .catch(() => showMessage('Network error', 'error'))
    .finally(() => { submitBtn.textContent = originalText; submitBtn.disabled = false; });
}

function submitSignupForm(form) {
    const submitBtn = form.querySelector('.btn');
    const originalText = submitBtn.textContent;

    const p1 = document.getElementById('signupPassword').value;
    const p2 = document.getElementById('signupConfirmPassword').value;

    if (p1 !== p2) {
        showMessage("Passwords do not match!", "error");
        return;
    }

    submitBtn.textContent = 'Processing...'; submitBtn.disabled = true;

    const data = {
        first_name: document.getElementById('regFirstName').value,
        last_name: document.getElementById('regLastName').value,
        email: document.getElementById('regEmail').value,
        password: p1,
        csrf_token: (typeof csrfToken !== 'undefined') ? csrfToken : '',
        recaptcha_response: (typeof grecaptcha !== 'undefined') ? grecaptcha.getResponse() : ''
    };

    fetch('auth/signup.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            showMessage(res.message, 'success');
            form.reset();
            try { grecaptcha.reset(); } catch(e){} 
            setTimeout(() => { document.querySelector('.showLoginBtn').click(); }, 2000);
        } else {
            showMessage(res.message, 'error');
            try { grecaptcha.reset(); } catch(e){}
        }
    })
    .catch(() => showMessage('Network error', 'error'))
    .finally(() => { submitBtn.textContent = originalText; submitBtn.disabled = false; });
}

function showMessage(message, type) {
    const old = document.querySelectorAll('.message');
    old.forEach(m => m.remove());

    const div = document.createElement('div');
    div.className = `message ${type}`;
    div.textContent = message;
    div.style.cssText = `padding:10px; margin-bottom:10px; border-radius:4px; text-align:center; 
        background:${type === 'success' ? '#d4edda' : '#f8d7da'}; 
        color:${type === 'success' ? '#155724' : '#721c24'}; border:1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'};`;

    const form = document.querySelector('.auth-form[style*="display: block"]') || document.querySelector('.auth-form.active') || document.getElementById('loginForm');
    if(form) form.prepend(div);
    setTimeout(() => div.remove(), 5000);
}