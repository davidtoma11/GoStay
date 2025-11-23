// Handle form submissions
document.addEventListener('DOMContentLoaded', function() {
    // Login form
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitLoginForm(this);
    });

    // Signup form  
    document.getElementById('signupForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitSignupForm(this);
    });

    // Form toggle
    document.getElementById('showSignup').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('loginForm').style.display = 'none';
        document.getElementById('signupForm').style.display = 'block';
    });

    document.getElementById('showLogin').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('signupForm').style.display = 'none';
        document.getElementById('loginForm').style.display = 'block';
    });

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

    // Show loading
    submitBtn.textContent = 'Processing...';
    submitBtn.disabled = true;

    const formData = new FormData(form);
    const data = {
        email: formData.get('email'),
        password: formData.get('password')
    };

    console.log('Sending login data:', data); 

    fetch('auth/login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        console.log('Server response:', result); // Pentru debugging
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

    // Show loading
    submitBtn.textContent = 'Processing...';
    submitBtn.disabled = true;

    const formData = new FormData(form);
    const data = {
        first_name: formData.get('first_name'),
        last_name: formData.get('last_name'),
        email: formData.get('email'),
        password: formData.get('password')
    };

    console.log('Sending signup data:', data); // Pentru debugging

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

function showMessage(message, type) {
    // Remove old messages
    const oldMessages = document.querySelectorAll('.message');
    oldMessages.forEach(msg => msg.remove());

    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;

    // Insert message in form
    const form = document.querySelector('.auth-form[style*="display: block"]') || 
                 document.querySelector('.auth-form:not([style*="display: none"])');
    form.insertBefore(messageDiv, form.firstChild);

    // Auto remove after 5 seconds
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}