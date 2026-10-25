import { initializePurchaseForms } from './product_purchase';
import { initializeProductFavorites } from './product_favorites';
import { initializeProductSearch } from './product_search';

// スマートフォン用メニューを開閉します。Escapeキーや画面幅の変更でも閉じられます。
function initializeNavigation() {
    document.querySelectorAll('[data-navigation]').forEach((navigation) => {
        const toggle_button = navigation.querySelector('[data-navigation-toggle]');
        const navigation_panel = navigation.querySelector('[data-navigation-panel]');
        const closeNavigation = () => {
            navigation_panel.hidden = true;
            toggle_button.setAttribute('aria-expanded', 'false');
        };
        toggle_button.addEventListener('click', () => {
            navigation_panel.hidden = !navigation_panel.hidden;
            toggle_button.setAttribute('aria-expanded', String(!navigation_panel.hidden));
        });
        navigation.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !navigation_panel.hidden) {
                closeNavigation();
                toggle_button.focus();
            }
        });
        window.matchMedia('(min-width: 960px)').addEventListener('change', closeNavigation);
    });
}

// 利用者メニューを開閉します。外側のクリックやキーボード操作にも対応します。
function initializeDropdowns() {
    document.querySelectorAll('[data-dropdown]').forEach((dropdown, index) => {
        const trigger_button = dropdown.querySelector('[data-dropdown-trigger] button');
        const dropdown_panel = dropdown.querySelector('[data-dropdown-panel]');
        dropdown_panel.id = `accountDropdown${index}`;
        trigger_button.setAttribute('aria-controls', dropdown_panel.id);
        const closeDropdown = () => {
            dropdown_panel.hidden = true;
            trigger_button.setAttribute('aria-expanded', 'false');
        };
        trigger_button.addEventListener('click', () => {
            dropdown_panel.hidden = !dropdown_panel.hidden;
            trigger_button.setAttribute('aria-expanded', String(!dropdown_panel.hidden));
        });
        document.addEventListener('click', (event) => {
            if (!dropdown.contains(event.target)) {
                closeDropdown();
            }
        });
        dropdown.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !dropdown_panel.hidden) {
                closeDropdown();
                trigger_button.focus();
            }
        });
        dropdown.addEventListener('focusout', (event) => {
            if (!dropdown.contains(event.relatedTarget)) {
                closeDropdown();
            }
        });
    });
}

// 確認用の小窓（モーダル）を開閉します。開いている間は背景をスクロールさせません。
function initializeModals() {
    const updateModalState = () => {
        document.body.classList.toggle('site-page--modal-open', Boolean(document.querySelector('dialog[open]')));
    };
    document.querySelectorAll('[data-modal]').forEach((modal) => {
        modal.addEventListener('close', updateModalState);
        modal.addEventListener('click', (event) => {
            const bounds = modal.getBoundingClientRect();
            if (event.target === modal && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) {
                modal.close();
            }
        });
        if (modal.hasAttribute('data-modal-initial-open')) {
            modal.showModal();
        }
    });
    document.querySelectorAll('[data-modal-open]').forEach((trigger_button) => {
        const modal = document.getElementById(trigger_button.dataset.modalOpen);
        trigger_button.setAttribute('aria-controls', modal.id);
        trigger_button.setAttribute('aria-haspopup', 'dialog');
        trigger_button.addEventListener('click', () => {
            modal.showModal();
            updateModalState();
        });
    });
    document.querySelectorAll('[data-modal-close]').forEach((close_button) => {
        close_button.addEventListener('click', () => close_button.closest('dialog').close());
    });
    updateModalState();
}

// HTMLのdata-*属性を目印に、各画面にある部品へ操作を登録します。
// 対象の部品がない画面では、その部品の処理は動きません。
initializeNavigation();
initializeDropdowns();
initializeModals();
document.querySelectorAll('[data-auto-dismiss]').forEach((message) => {
    window.setTimeout(() => { message.hidden = true; }, Number(message.dataset.autoDismiss));
});

initializeProductSearch();

initializeProductFavorites();

initializePurchaseForms();
