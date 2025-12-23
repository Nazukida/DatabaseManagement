/**
 * Common Tab Bar Component
 * Renders the bottom navigation tab bar for different user roles
 */
class TabBarComponent {
    constructor() {
        this.userRoles = {
            customer: [
                { href: 'customer_home.html', icon: 'fas fa-utensils', text: 'Home' },
                { href: 'customer_orders.html', icon: 'fas fa-receipt', text: 'Orders' },
                { href: 'customer_cart.html', icon: 'fas fa-shopping-cart', text: 'Cart' },
                { href: 'customer_profile.html', icon: 'fas fa-user', text: 'Profile' }
            ],
            merchant: [
                { href: 'merchant.html', icon: 'fas fa-store', text: 'Home' },
                { href: 'merchant_orders.html', icon: 'fas fa-receipt', text: 'Orders' },
                { href: 'merchant_menu.html', icon: 'fas fa-utensils', text: 'Menu' },
                { href: 'merchant_profile.html', icon: 'fas fa-user', text: 'Profile' }
            ],
            rider: [
                { href: 'rider_home.html', icon: 'fas fa-motorcycle', text: 'Home' },
                { href: 'rider_dashboard.html', icon: 'fas fa-tachometer-alt', text: 'Dashboard' },
                { href: 'rider_history.html', icon: 'fas fa-history', text: 'History' },
                { href: 'rider_profile.html', icon: 'fas fa-user', text: 'Profile' }
            ],
            admin: [
                { href: 'admin_home.html', icon: 'fas fa-home', text: 'Home' },
                { href: 'admin_merchants.html', icon: 'fas fa-store', text: 'Merchants' },
                { href: 'admin_orders.html', icon: 'fas fa-receipt', text: 'Orders' },
                { href: 'Admin.html', icon: 'fas fa-user-shield', text: 'Admin' }
            ]
        };
    }

    /**
     * Renders the tab bar HTML
     * @param {string} role - User role (customer, merchant, rider, admin)
     * @param {string} activePage - Current page filename to mark as active
     * @returns {string} HTML string
     */
    render(role, activePage) {
        const tabs = this.userRoles[role];
        if (!tabs) {
            console.error(`Unknown role: ${role}`);
            return '';
        }

        // Get the current page filename for active state
        const currentPage = activePage || window.location.pathname.split('/').pop();

        const tabsHTML = tabs.map(tab => {
            const isActive = currentPage === tab.href || 
                            currentPage === tab.href.replace('.html', '') || 
                            window.location.pathname.endsWith(tab.href);
            const activeClass = isActive ? ' active' : '';
            
            return `        <a href="${tab.href}" class="tab-item${activeClass}">
            <i class="${tab.icon}"></i>
            <span>${tab.text}</span>
        </a>`;
        }).join('\n');

        return `<div class="common-tab-bar container">
${tabsHTML}
    </div>`;
    }

    /**
     * Injects the tab bar into the specified element
     * @param {string} selector - CSS selector for the target element
     * @param {string} role - User role
     * @param {string} activePage - Current page filename
     */
    inject(selector, role, activePage) {
        const element = document.querySelector(selector);
        if (element) {
            element.innerHTML = this.render(role, activePage);
        }
    }

    /**
     * Appends the tab bar to the body element
     * @param {string} role - User role
     * @param {string} activePage - Current page filename
     */
    appendToBody(role, activePage) {
        const tabBarHTML = this.render(role, activePage);
        document.body.insertAdjacentHTML('beforeend', tabBarHTML);
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TabBarComponent;
}
