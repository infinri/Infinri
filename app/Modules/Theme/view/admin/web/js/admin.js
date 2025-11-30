/**
 * Admin Layout JavaScript
 * 
 * Sidebar toggle and keyboard shortcuts only.
 * Uses Core's modal, dropdown, and alert components.
 */

(function() {
    'use strict';

    const sidebar = document.querySelector('[data-sidebar]');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebarCollapse = document.querySelector('[data-sidebar-collapse]');

    // Mobile toggle
    sidebarToggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
    });

    // Desktop collapse with localStorage
    if (sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('admin-sidebar', sidebar.classList.contains('collapsed') ? 'collapsed' : 'open');
        });

        // Restore preference
        if (localStorage.getItem('admin-sidebar') === 'collapsed') {
            sidebar.classList.add('collapsed');
        }
    }

    // Close sidebar on outside click (mobile)
    document.addEventListener('click', (e) => {
        if (sidebar?.classList.contains('open') && 
            !sidebar.contains(e.target) && 
            !sidebarToggle?.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            sidebarCollapse?.click();
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.querySelector('[data-search]')?.focus();
        }
    });

})();
