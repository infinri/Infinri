/**
 * About Page JavaScript
 * 
 * Page-specific interactivity for the About module
 */

(function() {
    'use strict';

    /**
     * Initialize about page features
     */
    function init() {
        initProfileImageFallback();
        initStatsAnimation();
        initSkillCardAnimations();
    }

    /**
     * Handle profile image error fallback (CSP compliant)
     */
    function initProfileImageFallback() {
        const profileImage = document.getElementById('profile-image');
        const profilePlaceholder = document.getElementById('profile-placeholder');
        
        if (!profileImage || !profilePlaceholder) return;
        
        profileImage.addEventListener('error', function() {
            this.classList.add('hidden');
            profilePlaceholder.classList.remove('hidden');
        });
    }

    /**
     * Animate stats on scroll into view
     */
    function initStatsAnimation() {
        const stats = document.querySelectorAll('.stat-value');
        
        if (stats.length === 0) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const stat = entry.target;
                    const finalValue = stat.textContent.trim();
                    
                    // Only animate numbers
                    if (/^\d+/.test(finalValue)) {
                        animateValue(stat, finalValue);
                    }
                    
                    observer.unobserve(stat);
                }
            });
        }, { threshold: 0.5 });

        stats.forEach(stat => observer.observe(stat));
    }

    /**
     * Animate a stat value counting up (optimized with requestAnimationFrame)
     */
    function animateValue(element, finalValue) {
        const numericPart = finalValue.match(/\d+\.?\d*/)[0];
        const suffix = finalValue.replace(numericPart, '');
        const target = parseFloat(numericPart);
        const duration = 1500;
        const startTime = performance.now();
        let current = 0;

        function updateValue(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            current = target * progress;
            
            if (progress < 1) {
                element.textContent = Math.floor(current) + suffix;
                requestAnimationFrame(updateValue);
            } else {
                element.textContent = finalValue;
            }
        }

        requestAnimationFrame(updateValue);
    }

    /**
     * Add stagger animation to skill cards (CSP compliant)
     */
    function initSkillCardAnimations() {
        const skillCards = document.querySelectorAll('.skill-card');
        
        if (skillCards.length === 0) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.remove('animate-hidden');
                        entry.target.classList.add('animate-visible');
                    }, index * 100);
                    
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        skillCards.forEach((card) => {
            card.classList.add('animate-hidden');
            observer.observe(card);
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
