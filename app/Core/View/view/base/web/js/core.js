/**
 * Core JavaScript Utilities
 * 
 * Shared utilities for frontend and admin.
 * No external dependencies - vanilla JS only.
 */

(function(window, document) {
    'use strict';

    /**
     * Infinri namespace
     */
    window.Infinri = window.Infinri || {};

    /**
     * DOM Ready helper
     * @param {Function} fn Callback when DOM is ready
     */
    Infinri.ready = function(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    };

    /**
     * Query selector helper
     * @param {string} selector CSS selector
     * @param {Element} context Parent element (default: document)
     * @returns {Element|null}
     */
    Infinri.$ = function(selector, context) {
        return (context || document).querySelector(selector);
    };

    /**
     * Query selector all helper
     * @param {string} selector CSS selector
     * @param {Element} context Parent element (default: document)
     * @returns {NodeList}
     */
    Infinri.$$ = function(selector, context) {
        return (context || document).querySelectorAll(selector);
    };

    /**
     * Add event listener with delegation support
     * @param {Element|string} target Element or selector
     * @param {string} event Event name
     * @param {Function|string} handlerOrSelector Handler or delegate selector
     * @param {Function} delegateHandler Handler for delegation
     */
    Infinri.on = function(target, event, handlerOrSelector, delegateHandler) {
        const element = typeof target === 'string' ? document : target;
        const selector = typeof target === 'string' ? target : 
                        (typeof handlerOrSelector === 'string' ? handlerOrSelector : null);
        const handler = delegateHandler || handlerOrSelector;

        if (selector) {
            // Event delegation
            element.addEventListener(event, function(e) {
                const delegateTarget = e.target.closest(selector);
                if (delegateTarget) {
                    handler.call(delegateTarget, e, delegateTarget);
                }
            });
        } else {
            element.addEventListener(event, handler);
        }
    };

    /**
     * Toggle class helper
     * @param {Element} element Target element
     * @param {string} className Class to toggle
     * @param {boolean} force Force add/remove
     * @returns {boolean} New state
     */
    Infinri.toggle = function(element, className, force) {
        return element.classList.toggle(className, force);
    };

    /**
     * Debounce function
     * @param {Function} fn Function to debounce
     * @param {number} wait Wait time in ms
     * @returns {Function}
     */
    Infinri.debounce = function(fn, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn.apply(this, args), wait);
        };
    };

    /**
     * Throttle function
     * @param {Function} fn Function to throttle
     * @param {number} limit Time limit in ms
     * @returns {Function}
     */
    Infinri.throttle = function(fn, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                fn.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    };

    /**
     * Simple AJAX/Fetch wrapper
     * @param {string} url Request URL
     * @param {Object} options Fetch options
     * @returns {Promise}
     */
    Infinri.fetch = async function(url, options = {}) {
        const defaults = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        };

        // Add CSRF token if available
        const csrfToken = Infinri.$('meta[name="csrf-token"]')?.content;
        if (csrfToken) {
            defaults.headers['X-CSRF-TOKEN'] = csrfToken;
        }

        const config = { ...defaults, ...options };
        
        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }

        const response = await fetch(url, config);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const contentType = response.headers.get('Content-Type');
        if (contentType?.includes('application/json')) {
            return response.json();
        }
        
        return response.text();
    };

    /**
     * Toast notification system
     */
    Infinri.toast = {
        container: null,
        
        init: function() {
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.className = 'toast-container toast-container-top-right';
                document.body.appendChild(this.container);
            }
        },
        
        show: function(message, options = {}) {
            this.init();
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${options.type || 'info'}`;
            toast.innerHTML = `
                <div class="toast-content">
                    ${options.title ? `<div class="toast-title">${options.title}</div>` : ''}
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" aria-label="Close">&times;</button>
            `;
            
            const closeBtn = toast.querySelector('.toast-close');
            closeBtn.addEventListener('click', () => this.hide(toast));
            
            this.container.appendChild(toast);
            
            // Auto dismiss
            if (options.duration !== false) {
                setTimeout(() => this.hide(toast), options.duration || 5000);
            }
            
            return toast;
        },
        
        hide: function(toast) {
            toast.classList.add('toast-exiting');
            setTimeout(() => toast.remove(), 200);
        },
        
        success: function(message, options = {}) {
            return this.show(message, { ...options, type: 'success' });
        },
        
        error: function(message, options = {}) {
            return this.show(message, { ...options, type: 'danger' });
        },
        
        warning: function(message, options = {}) {
            return this.show(message, { ...options, type: 'warning' });
        },
        
        info: function(message, options = {}) {
            return this.show(message, { ...options, type: 'info' });
        }
    };

    /**
     * Modal helper
     */
    Infinri.modal = {
        open: function(modalId) {
            const modal = Infinri.$(`#${modalId}`);
            const backdrop = Infinri.$(`#${modalId}-backdrop`) || this.createBackdrop(modalId);
            
            if (modal) {
                document.body.classList.add('modal-open');
                backdrop.setAttribute('data-visible', 'true');
                modal.setAttribute('data-visible', 'true');
                
                // Focus first focusable element
                const focusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (focusable) focusable.focus();
            }
        },
        
        close: function(modalId) {
            const modal = Infinri.$(`#${modalId}`);
            const backdrop = Infinri.$(`#${modalId}-backdrop`);
            
            if (modal) {
                modal.setAttribute('data-visible', 'false');
                if (backdrop) backdrop.setAttribute('data-visible', 'false');
                document.body.classList.remove('modal-open');
            }
        },
        
        createBackdrop: function(modalId) {
            const backdrop = document.createElement('div');
            backdrop.id = `${modalId}-backdrop`;
            backdrop.className = 'modal-backdrop';
            backdrop.addEventListener('click', () => this.close(modalId));
            document.body.appendChild(backdrop);
            return backdrop;
        }
    };

    /**
     * Form validation helper
     */
    Infinri.validate = {
        form: function(form) {
            const inputs = form.querySelectorAll('[required], [data-validate]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!this.input(input)) {
                    isValid = false;
                }
            });
            
            return isValid;
        },
        
        input: function(input) {
            const value = input.value.trim();
            const rules = input.dataset.validate?.split('|') || [];
            
            // Required check
            if (input.required && !value) {
                this.showError(input, 'This field is required');
                return false;
            }
            
            // Custom rules
            for (const rule of rules) {
                const [name, param] = rule.split(':');
                
                if (name === 'email' && value && !this.isEmail(value)) {
                    this.showError(input, 'Please enter a valid email');
                    return false;
                }
                
                if (name === 'min' && value.length < parseInt(param)) {
                    this.showError(input, `Minimum ${param} characters required`);
                    return false;
                }
                
                if (name === 'max' && value.length > parseInt(param)) {
                    this.showError(input, `Maximum ${param} characters allowed`);
                    return false;
                }
            }
            
            this.clearError(input);
            return true;
        },
        
        isEmail: function(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },
        
        showError: function(input, message) {
            input.classList.add('form-input-error');
            input.setAttribute('aria-invalid', 'true');
            
            let error = input.nextElementSibling;
            if (!error || !error.classList.contains('form-error')) {
                error = document.createElement('div');
                error.className = 'form-error';
                input.parentNode.insertBefore(error, input.nextSibling);
            }
            error.textContent = message;
        },
        
        clearError: function(input) {
            input.classList.remove('form-input-error');
            input.removeAttribute('aria-invalid');
            
            const error = input.nextElementSibling;
            if (error?.classList.contains('form-error')) {
                error.remove();
            }
        }
    };

    /**
     * Auto-init components on DOM ready
     */
    Infinri.ready(function() {
        // Auto-init dropdowns, tooltips, etc. here
        console.log('Infinri Core initialized');
    });

})(window, document);
