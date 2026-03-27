import Swal from 'sweetalert2'

export const showSuccess = (message, title = 'Success') => {
    return Swal.fire({
        icon: 'success',
        title,
        text: message,
        confirmButtonText: 'OK',
        timer: 2200,
        timerProgressBar: true,
    })
}

export const showError = (message, title = 'Error') => {
    return Swal.fire({
        icon: 'error',
        title,
        text: message,
        confirmButtonText: 'OK',
    })
}

export const showInfo = (message, title = 'Info') => {
    return Swal.fire({
        icon: 'info',
        title,
        text: message,
        confirmButtonText: 'OK',
    })
}

export const showConfirm = async (message, title = 'Are you sure?') => {
    const result = await Swal.fire({
        icon: 'question',
        title,
        text: message,
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    })

    return result.isConfirmed
}