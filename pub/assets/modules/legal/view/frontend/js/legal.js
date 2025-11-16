/**
 * Legal Documentation JavaScript
 * 
 * Handles print-to-PDF functionality and smooth scrolling
 */

document.addEventListener('DOMContentLoaded', () => {
    // Download PDF button
    const downloadBtn = document.getElementById('download-pdf');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', () => {
            window.print();
        });
    }

    // Add print event listener for analytics (optional)
    window.addEventListener('beforeprint', () => {
        console.log('User initiated print/PDF download');
    });

    // Smooth scroll to sections if hash present
    if (window.location.hash) {
        const targetId = window.location.hash.substring(1);
        const targetElement = document.getElementById(targetId);
        if (targetElement) {
            setTimeout(() => {
                targetElement.scrollIntoView({ behavior: 'smooth' });
            }, 100);
        }
    }
});
