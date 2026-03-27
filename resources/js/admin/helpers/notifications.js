/**
 * Show success notification using SweetAlert
 * @param {string} message - The success message to display
 * @param {string} title - Optional title (default: 'Success')
 */
export function showSuccess(message, title = 'Success') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: title,
            text: message,
            confirmButtonText: 'OK',
            timer: 3000,
            timerProgressBar: true
        });
    } else if (typeof flasher !== 'undefined') {
        flasher.success(message);
    } else {
        alert(message);
    }
}

/**
 * Show error notification using SweetAlert
 * @param {string} message - The error message to display
 * @param {string} title - Optional title (default: 'Error')
 */
export function showError(message, title = 'Error') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: title,
            text: message,
            confirmButtonText: 'OK'
        });
    } else if (typeof flasher !== 'undefined') {
        flasher.error(message);
    } else {
        alert(message);
    }
}

/**
 * Show info notification using SweetAlert
 * @param {string} message - The info message to display
 * @param {string} title - Optional title (default: 'Info')
 */
export function showInfo(message, title = 'Info') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: title,
            text: message,
            confirmButtonText: 'OK',
            timer: 3000,
            timerProgressBar: true
        });
    } else if (typeof flasher !== 'undefined') {
        flasher.info(message);
    } else {
        alert(message);
    }
}

/**
 * Show warning notification using SweetAlert
 * @param {string} message - The warning message to display
 * @param {string} title - Optional title (default: 'Warning')
 */
export function showWarning(message, title = 'Warning') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: title,
            text: message,
            confirmButtonText: 'OK'
        });
    } else if (typeof flasher !== 'undefined') {
        flasher.warning(message);
    } else {
        alert(message);
    }
}

/**
 * Show confirmation dialog
 * @param {string} message - The confirmation message
 * @param {string} title - Optional title (default: 'Are you sure?')
 * @returns {Promise<boolean>} - Returns true if confirmed, false otherwise
 */
export function showConfirm(message, title = 'Are you sure?') {
    if (typeof Swal !== 'undefined') {
        return Swal.fire({
            title: title,
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            reverseButtons: true
        }).then((result) => result.isConfirmed);
    } else {
        return Promise.resolve(confirm(message));
    }
}
