/**
 * Footer JavaScript
 * 
 * Footer-specific interactivity
 */

(function() {
    'use strict';

    /**
     * Initialize footer features
     */
    function init() {
        initSmoothScroll();
        initLinkHoverEffects();
    }

    /**
     * Smooth scroll for footer links
     */
    function initSmoothScroll() {
        const footerLinks = document.querySelectorAll('.footer-section a[href^="/"]');
        
        footerLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Only for internal links that match current domain
                const href = this.getAttribute('href');
                if (href && href.startsWith('/') && !href.includes('#')) {
                    // Let browser handle normal navigation
                    return;
                }
                
                // Handle anchor links
                if (href && href.includes('#')) {
                    const targetId = href.split('#')[1];
                    const targetElement = document.getElementById(targetId);
                    
                    if (targetElement) {
                        e.preventDefault();
                        targetElement.scrollIntoView({ 
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });
    }

    /**
     * Enhanced hover effects for footer links
     */
    function initLinkHoverEffects() {
        const footerLinks = document.querySelectorAll('.footer-section a');
        
        footerLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.textShadow = '0 0 8px rgba(157, 78, 221, 0.4)';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.textShadow = 'none';
            });
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
