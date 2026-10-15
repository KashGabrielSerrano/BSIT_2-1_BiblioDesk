const passwordInput = document.getElementById('password');
const toggleButton = document.getElementById('togglePassword');
const toggleIcon = document.getElementById('toggleIcon');

toggleButton.addEventListener('click', function () {
    const isHidden = passwordInput.type === 'password';
    passwordInput.type = isHidden ? 'text' : 'password';
    toggleIcon.classList.toggle('bi-eye');
    toggleIcon.classList.toggle('bi-eye-slash');
});
