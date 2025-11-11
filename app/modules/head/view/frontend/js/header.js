/**
 * Header & Navigation JavaScript
 * 
 * Mobile menu and navigation interactivity
 */

(function() {
    'use strict';

    // Prevent multiple initializations
    if (window.headerInitialized) {
        return;
    }
    window.headerInitialized = true;

    let isMenuOpen = false;
    let isAnimating = false;


    /**
     * Initialize header features
     */
    function init() {
        initMobileMenu();
        initActiveNav();
        initScrollBehavior();
    }

    /**
     * Initialize mobile menu toggle
     */
    function initMobileMenu() {
        const menuToggle = document.querySelector('.menu-toggle');
        const navList = document.querySelector('.nav-list');
        const mainNav = document.querySelector('.main-nav');
        const menuClose = document.querySelector('.menu-close');

        if (!menuToggle || !navList || !mainNav) return;

        function closeMenu() {
            if (isAnimating || !isMenuOpen) return;
            
            isAnimating = true;
            isMenuOpen = false;
            navList.classList.remove('menu-open');
            mainNav.classList.remove('menu-open');
            document.body.classList.remove('menu-animating');
            menuToggle.setAttribute('aria-expanded', 'false');
            
            // Reset animation flag after transition
            setTimeout(() => {
                isAnimating = false;
            }, 100);
        }

        function openMenu() {
            if (isAnimating || isMenuOpen) return;
            
            isAnimating = true;
            isMenuOpen = true;
            document.body.classList.add('menu-animating');
            navList.classList.add('menu-open');
            mainNav.classList.add('menu-open');
            menuToggle.setAttribute('aria-expanded', 'true');
            
            // Reset animation flag after transition
            setTimeout(() => {
                isAnimating = false;
                document.body.classList.remove('menu-animating');
            }, 100);
        }

        // Toggle menu on button click
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (isMenuOpen) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        // Close menu on close button click
        if (menuClose) {
            menuClose.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeMenu();
            });
        }

        // Close menu when clicking outside (throttled)
        const throttledOutsideClick = App.throttle(function(event) {
            if (isMenuOpen && !event.target.closest('.nav-list') && !event.target.closest('.menu-toggle')) {
                closeMenu();
            }
        }, 100);

        document.addEventListener('click', throttledOutsideClick);

        // Close menu on ESC key (throttled)
        const throttledEscClose = App.throttle(function(event) {
            if (event.key === 'Escape' && isMenuOpen) {
                closeMenu();
            }
        }, 200);

        document.addEventListener('keydown', throttledEscClose);

        // Close menu when clicking a nav link (mobile) - throttled
        navList.querySelectorAll('.nav-link').forEach(link => {
            const throttledNavClick = App.throttle(function() {
                if (window.innerWidth <= 768 && isMenuOpen) {
                    closeMenu();
                }
            }, 200);

            link.addEventListener('click', throttledNavClick);
        });
    }

    /**
     * Initialize active navigation highlighting
     */
    function initActiveNav() {
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-link').forEach(link => {
            const linkPath = link.getAttribute('href');
            
            // Exact match or home page
            if (linkPath === currentPath || 
                (currentPath === '/' && linkPath === '/') ||
                (currentPath !== '/' && linkPath !== '/' && currentPath.startsWith(linkPath))) {
                link.classList.add('current-page');
            }
        });
    }

    /**
     * Header scroll behavior - hide/show on scroll
     */
    function initScrollBehavior() {
        const header = document.querySelector('.main-header');
        if (!header) return;

        let lastScrollTop = 0;

        // Throttled scroll handler for better performance
        const throttledScroll = App.throttle(function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Add/remove shadow based on scroll position
            if (scrollTop > 10) {
                header.style.boxShadow = 'var(--shadow-xl), var(--glow-purple)';
            } else {
                header.style.boxShadow = 'var(--shadow-elevated)';
            }
            
            lastScrollTop = scrollTop;
        }, 16); // ~60fps

        window.addEventListener('scroll', throttledScroll, { passive: true });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
