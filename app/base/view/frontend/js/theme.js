/**
 * Frontend Theme JavaScript
 *
 * Customer-facing theme interactions and behaviors
 */

(function () {
    'use strict';

    /**
     * Initialize mobile menu toggle
     */
    function initMobileMenu()
    {
        const menuToggle = document.querySelector('.menu-toggle');
        const navList = document.querySelector('.nav-list');

        if (menuToggle && navList) {
            menuToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                navList.classList.toggle('active');
                const isExpanded = navList.classList.contains('active');
                menuToggle.setAttribute('aria-expanded', isExpanded);
            });

            // Close menu when clicking outside
            document.addEventListener('click', function (event) {
                if (! event.target.closest('.main-header')) {
                    navList.classList.remove('active');
                    menuToggle.setAttribute('aria-expanded', 'false');
                }
            }, { passive: true });

            // Close menu on ESC key
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && navList.classList.contains('active')) {
                    navList.classList.remove('active');
                    menuToggle.setAttribute('aria-expanded', 'false');
                }
            }, { passive: true });

            // Close menu when clicking a nav link (mobile) - use delegation
            navList.addEventListener('click', function (e) {
                const link = e.target.closest('.nav-link');
                if (link && window.innerWidth <= 768) {
                    navList.classList.remove('active');
                    menuToggle.setAttribute('aria-expanded', 'false');
                }
            }, { passive: true });
        }
    }

    /**
     * Initialize smooth scroll for anchor links
     */
    function initSmoothScroll()
    {
        // Use event delegation to avoid multiple listeners
        document.addEventListener('click', function (e) {
            const anchor = e.target.closest('a[href^="#"]');
            if (!anchor) return;
            
            const href = anchor.getAttribute('href');
            if (href === '#') return;

            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }, { passive: false }); // Can't be passive due to preventDefault
    }

    /**
     * Initialize form validation and AJAX submission
     */
    function initForms()
    {
        document.querySelectorAll('form[data-ajax]').forEach(form => {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                // Add loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Sending...';

                try {
                    const formData = new FormData(form);
                    const response = await window.App.fetchWithCsrf(form.action, {
                        method: form.method || 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok) {
                        showAlert('success', data.message || 'Form submitted successfully');
                        form.reset();
                    } else {
                        showAlert('danger', data.message || 'An error occurred');
                    }
                } catch (error) {
                    showAlert('danger', 'Network error. Please try again.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            });
        });
    }

    /**
     * Show alert message
     * @param {string} type - Alert type (success, danger, warning, info)
     * @param {string} message - Alert message
     */
    function showAlert(type, message)
    {
        const alert = document.createElement('div');
        alert.className = `alert alert - ${type}`;
        alert.textContent = message;
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.style.maxWidth = '400px';

        document.body.appendChild(alert);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }

    /**
     * Initialize lazy loading for images
     */
    function initLazyLoading()
    {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                        }
                        observer.unobserve(img);
                    }
                });
            }, {
                // Load images slightly before they enter viewport
                rootMargin: '50px'
            });

            // Batch DOM query
            const lazyImages = document.querySelectorAll('img.lazy');
            lazyImages.forEach(img => imageObserver.observe(img));
        }
    }

    /**
     * Initialize scroll-to-top button
     */
    function initScrollToTop()
    {
        const scrollBtn = document.querySelector('.scroll-to-top');
        if (! scrollBtn) {
            return;
        }

        window.addEventListener('scroll', window.App.throttle(() => {
            if (window.pageYOffset > 300) {
                scrollBtn.style.display = 'block';
            } else {
                scrollBtn.style.display = 'none';
            }
        }, 200), { passive: true });

        scrollBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    /**
     * Initialize active navigation highlighting
     */
    function initActiveNav()
    {
        const currentPath = window.location.pathname;
        // Use CSS selector to find exact match, reducing loop iterations
        const activeLink = document.querySelector(`.nav-link[href="${currentPath}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }

    /**
     * Schedule non-critical tasks using requestIdleCallback
     * @param {Function} task - Task to schedule
     */
    function scheduleIdleTask(task)
    {
        if ('requestIdleCallback' in window) {
            requestIdleCallback(task, { timeout: 2000 });
        } else {
            // Fallback for browsers without requestIdleCallback
            setTimeout(task, 1);
        }
    }

    /**
     * Initialize critical features immediately
     */
    function initCritical()
    {
        // Critical: Mobile menu must work immediately
        initMobileMenu();
        // Critical: Active nav for visual feedback
        initActiveNav();
    }

    /**
     * Initialize non-critical features during idle time
     */
    function initNonCritical()
    {
        // Schedule each initialization separately to break up long tasks
        scheduleIdleTask(initSmoothScroll);
        scheduleIdleTask(initForms);
        scheduleIdleTask(initLazyLoading);
        scheduleIdleTask(initScrollToTop);
    }

    /**
     * Initialize all theme features on DOM ready
     */
    function init()
    {
        // Run critical features immediately
        initCritical();
        
        // Defer non-critical features to idle time
        initNonCritical();
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export theme utilities to App namespace
    window.App = window.App || {};
    Object.assign(window.App, {
        showAlert,
        initMobileMenu,
        initSmoothScroll
    });

})();
