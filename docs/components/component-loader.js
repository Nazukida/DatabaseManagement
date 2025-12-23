/**
 * UI Components Loader
 * Utility to easily load and initialize UI components across pages
 */
class UIComponentsLoader {
    constructor() {
        this.topBar = null;
        this.tabBar = null;
        this.initialized = false;
    }

    /**
     * Initialize components
     * @param {Object} config - Configuration object
     * @param {boolean} config.topBar - Whether to load top bar
     * @param {string} config.role - User role for tab bar (customer, merchant, rider, admin)
     * @param {string} config.activePage - Current active page
     * @param {Object} config.topBarOptions - Custom options for top bar
     */
    init(config = {}) {
        if (this.initialized) {
            console.warn('UIComponentsLoader already initialized');
            return;
        }

        // Initialize top bar if requested
        if (config.topBar !== false) {
            this.topBar = new TopBarComponent();
            this.topBar.prependToBody(config.topBarOptions || {});
        }

        // Initialize tab bar if role is provided
        if (config.role) {
            this.tabBar = new TabBarComponent();
            this.tabBar.appendToBody(config.role, config.activePage);
        }

        this.initialized = true;
    }

    /**
     * Initialize with automatic role detection based on page name
     */
    initAuto() {
        const currentPage = window.location.pathname.split('/').pop();
        let role = null;

        if (currentPage.startsWith('customer_')) {
            role = 'customer';
        } else if (currentPage.startsWith('merchant_')) {
            role = 'merchant';
        } else if (currentPage.startsWith('rider_')) {
            role = 'rider';
        } else if (currentPage.startsWith('admin_') || currentPage === 'Admin.html') {
            role = 'admin';
        }

        this.init({
            topBar: true,
            role: role,
            activePage: currentPage
        });
    }
}

// Create global instance for easy access
window.UIComponents = new UIComponentsLoader();

/**
 * Helper function for quick component loading
 * Usage in HTML: <script>loadUIComponents('customer');</script>
 * @param {string} role - User role (customer, merchant, rider, admin, or 'auto' for auto-detection)
 * @param {Object} options - Additional options
 */
function loadUIComponents(role = 'auto', options = {}) {
    if (role === 'auto') {
        window.UIComponents.initAuto();
    } else {
        window.UIComponents.init({
            topBar: true,
            role: role,
            activePage: window.location.pathname.split('/').pop(),
            ...options
        });
    }
}

// Auto-initialize on DOMContentLoaded if data-auto-load attribute is present
document.addEventListener('DOMContentLoaded', function() {
    const scriptTag = document.querySelector('script[data-auto-load="true"]');
    if (scriptTag) {
        const role = scriptTag.getAttribute('data-role') || 'auto';
        loadUIComponents(role);
    }
});
