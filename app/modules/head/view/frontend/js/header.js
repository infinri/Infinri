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
        const navMenu = document.querySelector('.nav-menu');
        const menuClose = document.querySelector('.menu-close');

        if (!menuToggle || !navMenu) return;

        function closeMenu() {
            if (isAnimating || !isMenuOpen) return;
            
            isAnimating = true;
            isMenuOpen = false;
            navMenu.classList.remove('open');
            menuToggle.setAttribute('aria-expanded', 'false');
            
            // Reset animation flag after transition
            setTimeout(() => {
                isAnimating = false;
            }, 300);
        }

        function openMenu() {
            if (isAnimating || isMenuOpen) return;
            
            isAnimating = true;
            isMenuOpen = true;
            navMenu.classList.add('open');
            menuToggle.setAttribute('aria-expanded', 'true');
            
            // Reset animation flag after transition
            setTimeout(() => {
                isAnimating = false;
            }, 300);
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

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (isMenuOpen && !event.target.closest('.nav-menu') && !event.target.closest('.menu-toggle')) {
                closeMenu();
            }
        });

        // Close menu on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && isMenuOpen) {
                closeMenu();
            }
        });

        // Close menu when clicking a nav link (mobile)
        navMenu.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768 && isMenuOpen) {
                    closeMenu();
                }
            });
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
     * Header scroll behavior - enhance shadow on scroll
     */
    function initScrollBehavior() {
        const header = document.querySelector('.header');
        if (!header) return;

        let lastScrollTop = 0;

        // Scroll handler for shadow enhancement
        function handleScroll() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Add/remove enhanced shadow based on scroll position
            if (scrollTop > 10) {
                header.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.9), 0 0 2px rgba(157, 78, 221, 0.3)';
            } else {
                header.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.8), 0 0 1px rgba(157, 78, 221, 0.15)';
            }
            
            lastScrollTop = scrollTop;
        }

        window.addEventListener('scroll', handleScroll, { passive: true });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
