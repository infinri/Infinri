/**
 * Contact Page JavaScript
 * 
 * Form validation and AJAX submission for the Contact module
 */

(function() {
    'use strict';

    /**
     * Initialize contact page features
     */
    function init() {
        initFormValidation();
        initFormSubmission();
        initInfoCardAnimations();
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        const form = document.querySelector('.contact-form');
        if (!form) return;

        const inputs = form.querySelectorAll('.form-input, .form-textarea');
        
        inputs.forEach(input => {
            // Validate on blur
            input.addEventListener('blur', function() {
                validateField(this);
            });

            // Clear error on input
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    }

    /**
     * Validate a single field
     */
    function validateField(field) {
        const value = field.value.trim();
        const type = field.type;
        let isValid = true;
        let errorMessage = '';

        // Required field check
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }
        // Email validation
        else if (type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }

        if (!isValid) {
            showFieldError(field, errorMessage);
        }

        return isValid;
    }

    /**
     * Show field error
     */
    function showFieldError(field, message) {
        const formGroup = field.closest('.form-group');
        if (!formGroup) return;

        // Remove existing error
        const existingError = formGroup.querySelector('.field-error');
        if (existingError) existingError.remove();

        // Add error class
        field.classList.add('field-invalid');

        // Add error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = 'var(--color-danger)';
        errorDiv.style.fontSize = 'var(--font-size-sm)';
        errorDiv.style.marginTop = 'var(--spacing-2)';
        errorDiv.textContent = message;
        formGroup.appendChild(errorDiv);
    }

    /**
     * Clear field error
     */
    function clearFieldError(field) {
        const formGroup = field.closest('.form-group');
        if (!formGroup) return;

        field.classList.remove('field-invalid');
        const error = formGroup.querySelector('.field-error');
        if (error) error.remove();
    }

    /**
     * Initialize AJAX form submission
     */
    function initFormSubmission() {
        const form = document.querySelector('.contact-form');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate all fields
            const inputs = form.querySelectorAll('.form-input, .form-textarea');
            let isValid = true;

            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                showMessage('Please fix the errors above', 'error');
                return;
            }

            // Submit form
            submitForm(form);
        });
    }

    /**
     * Submit form via AJAX
     */
    function submitForm(form) {
        const submitButton = form.querySelector('.form-submit');
        const originalText = submitButton.innerHTML;
        
        // Disable button and show loading
        submitButton.disabled = true;
        submitButton.innerHTML = '<span>Sending...</span>';

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Message sent successfully! We\'ll get back to you soon.', 'success');
                form.reset();
            } else {
                showMessage(data.message || 'An error occurred. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Form submission error:', error);
            showMessage('An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            // Re-enable button
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        });
    }

    /**
     * Show message to user
     */
    function showMessage(message, type) {
        // Remove existing message
        const existing = document.querySelector('.form-message');
        if (existing) existing.remove();

        const messageDiv = document.createElement('div');
        messageDiv.className = `form-message alert alert-${type}`;
        messageDiv.textContent = message;
        messageDiv.style.marginTop = 'var(--spacing-4)';

        const form = document.querySelector('.contact-form');
        form.parentNode.insertBefore(messageDiv, form.nextSibling);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            messageDiv.style.transition = 'opacity 0.5s';
            setTimeout(() => messageDiv.remove(), 500);
        }, 5000);
    }

    /**
     * Animate info cards on scroll
     */
    function initInfoCardAnimations() {
        const infoCards = document.querySelectorAll('.info-card');
        if (infoCards.length === 0) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 100);
                    
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        infoCards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
