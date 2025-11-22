/**
 * Base JavaScript
 *
 * Core utilities shared across all pages
 * DO NOT duplicate these functions - use them throughout the app
 */

(function () {
    'use strict';

    /**
     * Get CSRF token from meta tag
     * @returns {string} CSRF token
     */
    function getCsrfToken()
    {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.content : '';
    }

    /**
     * Fetch with CSRF token automatically included
     * @param {string} url - URL to fetch
     * @param {Object} options - Fetch options
     * @returns {Promise} Fetch promise
     */
    function fetchWithCsrf(url, options = {})
    {
        options.headers = {
            ...options.headers,
            'X-CSRF-Token': getCsrfToken()
        };
        return fetch(url, options);
    }

    /**
     * Debounce function calls
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in ms
     * @returns {Function} Debounced function
     */
    function debounce(func, wait = 300)
    {
        let timeout;
        return function executedFunction(...args)
        {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle function calls
     * @param {Function} func - Function to throttle
     * @param {number} limit - Time limit in ms
     * @returns {Function} Throttled function
     */
    function throttle(func, limit = 300)
    {
        let inThrottle;
        return function (...args) {
            if (! inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Add event listener with delegation
     * @param {string} selector - CSS selector
     * @param {string} eventType - Event type
     * @param {Function} handler - Event handler
     * @param {Element} parent - Parent element (default: document)
     */
    function delegate(selector, eventType, handler, parent = document)
    {
        parent.addEventListener(eventType, function (event) {
            const target = event.target.closest(selector);
            if (target) {
                handler.call(target, event);
            }
        });
    }

    /**
     * Parse JSON safely
     * @param {string} json - JSON string
     * @param {*} fallback - Fallback value
     * @returns {*} Parsed value or fallback
     */
    function parseJSON(json, fallback = null)
    {
        try {
            return JSON.parse(json);
        } catch (e) {
            return fallback;
        }
    }

    /**
     * Get query parameter from URL
     * @param {string} param - Parameter name
     * @returns {string|null} Parameter value
     */
    function getQueryParam(param)
    {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }

    /**
     * Simple cookie management
     */
    const Cookie = {
        get(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name} = `);
            if (parts.length === 2) {
                return parts.pop().split(';').shift();
            }
            return null;
        },

        set(name, value, days = 7) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = `${name} = ${value};expires = ${expires.toUTCString()};path = / ;SameSite = Strict`;
        },

        delete(name) {
            document.cookie = `${name} = ;expires = Thu, 01 Jan 1970 00:00:00 UTC;path = / `;
        }
    };

    /**
     * Simple local storage wrapper with JSON support
     */
    const Storage = {
        get(key, fallback = null) {
            try {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : fallback;
            } catch (e) {
                return fallback;
            }
        },

        set(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
                return true;
            } catch (e) {
                return false;
            }
        },

        remove(key) {
            try {
                localStorage.removeItem(key);
                return true;
                } catch (e) {
                return false;
            }
        }
    };

    /**
     * Task Scheduler - Break up long initialization tasks
     * Prevents main thread blocking by spreading work across idle periods
     */
    const TaskScheduler = {
        queue: [],
        isProcessing: false,

        /**
         * Schedule a task to run during idle time
         * @param {Function} task - Task to execute
         * @param {string} priority - 'critical' or 'normal'
         */
        schedule(task, priority = 'normal') {
            this.queue.push({ task, priority });
            
            if (!this.isProcessing) {
                this.process();
            }
        },

        /**
         * Process queued tasks
         */
        process() {
            if (this.queue.length === 0) {
                this.isProcessing = false;
                return;
            }

            this.isProcessing = true;

            // Sort by priority (critical first)
            this.queue.sort((a, b) => {
                if (a.priority === 'critical' && b.priority !== 'critical') return -1;
                if (a.priority !== 'critical' && b.priority === 'critical') return 1;
                return 0;
            });

            const { task } = this.queue.shift();

            // Use requestIdleCallback if available, otherwise setTimeout
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => {
                    task();
                    this.process();
                }, { timeout: 2000 });
            } else {
                setTimeout(() => {
                    task();
                    this.process();
                }, 0);
            }
        }
    };

    // Export to global App namespace
    window.App = window.App || {};
    Object.assign(window.App, {
        // CSRF
        getCsrfToken,
        fetchWithCsrf,

        // Utilities
        debounce,
        throttle,
        delegate,
        parseJSON,
        getQueryParam,

        // Storage
        Cookie,
        Storage,

        // Task Scheduler
        TaskScheduler
    });

})();
