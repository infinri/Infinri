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
    }

    /**
     * Smooth scroll for anchor links in footer
     */
    function initSmoothScroll() {
        const footerLinks = document.querySelectorAll('.main-footer a[href*="#"]');
        
        footerLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                // Skip if no href, just "#", or external link
                if (!href || href === '#' || href.startsWith('http')) {
                    return;
                }
                
                // Extract target ID from href (handles both /page#section and #section)
                const hashIndex = href.indexOf('#');
                if (hashIndex === -1) return;
                
                const targetId = href.substring(hashIndex + 1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    e.preventDefault();
                    targetElement.scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Update URL without jumping
                    if (history.pushState) {
                        history.pushState(null, null, '#' + targetId);
                    }
                }
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
