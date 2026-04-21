/**
 * Chicken Deluxe — Main JavaScript
 */

/* ------------------------------------------------------------------
 * showConfirmModal(options)
 *
 * Drop-in replacement for native window.confirm(). Drives the single
 * #confirmOverlay markup defined in views/layouts/modal.php.
 *
 * options:
 *   title       (string)  — modal heading
 *   message     (string)  — modal body text
 *   confirmText (string)  — label on the confirm button
 *   type        (string)  — 'delete' | 'unlock' | 'lock' | 'submit'
 *                           controls the confirm button colour
 *   onConfirm   (func)    — callback fired when the user confirms
 * ----------------------------------------------------------------*/
const CONFIRM_COLORS = {
    delete: '#E53935', // red
    unlock: '#F57C00', // orange
    lock:   '#546E7A', // blue-gray
    submit: '#1565C0'  // blue
};

function showConfirmModal(options) {
    const overlay     = document.getElementById('confirmOverlay');
    const titleEl     = document.getElementById('modalTitle');
    const messageEl   = document.getElementById('modalMessage');
    const confirmBtn  = document.getElementById('modalConfirm');
    const cancelBtn   = document.getElementById('modalCancel');
    const closeBtn    = document.getElementById('modalClose');

    if (!overlay) {
        // Modal partial not on this page — fall back to native confirm
        if (window.confirm(options.message || 'Are you sure?')) {
            if (typeof options.onConfirm === 'function') options.onConfirm();
        }
        return;
    }

    // Populate
    titleEl.textContent    = options.title       || 'Confirm';
    messageEl.textContent  = options.message     || 'Are you sure?';
    confirmBtn.textContent = options.confirmText || 'Confirm';
    confirmBtn.style.backgroundColor = CONFIRM_COLORS[options.type] || CONFIRM_COLORS.submit;

    // Show
    overlay.classList.add('modal-overlay-visible');
    overlay.setAttribute('aria-hidden', 'false');

    // Close helper — also tears down its own listeners
    const closeModal = () => {
        overlay.classList.remove('modal-overlay-visible');
        overlay.setAttribute('aria-hidden', 'true');
        confirmBtn.removeEventListener('click', handleConfirm);
        cancelBtn.removeEventListener('click', closeModal);
        closeBtn.removeEventListener('click', closeModal);
        overlay.removeEventListener('click', handleBackdrop);
        document.removeEventListener('keydown', handleEscape);
    };

    const handleConfirm = () => {
        closeModal();
        if (typeof options.onConfirm === 'function') options.onConfirm();
    };

    const handleBackdrop = (e) => {
        if (e.target === overlay) closeModal();
    };

    const handleEscape = (e) => {
        if (e.key === 'Escape') closeModal();
    };

    confirmBtn.addEventListener('click', handleConfirm);
    cancelBtn.addEventListener('click', closeModal);
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', handleBackdrop);
    document.addEventListener('keydown', handleEscape);
}

// Sidebar toggle for mobile
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 &&
                !sidebar.contains(e.target) &&
                !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
});
