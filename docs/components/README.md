# UI Components (UIC)

This directory contains reusable UI components for the Campus Food Delivery System.

## Components

### 1. Top Bar Component (`topbar.js`)
The unified top navigation bar used across all pages.

**Features:**
- Displays brand name "YouShi LinLi"
- Customizable navigation links
- Consistent styling across all pages

### 2. Tab Bar Component (`tabbar.js`)
The bottom navigation tab bar for different user roles.

**Supported Roles:**
- **Customer**: Home, Orders, Cart, Profile
- **Merchant**: Home, Orders, Menu, Profile
- **Rider**: Home, Dashboard, History, Profile
- **Admin**: Home, Merchants, Orders, Admin

### 3. Component Loader (`component-loader.js`)
Utility for easy component loading and initialization.

## Usage

### Method 1: Automatic Loading (Recommended)

Add the following scripts to your HTML page (before closing `</body>` tag):

```html
<script src="components/topbar.js"></script>
<script src="components/tabbar.js"></script>
<script src="components/component-loader.js"></script>
<script>
    // Automatically detects role from page name
    loadUIComponents('auto');
</script>
```

### Method 2: Manual Role Specification

```html
<script src="components/topbar.js"></script>
<script src="components/tabbar.js"></script>
<script src="components/component-loader.js"></script>
<script>
    // Specify role explicitly
    loadUIComponents('customer'); // or 'merchant', 'rider', 'admin'
</script>
```

### Method 3: Data Attribute Auto-load

```html
<script src="components/topbar.js"></script>
<script src="components/tabbar.js"></script>
<script src="components/component-loader.js" data-auto-load="true" data-role="auto"></script>
```

### Method 4: Custom Configuration

```javascript
window.UIComponents.init({
    topBar: true,
    role: 'customer',
    activePage: 'customer_home.html',
    topBarOptions: {
        links: [
            { text: 'Home', href: 'index.html' },
            { text: 'Logout', href: 'index.html', onclick: "alert('Logged out!')" }
        ]
    }
});
```

## Migration Guide

To convert an existing page to use UI components:

### Before:
```html
<body>
    <div class="unified-top-bar">
        <div class="top-bar-content">
            <span class="brand-name">YouShi LinLi</span>
            <div class="top-nav-links">
                <a href="index.html">Home</a>
                <a href="register.html">Register</a>
                <a href="login.html">Login</a>
                <a href="index.html" onclick="alert('Logged out successfully!')">Logout</a>
            </div>
        </div>
    </div>

    <!-- Page content -->

    <div class="common-tab-bar container">
        <a href="customer_home.html" class="tab-item active">
            <i class="fas fa-utensils"></i>
            <span>Home</span>
        </a>
        <!-- More tabs... -->
    </div>

    <!-- Scripts -->
</body>
```

### After:
```html
<body>
    <!-- Page content only -->

    <!-- Load components at the end -->
    <script src="components/topbar.js"></script>
    <script src="components/tabbar.js"></script>
    <script src="components/component-loader.js"></script>
    <script>
        loadUIComponents('auto');
    </script>
    
    <!-- Other scripts -->
</body>
```

## Benefits

1. **Code Reusability**: Write once, use everywhere
2. **Easy Maintenance**: Update in one place, applies to all pages
3. **Consistency**: Ensures consistent UI across all pages
4. **Reduced Duplication**: Eliminates repetitive HTML code
5. **Flexibility**: Easy to customize per page if needed

## Browser Compatibility

Works in all modern browsers that support ES6 classes:
- Chrome 49+
- Firefox 45+
- Safari 9+
- Edge 13+
