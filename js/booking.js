// Richards Hotel - Booking JavaScript Functions

// Initialize booking functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initBookingForm();
});

// Booking form initialization
function initBookingForm() {
    const bookingForm = document.getElementById('bookingForm');
    if (!bookingForm) return;

    // Initialize booking functionality
    initBookingPriceCalculation();
    initBookingDateValidation();
    initBookingFormSubmission();
}

// Booking price calculation with database rates
function initBookingPriceCalculation() {
    const roomTypeSelect = document.getElementById('room_type_id');
    const checkInDateTime = document.getElementById('check_in_datetime');
    const durationHours = document.getElementById('duration_hours');
    const priceDisplay = document.getElementById('price_display');
    
    // Store booking rates from database
    let bookingRates = {};
    
    // Fetch booking rates from database
    async function loadBookingRates() {
        try {
            const response = await fetch('controller/get_booking_rates.php');
            const data = await response.json();
            
            if (data.success) {
                bookingRates = data.rates;
            } else {
                console.error('Failed to load booking rates:', data.error);
                // Fallback to default rates if database fails
                bookingRates = {
                    '1': { '3': 500, '12': 1200, '24': 1000 },
                    '2': { '24': 2000 }
                };
            }
        } catch (error) {
            console.error('Error fetching booking rates:', error);
            // Fallback to default rates
            bookingRates = {
                '1': { '3': 500, '12': 1200, '24': 1000 },
                '2': { '24': 2000 }
            };
        }
    }
    
    function calculatePrice() {
        if (!roomTypeSelect.value || !durationHours.value) {
            if (priceDisplay) priceDisplay.textContent = 'Select room type and duration to see price';
            // Reset booking fee amount to 0.00 when no selection
            const bookingFeeAmountElement = document.getElementById('booking-fee-amount');
            if (bookingFeeAmountElement) {
                bookingFeeAmountElement.textContent = '₱0.00';
            }
            return;
        }
        
        const roomTypeId = roomTypeSelect.value;
        const hours = parseInt(durationHours.value);
        
        // Get price from database rates
        let basePrice = 0;
        if (bookingRates[roomTypeId] && bookingRates[roomTypeId][hours]) {
            basePrice = parseFloat(bookingRates[roomTypeId][hours]);
        } else {
            if (priceDisplay) {
                priceDisplay.innerHTML = `
                    <div class="text-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No rate available for selected room type and duration
                    </div>
                `;
            }
            return;
        }
        
        // Calculate booking fee (12% of base price)
        const bookingFee = basePrice * 0.12;
        const totalPrice = basePrice + bookingFee;
        
        // Update the booking fee amount in the notice
        const bookingFeeAmountElement = document.getElementById('booking-fee-amount');
        if (bookingFeeAmountElement) {
            bookingFeeAmountElement.textContent = `₱${bookingFee.toFixed(2)}`;
        }
        
        console.log('Price calculation:', { basePrice, bookingFee, totalPrice }); // Debug log
        
        if (priceDisplay) {
            priceDisplay.innerHTML = `
                <div class="d-flex justify-content-between">
                    <span>Rate (${hours} hours):</span>
                    <span>₱${basePrice.toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between text-warning">
                    <span>Booking Fee (12%):</span>
                    <span>₱${bookingFee.toFixed(2)}</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total Amount:</span>
                    <span class="text-primary">₱${totalPrice.toFixed(2)}</span>
                </div>
            `;
        }
        
        // Auto-calculate checkout date/time
        calculateCheckoutDateTime();
    }
    
    function calculateCheckoutDateTime() {
        const checkInInput = document.getElementById('check_in_datetime');
        const durationInput = document.getElementById('duration_hours');
        const checkOutDisplay = document.getElementById('check_out_display');
        
        if (checkInInput && checkInInput.value && durationInput && durationInput.value) {
            const checkInDate = new Date(checkInInput.value);
            const hours = parseInt(durationInput.value);
            
            const checkOutDate = new Date(checkInDate.getTime() + (hours * 60 * 60 * 1000));
            
            // Display checkout time in the dedicated field
            if (checkOutDisplay) {
                const checkOutFormatted = checkOutDate.toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                checkOutDisplay.value = checkOutFormatted;
            }
        } else {
            // Clear the checkout display if inputs are incomplete
            if (checkOutDisplay) {
                checkOutDisplay.value = '';
            }
        }
    }
    
    // Initialize rates and event listeners
    loadBookingRates().then(() => {
        // Add event listeners
        if (roomTypeSelect) {
            roomTypeSelect.addEventListener('change', function() {
                // Auto-set duration for Family Room (room type 2)
                if (this.value === '2' && durationHours) {
                    durationHours.value = '24';
                }
                calculatePrice();
            });
        }
        if (checkInDateTime) checkInDateTime.addEventListener('change', calculatePrice);
        if (durationHours) durationHours.addEventListener('change', calculatePrice);
    });
}

