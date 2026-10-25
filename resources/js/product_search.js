export function initializeProductSearch() {
    document.querySelectorAll('[data-product-search]').forEach((form) => {
        const results = document.querySelector('[data-search-results]');
        const status = document.querySelector('[data-search-status]');
        const error_message = document.querySelector('[data-search-error]');
        const button = form.querySelector('button[type="submit"]');
        let controller;
        let request_number = 0;

        const setBusy = (busy) => {
            results.setAttribute('aria-busy', String(busy));
            button.textContent = busy ? '検索中…' : '検索';
        };
        const clearErrors = () => {
            form.querySelectorAll('[data-search-errors]').forEach((list) => list.replaceChildren());
            form.querySelectorAll('[aria-invalid]').forEach((input) => input.removeAttribute('aria-invalid'));
            error_message.hidden = true;
            error_message.textContent = '';
        };
        form.addEventListener('input', () => {
            request_number += 1;
            controller?.abort();
            setBusy(false);
            clearErrors();
            status.textContent = '条件を変更しました。検索ボタンを押してください。';
        });
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const current_request = ++request_number;
            controller?.abort();
            controller = new AbortController();
            clearErrors();
            setBusy(true);
            status.textContent = '検索中です。';
            const url = new URL(form.action);
            url.search = new URLSearchParams(new FormData(form)).toString();
            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                if (current_request !== request_number) {
                    return;
                }
                if (response.status === 401 || response.redirected) {
                    throw new Error('ログイン状態を確認できません。画面を再読み込みしてログインしてください。');
                }
                if (response.status === 422) {
                    const data = await response.json();
                    if (current_request !== request_number) {
                        return;
                    }
                    form.querySelectorAll('[data-search-errors]').forEach((list) => {
                        const messages = data.errors?.[list.dataset.searchErrors] ?? [];
                        for (const message of messages) {
                            const item = document.createElement('li');
                            item.textContent = message;
                            list.append(item);
                        }
                        if (messages.length) {
                            form.elements.namedItem(list.dataset.searchErrors)?.setAttribute('aria-invalid', 'true');
                        }
                    });
                    status.textContent = '入力内容を確認してください。前回の検索結果を表示しています。';
                    form.querySelector('[aria-invalid="true"]')?.focus();
                    return;
                }
                if (!response.ok) {
                    throw new Error('検索に失敗しました。通信状態を確認し、再度お試しください。');
                }
                const data = await response.json();
                if (current_request !== request_number) {
                    return;
                }
                if (typeof data.html !== 'string' || !Number.isInteger(data.count)) {
                    throw new Error('検索結果を取得できませんでした。再度お試しください。');
                }
                results.innerHTML = data.html;
                status.textContent = `${data.count}件の商品が見つかりました。`;
            } catch (error) {
                if (current_request !== request_number || error.name === 'AbortError') {
                    return;
                }
                error_message.textContent = error instanceof TypeError
                    ? '検索に失敗しました。通信状態を確認し、再度お試しください。'
                    : error.message;
                error_message.hidden = false;
                status.textContent = '前回の検索結果を表示しています。';
            } finally {
                if (current_request === request_number) {
                    setBusy(false);
                }
            }
        });
    });
}
