// Import Alpine.js
import Alpine from 'alpinejs';

// Initialize Alpine.js
window.Alpine = Alpine;
Alpine.start();

// HTMX configuration (if needed)
document.body.addEventListener('htmx:configRequest', (event) => {
    // Add CSRF token to all HTMX requests
    event.detail.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
});

// Initialize any global JavaScript here
document.addEventListener('DOMContentLoaded', () => {
    console.log('Application initialized');
});
