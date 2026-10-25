// ハートを押すとサーバーへ登録・解除を依頼し、成功が確認できてから表示を変えます。
export function initializeProductFavorites() {
    document.querySelectorAll('[data-product-favorite]').forEach((favorite) => {
        const button = favorite.querySelector('[data-favorite-button]');
        const status = favorite.querySelector('[data-favorite-status]');
        const error_message = favorite.querySelector('[data-favorite-error]');
        // 通信中の連打を止め、登録と解除の依頼が重ならないようにします。
        let processing = false;
        button.addEventListener('click', async () => {
            if (processing) {
                return;
            }
            const liked = button.getAttribute('aria-pressed') === 'true';
            const restore_focus = document.activeElement === button;
            processing = true;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            status.textContent = 'お気に入りを更新中です。';
            error_message.hidden = true;
            error_message.textContent = '';
            try {
                const response = await fetch(button.dataset.favoriteUrl, {
                    method: liked ? 'DELETE' : 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        // CSRFトークンは、この画面からの送信かサーバーが確かめるための合言葉です。
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                if (response.status === 401 || response.status === 419 || response.redirected) {
                    throw new Error('ログイン状態を確認できません。画面を再読み込みしてログインしてください。');
                }
                if (response.status === 404) {
                    throw new Error('この商品は削除されたか、存在しません。商品一覧へ戻ってください。');
                }
                if (!response.ok) {
                    throw new Error('お気に入りを更新できませんでした。画面を再読み込みして状態を確認し、再度お試しください。');
                }
                const data = await response.json();
                if (typeof data.liked !== 'boolean' || data.liked === liked) {
                    throw new Error('更新結果を確認できませんでした。画面を再読み込みしてください。');
                }
                button.setAttribute('aria-pressed', String(data.liked));
                button.setAttribute('aria-label', data.liked ? 'お気に入りを解除' : 'お気に入りに登録');
                button.classList.toggle('favorite-button--active', data.liked);
            } catch (error) {
                error_message.textContent = error instanceof TypeError || error instanceof SyntaxError
                    ? '通信結果を確認できませんでした。画面を再読み込みして状態を確認し、再度お試しください。'
                    : error.message;
                error_message.hidden = false;
            // 成功でも失敗でも最後に操作可能へ戻し、キーボード操作中ならフォーカスも戻します。
            } finally {
                status.textContent = button.getAttribute('aria-pressed') === 'true' ? 'お気に入り登録済み' : 'お気に入り未登録';
                button.disabled = false;
                if (restore_focus && document.activeElement === document.body) {
                    button.focus({ preventScroll: true });
                }
                button.setAttribute('aria-busy', 'false');
                processing = false;
            }
        });
    });
}
