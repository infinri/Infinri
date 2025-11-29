/**
 * Infinri Theme JavaScript
 * 
 * Theme-specific interactions:
 * - Mobile menu toggle
 * - Active navigation highlighting
 * - Smooth scroll (uses Core's utilities)
 */

(function() {
    'use strict';

    /**
     * Mobile Menu
     * Handles hamburger toggle and slide-out menu
     */
    function initMobileMenu() {
        const menuToggle = document.querySelector('.menu-toggle');
        const navMenu = document.querySelector('.nav-menu');
        const menuClose = document.querySelector('.menu-close');

        if (!menuToggle || !navMenu) return;

        // Toggle menu on hamburger click
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = navMenu.classList.toggle('active');
            menuToggle.setAttribute('aria-expanded', isOpen);
        });

        // Close on X button
        if (menuClose) {
            menuClose.addEventListener('click', function() {
                navMenu.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            });
        }

        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.header') && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        }, { passive: true });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.focus();
            }
        });

        // Close on link click (mobile)
        navMenu.addEventListener('click', function(e) {
            if (e.target.closest('.nav-link') && window.innerWidth <= 768) {
                navMenu.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        }, { passive: true });
    }

    /**
     * Active Navigation
     * Highlights current page in nav
     */
    function initActiveNav() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPath || (currentPath === '/' && href === '/')) {
                link.classList.add('active');
            }
        });
    }

    /**
     * Smooth Scroll
     * For anchor links (uses native CSS scroll-behavior as fallback)
     */
    function initSmoothScroll() {
        document.addEventListener('click', function(e) {
            const anchor = e.target.closest('a[href^="#"]');
            if (!anchor) return;
            
            const href = anchor.getAttribute('href');
            if (href === '#') return;

            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    /**
     * Initialize
     */
    function init() {
        // Critical - run immediately
        initMobileMenu();
        initActiveNav();
        
        // Non-critical - defer
        if ('requestIdleCallback' in window) {
            requestIdleCallback(initSmoothScroll);
        } else {
            setTimeout(initSmoothScroll, 1);
        }
    }

    // Run when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
