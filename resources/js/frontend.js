// Interactions des formulaires publics, sans dependance aux plugins historiques.
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = document.getElementById(button.dataset.passwordToggle);
    if (!input) return;
    button.addEventListener('click', () => {
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? button.dataset.hideLabel : button.dataset.showLabel);
    });
});

const digits = [...document.querySelectorAll('[data-otp-digit]')];
digits.forEach((input, index) => {
    input.addEventListener('input', () => {
        input.value = input.value.replace(/\D/g, '').slice(-1);
        if (input.value) digits[index + 1]?.focus();
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Backspace' && !input.value) digits[index - 1]?.focus();
    });
    input.addEventListener('paste', (event) => {
        const code = event.clipboardData?.getData('text').replace(/\D/g, '');
        if (!code) return;
        event.preventDefault();
        [...code.slice(0, digits.length - index)].forEach((digit, offset) => {
            digits[index + offset].value = digit;
        });
        digits[Math.min(index + code.length, digits.length - 1)]?.focus();
    });
});

document.querySelectorAll('[data-password-confirmation]').forEach((form) => {
    const password = form.elements.namedItem('password');
    const confirmation = form.elements.namedItem('password_confirmation');
    const validate = () => confirmation.setCustomValidity(
        confirmation.value && confirmation.value !== password.value ? form.dataset.passwordConfirmation : ''
    );
    password.addEventListener('input', validate);
    confirmation.addEventListener('input', validate);
});
