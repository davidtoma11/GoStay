document.addEventListener('DOMContentLoaded', function() {
    console.log("Auth script loaded.");

    // --- ELEMENT REFERENCES ---
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const signupVerifyForm = document.getElementById('signupVerifyForm');
    
    const forgotForm = document.getElementById('forgotForm');
    const verifyCodeForm = document.getElementById('verifyCodeForm');
    const newPasswordForm = document.getElementById('newPasswordForm');

    // --- VARIABLES ---
    let resetEmail = "";
    let resetCode = "";
    let countdown; 
    const TIMER_DURATION = 300; 

    // --- HELPERS ---
    function hideAllForms() {
        document.querySelectorAll('.auth-form').forEach(f => {
            f.style.display = 'none';
            f.classList.remove('active');
        });
        clearInterval(countdown);
    }

    function showMessage(message, type) {
        document.querySelectorAll('.message').forEach(m => m.remove());
        const div = document.createElement('div');
        div.className = `message ${type}`;
        div.textContent = message;
        div.style.cssText = `padding:10px; margin-bottom:10px; border-radius:4px; text-align:center; 
            background:${type === 'success' ? '#d4edda' : '#f8d7da'}; 
            color:${type === 'success' ? '#155724' : '#721c24'}; 
            border:1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'};`;
        
        const form = document.querySelector('.auth-form[style*="display: block"]') || document.getElementById('loginForm');
        if(form) form.prepend(div);
        setTimeout(() => div.remove(), 5000);
    }

    function startTimer(duration, display, resendBtnId = null) {
        let timer = duration, minutes, seconds;
        const resendBtn = resendBtnId ? document.getElementById(resendBtnId) : null;
        
        if(resendBtn) {
            resendBtn.style.pointerEvents = "auto";
            resendBtn.textContent = "Resend Code";
            resendBtn.style.opacity = "1";
        }

        clearInterval(countdown);
        
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

    // --- NAVIGATION ---
    document.getElementById('showForgot')?.addEventListener('click', e => { e.preventDefault(); hideAllForms(); forgotForm.style.display='block'; });
    document.getElementById('showSignup')?.addEventListener('click', e => { e.preventDefault(); hideAllForms(); signupForm.style.display='block'; });
    
    document.body.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('showLoginBtn')) {
            e.preventDefault(); hideAllForms(); if(loginForm) loginForm.style.display='block';
        }
    });

    // --- FORM HANDLERS ---

    // 1. LOGIN
    if(loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('.btn');
            const original = btn.textContent;
            btn.textContent = 'Processing...'; btn.disabled = true;

            const csrfTokenInput = document.getElementById('csrf_token_input');
            const csrfTokenValue = csrfTokenInput ? csrfTokenInput.value : '';

            // PATH FIX: ../auth/login.php
            fetch('../auth/login.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    email: document.getElementById('loginEmail').value, 
                    password: document.getElementById('loginPassword').value,
                    csrf_token: csrfTokenValue 
                })
            })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    showMessage(res.message, 'success');
                    if(res.redirect) setTimeout(() => window.location.href = res.redirect, 1000);
                } else showMessage(res.message, 'error');
            })
            .catch(err => { console.error(err); showMessage('Network error', 'error'); })
            .finally(() => { btn.textContent = original; btn.disabled = false; });
        });
    }

    // 2. SIGNUP (Step 1: Save to Session)
    if(signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('.btn');
            const original = btn.textContent;
            
            const p1 = document.getElementById('signupPassword').value;
            const p2 = document.getElementById('signupConfirmPassword').value;
            if (p1 !== p2) { showMessage("Passwords do not match!", "error"); return; }

            btn.textContent = 'Sending Code...'; btn.disabled = true;

            const data = {
                action: 'signup',
                first_name: document.getElementById('regFirstName').value,
                last_name: document.getElementById('regLastName').value,
                email: document.getElementById('regEmail').value,
                password: p1
            };

            // PATH FIX: ../auth/signup.php
            fetch('../auth/signup.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    showMessage(res.message, 'success');
                    if(res.debug_code) console.log("Code:", res.debug_code);
                    
                    setTimeout(() => {
                        hideAllForms();
                        if(signupVerifyForm) {
                            signupVerifyForm.style.display = 'block';
                            const display = document.getElementById('signupTimerDisplay');
                            if(display) startTimer(TIMER_DURATION, display);
                        }
                    }, 1000);
                } else {
                    showMessage(res.message, 'error');
                }
            })
            .catch(err => {
                console.error("Signup Error:", err);
                showMessage('Network error. Check console.', 'error');
            })
            .finally(() => { btn.textContent = original; btn.disabled = false; });
        });
    }

    // 3. SIGNUP VERIFY (Step 2: Check Code & Insert DB)
    if(signupVerifyForm) {
        signupVerifyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const code = document.getElementById('signupCodeInput').value;
            const btn = this.querySelector('.btn');
            btn.disabled = true; btn.textContent = "Activating...";

            // PATH FIX: ../auth/signup.php
            fetch('../auth/signup.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'verify', 
                    code: code 
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, "success");
                    setTimeout(() => { hideAllForms(); loginForm.style.display = 'block'; }, 2000);
                } else {
                    showMessage(data.message, "error");
                }
            })
            .catch(err => {
                console.error("Verify Error:", err);
                showMessage('Network error. Check console.', 'error');
            })
            .finally(() => { btn.disabled = false; btn.textContent = "Activate Account"; });
        });
    }

    // --- FORGOT PASSWORD FLOW ---
    if(forgotForm) {
        forgotForm.addEventListener('submit', function(e) {
            e.preventDefault();
            resetEmail = document.getElementById('forgotEmail').value;
            const btn = this.querySelector('.btn');
            btn.disabled = true; btn.textContent = "Sending...";

            // PATH FIX: ../auth/send_reset.php
            fetch('../auth/send_reset.php', { 
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: resetEmail })
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    showMessage("Code sent!", "success");
                    setTimeout(() => {
                        hideAllForms();
                        if(verifyCodeForm) {
                            verifyCodeForm.style.display = 'block';
                            startTimer(TIMER_DURATION, document.querySelector('#timerDisplay'), 'resendCodeBtn');
                        }
                    }, 1000);
                } else showMessage(d.message, "error");
            })
            .finally(() => { btn.disabled = false; btn.textContent = "Send Reset Code"; });
        });
    }

    // Verify Reset Code
    if(verifyCodeForm) {
        verifyCodeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            resetCode = document.getElementById('verifyCodeInput').value;
            const btn = this.querySelector('.btn');
            btn.disabled = true; 

            // PATH FIX: ../auth/verify_code.php
            fetch('../auth/verify_code.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: resetEmail, code: resetCode })
            })
            .then(r => r.json())
            .then(d => {
                if(d.success) { hideAllForms(); newPasswordForm.style.display='block'; }
                else showMessage(d.message, 'error');
            })
            .finally(() => { btn.disabled = false; });
        });
    }

    // New Password
    if(newPasswordForm) {
        newPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const p1 = document.getElementById('newPassInput').value;
            const p2 = document.getElementById('confirmPassInput').value;
            if(p1!==p2) return showMessage("Passwords do not match", "error");

            // PATH FIX: ../auth/reset_confirm.php
            fetch('../auth/reset_confirm.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: resetEmail, code: resetCode, password: p1 })
            }).then(r=>r.json()).then(d=>{
                if(d.success) { showMessage("Success!", "success"); setTimeout(()=>{ hideAllForms(); loginForm.style.display='block'; }, 2000); }
                else showMessage(d.message, 'error');
            });
        });
    }

    // Resend Button
    document.body.addEventListener('click', function(e) {
        if(e.target && e.target.id === 'resendCodeBtn') {
            e.preventDefault();
            const btn = e.target;
            btn.textContent = "Sending..."; btn.style.pointerEvents = "none";
            
            // PATH FIX: ../auth/send_reset.php
            fetch('../auth/send_reset.php', { 
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: resetEmail })
            }).then(r=>r.json()).then(d=>{
                if(d.success) {
                    startTimer(TIMER_DURATION, document.querySelector('#timerDisplay')); 
                    showMessage("New code sent!", "success");
                } else showMessage(d.message, "error");
            }).finally(() => {
                btn.style.pointerEvents = "auto"; btn.textContent = "Resend Code";
            });
        }
    });

    // Toggle Password Visibility
    document.querySelectorAll('.toggle-visibility').forEach(b => {
        b.addEventListener('click', function() {
            const i = document.getElementById(this.getAttribute('data-target'));
            if(i) i.type = i.type === 'password' ? 'text' : 'password';
        });
    });
});