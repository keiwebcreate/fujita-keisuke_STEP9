export function initializePurchaseForms() {
    document.querySelectorAll('[data-purchase-form]').forEach((form) => {
        const quantity = form.elements.namedItem('quantity');
        const modal = document.getElementById('purchaseConfirmModal');
        const open_button = form.querySelector('[data-purchase-open]');
        const confirm_button = modal.querySelector('[data-purchase-confirm]');
        let confirmed = false;
        let submitting = false;

        quantity.addEventListener('input', () => quantity.setCustomValidity(''));
        form.addEventListener('submit', (event) => {
            if (submitting) {
                event.preventDefault();
                return;
            }
            if (confirmed) {
                submitting = true;
                open_button.disabled = true;
                confirm_button.disabled = true;
                confirm_button.textContent = '購入処理中…';
                modal.querySelector('[data-modal-close]').disabled = true;
                return;
            }
            event.preventDefault();
            const value = Number(quantity.value);
            if (!/^[0-9]+$/.test(quantity.value) || !Number.isInteger(value) || value < 1 || value > 2147483647) {
                quantity.setCustomValidity('数量は1〜2147483647の半角数字の整数で入力してください。');
                quantity.reportValidity();
                return;
            }
            modal.querySelector('[data-purchase-quantity]').textContent = String(value);
            modal.showModal();
            document.body.classList.add('site-page--modal-open');
        });
        confirm_button.addEventListener('click', () => {
            if (submitting) {
                return;
            }
            confirmed = true;
            form.requestSubmit();
            confirmed = false;
        });
        window.addEventListener('pageshow', (event) => {
            if (event.persisted && submitting) {
                window.location.reload();
            }
        });
    });
}