// Booking date validation
function initBookingDateValidation() {
    const checkInDate = document.getElementById('check_in_date');
    const checkOutDate = document.getElementById('check_out_date');
    const checkInDateTime = document.getElementById('check_in_datetime');
    
    // Set minimum date to today for date fields
    const today = new Date().toISOString().split('T')[0];
    if (checkInDate) {
        checkInDate.min = today;
    }
    
    // Set minimum datetime to current datetime for datetime fields
    if (checkInDateTime) {
        const now = new Date();
        // Allow booking for today by setting minimum to start of today
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const minDateTime = new Date(today.getTime() - today.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        checkInDateTime.min = minDateTime;
        
        // Add real-time validation for datetime field - allow today's bookings
        checkInDateTime.addEventListener('change', function() {
            const selectedDateTime = new Date(this.value);
            const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            
            if (selectedDateTime < todayStart) {
                this.classList.add('is-invalid');
                if (typeof showAlert === 'function') {
                    showAlert('Check-in date cannot be in the past', 'error');
                }
                this.value = '';
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        // Add OK button functionality for datetime confirmation
        const confirmDateTimeBtn = document.getElementById('confirm_datetime');
        if (confirmDateTimeBtn) {
            confirmDateTimeBtn.addEventListener('click', function() {
                if (checkInDateTime.value) {
                    // Trigger change event to validate and calculate prices
                    checkInDateTime.dispatchEvent(new Event('change'));
                    
                    // Show confirmation feedback
                    this.innerHTML = '<i class="fas fa-check-circle text-success"></i> Confirmed';
                    this.classList.add('btn-success');
                    this.classList.remove('btn-outline-primary');
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-check"></i> OK';
                        this.classList.remove('btn-success');
                        this.classList.add('btn-outline-primary');
                    }, 2000);
                } else {
                    if (typeof showAlert === 'function') {
                        showAlert('Please select a date and time first', 'warning');
                    }
                }
            });
        }
    }
    
    if (!checkInDate || !checkOutDate) return;
    
    checkInDate.addEventListener('change', function() {
        const minCheckOut = new Date(this.value);
        minCheckOut.setDate(minCheckOut.getDate() + 1);
        checkOutDate.min = minCheckOut.toISOString().split('T')[0];
        
        if (checkOutDate.value && checkOutDate.value <= this.value) {
            checkOutDate.value = '';
            if (typeof showAlert === 'function') {
                showAlert('Check-out date must be after check-in date', 'warning');
            }
        }
    });
    
    checkOutDate.addEventListener('change', function() {
        if (checkInDate.value && this.value <= checkInDate.value) {
            this.value = '';
            if (typeof showAlert === 'function') {
                showAlert('Check-out date must be after check-in date', 'warning');
            }
        }
    });
}

// Booking form submission
function initBookingFormSubmission() {
    const bookingForm = document.getElementById('bookingForm');
    if (!bookingForm) return;
    
    let isSubmitting = false; // Flag to prevent double submission
    
    bookingForm.addEventListener('submit', async function(e) {
        e.preventDefault(); // Always prevent default form submission
        
        // Prevent double submission
        if (isSubmitting) {
            return false;
        }
        
        if (!validateBookingForm()) {
            return false;
        }
        
        isSubmitting = true; // Set flag to prevent further submissions
        
        // Show loading state
        const submitBtn = bookingForm.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : 'Submit Booking';
        
        if (submitBtn) {
            submitBtn.textContent = 'Processing...';
            submitBtn.disabled = true;
        }
        
        try {
            // Create FormData object from the form
            const formData = new FormData(bookingForm);
            
            // Submit the form via AJAX
            const response = await fetch('bookings/controller/BookingController.php', {
                method: 'POST',
                body: formData
            });
            
            // Check if the response is successful
            if (response.ok) {
                // Show success notification
                if (typeof showBookingSuccessNotification === 'function') {
                    showBookingSuccessNotification();
                } else {
                    alert('Booking submitted successfully!');
                }
                
                // Close the modal and reset the form
                closeBookingModal();
                resetBookingForm();
                
            } else {
                // Show error notification
                if (typeof showBookingErrorNotification === 'function') {
                    showBookingErrorNotification('Booking submission failed. Please try again.');
                } else {
                    alert('Booking submission failed. Please try again.');
                }
            }
            
        } catch (error) {
            console.error('Booking submission error:', error);
            
            // Show error notification
            if (typeof showBookingErrorNotification === 'function') {
                showBookingErrorNotification('Network error. Please check your connection and try again.');
            } else {
                alert('Network error. Please check your connection and try again.');
            }
        } finally {
            // Re-enable submit button and reset submission flag
            if (submitBtn) {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
            isSubmitting = false; // Reset flag to allow future submissions
        }
    });
}

// Validate booking form
function validateBookingForm() {
    const requiredFields = [
        'customer_name',
        'email',
        'phone',
        'room_type_id',
        'check_in_date',
        'check_out_date',
        'guests'
    ];
    
    let isValid = true;
    
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field && !field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else if (field) {
            field.classList.remove('is-invalid');
        }
    });
    
    // Validate email format
    const emailField = document.getElementById('email');
    if (emailField && emailField.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailField.value)) {
            emailField.classList.add('is-invalid');
            if (typeof showAlert === 'function') {
                showAlert('Please enter a valid email address', 'error');
            }
            isValid = false;
        }
    }
    
    // Validate phone format
    const phoneField = document.getElementById('phone');
    if (phoneField && phoneField.value) {
        const phoneRegex = /^[\d\s\-\+\(\)]+$/;
        if (!phoneRegex.test(phoneField.value) || phoneField.value.length < 10) {
            phoneField.classList.add('is-invalid');
            if (typeof showAlert === 'function') {
                showAlert('Please enter a valid phone number', 'error');
            }
            isValid = false;
        }
    }
    
    // Validate dates and datetime
    const checkInDate = document.getElementById('check_in_date');
    const checkOutDate = document.getElementById('check_out_date');
    const checkInDateTime = document.getElementById('check_in_datetime');
    
    // Validate datetime field (for hourly bookings)
    if (checkInDateTime && checkInDateTime.value) {
        const checkInDT = new Date(checkInDateTime.value);
        const now = new Date();
        
        if (checkInDT <= now) {
            checkInDateTime.classList.add('is-invalid');
            if (typeof showAlert === 'function') {
                showAlert('Check-in date and time must be in the future', 'error');
            }
            isValid = false;
        }
    }
    
    // Validate date fields (for daily bookings)
    if (checkInDate && checkOutDate && checkInDate.value && checkOutDate.value) {
        const checkIn = new Date(checkInDate.value);
        const checkOut = new Date(checkOutDate.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (checkIn < today) {
            checkInDate.classList.add('is-invalid');
            if (typeof showAlert === 'function') {
                showAlert('Check-in date cannot be in the past', 'error');
            }
            isValid = false;
        }
        
        if (checkOut <= checkIn) {
            checkOutDate.classList.add('is-invalid');
            if (typeof showAlert === 'function') {
                showAlert('Check-out date must be after check-in date', 'error');
            }
            isValid = false;
        }
    }
    
    if (!isValid && typeof showAlert === 'function') {
        showAlert('Please fill in all required fields correctly', 'error');
    }
    
    return isValid;
}

