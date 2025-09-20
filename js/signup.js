// Signup Form with OTP Verification
document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.getElementById('signupForm');
    const otpForm = document.getElementById('otpForm');
    const sendOtpBtn = document.getElementById('sendOtpBtn');
    const verifyOtpBtn = document.getElementById('verifyOtpBtn');
    const resendOtpBtn = document.getElementById('resendOtpBtn');
    const backToFormBtn = document.getElementById('backToFormBtn');
    const alertContainer = document.getElementById('alertContainer');
    const emailDisplay = document.getElementById('emailDisplay');
    const otpInput = document.getElementById('otp');
    
    let resendTimer = null;
    let resendCountdown = 60;
    
    // Step 1: Send OTP
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateSignupForm()) {
                return;
            }
            
            sendOTP();
        });
    }
    
    // Step 2: Verify OTP
    if (otpForm) {
        otpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            verifyOTP();
        });
    }
    
    // Resend OTP
    if (resendOtpBtn) {
        resendOtpBtn.addEventListener('click', function() {
            sendOTP(true);
        });
    }
    
    // Back to form
    if (backToFormBtn) {
        backToFormBtn.addEventListener('click', function() {
            showSignupForm();
        });
    }
    
    // OTP input formatting
    if (otpInput) {
        otpInput.addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Auto-submit when 6 digits are entered
            if (this.value.length === 6) {
                setTimeout(() => {
                    verifyOTP();
                }, 500);
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
                }, 500);
            }
        });
    }
    
    // Send OTP function
    function sendOTP(isResend = false) {
        const formData = new FormData(signupForm);
        const button = isResend ? resendOtpBtn : sendOtpBtn;
        
        setButtonLoading(button, true);
        clearAlerts();
        
        fetch('controller/SignupController.php?action=send_otp', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to OTP verification page
                window.location.href = 'verify-otp.php';
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.', 'danger');
        })
        .finally(() => {
            setButtonLoading(button, false);
        });
    }
    
    // Verify OTP function
    function verifyOTP() {
        const otp = otpInput.value.trim();
        
        if (otp.length !== 6) {
            showAlert('Please enter a valid 6-digit OTP code', 'danger');
            return;
        }
        
        setButtonLoading(verifyOtpBtn, true);
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
                
                // Redirect to login page after 2 seconds
                setTimeout(() => {
                    window.location.href = '../centralized-login/login.php?message=' + encodeURIComponent('Account created successfully! Please login.');
                }, 2000);
            } else {
                showAlert(data.message, 'danger');
                otpInput.focus();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.', 'danger');
        })
        .finally(() => {
            setButtonLoading(verifyOtpBtn, false);
        });
    }
    
    // Show signup form
    function showSignupForm() {
        signupForm.style.display = 'block';
        otpForm.style.display = 'none';
        clearAlerts();
        stopResendTimer();
    }
    
    // Show OTP form
    function showOTPForm() {
        signupForm.style.display = 'none';
        otpForm.style.display = 'block';
        otpInput.focus();
        clearAlerts();
    }
    
    // Start resend timer
    function startResendTimer() {
        resendCountdown = 60;
        resendOtpBtn.disabled = true;
        updateResendButton();
        
        resendTimer = setInterval(() => {
            resendCountdown--;
            updateResendButton();
            
            if (resendCountdown <= 0) {
                stopResendTimer();
            }
        }, 1000);
    }
    
    // Stop resend timer
    function stopResendTimer() {
        if (resendTimer) {
            clearInterval(resendTimer);
            resendTimer = null;
        }
        resendOtpBtn.disabled = false;
        resendOtpBtn.textContent = 'Resend OTP';
    }
    
    // Update resend button
    function updateResendButton() {
        if (resendCountdown > 0) {
            resendOtpBtn.textContent = `Resend OTP (${resendCountdown}s)`;
        } else {
            resendOtpBtn.textContent = 'Resend OTP';
        }
    }
    
    // Validate signup form
    function validateSignupForm() {
        const firstName = document.getElementById('first_name').value.trim();
        const lastName = document.getElementById('last_name').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const terms = document.getElementById('terms').checked;
        
        // Basic validation
        if (!firstName || !lastName || !email || !phone || !password || !confirmPassword) {
            showAlert('Please fill in all required fields', 'danger');
            return false;
        }
        
        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showAlert('Please enter a valid email address', 'danger');
            return false;
        }
        
        // Phone validation
        const phoneRegex = /^[\d\s\-\+\(\)]+$/;
        if (!phoneRegex.test(phone) || phone.length < 10) {
            showAlert('Please enter a valid phone number', 'danger');
            return false;
        }
        
        // Password validation
        if (password.length < 6) {
            showAlert('Password must be at least 6 characters long', 'danger');
            return false;
        }
        
        // Password match validation
        if (password !== confirmPassword) {
            showAlert('Passwords do not match', 'danger');
            return false;
        }
        
        // Terms validation
        if (!terms) {
            showAlert('Please accept the terms and conditions', 'danger');
            return false;
        }
        
        return true;
    }
    
    // Set button loading state
    function setButtonLoading(button, loading) {
        const spinner = button.querySelector('.spinner-border');
        const text = button.querySelector('.btn-text');
        
        if (loading) {
            button.disabled = true;
            spinner.classList.remove('d-none');
            if (text) text.style.opacity = '0.7';
        } else {
            button.disabled = false;
            spinner.classList.add('d-none');
            if (text) text.style.opacity = '1';
        }
    }
    
    // Show alert
    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        alertContainer.innerHTML = alertHtml;
        alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    // Clear alerts
    function clearAlerts() {
        alertContainer.innerHTML = '';
    }
    
    // Real-time password validation
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            validatePasswordStrength(this.value);
        });
    }
    
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            validatePasswordMatch();
        });
    }
    
    function validatePasswordStrength(password) {
        const field = passwordField;
        
        if (password.length === 0) {
            field.classList.remove('is-valid', 'is-invalid');
            return;
        }
        
        if (password.length >= 6) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
        }
    }
    
    function validatePasswordMatch() {
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;
        
        if (confirmPassword.length === 0) {
            confirmPasswordField.classList.remove('is-valid', 'is-invalid');
            return;
        }
        
        if (password === confirmPassword) {
            confirmPasswordField.classList.remove('is-invalid');
            confirmPasswordField.classList.add('is-valid');
        } else {
            confirmPasswordField.classList.remove('is-valid');
            confirmPasswordField.classList.add('is-invalid');
        }
    }
});