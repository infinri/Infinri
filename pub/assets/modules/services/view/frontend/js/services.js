/**
 * Services Page JavaScript
 * 
 * Service card animations and interactions
 */

(function() {
    'use strict';


    /**
     * Initialize services page features
     */
    function init() {
        initServiceCardAnimations();
        initIconFloatEffect();
        initBadgeAnimation();
    }

    /**
     * Animate service cards on scroll into view
     */
    function initServiceCardAnimations() {
        const serviceCards = document.querySelectorAll('.service-card');
        
        if (serviceCards.length === 0) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    // Stagger animation delay
                    setTimeout(() => {
                        entry.target.classList.remove('animate-hidden');
                        entry.target.classList.add('animate-visible');
                    }, index * 150);
                    
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        // Set initial state and observe
        serviceCards.forEach(card => {
            card.classList.add('animate-hidden');
            observer.observe(card);
        });
    }

    /**
     * Enhanced float effect for service icons
     */
    function initIconFloatEffect() {
        const serviceIcons = document.querySelectorAll('.service-icon');
        
        serviceIcons.forEach((icon, index) => {
            // Vary animation delay for each icon (using CSS custom property)
            icon.style.setProperty('--animation-delay', `${index * 0.2}s`);
            
            // Add hover pause effect (throttled)
            const throttledPause = App.throttle(function() {
                icon.classList.add('paused');
                icon.classList.remove('running');
            }, 50);
            
            const throttledResume = App.throttle(function() {
                icon.classList.remove('paused');
                icon.classList.add('running');
            }, 50);
            
            icon.parentElement.addEventListener('mouseenter', throttledPause);
            icon.parentElement.addEventListener('mouseleave', throttledResume);
        });
    }

    /**
     * Animate badge on card hover
     */
    function initBadgeAnimation() {
        const cardsWithBadge = document.querySelectorAll('.service-card .service-badge');
        
        cardsWithBadge.forEach(badge => {
            const card = badge.closest('.service-card');
            
            if (!card) return;
            
            card.addEventListener('mouseenter', function() {
                badge.classList.add('badge-hover');
            });
            
            card.addEventListener('mouseleave', function() {
                badge.classList.remove('badge-hover');
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
