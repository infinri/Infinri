/**
 * Home Page JavaScript
 * 
 * Landing page animations and interactions
 */

(function() {
    'use strict';

    /**
     * Initialize home page features
     */
    function init() {
        initHeroAnimation();
        initFeatureCards();
        initTypingEffect();
    }

    /**
     * Animate hero section on page load
     */
    function initHeroAnimation() {
        const heroTitle = document.querySelector('.hero-title');
        const heroSubtitle = document.querySelector('.hero-subtitle');
        const heroButtons = document.querySelector('.hero-buttons');

        if (!heroTitle) return;

        // Stagger fade-in animation by adding 'loaded' class
        setTimeout(() => {
            if (heroTitle) {
                heroTitle.classList.add('loaded');
            }
        }, 100);

        setTimeout(() => {
            if (heroSubtitle) {
                heroSubtitle.classList.add('loaded');
            }
        }, 300);

        setTimeout(() => {
            if (heroButtons) {
                heroButtons.classList.add('loaded');
            }
        }, 500);
    }

    /**
     * Animate feature cards on scroll into view
     */
    function initFeatureCards() {
        const cards = document.querySelectorAll('.features-section .card');
        
        if (cards.length === 0) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, index * 150); // Stagger animation
                    
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        cards.forEach(card => {
            observer.observe(card);
        });
    }

    /**
     * Simple typing effect for hero title (optional enhancement)
     */
    function initTypingEffect() {
        const heroTitle = document.querySelector('.hero-title');
        if (!heroTitle) return;

        // Store original text
        const originalText = heroTitle.textContent;
        
        // Optional: Add a cursor blink effect
        const addCursorBlink = false; // Set to true to enable
        
        if (addCursorBlink) {
            // Create cursor element
            const cursor = document.createElement('span');
            cursor.className = 'typing-cursor';
            cursor.textContent = '|';
            cursor.style.animation = 'blink 1s infinite';
            cursor.style.marginLeft = '2px';
            
            // Add cursor after title loads
            setTimeout(() => {
                heroTitle.appendChild(cursor);
            }, 1000);
            
            // Add blink animation to page
            if (!document.querySelector('#typing-cursor-animation')) {
                const style = document.createElement('style');
                style.id = 'typing-cursor-animation';
                style.textContent = `
                    @keyframes blink {
                        0%, 50% { opacity: 1; }
                        51%, 100% { opacity: 0; }
                    }
                `;
                document.head.appendChild(style);
            }
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
