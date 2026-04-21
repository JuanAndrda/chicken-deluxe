<?php
/**
 * views/layouts/modal.php
 *
 * Reusable confirmation-modal markup. Included once in main.php so the
 * DOM contains a single overlay/modal that every showConfirmModal()
 * call in app.js drives. The title, message, button text and confirm
 * button colour are populated dynamically by JavaScript.
 */
?>
<div id="confirmOverlay" class="modal-overlay" aria-hidden="true">
    <div id="confirmModal"
         class="modal-box"
         role="dialog"
         aria-modal="true"
         aria-labelledby="modalTitle"
         aria-describedby="modalMessage">
        <button type="button" id="modalClose" class="modal-close" aria-label="Close">&times;</button>
        <h3 id="modalTitle" class="modal-title"></h3>
        <p  id="modalMessage" class="modal-message"></p>
        <div class="modal-actions">
            <button type="button" id="modalCancel"  class="modal-btn modal-btn-cancel">Cancel</button>
            <button type="button" id="modalConfirm" class="modal-btn modal-btn-confirm">Confirm</button>
        </div>
    </div>
</div>
