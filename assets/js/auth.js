document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('.auth-shell');
    if (!shell) {
        return;
    }

    const toSignupButtons = document.querySelectorAll('.js-switch-to-signup');
    const toLoginButtons = document.querySelectorAll('.js-switch-to-login');
    const loginForm = document.querySelector('.form-login');
    const signupForm = document.querySelector('.form-signup');
    const loginVisual = document.querySelector('.visual-state-login');
    const signupVisual = document.querySelector('.visual-state-signup');
    const loginImage = document.querySelector('.visual-image-login');
    const signupImage = document.querySelector('.visual-image-signup');

    const activateSignup = () => {
        shell.classList.add('is-signup');
        if (loginForm) loginForm.classList.remove('is-active');
        if (signupForm) signupForm.classList.add('is-active');
        if (loginVisual) loginVisual.classList.remove('is-active');
        if (signupVisual) signupVisual.classList.add('is-active');
        if (loginImage) loginImage.classList.remove('is-active');
        if (signupImage) signupImage.classList.add('is-active');
    };

    const activateLogin = () => {
        shell.classList.remove('is-signup');
        if (signupForm) signupForm.classList.remove('is-active');
        if (loginForm) loginForm.classList.add('is-active');
        if (signupVisual) signupVisual.classList.remove('is-active');
        if (loginVisual) loginVisual.classList.add('is-active');
        if (signupImage) signupImage.classList.remove('is-active');
        if (loginImage) loginImage.classList.add('is-active');
    };

    toSignupButtons.forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            activateSignup();
        });
    });

    toLoginButtons.forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            activateLogin();
        });
    });

    if (shell.classList.contains('is-signup')) {
        activateSignup();
    } else {
        activateLogin();
    }
});
