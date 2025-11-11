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
            });

            // Close menu on ESC key
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && navList.classList.contains('active')) {
                    navList.classList.remove('active');
                    menuToggle.setAttribute('aria-expanded', 'false');
                }
            });

            // Close menu when clicking a nav link (mobile)
            navList.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function () {
                    if (window.innerWidth <= 768) {
                        navList.classList.remove('active');
                        menuToggle.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        }
    }

    /**
     * Initialize smooth scroll for anchor links
     */
    function initSmoothScroll()
    {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#') {
                    return;
                }

                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
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
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img.lazy').forEach(img => {
                imageObserver.observe(img);
            });
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
        }, 200));

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
        document.querySelectorAll('.nav-link').forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
    }

    /**
     * Initialize all theme features on DOM ready
     */
    function init()
    {
        initMobileMenu();
        initSmoothScroll();
        initForms();
        initLazyLoading();
        initScrollToTop();
        initActiveNav();
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
