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
        initErrorCodeAnimation();
        initCardAnimations();
        initRedirectSuggestions();
    }

    /**
     * Animate the 404 code on page load
     */
    function initErrorCodeAnimation() {
        const errorCode = document.querySelector('.error-code');
        if (!errorCode) return;

        // Start hidden
        errorCode.style.opacity = '0';
        errorCode.style.transform = 'scale(0.5)';
        errorCode.style.transition = 'opacity 0.6s ease, transform 0.6s ease';

        // Animate in
        setTimeout(() => {
            errorCode.style.opacity = '1';
            errorCode.style.transform = 'scale(1)';
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
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 100);
                    
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        cards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
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
     * Highlight suggested card
     */
    function highlightSuggestion(page) {
        const cards = document.querySelectorAll('.error-card');
        
        cards.forEach(card => {
            const link = card.querySelector('a[href*="/' + page + '"]');
            if (link) {
                // Add visual emphasis
                card.style.border = '2px solid var(--color-primary)';
                card.style.background = 'var(--color-bg-tertiary)';
                
                // Add "Suggested" badge
                const badge = document.createElement('span');
                badge.textContent = 'Suggested';
                badge.style.cssText = `
                    display: inline-block;
                    background: var(--color-primary);
                    color: white;
                    padding: 4px 12px;
                    border-radius: 12px;
                    font-size: var(--font-size-sm);
                    font-weight: var(--font-weight-bold);
                    margin-bottom: var(--spacing-3);
                `;
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
