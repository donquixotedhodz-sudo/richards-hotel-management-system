// Notification System for Richards Hotel

/**
 * Show notification in the upper right corner
 * @param {string} message - The message to display
 * @param {string} type - The type of notification (success, error, warning)
 * @param {number} duration - Duration in milliseconds (default: 5000)
 */
function showNotification(message, type = 'success', duration = 5000) {
    const container = document.getElementById('notification-container');
    const messageElement = document.getElementById('notification-message');
    const alertElement = container.querySelector('.alert');
    
    if (!container || !messageElement || !alertElement) {
        console.error('Notification elements not found');
        return;
    }
    
    // Set the message
    messageElement.textContent = message;
    
    // Remove existing alert classes
    alertElement.classList.remove('alert-success', 'alert-error', 'alert-warning');
    
    // Add the appropriate alert class
    switch(type) {
        case 'error':
            alertElement.classList.add('alert-error');
            alertElement.querySelector('i').className = 'fas fa-exclamation-circle me-2';
            break;
        case 'warning':
            alertElement.classList.add('alert-warning');
            alertElement.querySelector('i').className = 'fas fa-exclamation-triangle me-2';
            break;
        case 'success':
        default:
            alertElement.classList.add('alert-success');
            alertElement.querySelector('i').className = 'fas fa-check-circle me-2';
            break;
    }
    
    // Remove any existing hide animation class
    container.classList.remove('notification-hide');
    
    // Show the notification
    container.style.display = 'block';
    
    // Auto-hide after specified duration
    if (duration > 0) {
        setTimeout(() => {
            hideNotification();
        }, duration);
    }
}

/**
 * Hide the notification with animation
 */
function hideNotification() {
    const container = document.getElementById('notification-container');
    
    if (!container) {
        return;
    }
    
    // Add hide animation class
    container.classList.add('notification-hide');
    
    // Hide the container after animation completes
    setTimeout(() => {
        container.style.display = 'none';
        container.classList.remove('notification-hide');
    }, 300); // Match the animation duration
}

/**
 * Show booking success notification
 * @param {string} bookingId - The booking ID or reference
 */
function showBookingSuccessNotification(bookingId = '') {
    const message = bookingId ? 
        `Booking successful! Reference: ${bookingId}` : 
        'Booking successful! Your reservation has been confirmed.';
    
    showNotification(message, 'success', 6000);
}

/**
 * Show booking error notification
 * @param {string} errorMessage - The error message to display
 */
function showBookingErrorNotification(errorMessage = 'Booking failed. Please try again.') {
    showNotification(errorMessage, 'error', 8000);
}

// Initialize notification system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add click event to close button
    const closeButton = document.querySelector('#notification-container .btn-close');
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            hideNotification();
        });
    }
    
    // Close notification when clicking outside
    document.addEventListener('click', function(event) {
        const container = document.getElementById('notification-container');
        if (container && container.style.display === 'block' && !container.contains(event.target)) {
            // Optional: uncomment to close on outside click
            // hideNotification();
        }
    });
});