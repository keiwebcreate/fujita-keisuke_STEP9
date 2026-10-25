// 数量入力 → 確認の小窓 → 確定送信の順に進めます。途中のキャンセルでは購入しません。
export function initializePurchaseForms() {
    document.querySelectorAll('[data-purchase-form]').forEach((form) => {
        const quantity = form.elements.namedItem('quantity');
        const modal = document.getElementById('purchaseConfirmModal');
        const open_button = form.querySelector('[data-purchase-open]');
        const confirm_button = modal.querySelector('[data-purchase-confirm]');
        // confirmedは確認済み、submittingは送信開始済みを覚える目印です。
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
            // 確認済みの状態でもう一度送信処理へ進みます。今度は小窓を出さずサーバーへ送ります。
            form.requestSubmit();
            confirmed = false;
        });
        // ブラウザーの戻る操作で送信中の古い画面が復元されたら、最新状態を読み直します。
        window.addEventListener('pageshow', (event) => {
            if (event.persisted && submitting) {
                window.location.reload();
            }
        });
    });
}
