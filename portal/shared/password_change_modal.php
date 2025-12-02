<!-- Password Change Modal -->
<div id="passwordChangeModal" class="password-modal-overlay">
    <div class="password-modal-content">
        <!-- Modal Header -->
        <div class="password-modal-header">
            <h3 class="password-modal-title">Change Password</h3>
            <button type="button" class="password-modal-close" onclick="PasswordChangeModal.close()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Progress Indicator -->
        <div class="password-progress">
            <div class="password-step active" data-step="1">
                <div class="password-step-number">1</div>
                <div class="password-step-label">Verify</div>
            </div>
            <div class="password-progress-line"></div>
            <div class="password-step" data-step="2">
                <div class="password-step-number">2</div>
                <div class="password-step-label">OTP</div>
            </div>
            <div class="password-progress-line"></div>
            <div class="password-step" data-step="3">
                <div class="password-step-number">3</div>
                <div class="password-step-label">New Password</div>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="password-modal-body">
            <!-- Step 1: Verify Current Password -->
            <div class="password-step-content active" data-step="1">
                <p class="password-step-description">Enter your current password to verify your identity</p>
                <div class="password-form-group">
                    <label class="password-form-label">
                        <i class="fas fa-lock"></i> Current Password
                    </label>
                    <div class="password-input-wrapper">
                        <input type="password" id="currentPassword" class="password-form-input" placeholder="Enter current password">
                        <button type="button" class="password-toggle-btn" onclick="PasswordChangeModal.togglePassword('currentPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="password-error-message" id="step1Error"></div>
            </div>

            <!-- Step 2: OTP Verification -->
            <div class="password-step-content" data-step="2">
                <p class="password-step-description">
                    Enter the 6-digit code sent to <strong id="otpEmail"></strong>
                </p>
                <div class="password-form-group">
                    <label class="password-form-label">
                        <i class="fas fa-envelope"></i> Verification Code
                    </label>
                    <input type="text" id="otpCode" class="password-form-input otp-input" 
                           placeholder="000000" maxlength="6" pattern="[0-9]{6}">
                </div>
                <div class="password-otp-timer" id="otpTimer">
                    <i class="fas fa-clock"></i> Time remaining: <span id="otpTimeLeft">10:00</span>
                </div>
                <button type="button" class="password-resend-btn" id="resendOtpBtn" onclick="PasswordChangeModal.resendOTP()" disabled>
                    <i class="fas fa-redo"></i> Resend OTP
                </button>
                <div class="password-error-message" id="step2Error"></div>
            </div>

            <!-- Step 3: New Password -->
            <div class="password-step-content" data-step="3">
                <p class="password-step-description">Create a strong password that meets all requirements</p>
                
                <div class="password-form-group">
                    <label class="password-form-label">
                        <i class="fas fa-key"></i> New Password
                    </label>
                    <div class="password-input-wrapper">
                        <input type="password" id="newPassword" class="password-form-input" 
                               placeholder="Enter new password" onkeyup="PasswordChangeModal.checkPasswordStrength()">
                        <button type="button" class="password-toggle-btn" onclick="PasswordChangeModal.togglePassword('newPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="password-form-group">
                    <label class="password-form-label">
                        <i class="fas fa-check-circle"></i> Confirm New Password
                    </label>
                    <div class="password-input-wrapper">
                        <input type="password" id="confirmPassword" class="password-form-input" 
                               placeholder="Re-enter new password" onkeyup="PasswordChangeModal.checkPasswordMatch()">
                        <button type="button" class="password-toggle-btn" onclick="PasswordChangeModal.togglePassword('confirmPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Password Requirements Checklist -->
                <div class="password-requirements">
                    <p class="password-requirements-title">Password must contain:</p>
                    <div class="password-requirement" id="req-length">
                        <i class="fas fa-circle"></i> At least 8 characters
                    </div>
                    <div class="password-requirement" id="req-uppercase">
                        <i class="fas fa-circle"></i> One uppercase letter (A-Z)
                    </div>
                    <div class="password-requirement" id="req-lowercase">
                        <i class="fas fa-circle"></i> One lowercase letter (a-z)
                    </div>
                    <div class="password-requirement" id="req-number">
                        <i class="fas fa-circle"></i> One number (0-9)
                    </div>
                </div>

                <div class="password-match-indicator" id="passwordMatchIndicator"></div>
                <div class="password-error-message" id="step3Error"></div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="password-modal-footer">
            <button type="button" class="password-btn password-btn-secondary" id="backBtn" 
                    onclick="PasswordChangeModal.prevStep()" style="display: none;">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <button type="button" class="password-btn password-btn-primary" id="nextBtn" 
                    onclick="PasswordChangeModal.nextStep()">
                Next <i class="fas fa-arrow-right"></i>
            </button>
        </div>

        <!-- Loading Overlay -->
        <div class="password-loading-overlay" id="passwordLoadingOverlay">
            <div class="password-spinner"></div>
            <p id="loadingMessage">Processing...</p>
        </div>
    </div>
</div>

<style>
/* Modal Overlay */
.password-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease;
}

.password-modal-overlay.active {
    display: flex;
}

/* Modal Content */
.password-modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 550px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    animation: slideDown 0.3s ease;
    position: relative;
}

/* Modal Header */
.password-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    border-bottom: 1px solid #e5e7eb;
}

.password-modal-title {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.password-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #6b7280;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    transition: all 0.2s;
}

.password-modal-close:hover {
    background: #f3f4f6;
    color: #1f2937;
}

/* Progress Indicator */
.password-progress {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 48px;
    background: #f9fafb;
}

.password-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.password-step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: white;
    border: 2px solid #d1d5db;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #9ca3af;
    transition: all 0.3s;
}