// Open booking modal with pre-selected room type
function openBookingModal(roomTypeId = null, roomTypeName = null) {
    const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
    
    // Pre-select room type if provided
    if (roomTypeId) {
        const roomTypeSelect = document.getElementById('room_type_id');
        if (roomTypeSelect) {
            roomTypeSelect.value = roomTypeId;
            // Trigger change event to update price calculation
            roomTypeSelect.dispatchEvent(new Event('change'));
        }
    }
    
    bookingModal.show();
}

// Show booking modal (alternative function name for compatibility)
function showBookingModal() {
    openBookingModal();
}

// Close booking modal
function closeBookingModal() {
    const bookingModal = bootstrap.Modal.getInstance(document.getElementById('bookingModal'));
    if (bookingModal) {
        bookingModal.hide();
    }
}

// Reset booking form
function resetBookingForm() {
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.reset();
        
        // Remove validation classes
        const invalidFields = bookingForm.querySelectorAll('.is-invalid');
        invalidFields.forEach(field => field.classList.remove('is-invalid'));
        
        // Reset price display
        const totalPriceElement = document.getElementById('totalPrice');
        if (totalPriceElement) {
            totalPriceElement.textContent = '₱0.00';
        }
        
        // Reset booking fee amount in notice
        const bookingFeeAmountElement = document.getElementById('booking-fee-amount');
        if (bookingFeeAmountElement) {
            bookingFeeAmountElement.textContent = '₱0.00';
        }
    }
}

// Handle modal events
document.addEventListener('DOMContentLoaded', function() {
    const bookingModal = document.getElementById('bookingModal');
    if (bookingModal) {
        // Reset form when modal is closed
        bookingModal.addEventListener('hidden.bs.modal', function() {
            resetBookingForm();
        });
    }
});