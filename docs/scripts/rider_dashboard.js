// scripts/rider_dashboard.js

const RiderApp = {
    riderId: 0,
    apiBase: 'php/rider_api.php',
    isOnline: false,
    pollTimer: null,

    init: function() {
        this.riderId = this.resolveRiderId();
        
        if (!this.riderId || this.riderId <= 0) {
            alert('Session expired or invalid. Please login again.');
            window.location.href = 'login_rider.html';
            return;
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('rider_id')) {
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('rider_id', this.riderId);
            window.history.replaceState({path: newUrl.href}, '', newUrl.href);
        }

        sessionStorage.setItem('rider_id', this.riderId);
        this.updateNavLinks();
        this.updateRiderStatus();
        this.startPolling();
    },

    resolveRiderId: function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('rider_id')) {
            const id = parseInt(urlParams.get('rider_id'));
            if (!isNaN(id) && id > 0) return id;
        }

        const sessionId = sessionStorage.getItem('rider_id');
        if (sessionId) {
            const id = parseInt(sessionId);
            if (!isNaN(id) && id > 0) return id;
        }

        try {
            const session = JSON.parse(localStorage.getItem('appSession'));
            if (session && session.user && session.user.RiderID) {
                const id = parseInt(session.user.RiderID);
                if (!isNaN(id) && id > 0) return id;
            }
        } catch (e) {
            console.error('Error parsing appSession:', e);
        }

        return 0; 
    },

    updateNavLinks: function() {
        const selectors = [
            '.common-tab-bar a', 
            '.top-nav-links a', 
            '.rider-sidebar a',
            'a[href^="rider_"]'
        ];
        
        const links = document.querySelectorAll(selectors.join(', '));
        links.forEach(link => {
            const href = link.getAttribute('href');
            // Skip javascript: links or empty hrefs
            if (!href || href.startsWith('javascript:') || href.startsWith('#')) return;

            // Check if it already has the param
            if (!href.includes('rider_id=')) {
                const separator = href.includes('?') ? '&' : '?';
                link.setAttribute('href', `${href}${separator}rider_id=${this.riderId}`);
            }
        });
    },

    // Start polling loop
    startPolling: function() {
        if (this.pollTimer) clearTimeout(this.pollTimer);
        
        const poll = async () => {
            await this.updateRiderStatus();
            this.pollTimer = setTimeout(poll, 3000);
        };
        this.pollTimer = setTimeout(poll, 3000);
    },

    // Fetch status and update UI
    updateRiderStatus: async function() {
        try {
            const timestamp = new Date().getTime();
            const response = await fetch(`${this.apiBase}?action=get_status&rider_id=${this.riderId}&_t=${timestamp}`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();
            
            if (data.success) {
                const serverStatus = data.status;
                this.isOnline = (serverStatus === 'Online' || serverStatus === 'Available');
                
                this.updateStatusUI(data);
                
                if (this.isOnline) {
                    this.loadAvailableOrders();
                    this.loadActiveOrders();
                } else {
                    this.clearOrdersUI();
                }
                
                this.loadHistory();
                this.loadProfileStats(data);
            }
        } catch (error) {
            console.error('Error fetching status:', error);
        }
    },

    updateStatusUI: function(data) {
        const statusBox = document.getElementById('rider-status-display');
        const toggleBtn = document.getElementById('toggle-status-btn');
        const welcomeBox = document.getElementById('rider-welcome-msg');
        
        // Update Welcome Message with Name and ID
        if (welcomeBox && data.rider_name) {
            welcomeBox.innerHTML = `Welcome, <strong>${data.rider_name}</strong> (ID: ${data.rider_id})`;
        } else if (data.rider_name) {
            // Fallback if element doesn't exist, try to inject it
            const pageTitle = document.querySelector('.page-title');
            if (pageTitle) {
                let info = document.getElementById('dynamic-rider-info');
                if (!info) {
                    info = document.createElement('div');
                    info.id = 'dynamic-rider-info';
                    info.style.fontSize = '0.9em';
                    info.style.color = '#666';
                    info.style.marginBottom = '15px';
                    pageTitle.parentNode.insertBefore(info, pageTitle.nextSibling);
                }
                info.innerHTML = `Logged in as: <strong>${data.rider_name}</strong> (#${data.rider_id})`;
            }
        }

        if (statusBox && toggleBtn) {
            if (this.isOnline) {
                statusBox.textContent = "Current: ONLINE - Ready to Receive Orders";
                statusBox.className = "status-indicator status-online";
                toggleBtn.textContent = "Go Offline";
                toggleBtn.className = "btn btn-secondary full-width mt-10";
                toggleBtn.style.backgroundColor = '#dc3545';
                toggleBtn.onclick = () => this.toggleStatus('Offline');
            } else {
                statusBox.textContent = "Current: OFFLINE";
                statusBox.className = "status-indicator status-offline";
                toggleBtn.textContent = "Go Online";
                toggleBtn.className = "btn btn-primary full-width mt-10";
                toggleBtn.style.backgroundColor = '#28a745';
                toggleBtn.onclick = () => this.toggleStatus('Online');
            }
        }
    },

    toggleStatus: async function(newStatus) {
        try {
            const params = new URLSearchParams();
            params.append('status', newStatus);
            
            const response = await fetch(`${this.apiBase}?action=toggle_status&rider_id=${this.riderId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            });
            
            const result = await response.json();
            if (result.success) {
                this.updateRiderStatus();
                alert(`You are now ${newStatus.toUpperCase()}`);
            } else {
                alert('Failed to update status');
            }
        } catch (error) {
            console.error('Error toggling status:', error);
        }
    },

    loadAvailableOrders: async function() {
        const container = document.getElementById('assigned-orders-list');
        if (!container) return;

        try {
            const timestamp = new Date().getTime();
            const response = await fetch(`${this.apiBase}?action=get_available_orders&rider_id=${this.riderId}&_t=${timestamp}`);
            const result = await response.json();
            
            if (result.success && result.orders && result.orders.length > 0) {
                let html = '';
                result.orders.forEach(order => {
                    html += `
                        <div class="card" style="margin-bottom:10px; padding:15px; border:1px solid #eee; border-left: 4px solid #28a745;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <h4>Order #${order.OrderID}</h4>
                                <span style="font-weight:bold; color:#e67e22;">¥${parseFloat(order.TotalAmount).toFixed(2)}</span>
                            </div>
                            <p><strong>Restaurant:</strong> ${order.RestaurantName || 'Unknown'}</p>
                            <p><strong>Pickup:</strong> ${order.PickupAddress || 'See Map'}</p>
                            <p><strong>Delivery Fee:</strong> ¥${parseFloat(order.DeliveryFee).toFixed(2)}</p>
                            <button id="btn-accept-${order.OrderID}" onclick="RiderApp.acceptOrder(${order.OrderID})" class="btn btn-primary full-width" style="margin-top:10px;">Accept Order</button>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="order-empty">No new orders available at the moment.</p>';
            }
        } catch (error) {
            console.error('Error loading available orders:', error);
            container.innerHTML = '<p class="order-empty" style="color:red">Connection error. Retrying...</p>';
        }
    },

    loadActiveOrders: async function() {
        const container = document.getElementById('active-delivery-order');
        if (!container) return;

        try {
            const response = await fetch(`${this.apiBase}?action=get_active_orders&rider_id=${this.riderId}`);
            const result = await response.json();
            
            if (result.success && result.orders.length > 0) {
                let html = '';
                result.orders.forEach(order => {
                    html += `
                        <div class="card" style="margin-bottom:15px; background:#e6f7ff; padding:15px; border:1px solid #b3e0ff;">
                            <div style="display:flex; justify-content:space-between;">
                                <h4>Current Delivery: #${order.OrderID}</h4>
                                <span class="status-badge status-pending">${order.OrderStatus}</span>
                            </div>
                            <div class="mt-10">
                                <p><strong>Restaurant:</strong> ${order.RestaurantName}</p>
                                <p><strong>Pickup:</strong> ${order.PickupAddress || '-'}</p>
                                <hr style="margin: 8px 0; border: 0; border-top: 1px solid #dcdcdc;">
                                <p><strong>Customer:</strong> ${order.CustomerName || 'Guest'}</p>
                                <p><strong>Phone:</strong> ${order.CustomerPhone || '-'}</p>
                                <p><strong>Deliver To:</strong> ${order.DeliveryAddress || 'Unknown Address'}</p>
                            </div>
                            <button onclick="RiderApp.completeOrder(${order.OrderID})" class="btn btn-primary full-width" style="background:green; margin-top:15px;">Mark Delivered</button>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="order-empty">You currently have no active deliveries.</p>';
            }
        } catch (error) {
            console.error('Error loading active orders:', error);
        }
    },

    loadHistory: async function() {
        const container = document.getElementById('completed-orders-list');
        if (!container) return;

        try {
            const response = await fetch(`${this.apiBase}?action=get_history&rider_id=${this.riderId}`);
            const result = await response.json();
            
            if (result.success && result.orders.length > 0) {
                let html = '';
                result.orders.forEach(order => {
                    html += `
                        <div style="padding:10px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-weight:bold;">Order #${order.OrderID}</div>
                                <div style="font-size:0.85em; color:#666;">${order.RestaurantName}</div>
                                <div style="font-size:0.8em; color:#999;">${order.OrderTime}</div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:bold; color:#28a745;">+¥${parseFloat(order.DeliveryFee).toFixed(2)}</div>
                                <div style="font-size:0.8em; color:#999;">Total: ¥${parseFloat(order.TotalAmount).toFixed(2)}</div>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="order-empty">No history yet.</p>';
            }
        } catch (error) {
            console.error('Error loading history:', error);
        }
    },

    loadProfileStats: function(data) {
        const totalEarningsEl = document.getElementById('profile-total-earnings');
        if (totalEarningsEl) totalEarningsEl.textContent = '¥' + data.total_earnings;
        
        const completedCountEl = document.getElementById('profile-completed-count');
        if (completedCountEl) completedCountEl.textContent = data.completed_count;
    },

    acceptOrder: async function(orderId) {
        if (!confirm('Accept this order?')) return;
        
        const btn = document.getElementById(`btn-accept-${orderId}`);
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Accepting...';
        }

        try {
            const params = new URLSearchParams();
            params.append('order_id', orderId);
            
            const response = await fetch(`${this.apiBase}?action=accept_order&rider_id=${this.riderId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            });
            
            const result = await response.json();
            if (result.success) {
                if (btn) {
                    const card = btn.closest('.card');
                    if (card) card.remove();
                }
                this.loadAvailableOrders();
                this.loadActiveOrders();
            } else {
                alert('Failed to accept order: ' + (result.message || 'Unknown error'));
                this.loadAvailableOrders();
            }
        } catch (error) {
            console.error('Error accepting order:', error);
            alert('Network error. Please try again.');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Accept Order';
            }
        }
    },

    completeOrder: async function(orderId) {
        if (!confirm('Confirm delivery completion?')) return;
        
        try {
            const params = new URLSearchParams();
            params.append('order_id', orderId);
            
            const response = await fetch(`${this.apiBase}?action=complete_order&rider_id=${this.riderId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            });
            
            const result = await response.json();
            if (result.success) {
                alert('Order completed! Great job.');
                this.loadActiveOrders();
                this.loadHistory();
                this.updateRiderStatus();
            } else {
                alert('Failed to complete order: ' + result.message);
            }
        } catch (error) {
            console.error('Error completing order:', error);
        }
    },

    clearOrdersUI: function() {
        const availableContainer = document.getElementById('assigned-orders-list');
        if (availableContainer) availableContainer.innerHTML = '<p class="order-empty">Go Online to receive orders.</p>';
        
        const activeContainer = document.getElementById('active-delivery-order');
        if (activeContainer) activeContainer.innerHTML = '<p class="order-empty">You are offline.</p>';
    }
};

// Global functions for HTML onclick handlers (bridging to RiderApp)
function loadAvailableOrders() { RiderApp.loadAvailableOrders(); }
function toggleRiderStatus() { /* Handled by UI update */ } 

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    RiderApp.init();
});
