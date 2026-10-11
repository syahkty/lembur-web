import Swal from 'sweetalert2';

/**
 * Tema SweetAlert SILEKA — warna hijau sawit, font mengikuti aplikasi.
 */
const base = Swal.mixin({
    buttonsStyling: false,
    reverseButtons: true,
    customClass: {
        popup: 'swal-sileka-popup',
        title: 'swal-sileka-title',
        htmlContainer: 'swal-sileka-text',
        confirmButton: 'swal-sileka-btn swal-sileka-btn-confirm',
        cancelButton: 'swal-sileka-btn swal-sileka-btn-cancel',
        denyButton: 'swal-sileka-btn swal-sileka-btn-danger',
        input: 'swal-sileka-input',
    },
});

export const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    customClass: { popup: 'swal-sileka-toast' },
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

export function toastSuccess(message) {
    return Toast.fire({ icon: 'success', title: message });
}

export function toastError(message) {
    return Toast.fire({ icon: 'error', title: message });
}

export function alertSuccess(title, text = '') {
    return base.fire({ icon: 'success', title, text, confirmButtonText: 'OK' });
}

export function alertError(title, text = '') {
    return base.fire({ icon: 'error', title, text, confirmButtonText: 'Tutup' });
}

/**
 * Dialog konfirmasi. Mengembalikan Promise<boolean>.
 */
export async function confirmDialog({
    title = 'Apakah Anda yakin?',
    text = '',
    confirmText = 'Ya, lanjutkan',
    cancelText = 'Batal',
    danger = false,
    icon = 'warning',
} = {}) {
    const result = await base.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        customClass: {
            popup: 'swal-sileka-popup',
            title: 'swal-sileka-title',
            htmlContainer: 'swal-sileka-text',
            confirmButton: danger
                ? 'swal-sileka-btn swal-sileka-btn-danger'
                : 'swal-sileka-btn swal-sileka-btn-confirm',
            cancelButton: 'swal-sileka-btn swal-sileka-btn-cancel',
        },
    });
    return result.isConfirmed;
}

export const showConfirmDialog = confirmDialog;
export const showSuccessAlert = alertSuccess;
export const showErrorAlert = alertError;

export default Swal;
