/**
 * Global Spinner Helper Functions
 * Manages cursor spinner and form overlay states
 */

const spinnerState = {
    isActive: false,
    overlay: null,
};

/**
 * Show loading spinner and disable form interaction
 */
export const setSpinner = (element = null) => {
    if (spinnerState.isActive) return;

    // Change cursor to waiting
    document.body.style.cursor = 'wait';
    spinnerState.isActive = true;

    // If element provided, create overlay to prevent clicks
    if (element) {
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.1);
            z-index: 9999;
            cursor: wait;
        `;
        document.body.appendChild(overlay);
        spinnerState.overlay = overlay;
    }
};

/**
 * Hide loading spinner and enable form interaction
 */
export const unsetSpinner = () => {
    if (!spinnerState.isActive) return;

    // Reset cursor
    document.body.style.cursor = 'auto';
    spinnerState.isActive = false;

    // Remove overlay if exists
    if (spinnerState.overlay) {
        spinnerState.overlay.remove();
        spinnerState.overlay = null;
    }
};

/**
 * Show spinner on modal loading
 * Usage: openModal() -> setSpinner() -> fetch data -> unsetSpinner()
 */
export const showModalSpinner = () => {
    setSpinner();
};

/**
 * Hide spinner when modal is loaded
 */
export const hideModalSpinner = () => {
    unsetSpinner();
};

/**
 * Global functions exposed to window
 */
window.setSpinner = setSpinner;
window.unsetSpinner = unsetSpinner;
window.showModalSpinner = showModalSpinner;
window.hideModalSpinner = hideModalSpinner;
