const PasswordChangeModal = {
    currentStep: 1,
    otpTimerInterval: null,
    otpExpiryTime: null,

    /**
     * Open the password change modal
     */
    open: function() {
        document.getElementById('passwordChangeModal').classList.add('active');
        this.reset();
    },

    /**
     * Close the password change modal
     */
    close: function() {
        this.showConfirm('Are you sure you want to cancel the password change process?', () => {
            document.getElementById('passwordChangeModal').classList.remove('active');
            this.reset();
        });
    },

    /**
     * Reset the modal to initial state
     */
    reset: function() {
        this.currentStep = 1;
        this.updateStep(1);
        this.clearAllInputs();
        this.hideAllErrors();
        this.stopOtpTimer();
        document.getElementById('backBtn').style.display = 'none';
        document.getElementById('nextBtn').textContent = 'Next ';
        document.getElementById('nextBtn').innerHTML = 'Next <i class="fas fa-arrow-right"></i>';
    },

    /**
     * Clear all input fields
     */
    clearAllInputs: function() {
        document.getElementById('currentPassword').value = '';
        document.getElementById('otpCode').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
        
        // Reset password requirements
        ['length', 'uppercase', 'lowercase', 'number'].forEach(req => {
            document.getElementById(`req-${req}`).classList.remove('met');
        });
        
        document.getElementById('passwordMatchIndicator').className = 'password-match-indicator';
    },

    /**
     * Hide all error messages
     */
    hideAllErrors: function() {
        document.getElementById('step1Error').classList.remove('show');
        document.getElementById('step2Error').classList.remove('show');
        document.getElementById('step3Error').classList.remove('show');
    },

    /**
     * Show error message
     */
    showError: function(step, message) {
        const errorEl = document.getElementById(`step${step}Error`);
        errorEl.textContent = message;
        errorEl.classList.add('show');
    },

    /**
     * Update step visibility and progress
     */
    updateStep: function(step) {
        // Update step content
        document.querySelectorAll('.password-step-content').forEach(content => {
            content.classList.remove('active');
        });
        document.querySelector(`.password-step-content[data-step="${step}"]`).classList.add('active');

        // Update progress indicator
        document.querySelectorAll('.password-step').forEach((stepEl, index) => {
            stepEl.classList.remove('active', 'completed');
            if (index + 1 < step) {
                stepEl.classList.add('completed');
            } else if (index + 1 === step) {
                stepEl.classList.add('active');
            }
        });

        // Update buttons
        if (step === 1) {
            document.getElementById('backBtn').style.display = 'none';
            document.getElementById('nextBtn').innerHTML = 'Send OTP <i class="fas fa-arrow-right"></i>';
            document.getElementById('nextBtn').style.display = 'inline-flex';
        } else if (step === 2) {
            document.getElementById('backBtn').style.display = 'block';
            document.getElementById('nextBtn').innerHTML = '<i class="fas fa-check"></i> Verify & Change Password';
            document.getElementById('nextBtn').style.display = 'inline-flex';
        } else if (step === 3) {
            document.getElementById('backBtn').style.display = 'none';
            document.getElementById('nextBtn').innerHTML = '<i class="fas fa-check"></i> Close';
            document.getElementById('nextBtn').style.display = 'inline-flex';
        }

        this.currentStep = step;
        this.hideAllErrors();
    },

    /**
     * Go to next step
     */
    nextStep: async function() {
        if (this.currentStep === 1) {
            await this.validateAndSendOTP();
        } else if (this.currentStep === 2) {
            await this.verifyOTPAndChangePassword();
        } else if (this.currentStep === 3) {
            // Close modal on success
            document.getElementById('passwordChangeModal').classList.remove('active');
            this.reset();
            // Optionally reload the page
            setTimeout(() => window.location.reload(), 500);
        }
    },

    /**
     * Go to previous step
     */
    prevStep: function() {
        if (this.currentStep > 1) {
            this.updateStep(this.currentStep - 1);
        }
    },

    /**
     * Step 1: Validate all fields and send OTP
     */
    validateAndSendOTP: async function() {
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Validate current password
        if (!currentPassword) {
            this.showError(1, 'Please enter your current password');
            return;
        }

        // Validate new password
        if (!newPassword || !confirmPassword) {
            this.showError(1, 'Please fill in all password fields');
            return;
        }

        if (newPassword !== confirmPassword) {
            this.showError(1, 'Passwords do not match');
            return;
        }

        // Validate password requirements
        const validation = this.validatePassword(newPassword);
        if (!validation.valid) {
            this.showError(1, 'Password does not meet all requirements');
            return;
        }

        this.showLoading('Verifying password and sending OTP...');

        try {
            const response = await fetch('../portal/shared/change_password_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    current_password: currentPassword
                })
            });

            const data = await response.json();

            if (data.success) {
                // OTP sent successfully
                document.getElementById('otpEmail').textContent = data.email;
                this.startOtpTimer(data.expires_in || 180);
                this.updateStep(2);
            } else {
                this.showError(1, data.message || 'Failed to verify password');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showError(1, 'An error occurred. Please try again.');
        } finally {
            this.hideLoading();
        }
    },

    /**
     * Step 2: Verify OTP and Change Password
     */
    verifyOTPAndChangePassword: async function() {
        const otpCode = document.getElementById('otpCode').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Validate OTP
        if (!otpCode || otpCode.length !== 6) {
            this.showError(2, 'Please enter the 6-digit OTP code');
            return;
        }

        if (!/^\d{6}$/.test(otpCode)) {
            this.showError(2, 'OTP must contain only numbers');
            return;
        }

        this.showLoading('Verifying OTP and changing password...');

        try {
            const response = await fetch('../portal/shared/change_password_verify.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    otp: otpCode,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                })
            });

            const data = await response.json();

            if (data.success) {
                this.hideLoading();
                this.stopOtpTimer();
                this.updateStep(3);
            } else {
                this.hideLoading();
                this.showError(2, data.message || 'Failed to verify OTP or change password');
            }
        } catch (error) {
            console.error('Error:', error);
            this.hideLoading();
            this.showError(2, 'An error occurred. Please try again.');
        }
    },

    /**
     * Resend OTP
     */
    resendOTP: async function() {
        const currentPassword = document.getElementById('currentPassword').value;

        if (!currentPassword) {
            this.showAlert('Please go back and enter your current password', 'error');
            return;
        }

        this.showLoading('Resending OTP...');

        try {
            const response = await fetch('../portal/shared/change_password_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    current_password: currentPassword
                })
            });

            const data = await response.json();

            if (data.success) {
                document.getElementById('otpCode').value = '';
                this.startOtpTimer(data.expires_in || 180);
                this.showAlert('New OTP sent to your email', 'success');
            } else {
                this.showAlert('Failed to resend OTP. Please try again.', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('An error occurred. Please try again.', 'error');
        } finally {
            this.hideLoading();
        }
    },

    /**
     * Start OTP countdown timer
     */
    startOtpTimer: function(seconds) {
        this.stopOtpTimer();
        this.otpExpiryTime = Date.now() + (seconds * 1000);
        document.getElementById('resendOtpBtn').disabled = true;

        this.otpTimerInterval = setInterval(() => {
            const remaining = Math.max(0, Math.floor((this.otpExpiryTime - Date.now()) / 1000));
            const minutes = Math.floor(remaining / 60);
            const secs = remaining % 60;

            document.getElementById('otpTimeLeft').textContent = 
                `${minutes}:${secs.toString().padStart(2, '0')}`;

            if (remaining <= 0) {
                this.stopOtpTimer();
                document.getElementById('resendOtpBtn').disabled = false;
                document.getElementById('otpTimer').innerHTML = 
                    '<i class="fas fa-exclamation-triangle"></i> OTP expired. Please request a new one.';
                document.getElementById('otpTimer').style.background = '#fee2e2';
                document.getElementById('otpTimer').style.color = '#991b1b';
            }
        }, 1000);
    },

    /**
     * Stop OTP timer
     */
    stopOtpTimer: function() {
        if (this.otpTimerInterval) {
            clearInterval(this.otpTimerInterval);
            this.otpTimerInterval = null;
        }
    },

    /**
     * Toggle password visibility
     */
    togglePassword: function(inputId) {
        const input = document.getElementById(inputId);
        const button = input.parentElement.querySelector('.password-toggle-btn');
        const icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    },

    /**
     * Check password strength and requirements
     */
    checkPasswordStrength: function() {
        const password = document.getElementById('newPassword').value;
        const validation = this.validatePassword(password);

        // Update requirement indicators
        Object.keys(validation.requirements).forEach(req => {
            const element = document.getElementById(`req-${req}`);
            if (validation.requirements[req]) {
                element.classList.add('met');
            } else {
                element.classList.remove('met');
            }
        });

        this.checkPasswordMatch();
    },

    /**
     * Check if passwords match
     */
    checkPasswordMatch: function() {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const indicator = document.getElementById('passwordMatchIndicator');

        if (confirmPassword.length === 0) {
            indicator.className = 'password-match-indicator';
            return;
        }

        if (newPassword === confirmPassword) {
            indicator.className = 'password-match-indicator match';
            indicator.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
        } else {
            indicator.className = 'password-match-indicator no-match';
            indicator.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
        }
    },

    /**
     * Validate password requirements
     */
    validatePassword: function(password) {
        return {
            valid: password.length >= 8 &&
                   /[A-Z]/.test(password) &&
                   /[a-z]/.test(password) &&
                   /[0-9]/.test(password),
            requirements: {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password)
            }
        };
    },

    /**
     * Show loading overlay
     */
    showLoading: function(message) {
        document.getElementById('loadingMessage').textContent = message;
        document.getElementById('passwordLoadingOverlay').classList.add('active');
    },

    /**
     * Hide loading overlay
     */
    hideLoading: function() {
        document.getElementById('passwordLoadingOverlay').classList.remove('active');
    },

    /**
     * Show custom confirmation modal
     */
    showConfirm: function(message, onConfirm) {
        const overlay = document.createElement('div');
        overlay.className = 'custom-modal-overlay';
        overlay.innerHTML = `
            <div class="custom-modal">
                <div class="custom-modal-icon warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="custom-modal-message">${message}</div>
                <div class="custom-modal-buttons">
                    <button class="custom-modal-btn cancel">Cancel</button>
                    <button class="custom-modal-btn confirm">Confirm</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        
        setTimeout(() => overlay.classList.add('active'), 10);
        
        overlay.querySelector('.cancel').addEventListener('click', () => {
            overlay.classList.remove('active');
            setTimeout(() => overlay.remove(), 300);
        });
        
        overlay.querySelector('.confirm').addEventListener('click', () => {
            overlay.classList.remove('active');
            setTimeout(() => overlay.remove(), 300);
            if (onConfirm) onConfirm();
        });
    },

    /**
     * Show custom alert modal
     */
    showAlert: function(message, type = 'info', onClose) {
        const overlay = document.createElement('div');
        overlay.className = 'custom-modal-overlay';
        const iconClass = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-times-circle' : 'fa-info-circle';
        overlay.innerHTML = `
            <div class="custom-modal">
                <div class="custom-modal-icon ${type}">
                    <i class="fas ${iconClass}"></i>
                </div>
                <div class="custom-modal-message">${message}</div>
                <div class="custom-modal-buttons">
                    <button class="custom-modal-btn confirm">OK</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        
        setTimeout(() => overlay.classList.add('active'), 10);
        
        const closeModal = () => {
            overlay.classList.remove('active');
            setTimeout(() => overlay.remove(), 300);
            if (onClose) onClose();
        };
        
        overlay.querySelector('.confirm').addEventListener('click', closeModal);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal();
        });
    }
};

// Allow OTP input to accept only numbers
document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.getElementById('otpCode');
    if (otpInput) {
        otpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
});
