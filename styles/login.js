// Forms toogle
document.getElementById('showSignup').addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('loginForm').style.display = 'none';
    document.getElementById('signupForm').style.display = 'block';
});

document.getElementById('showLogin').addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('signupForm').style.display = 'none';
    document.getElementById('loginForm').style.display = 'block';
});

// Password visibility toggle
document.querySelectorAll('.toggle-visibility').forEach(button => {
    button.addEventListener('click', function () {
        const targetId = this.getAttribute('data-target');
        const pwd = document.getElementById(targetId);
        const eyeImg = this.querySelector('.eye-icon');

        if (pwd.type === 'password') {
            pwd.type = 'text';
            if (eyeImg) eyeImg.style.filter = 'brightness(0.8)';
        } else {
            pwd.type = 'password';
            if (eyeImg) eyeImg.style.filter = 'none';
        }
        pwd.focus();
    });
});