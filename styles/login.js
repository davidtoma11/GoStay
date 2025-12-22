// Handle form submissions
document.addEventListener('DOMContentLoaded', function() {
    
    // Login form listener
    const loginForm = document.getElementById('loginForm');
    if(loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitLoginForm(this);
        });
    }

    // Signup form listener
    const signupForm = document.getElementById('signupForm');
    if(signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitSignupForm(this);
        });
    }

    // Form toggle: Show Signup
    const showSignup = document.getElementById('showSignup');
    if(showSignup) {
        showSignup.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('signupForm').style.display = 'block';
        });
    }

    // Form toggle: Show Login
    const showLogin = document.getElementById('showLogin');
    if(showLogin) {
        showLogin.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('signupForm').style.display = 'none';
            document.getElementById('loginForm').style.display = 'block';
        });
    }

    // Password visibility toggle
    document.querySelectorAll('.toggle-visibility').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const pwd = document.getElementById(targetId);
            const eyeImg = this.querySelector('.eye-icon');
            
            if(pwd.type === 'password'){
                pwd.type = 'text';
                if(eyeImg) eyeImg.style.filter = 'brightness(0.8)';
            } else {
                pwd.type = 'password';
                if(eyeImg) eyeImg.style.filter = 'none';
            }
        });
    });
});

function submitLoginForm(form) {
    const submitBtn = form.querySelector('.btn');
    const originalText = submitBtn.textContent;

    // Show loading state
    submitBtn.textContent = 'Processing...';
    submitBtn.disabled = true;

    // 1. Preluăm valorile explicit după ID
    const emailInput = document.getElementById('loginEmail');
    const passInput = document.getElementById('loginPassword');

    const emailVal = emailInput ? emailInput.value : '';
    const passwordVal = passInput ? passInput.value : '';
    
    // DEBUG: Vezi în consolă (F12) ce valori se citesc
    console.log("Valoare Email citită:", emailVal);
    console.log("Valoare Parolă citită:", passwordVal);

    // Check if csrfToken exists
    const token = (typeof csrfToken !== 'undefined') ? csrfToken : '';

    const data = {
        email: emailVal,
        password: passwordVal,
        csrf_token: token
    };

    fetch('auth/login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        console.log('Server response:', result);
        if (result.success) {
            showMessage(result.message, 'success');
            if (result.redirect) {
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 1000);
            }
        } else {
            showMessage(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Network error. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function submitSignupForm(form) {
    const submitBtn = form.querySelector('.btn');
    const originalText = submitBtn.textContent;

    // Show loading state
    submitBtn.textContent = 'Processing...';
    submitBtn.disabled = true;

    // CRITICAL FIX: Retrieve values by ID to avoid "all fields required" error
    const firstNameVal = document.getElementById('regFirstName').value;
    const lastNameVal = document.getElementById('regLastName').value;
    const emailVal = document.getElementById('regEmail').value;
    const passwordVal = document.getElementById('signupPassword').value;

    // Get reCAPTCHA response and CSRF Token
    const token = (typeof csrfToken !== 'undefined') ? csrfToken : '';
    let recaptchaResponse = '';
    
    try {
        recaptchaResponse = grecaptcha.getResponse();
    } catch (e) {
        console.warn("reCAPTCHA not loaded or error:", e);
    }

    const data = {
        first_name: firstNameVal,
        last_name: lastNameVal,
        email: emailVal,
        password: passwordVal,
        csrf_token: token,
        recaptcha_response: recaptchaResponse
    };

    console.log('Sending signup data:', data);

    fetch('auth/signup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        console.log('Server response:', result);
        if (result.success) {
            showMessage(result.message, 'success');
            
            // Reset form on success
            form.reset();
            try { grecaptcha.reset(); } catch(e){} 

            if (result.redirect) {
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 1000);
            } else {
                setTimeout(() => {
                    document.getElementById('showLogin').click();
                }, 2000);
            }
        } else {
            showMessage(result.message, 'error');
            try { grecaptcha.reset(); } catch(e){}
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Network error. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function showMessage(message, type) {
    // Remove old messages
    const oldMessages = document.querySelectorAll('.message');
    oldMessages.forEach(msg => msg.remove());

    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    
    // Quick styling
    messageDiv.style.padding = '10px';
    messageDiv.style.marginBottom = '10px';
    messageDiv.style.borderRadius = '4px';
    messageDiv.style.textAlign = 'center';
    
    if (type === 'success') {
        messageDiv.style.backgroundColor = '#d4edda';
        messageDiv.style.color = '#155724';
        messageDiv.style.border = '1px solid #c3e6cb';
    } else {
        messageDiv.style.backgroundColor = '#f8d7da';
        messageDiv.style.color = '#721c24';
        messageDiv.style.border = '1px solid #f5c6cb';
    }

    // Insert message in form
    const form = document.querySelector('.auth-form[style*="display: block"]') || 
                 document.querySelector('.auth-form:not([style*="display: none"])');
    
    if(form) {
        const heading = form.querySelector('.login-heading');
        if(heading) {
            heading.parentNode.insertBefore(messageDiv, heading.nextSibling);
        } else {
            form.insertBefore(messageDiv, form.firstChild);
        }
    }

    // Auto remove after 5 seconds
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}