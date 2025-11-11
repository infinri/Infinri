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
        initStatsAnimation();
        initSkillCardAnimations();
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
     * Animate a stat value counting up
     */
    function animateValue(element, finalValue) {
        const numericPart = finalValue.match(/\d+\.?\d*/)[0];
        const suffix = finalValue.replace(numericPart, '');
        const target = parseFloat(numericPart);
        const duration = 1500;
        const step = target / (duration / 16);
        let current = 0;

        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                element.textContent = finalValue;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current) + suffix;
            }
        }, 16);
    }

    /**
     * Add stagger animation to skill cards
     */
    function initSkillCardAnimations() {
        const skillCards = document.querySelectorAll('.skill-card');
        
        if (skillCards.length === 0) return;

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

        skillCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
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
