/**
 * Unified Top Bar Component
 * Renders the common top navigation bar used across all pages
 */
class TopBarComponent {
    constructor() {
        this.brandName = "YouShi LinLi";
    }

    /**
     * Renders the top bar HTML
     * @param {Object} options - Configuration options
     * @param {Array} options.links - Array of navigation links {text, href, onclick}
     * @returns {string} HTML string
     */
    render(options = {}) {
        const defaultLinks = [
            { text: 'Home', href: 'index.html' },
            { text: 'Register', href: 'register.html' },
            { text: 'Login', href: 'login.html' },
            { text: 'Logout', href: 'index.html', onclick: "alert('Logged out successfully!')" }
        ];

        const links = options.links || defaultLinks;

        const linksHTML = links.map(link => {
            const onclickAttr = link.onclick ? ` onclick="${link.onclick}"` : '';
            return `<a href="${link.href}"${onclickAttr}>${link.text}</a>`;
        }).join('\n                ');

        return `<div class="unified-top-bar">
        <div class="top-bar-content">
            <span class="brand-name">${this.brandName}</span>
            <div class="top-nav-links">
                ${linksHTML}
            </div>
        </div>
    </div>`;
    }

    /**
     * Injects the top bar into the specified element
     * @param {string} selector - CSS selector for the target element
     * @param {Object} options - Configuration options
     */
    inject(selector, options = {}) {
        const element = document.querySelector(selector);
        if (element) {
            element.innerHTML = this.render(options);
        }
    }

    /**
     * Prepends the top bar to the body element
     * @param {Object} options - Configuration options
     */
    prependToBody(options = {}) {
        const topBarHTML = this.render(options);
        document.body.insertAdjacentHTML('afterbegin', topBarHTML);
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TopBarComponent;
}
