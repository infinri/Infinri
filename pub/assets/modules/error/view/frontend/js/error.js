/**
 * Error Page JavaScript
 * 
 * 404 page interactions and animations
 */

(function() {
    'use strict';

    /**
     * Initialize error page features
     */
    function init() {
        initNavigationButtons();
        initErrorCodeAnimation();
        initCardAnimations();
        initRedirectSuggestions();
    }

    /**
     * Handle navigation buttons (CSP compliant)
     */
    function initNavigationButtons() {
        // Handle reload page button
        const reloadButton = document.getElementById('reload-page');
        if (reloadButton) {
            reloadButton.addEventListener('click', function() {
                window.location.reload();
            });
        }

        // Handle go back button
        const goBackButton = document.getElementById('go-back');
        if (goBackButton) {
            goBackButton.addEventListener('click', function() {
                window.history.back();
            });
        }
    }

    /**
     * Animate the 404 code on page load (CSP compliant)
     */
    function initErrorCodeAnimation() {
        const errorCode = document.querySelector('.error-code');
        if (!errorCode) return;

        // Start with hidden class
        errorCode.classList.add('animate-hidden');

        // Animate in
        setTimeout(() => {
            errorCode.classList.remove('animate-hidden');
            errorCode.classList.add('animate-visible');
        }, 100);
    }

    /**
     * Animate cards on scroll into view
     */
    function initCardAnimations() {
        const cards = document.querySelectorAll('.error-card');
        if (cards.length === 0) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.remove('card-hidden');
                        entry.target.classList.add('card-visible');
                    }, index * 100);
                    
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        cards.forEach(card => {
            card.classList.add('card-hidden');
            observer.observe(card);
        });
    }

    /**
     * Smart redirect suggestions based on URL
     */
    function initRedirectSuggestions() {
        const currentPath = window.location.pathname;
        
        // Simple logic to suggest relevant page based on URL
        const suggestions = {
            '/about': 'about',
            '/service': 'services',
            '/contact': 'contact',
            '/portfolio': 'home'
        };

        // Check if current path is similar to any known page
        for (const [path, page] of Object.entries(suggestions)) {
            if (currentPath.toLowerCase().includes(path)) {
                highlightSuggestion(page);
                break;
            }
        }
    }

    /**
     * Highlight suggested card (CSP compliant)
     */
    function highlightSuggestion(page) {
        const cards = document.querySelectorAll('.error-card');
        
        cards.forEach(card => {
            const link = card.querySelector('a[href*="/' + page + '"]');
            if (link) {
                // Add visual emphasis with CSS class
                card.classList.add('suggested-card');
                
                // Add "Suggested" badge
                const badge = document.createElement('span');
                badge.textContent = 'Suggested';
                badge.className = 'suggested-badge';
                card.insertBefore(badge, card.firstChild);
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
