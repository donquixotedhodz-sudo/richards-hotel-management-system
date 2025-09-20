// OTP Verification Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const otpForm = document.getElementById('otpVerificationForm');
    const otpInput = document.getElementById('otp');
    const verifyBtn = document.getElementById('verifyBtn');
    const resendBtn = document.getElementById('resendBtn');
    const alertContainer = document.getElementById('alertContainer');
    const timerDisplay = document.getElementById('timerDisplay');
    const countdownElement = document.getElementById('countdown');
    
    let countdownTimer = null;
    let resendTimer = null;
    let resendCountdown = 60;
    let otpExpiryTime = 10 * 60; // 10 minutes in seconds
    
    // Initialize
    startOTPExpiryTimer();
    otpInput.focus();
    
    // OTP Form Submission
    otpForm.addEventListener('submit', function(e) {
        e.preventDefault();
        verifyOTP();
    });
    
    // Resend OTP
    resendBtn.addEventListener('click', function() {
        resendOTP();
    });
    
    // OTP Input Formatting
    otpInput.addEventListener('input', function(e) {
        // Only allow numbers
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Auto-submit when 6 digits are entered
        if (this.value.length === 6) {
            setTimeout(() => {
                verifyOTP();
            }, 300);
        }
    });
    
    // Handle paste
    otpInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const numbers = paste.replace(/[^0-9]/g, '').substring(0, 6);
        this.value = numbers;
        
        if (numbers.length === 6) {
            setTimeout(() => {
                verifyOTP();
            }, 300);
        }
    });
    
    // Verify OTP Function
    function verifyOTP() {
        const otp = otpInput.value.trim();
        
        if (otp.length !== 6) {
            showAlert('Please enter a valid 6-digit OTP code', 'danger');
            otpInput.focus();
            return;
        }
        
        setButtonLoading(verifyBtn, true);
        clearAlerts();
        
        const formData = new FormData();
        formData.append('otp', otp);
        
        fetch('controller/SignupController.php?action=verify_otp', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                
                // Stop timers
                stopAllTimers();
                
                // Redirect to login page after 2 seconds
                setTimeout(() => {
                    window.location.href = '../centralized-login/login.php?message=' + 
                        encodeURIComponent('Account created successfully! Please login.');
                }, 2000);
            } else {
                showAlert(data.message, 'danger');
                otpInput.focus();
                otpInput.select();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.', 'danger');
        })
        .finally(() => {
            setButtonLoading(verifyBtn, false);
        });
    }
    
    // Resend OTP Function
    function resendOTP() {
        setButtonLoading(resendBtn, true);
        clearAlerts();
        
        // Get user data from session (we'll make a request to get it)
        fetch('controller/SignupController.php?action=resend_otp', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                startResendCooldown();
                resetOTPExpiryTimer();
                otpInput.value = '';
                otpInput.focus();
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to resend OTP. Please try again.', 'danger');
        })
        .finally(() => {
            setButtonLoading(resendBtn, false);
        });
    }
    
    // Start OTP Expiry Timer
    function startOTPExpiryTimer() {
        timerDisplay.style.display = 'block';
        updateCountdownDisplay();
        
        countdownTimer = setInterval(() => {
            otpExpiryTime--;
            updateCountdownDisplay();
            
            if (otpExpiryTime <= 0) {
                stopAllTimers();
                showAlert('OTP has expired. Please request a new one.', 'warning');
                otpInput.disabled = true;
                verifyBtn.disabled = true;
            }
        }, 1000);
    }
    
    // Reset OTP Expiry Timer
    function resetOTPExpiryTimer() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
        }
        otpExpiryTime = 10 * 60; // Reset to 10 minutes
        otpInput.disabled = false;
        verifyBtn.disabled = false;
        startOTPExpiryTimer();
    }
    
    // Update Countdown Display
    function updateCountdownDisplay() {
        const minutes = Math.floor(otpExpiryTime / 60);
        const seconds = otpExpiryTime % 60;
        countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        // Change color when time is running out
        if (otpExpiryTime <= 60) {
            countdownElement.style.color = '#dc3545';
        } else if (otpExpiryTime <= 180) {
            countdownElement.style.color = '#ffc107';
        } else {
            countdownElement.style.color = '#dc3545';
        }
    }
    
    // Start Resend Cooldown
    function startResendCooldown() {
        resendCountdown = 60;
        resendBtn.disabled = true;
        updateResendButton();
        
        resendTimer = setInterval(() => {
            resendCountdown--;
            updateResendButton();
            
            if (resendCountdown <= 0) {
                stopResendTimer();
            }
        }, 1000);
    }
    
    // Update Resend Button
    function updateResendButton() {
        if (resendCountdown > 0) {
            resendBtn.innerHTML = `<i class="fas fa-clock me-2"></i>Resend in ${resendCountdown}s`;
        } else {
            resendBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Resend OTP';
        }
    }
    
    // Stop Resend Timer
    function stopResendTimer() {
        if (resendTimer) {
            clearInterval(resendTimer);
            resendTimer = null;
        }
        resendBtn.disabled = false;
        resendBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Resend OTP';
    }
    
    // Stop All Timers
    function stopAllTimers() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
        if (resendTimer) {
            clearInterval(resendTimer);
            resendTimer = null;
        }
        timerDisplay.style.display = 'none';
    }
    
    // Show Alert
    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        alertContainer.innerHTML = alertHtml;
        
        // Auto-dismiss after 5 seconds for non-error messages
        if (type !== 'danger') {
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    }
    
    // Clear Alerts
    function clearAlerts() {
        alertContainer.innerHTML = '';
    }
    
    // Set Button Loading State
    function setButtonLoading(button, loading) {
        const btnText = button.querySelector('.btn-text');
        const spinner = button.querySelector('.spinner-border');
        
        if (loading) {
            button.disabled = true;
            btnText.style.display = 'none';
            spinner.classList.remove('d-none');
        } else {
            button.disabled = false;
            btnText.style.display = 'inline';
            spinner.classList.add('d-none');
        }
    }
});