// The .esm build ships without inline CSS; our themed CSS is imported in app.css.
import Swal from 'sweetalert2/dist/sweetalert2.esm.js';

// SweetAlert2 styled with the app's own button and card classes.
const popupClasses = {
    popup: 'swal-card',
    title: 'swal-title',
    htmlContainer: 'swal-text',
    actions: 'swal-actions',
    confirmButton: 'btn btn-primary',
    cancelButton: 'btn btn-secondary',
    icon: 'swal-icon',
    timerProgressBar: 'swal-progress',
};

// SweetAlert replaces customClass instead of merging it, so pass overrides through here.
const classes = (overrides = {}) => ({ ...popupClasses, ...overrides });

export const Alert = Swal.mixin({
    // Keep full-height layouts (login page, sidebar) from collapsing while a popup is open.
    heightAuto: false,
    buttonsStyling: false,
    reverseButtons: true,
    showClass: { popup: 'swal-show' },
    hideClass: { popup: 'swal-hide' },
    customClass: classes(),
});

export const Toast = Swal.mixin({
    heightAuto: false,
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    customClass: { popup: 'swal-toast', title: 'swal-toast-title', timerProgressBar: 'swal-progress' },
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

/**
 * Forms with data-confirm ask before submitting. Optional attributes:
 * data-confirm-title, data-confirm-button, data-confirm-icon (warning|question|info),
 * data-confirm-tone="danger" for a red destructive button.
 */
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!form.dataset.confirm || form.dataset.confirmed) return;

    event.preventDefault();

    const danger = form.dataset.confirmTone === 'danger';
    const { isConfirmed } = await Alert.fire({
        icon: form.dataset.confirmIcon || (danger ? 'warning' : 'question'),
        title: form.dataset.confirmTitle || 'Are you sure?',
        text: form.dataset.confirm,
        showCancelButton: true,
        confirmButtonText: form.dataset.confirmButton || 'Yes, continue',
        cancelButtonText: 'Cancel',
        focusCancel: danger,
        customClass: classes(danger ? { confirmButton: 'btn btn-danger-solid' } : {}),
    });

    if (isConfirmed) {
        form.dataset.confirmed = '1';
        form.querySelectorAll('button[type="submit"]').forEach((b) => { b.disabled = true; });
        form.submit();
    }
});

// Messages flashed by the server on the previous request.
const flashEl = document.getElementById('flash-data');
if (flashEl) {
    const flash = JSON.parse(flashEl.textContent);

    if (flash.success) {
        Alert.fire({
            icon: 'success',
            title: flash.title || 'Success',
            text: flash.success,
            confirmButtonText: 'OK',
            timer: 4000,
            timerProgressBar: true,
        });
    } else if (flash.toast) {
        Toast.fire({ icon: 'success', title: flash.toast });
    }

    if (flash.errors) {
        Toast.fire({
            icon: 'error',
            title: flash.error || `Please check the ${flash.errors} highlighted fields.`,
        });
    }
}
