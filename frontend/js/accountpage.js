const form          = document.getElementById('profileForm');
const fields        = document.getElementById('profileFields');
const actions       = document.getElementById('formActions');
const confirmField  = document.getElementById('confirmField');
const editBtn       = document.getElementById('editBtn');
const cancelBtn     = document.getElementById('cancelBtn');
const togglePwBtn   = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');
const confirmInput  = document.getElementById('confirmPassword');
const displayName   = document.getElementById('displayName');
const saveMsg       = document.getElementById('saveMsg');

const MASK = '***************';

function setEditMode(on) {
    fields.disabled     = !on;
    actions.hidden      = !on;
    confirmField.hidden = !on;
}

editBtn.addEventListener('click', () => setEditMode(true));

cancelBtn.addEventListener('click', () => {
    form.reset();
    confirmInput.setCustomValidity('');
    setEditMode(false);
});

togglePwBtn.addEventListener('click', () => {
    passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
});

passwordInput.addEventListener('focus', () => {
    if (passwordInput.value === MASK) passwordInput.value = '';
});
passwordInput.addEventListener('blur', () => {
    if (passwordInput.value === '') passwordInput.value = MASK;
    checkPasswordMatch();
});

function checkPasswordMatch() {
    const changed = passwordInput.value !== MASK;
    confirmInput.setCustomValidity(
        !changed || passwordInput.value === confirmInput.value ? '' : 'Passwords do not match.'
    );
}
passwordInput.addEventListener('input', checkPasswordMatch);
confirmInput.addEventListener('input', checkPasswordMatch);

form.addEventListener('submit', (event) => {
    event.preventDefault();

    const first = document.getElementById('firstName').value.trim();
    const last  = document.getElementById('lastName').value.trim();
    displayName.textContent = `${first} ${last}`;

    form.querySelectorAll('input').forEach((input) => {
        if (input.type === 'password') return;
        input.defaultValue = input.value;
    });

    passwordInput.value = MASK;
    confirmInput.value  = '';
    confirmInput.setCustomValidity('');

    setEditMode(false);
    saveMsg.classList.remove('d-none');
    setTimeout(() => saveMsg.classList.add('d-none'), 3000);
});