.password-step.active .password-step-number {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.password-step.completed .password-step-number {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.password-step-label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
}

.password-step.active .password-step-label {
    color: #10b981;
    font-weight: 600;
}

.password-progress-line {
    flex: 1;
    height: 2px;
    background: #d1d5db;
    margin: 0 8px;
}

/* Modal Body */
.password-modal-body {
    padding: 32px 24px;
    min-height: 280px;
}

.password-step-content {
    display: none;
}

.password-step-content.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

.password-step-description {
    color: #6b7280;
    margin-bottom: 24px;
    line-height: 1.6;
}

/* Form Groups */
.password-form-group {
    margin-bottom: 20px;
}

.password-form-label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    font-size: 14px;
}

.password-form-label i {
    color: #10b981;
    margin-right: 6px;
}

.password-input-wrapper {
    position: relative;
}

.password-form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.2s;
    box-sizing: border-box;
}

.password-form-input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.password-input-wrapper .password-form-input {
    padding-right: 50px;
}

.password-toggle-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 8px;
    transition: color 0.2s;
}

.password-toggle-btn:hover {
    color: #10b981;
}

/* OTP Input */
.otp-input {
    text-align: center;
    font-size: 24px;
    letter-spacing: 8px;
    font-weight: 600;
}

/* OTP Timer */
.password-otp-timer {
    text-align: center;
    margin: 16px 0;
    padding: 12px;
    background: #fef3c7;
    border-radius: 8px;
    color: #92400e;
    font-weight: 500;
}

.password-otp-timer i {
    margin-right: 8px;
}

.password-resend-btn {
    display: block;
    width: 100%;
    padding: 10px;
    background: none;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    color: #6b7280;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.password-resend-btn:not(:disabled):hover {
    border-color: #10b981;
    color: #10b981;
}

.password-resend-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Password Requirements */
.password-requirements {
    background: #f9fafb;
    border-radius: 8px;
    padding: 16px;
    margin-top: 16px;
}

.password-requirements-title {
    font-weight: 600;
    color: #374151;
    margin-bottom: 12px;
    font-size: 14px;
}

.password-requirement {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 0;
    color: #6b7280;
    font-size: 14px;
}

.password-requirement i {
    font-size: 8px;
}

.password-requirement.met {
    color: #10b981;
}

.password-requirement.met i {
    color: #10b981;
}

/* Password Match Indicator */
.password-match-indicator {
    margin-top: 12px;
    padding: 10px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 14px;
    display: none;
}

.password-match-indicator.match {
    display: block;
    background: #d1fae5;
    color: #065f46;
}

.password-match-indicator.no-match {
    display: block;
    background: #fee2e2;
    color: #991b1b;
}

/* Error Messages */
.password-error-message {
    display: none;
    margin-top: 12px;
    padding: 12px;
    background: #fee2e2;
    border-left: 4px solid #dc2626;
    border-radius: 6px;
    color: #991b1b;
    font-size: 14px;
}

.password-error-message.show {
    display: block;
}

/* Modal Footer */
.password-modal-footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 20px 24px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}

.password-btn {
    flex: 1;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.password-btn-primary {
    background: #10b981;
    color: white;
}

.password-btn-primary:hover:not(:disabled) {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.password-btn-primary:disabled {
    background: #d1d5db;
    cursor: not-allowed;
}

.password-btn-secondary {
    background: white;
    color: #6b7280;
    border: 2px solid #e5e7eb;
}

.password-btn-secondary:hover {
    border-color: #10b981;
    color: #10b981;
}

/* Loading Overlay */
.password-loading-overlay {
    display: none;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    gap: 16px;
}

.password-loading-overlay.active {
    display: flex;
}

.password-spinner {
    width: 48px;
    height: 48px;
    border: 4px solid #e5e7eb;
    border-top-color: #10b981;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Custom Modal Styles */
.custom-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10001;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.custom-modal-overlay.active {
    opacity: 1;
}

.custom-modal {
    background: white;
    border-radius: 16px;
    padding: 32px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    transform: scale(0.9);
    transition: transform 0.3s ease;
    text-align: center;
}

.custom-modal-overlay.active .custom-modal {
    transform: scale(1);
}

.custom-modal-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
}

.custom-modal-icon.success {
    background: #d1fae5;
    color: #10b981;
}

.custom-modal-icon.error {
    background: #fee2e2;
    color: #ef4444;
}

.custom-modal-icon.warning {
    background: #fef3c7;
    color: #f59e0b;
}

.custom-modal-icon.info {
    background: #dbeafe;
    color: #3b82f6;
}

.custom-modal-message {
    font-size: 1.1rem;
    color: #374151;
    margin-bottom: 24px;
    line-height: 1.6;
}

.custom-modal-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.custom-modal-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.custom-modal-btn.cancel {
    background: #e5e7eb;
    color: #374151;
}

.custom-modal-btn.cancel:hover {
    background: #d1d5db;
}

.custom-modal-btn.confirm {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.custom-modal-btn.confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* Responsive */
@media (max-width: 640px) {
    .password-modal-content {
        width: 95%;
        max-height: 95vh;
    }
    
    .password-progress {
        padding: 16px 24px;
    }
    
    .password-step-number {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }
    
    .password-modal-footer {
        flex-direction: column-reverse;
    }

    .custom-modal {
        padding: 24px;
        max-width: 90%;
    }

    .custom-modal-icon {
        width: 56px;
        height: 56px;
        font-size: 28px;
    }

    .custom-modal-buttons {
        flex-direction: column;
        gap: 8px;
    }

    .custom-modal-btn {
        width: 100%;
    }
}
</style>
