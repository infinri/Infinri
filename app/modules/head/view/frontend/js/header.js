/**
 * Header & Navigation JavaScript
 * 
 * Mobile menu and navigation interactivity
 */

(function() {
    'use strict';

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

        if (!menuToggle || !navList) return;

        // Toggle menu on button click
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            navList.classList.toggle('active');
            const isExpanded = navList.classList.contains('active');
            menuToggle.setAttribute('aria-expanded', isExpanded);
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.main-header')) {
                navList.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close menu on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && navList.classList.contains('active')) {
                navList.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close menu when clicking a nav link (mobile)
        navList.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    navList.classList.remove('active');
                    menuToggle.setAttribute('aria-expanded', 'false');
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
                link.classList.add('active');
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
        let ticking = false;

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    
                    // Add/remove shadow based on scroll position
                    if (scrollTop > 10) {
                        header.style.boxShadow = 'var(--shadow-xl), var(--glow-purple)';
                    } else {
                        header.style.boxShadow = 'var(--shadow-elevated)';
                    }
                    
                    lastScrollTop = scrollTop;
                    ticking = false;
                });
                
                ticking = true;
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
