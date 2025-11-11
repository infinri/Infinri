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
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
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
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    }

    /**
     * Enhanced float effect for service icons
     */
    function initIconFloatEffect() {
        const serviceIcons = document.querySelectorAll('.service-icon');
        
        serviceIcons.forEach((icon, index) => {
            // Vary animation delay for each icon
            icon.style.animationDelay = `${index * 0.2}s`;
            
            // Add hover pause effect
            icon.parentElement.addEventListener('mouseenter', function() {
                icon.style.animationPlayState = 'paused';
            });
            
            icon.parentElement.addEventListener('mouseleave', function() {
                icon.style.animationPlayState = 'running';
            });
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
                badge.style.transform = 'scale(1.1) rotate(5deg)';
                badge.style.transition = 'transform 0.3s ease';
            });
            
            card.addEventListener('mouseleave', function() {
                badge.style.transform = 'scale(1) rotate(0deg)';
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
