document.addEventListener('DOMContentLoaded', function () {
    const inputs = document.querySelectorAll('[data-multiplier]');

    const validateInput = (input) => {
        const multiplier = Number(input.dataset.multiplier || 1);
        const value = Number(input.value || 0);
        if (value > 0 && value % multiplier !== 0) {
            input.setCustomValidity('Cantitatea trebuie să fie multiplu de ' + multiplier + '.');
        } else {
            input.setCustomValidity('');
        }
    };

    inputs.forEach((input) => {
        input.addEventListener('input', () => validateInput(input));
    });

    const form = document.getElementById('agent-order-form');
    if (form) {
        form.addEventListener('submit', function (event) {
            let valid = true;
            inputs.forEach((input) => {
                validateInput(input);
                if (!input.checkValidity()) {
                    valid = false;
                    input.focus();
                }
            });

            if (!valid) {
                event.preventDefault();
                alert('Există cantități invalide. Verifică multiplii ceruți.');
            }
        });
    }
});
