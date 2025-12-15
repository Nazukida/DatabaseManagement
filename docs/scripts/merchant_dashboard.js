/**
 * MerchantApp - Centralized logic for Merchant Dashboard
 * Refactored for stability and debugging
 */
const MerchantApp = {
    restaurantId: 0,
    apiBase: 'php/merchant_api.php',
    menuItems: [], // Local cache for menu items

    init: function() {
        console.log('MerchantApp initializing...');
        this.restaurantId = this.resolveRestaurantId();
        console.log('Resolved Restaurant ID:', this.restaurantId);
        
        if (!this.restaurantId || this.restaurantId <= 0) {
            alert('Session expired or invalid. Please login again.');
            window.location.href = 'login_merchant.html';
            return;
        }

        // Persist ID in URL for consistency
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('restaurant_id')) {
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('restaurant_id', this.restaurantId);
            window.history.replaceState({path: newUrl.href}, '', newUrl.href);
        }

        sessionStorage.setItem('restaurant_id', this.restaurantId);
        this.updateNavLinks();
        this.routePage();
    },

    resolveRestaurantId: function() {
        // 1. Check URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('restaurant_id')) {
            const id = parseInt(urlParams.get('restaurant_id'));
            if (!isNaN(id) && id > 0) return id;
        }

        // 2. Check Session Storage
        const sessionId = sessionStorage.getItem('restaurant_id');
        if (sessionId) {
            const id = parseInt(sessionId);
            if (!isNaN(id) && id > 0) return id;
        }

        // 3. Check LocalStorage (Login Session)
        try {
            const session = JSON.parse(localStorage.getItem('appSession'));
            if (session && session.user && session.user.RestaurantID) {
                const id = parseInt(session.user.RestaurantID);
                if (!isNaN(id) && id > 0) return id;
            }
        } catch (e) {
            console.error('Error parsing appSession:', e);
        }

        return 0;
    },

    updateNavLinks: function() {
        const links = document.querySelectorAll('a');
        links.forEach(link => {
            const href = link.getAttribute('href');
            if (!href || href.startsWith('javascript:') || href.startsWith('#')) return;
            
            if (href.includes('.html') && !href.includes('restaurant_id=')) {
                const separator = href.includes('?') ? '&' : '?';
                link.setAttribute('href', `${href}${separator}restaurant_id=${this.restaurantId}`);
            }
        });
    },

    routePage: function() {
        const path = window.location.pathname;
        console.log('Routing page:', path);
        if (path.includes('merchant.html')) {
            this.loadDashboard();
        } else if (path.includes('merchant_orders.html')) {
            this.loadOrders();
        } else if (path.includes('merchant_menu.html')) {
            this.loadMenu();
        } else if (path.includes('merchant_sales.html')) {
            this.loadSales();
        } else if (path.includes('merchant_profile.html')) {
            this.loadProfile();
        }
    },

    // --- Helper: API Call ---
    apiCall: async function(action, params = {}, method = 'GET') {
        let url = `${this.apiBase}?action=${action}&restaurant_id=${this.restaurantId}`;
        let options = { method: method };

        if (method === 'POST') {
            // If params is FormData, use it directly
            if (params instanceof FormData) {
                options.body = params;
            } else {
                // Convert object to FormData
                const formData = new FormData();
                for (const key in params) {
                    formData.append(key, params[key]);
                }
                options.body = formData;
            }
        }

        try {
            const response = await fetch(url, options);
            const text = await response.text(); // Get text first to debug non-JSON responses
            try {
                const data = JSON.parse(text);
                return data;
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Server returned invalid JSON');
            }
        } catch (e) {
            console.error('API Error:', e);
            alert('Network or Server Error: ' + e.message);
            return { success: false, message: e.message };
        }
    },

    // --- Dashboard Page ---
    loadDashboard: async function() {
        const data = await this.apiCall('get_dashboard_stats');
        if (data.success) {
            const welcome = document.getElementById('welcome-msg');
            if (welcome) welcome.textContent = `Welcome, ${data.restaurant_name}`;
            
            const pendingEl = document.getElementById('pending-count');
            if (pendingEl) pendingEl.textContent = data.pending_orders;
        }
    },

    // --- Profile Page ---
    loadProfile: async function() {
        const data = await this.apiCall('get_profile');
        if (data.success) {
            const p = data.profile;
            this.currentProfile = p; // Store for editing
            
            // Update UI elements safely
            const setSafe = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val || '-';
            };

            setSafe('profile-name', p.RestaurantName);
            setSafe('profile-phone', p.ContactPhone);
            setSafe('profile-status', p.BusinessStatus);
            setSafe('profile-address', p.Address);
            setSafe('profile-desc', p.Description);
            setSafe('profile-fee', parseFloat(p.DeliveryFee).toFixed(2));
            setSafe('profile-min', parseFloat(p.MinimumOrderAmount).toFixed(2));
        }
    },

    showEditProfileModal: function() {
        if (!this.currentProfile) return;
        const p = this.currentProfile;
        
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val;
        };

        setVal('edit-name', p.RestaurantName);
        setVal('edit-phone', p.ContactPhone);
        setVal('edit-address', p.Address);
        setVal('edit-desc', p.Description);
        setVal('edit-fee', p.DeliveryFee);
        setVal('edit-min', p.MinimumOrderAmount);
        setVal('edit-status', p.BusinessStatus);
        
        document.getElementById('editProfileModal').style.display = 'block';
    },

    saveProfile: async function(e) {
        e.preventDefault();
        const formData = new FormData(document.getElementById('editProfileForm')); // Assuming form has this ID
        // Or manually construct if form ID is different
        
        // Let's use manual construction to be safe based on previous code
        const data = new FormData();
        data.append('name', document.getElementById('edit-name').value);
        data.append('phone', document.getElementById('edit-phone').value);
        data.append('address', document.getElementById('edit-address').value);
        data.append('description', document.getElementById('edit-desc').value);
        data.append('delivery_fee', document.getElementById('edit-fee').value);
        data.append('min_order', document.getElementById('edit-min').value);
        data.append('status', document.getElementById('edit-status').value);

        const res = await this.apiCall('update_profile', data, 'POST');
        if (res.success) {
            alert('Profile updated successfully');
            document.getElementById('editProfileModal').style.display = 'none';
            this.loadProfile();
        } else {
            alert('Failed: ' + res.message);
        }
    },

    // --- Menu Page ---
    loadMenu: async function() {
        const data = await this.apiCall('get_menu');
        if (data.success) {
            this.menuItems = data.menu;
            const list = document.getElementById('menu-list');
            if (!list) return;
            
            list.innerHTML = '';
            
            if (this.menuItems.length === 0) {
                list.innerHTML = '<p style="text-align:center; color:#666; padding:20px;">No menu items found. Click "Add Item" to create one.</p>';
                return;
            }

            this.menuItems.forEach(item => {
                const div = document.createElement('div');
                div.className = 'order-card';
                div.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h3 style="margin:0 0 5px 0;">${this.escapeHtml(item.ItemName)}</h3>
                            <p style="margin:0; color:#666;">$${parseFloat(item.Price).toFixed(2)}</p>
                            <p style="margin:5px 0 0 0; font-size:12px; color:#888;">${this.escapeHtml(item.ItemDescription || 'No description')}</p>
                        </div>
                        <div>
                            <button onclick="MerchantApp.editMenuItem(${item.MenuItemID})" style="margin-right:5px; padding:5px 10px; cursor:pointer;">Edit</button>
                            <button onclick="MerchantApp.deleteMenuItem(${item.MenuItemID})" style="padding:5px 10px; cursor:pointer; background:#ff4d4d; color:white; border:none; border-radius:3px;">Delete</button>
                        </div>
                    </div>
                `;
                list.appendChild(div);
            });
        }
    },

    escapeHtml: function(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    },

    showAddMenuModal: function() {
        document.getElementById('modalTitle').textContent = 'Add Menu Item';
        document.getElementById('menuForm').reset();
        document.getElementById('item-id').value = '';
        document.getElementById('menuModal').style.display = 'block';
    },

    editMenuItem: function(id) {
        const item = this.menuItems.find(i => i.MenuItemID == id);
        if (!item) {
            console.error('Item not found in local cache:', id);
            return;
        }
        
        document.getElementById('modalTitle').textContent = 'Edit Menu Item';
        document.getElementById('item-id').value = item.MenuItemID;
        document.getElementById('item-name').value = item.ItemName;
        document.getElementById('item-price').value = item.Price;
        document.getElementById('item-desc').value = item.ItemDescription;
        document.getElementById('menuModal').style.display = 'block';
    },

    saveMenuItem: async function(e) {
        e.preventDefault();
        const id = document.getElementById('item-id').value;
        const action = id ? 'update_menu_item' : 'add_menu_item';
        
        const formData = new FormData();
        if (id) formData.append('item_id', id);
        formData.append('name', document.getElementById('item-name').value);
        formData.append('price', document.getElementById('item-price').value);
        formData.append('description', document.getElementById('item-desc').value);
        
        const res = await this.apiCall(action, formData, 'POST');
        if (res.success) {
            alert('Saved successfully');
            document.getElementById('menuModal').style.display = 'none';
            this.loadMenu();
        } else {
            alert('Failed to save: ' + res.message);
        }
    },

    deleteMenuItem: async function(id) {
        if (!confirm('Are you sure you want to delete this item?')) return;
        
        const formData = new FormData();
        formData.append('item_id', id);
        
        const res = await this.apiCall('delete_menu_item', formData, 'POST');
        if (res.success) {
            this.loadMenu();
        } else {
            alert('Failed to delete: ' + res.message);
        }
    },

    // --- Orders Page ---
    loadOrders: async function() {
        const container = document.getElementById('order-list');
        if (!container) return;
        
        container.innerHTML = '<p>Loading orders...</p>';

        const data = await this.apiCall('get_orders');
        if (data.success) {
            if (data.orders.length === 0) {
                container.innerHTML = '<p>No orders found.</p>';
                return;
            }

            let html = '';
            data.orders.forEach(order => {
                let actionBtns = '';
                if (order.OrderStatus === 'Pending') {
                    actionBtns = `<button class="btn btn-primary" onclick="MerchantApp.updateOrderStatus(${order.OrderID}, 'Confirmed')">Accept</button>`;
                } else if (order.OrderStatus === 'Confirmed') {
                    actionBtns = `<button class="btn btn-success" onclick="MerchantApp.updateOrderStatus(${order.OrderID}, 'Completed')">Ready for Pickup</button>`;
                }

                const itemsHtml = order.items.map(i => `${i.ItemName} x${i.Quantity}`).join(', ');

                html += `
                    <div class="card" style="margin-bottom:15px; padding:15px; border:1px solid #eee; border-radius:8px;">
                        <div style="display:flex; justify-content:space-between;">
                            <h4>Order #${order.OrderID}</h4>
                            <span class="status-badge">${order.OrderStatus}</span>
                        </div>
                        <p style="color:#666; font-size:0.9em;">${order.OrderTime} | Customer: ${order.CustomerName || 'Guest'}</p>
                        <div style="margin:10px 0; padding:10px; background:#f9f9f9;">
                            <strong>Items:</strong> ${itemsHtml}
                        </div>
                        <div style="text-align:right; font-weight:bold;">Total: ¥${parseFloat(order.TotalAmount).toFixed(2)}</div>
                        <div style="margin-top:10px; text-align:right;">
                            ${actionBtns}
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }
    },

    updateOrderStatus: async function(orderId, status) {
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('status', status);

        const res = await this.apiCall('update_order_status', formData, 'POST');
        if (res.success) {
            this.loadOrders();
        } else {
            alert('Failed to update status: ' + res.message);
        }
    },

    // --- Sales Page ---
    loadSales: async function() {
        const container = document.getElementById('sales-data');
        if (!container) return;

        const data = await this.apiCall('get_sales');
        if (data.success) {
            let itemSalesHtml = '';
            if (data.item_sales && data.item_sales.length > 0) {
                itemSalesHtml = `
                    <h4 style="margin-top:30px;">Sales by Item</h4>
                    <table style="width:100%; border-collapse:collapse; margin-top:10px;">
                        <thead>
                            <tr style="background:#f0f0f0; text-align:left;">
                                <th style="padding:10px;">Item Name</th>
                                <th style="padding:10px;">Quantity Sold</th>
                                <th style="padding:10px;">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                data.item_sales.forEach(item => {
                    itemSalesHtml += `
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px;">${this.escapeHtml(item.ItemName)}</td>
                            <td style="padding:10px;">${item.total_sold}</td>
                            <td style="padding:10px;">¥${parseFloat(item.item_revenue).toFixed(2)}</td>
                        </tr>
                    `;
                });
                itemSalesHtml += '</tbody></table>';
            } else {
                itemSalesHtml = '<p style="margin-top:20px; color:#666;">No item sales data available.</p>';
            }

            container.innerHTML = `
                <div class="card" style="text-align:center; padding:30px; background:#fff; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                    <h3>Total Revenue</h3>
                    <div style="font-size:2.5em; color:#28a745; margin:10px 0;">¥${data.total_revenue}</div>
                    <p>Total Completed Orders: ${data.total_orders}</p>
                </div>
                ${itemSalesHtml}
            `;
        }
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    MerchantApp.init();
});

// Ensure global access for inline event handlers
window.MerchantApp = MerchantApp;
