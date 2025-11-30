/**
 * Auth Module JavaScript
 * 
 * Handles:
 * - Password visibility toggle
 * - Password strength indicator
 * - Form submission states
 * - 2FA code input formatting
 * - Recovery code toggle
 * - Copy functionality
 */

(function() {
    'use strict';

    // ==========================================================================
    // Password Visibility Toggle
    // ==========================================================================
    
    function initPasswordToggle() {
        document.querySelectorAll('[data-toggle-password]').forEach(btn => {
            btn.addEventListener('click', function() {
                const wrapper = this.closest('.form-input-wrapper');
                const input = wrapper?.querySelector('input');
                
                if (!input) return;
                
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                
                // Update icon/aria
                this.setAttribute('aria-pressed', !isPassword);
            });
        });
    }

    // ==========================================================================
    // Password Strength Indicator
    // ==========================================================================
    
    function initPasswordStrength() {
        document.querySelectorAll('[data-password-strength]').forEach(input => {
            const meter = document.querySelector('[data-password-strength-meter]');
            if (!meter) return;
            
            input.addEventListener('input', function() {
                const password = this.value;
                const result = checkPasswordStrength(password);
                
                meter.hidden = password.length === 0;
                meter.setAttribute('data-level', result.level);
                
                const bar = meter.querySelector('.password-strength-bar');
                const text = meter.querySelector('.password-strength-text');
                
                if (bar) {
                    bar.style.setProperty('--strength-width', result.percentage + '%');
                    bar.style.setProperty('--strength-color', result.color);
                }
                
                if (text) {
                    text.textContent = result.message;
                }
            });
        });
    }
    
    function checkPasswordStrength(password) {
        let score = 0;
        const checks = {
            length: password.length >= 12,
            lowercase: /[a-z]/.test(password),
            uppercase: /[A-Z]/.test(password),
            numbers: /\d/.test(password),
            symbols: /[^a-zA-Z0-9]/.test(password),
            longLength: password.length >= 16
        };
        
        if (checks.length) score++;
        if (checks.lowercase) score++;
        if (checks.uppercase) score++;
        if (checks.numbers) score++;
        if (checks.symbols) score++;
        if (checks.longLength) score++;
        
        const levels = [
            { max: 2, level: 'weak', message: 'Weak', color: '#ef4444', percentage: 25 },
            { max: 3, level: 'fair', message: 'Fair', color: '#f59e0b', percentage: 50 },
            { max: 5, level: 'good', message: 'Good', color: '#10b981', percentage: 75 },
            { max: Infinity, level: 'strong', message: 'Strong', color: '#22c55e', percentage: 100 }
        ];
        
        return levels.find(l => score <= l.max);
    }

    // ==========================================================================
    // Form Submission State
    // ==========================================================================
    
    function initFormSubmit() {
        document.querySelectorAll('[data-auth-form]').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('[data-submit-btn]');
                if (btn) {
                    btn.disabled = true;
                    const loader = btn.querySelector('.btn-loader');
                    if (loader) loader.hidden = false;
                }
            });
        });
    }

    // ==========================================================================
    // 2FA Code Input
    // ==========================================================================
    
    function init2FAInput() {
        document.querySelectorAll('[data-2fa-input]').forEach(input => {
            // Auto-format: only numbers, auto-submit on 6 digits
            input.addEventListener('input', function(e) {
                // Strip non-numeric
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
                
                // Auto-submit when 6 digits entered
                if (this.value.length === 6) {
                    const form = this.closest('form');
                    if (form) {
                        // Small delay for UX
                        setTimeout(() => form.submit(), 100);
                    }
                }
            });
            
            // Paste handling
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text');
                this.value = pasted.replace(/\D/g, '').slice(0, 6);
                this.dispatchEvent(new Event('input'));
            });
        });
    }

    // ==========================================================================
    // Recovery Code Toggle
    // ==========================================================================
    
    function initRecoveryToggle() {
        document.querySelectorAll('[data-toggle-recovery]').forEach(btn => {
            btn.addEventListener('click', function() {
                const recoveryForm = document.querySelector('[data-recovery-form]');
                const totpForm = document.getElementById('totp-form');
                
                if (recoveryForm && totpForm) {
                    const showRecovery = recoveryForm.hidden;
                    recoveryForm.hidden = !showRecovery;
                    totpForm.hidden = showRecovery;
                    
                    // Focus appropriate input
                    const input = (showRecovery ? recoveryForm : totpForm).querySelector('input');
                    if (input) input.focus();
                }
            });
        });
    }

    // ==========================================================================
    // Copy Functionality
    // ==========================================================================
    
    function initCopy() {
        // Single value copy
        document.querySelectorAll('[data-copy]').forEach(btn => {
            btn.addEventListener('click', async function() {
                const value = this.dataset.copy;
                await copyToClipboard(value);
                showCopyFeedback(this);
            });
        });
        
        // Copy all recovery codes
        document.querySelectorAll('[data-copy-codes]').forEach(btn => {
            btn.addEventListener('click', async function() {
                const codes = Array.from(document.querySelectorAll('.recovery-code'))
                    .map(el => el.textContent)
                    .join('\n');
                await copyToClipboard(codes);
                showCopyFeedback(this);
            });
        });
        
        // Download recovery codes
        document.querySelectorAll('[data-download-codes]').forEach(btn => {
            btn.addEventListener('click', function() {
                const codes = Array.from(document.querySelectorAll('.recovery-code'))
                    .map(el => el.textContent)
                    .join('\n');
                
                const blob = new Blob([
                    '# Recovery Codes\n',
                    '# Keep these codes safe. Each code can only be used once.\n\n',
                    codes
                ], { type: 'text/plain' });
                
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'recovery-codes.txt';
                a.click();
                URL.revokeObjectURL(url);
            });
        });
    }
    
    async function copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            return true;
        }
    }
    
    function showCopyFeedback(btn) {
        const originalText = btn.textContent;
        btn.textContent = 'Copied!';
        btn.disabled = true;
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.disabled = false;
        }, 2000);
    }

    // ==========================================================================
    // Initialize
    // ==========================================================================
    
    function init() {
        initPasswordToggle();
        initPasswordStrength();
        initFormSubmit();
        init2FAInput();
        initRecoveryToggle();
        initCopy();
    }
    
    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();
