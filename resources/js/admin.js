// Admin JavaScript
class AdminUI {
    constructor() {
        this.mobileMenuOpen = false;
        this.dropdowns = new Map();
        
        this.init();
    }
    
    init() {
        // Initialize mobile menu toggle
        this.initMobileMenu();
        
        // Initialize dropdowns
        this.initDropdowns();
        
        // Initialize tooltips
        this.initTooltips();
        
        // Handle window resize
        this.handleResize();
    }
    
    initMobileMenu() {
        const menuButton = document.querySelector('[data-action="toggle-menu"]');
        const sidebar = document.querySelector('.admin-sidebar');
        
        if (menuButton && sidebar) {
            menuButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.mobileMenuOpen = !this.mobileMenuOpen;
                sidebar.classList.toggle('open', this.mobileMenuOpen);
            });
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (this.mobileMenuOpen && 
                !sidebar.contains(e.target) && 
                !e.target.closest('[data-action="toggle-menu"]')) {
                this.mobileMenuOpen = false;
                sidebar.classList.remove('open');
            }
        });
    }
    
    initDropdowns() {
        document.querySelectorAll('[data-dropdown]').forEach(dropdown => {
            const id = dropdown.dataset.dropdown;
            const toggle = dropdown.querySelector('[data-dropdown-toggle]');
            const menu = dropdown.querySelector('[data-dropdown-menu]');
            
            if (!toggle || !menu) return;
            
            // Store dropdown state
            this.dropdowns.set(id, {
                open: false,
                element: dropdown,
                toggle,
                menu
            });
            
            // Toggle dropdown on click
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleDropdown(id);
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', () => {
            this.dropdowns.forEach(dropdown => {
                if (dropdown.open) {
                    this.closeDropdown(dropdown);
                }
            });
        });
    }
    
    toggleDropdown(id) {
        const dropdown = this.dropdowns.get(id);
        if (!dropdown) return;
        
        if (dropdown.open) {
            this.closeDropdown(dropdown);
        } else {
            this.openDropdown(dropdown);
        }
    }
    
    openDropdown(dropdown) {
        // Close all other dropdowns first
        this.dropdowns.forEach(d => {
            if (d !== dropdown && d.open) {
                this.closeDropdown(d);
            }
        });
        
        dropdown.open = true;
        dropdown.element.classList.add('dropdown-open');
        dropdown.menu.classList.add('show');
    }
    
    closeDropdown(dropdown) {
        dropdown.open = false;
        dropdown.element.classList.remove('dropdown-open');
        dropdown.menu.classList.remove('show');
    }
    
    initTooltips() {
        // Initialize any tooltips if needed
    }
    
    handleResize() {
        let resizeTimer;
        
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                // Handle responsive behavior
                if (window.innerWidth > 768) {
                    this.mobileMenuOpen = false;
                    const sidebar = document.querySelector('.admin-sidebar');
                    if (sidebar) sidebar.classList.remove('open');
                }
            }, 250);
        });
    }
}

// Initialize admin UI when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.adminUI = new AdminUI();
});

// Export for ES modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminUI;
}
