// scripts/rider_dashboard.js

// Get Rider ID from URL parameter or localStorage, default to 0 if not found
const urlParams = new URLSearchParams(window.location.search);
const RIDER_ID = parseInt(urlParams.get('rider_id')) || parseInt(localStorage.getItem('rider_id')) || 0;

if (RIDER_ID === 0) {
    console.warn('No Rider ID found. Please login or provide rider_id in URL.');
    // Optional: Redirect to login if critical
    // window.location.href = 'login_rider.html';
} else {
    // Persist to localStorage for convenience on reload
    localStorage.setItem('rider_id', RIDER_ID);
    console.log('Current Rider ID:', RIDER_ID);
}

const API_BASE = 'php/rider_api.php';

let isOnline = false;

document.addEventListener('DOMContentLoaded', function() {
    initDashboard();
});

function initDashboard() {
    updateRiderStatus();
    
    // Set up auto-refresh every 3 seconds
    // We use setTimeout recursively to prevent request overlap if network is slow
    const poll = async () => {
        await updateRiderStatus();
        setTimeout(poll, 3000);
    };
    setTimeout(poll, 3000);
}

async function refreshData() {
    // This function is now redundant as polling handles it, 
    // but kept for manual refresh buttons if any
    await updateRiderStatus();
}

async function updateRiderStatus() {
    try {
        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        const response = await fetch(`${API_BASE}?action=get_status&rider_id=${RIDER_ID}&_t=${timestamp}`);
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();
        
        if (data.success) {
            // Accept both 'Online' and 'Available' as online status
            const serverStatus = data.status;
            isOnline = (serverStatus === 'Online' || serverStatus === 'Available');
            
            updateStatusUI(data);
            
            if (isOnline) {
                // Only load orders if we are truly online
                loadAvailableOrders();
                loadActiveOrders();
            } else {
                clearOrdersUI();
            }
            
            loadHistory(); // Always load history
            loadProfileStats(data); // Load profile stats if on profile page
        }
    } catch (error) {
        console.error('Error fetching status:', error);
        // Optional: Show a "Connection Lost" warning in UI
    }
}

function updateStatusUI(data) {
    const statusBox = document.getElementById('rider-status-display');
    const toggleBtn = document.getElementById('toggle-status-btn');
    
    if (statusBox && toggleBtn) {
        if (isOnline) {
            statusBox.textContent = "Current: ONLINE - Ready to Receive Orders";
            statusBox.className = "status-indicator status-online";
            toggleBtn.textContent = "Go Offline";
            toggleBtn.className = "btn btn-secondary full-width mt-10";
            toggleBtn.style.backgroundColor = '#dc3545';
            toggleBtn.onclick = () => toggleStatus('Offline');
        } else {
            statusBox.textContent = "Current: OFFLINE";
            statusBox.className = "status-indicator status-offline";
            toggleBtn.textContent = "Go Online";
            toggleBtn.className = "btn btn-primary full-width mt-10";
            toggleBtn.style.backgroundColor = '#28a745';
            toggleBtn.onclick = () => toggleStatus('Online');
        }
    }
}

async function toggleStatus(newStatus) {
    try {
        const params = new URLSearchParams();
        params.append('status', newStatus);
        
        const response = await fetch(`${API_BASE}?action=toggle_status&rider_id=${RIDER_ID}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        });
        
        const result = await response.json();
        if (result.success) {
            updateRiderStatus();
            alert(`You are now ${newStatus.toUpperCase()}`);
        } else {
            alert('Failed to update status');
        }
    } catch (error) {
        console.error('Error toggling status:', error);
    }
}

async function loadAvailableOrders() {
    const container = document.getElementById('assigned-orders-list');
    if (!container) return;

    try {
        const response = await fetch(`${API_BASE}?action=get_available_orders&rider_id=${RIDER_ID}`);
        if (!response.ok) throw new Error('Network response was not ok');
        
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
                        <button id="btn-accept-${order.OrderID}" onclick="acceptOrder(${order.OrderID})" class="btn btn-primary full-width" style="margin-top:10px;">Accept Order</button>
                    </div>
                `;
            });
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="order-empty">No new orders available at the moment.</p>';
        }
    } catch (error) {
        console.error('Error loading available orders:', error);
        // Don't overwrite if it's just a transient network error, maybe? 
        // But for now, showing error is better than stale data.
        container.innerHTML = '<p class="order-empty" style="color:red">Connection error. Retrying...</p>';
    }
}

async function loadActiveOrders() {
    const container = document.getElementById('active-delivery-order');
    if (!container) return;

    try {
        const response = await fetch(`${API_BASE}?action=get_active_orders&rider_id=${RIDER_ID}`);
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
                        <button onclick="completeOrder(${order.OrderID})" class="btn btn-primary full-width" style="background:green; margin-top:15px;">Mark Delivered</button>
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
}

async function loadHistory() {
    const container = document.getElementById('completed-orders-list');
    if (!container) return;

    try {
        const response = await fetch(`${API_BASE}?action=get_history&rider_id=${RIDER_ID}`);
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
}

function loadProfileStats(data) {
    const totalEarningsEl = document.getElementById('profile-total-earnings');
    if (totalEarningsEl) {
        totalEarningsEl.textContent = '¥' + data.total_earnings;
    }
    
    const completedCountEl = document.getElementById('profile-completed-count');
    if (completedCountEl) {
        completedCountEl.textContent = data.completed_count;
    }
}

async function acceptOrder(orderId) {
    if (!confirm('Accept this order?')) return;
    
    const btn = document.getElementById(`btn-accept-${orderId}`);
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Accepting...';
    }

    try {
        const params = new URLSearchParams();
        params.append('order_id', orderId);
        
        const response = await fetch(`${API_BASE}?action=accept_order&rider_id=${RIDER_ID}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        });
        
        const result = await response.json();
        if (result.success) {
            // alert('Order accepted! Please proceed to pickup.'); // Optional: Remove alert for smoother flow
            loadAvailableOrders();
            loadActiveOrders();
        } else {
            alert('Failed to accept order: ' + (result.message || 'Unknown error'));
            loadAvailableOrders(); // Refresh list to see if it's gone
        }
    } catch (error) {
        console.error('Error accepting order:', error);
        alert('Network error. Please try again.');
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Accept Order';
        }
    }
}

async function completeOrder(orderId) {
    if (!confirm('Confirm delivery completion?')) return;
    
    try {
        const params = new URLSearchParams();
        params.append('order_id', orderId);
        
        const response = await fetch(`${API_BASE}?action=complete_order&rider_id=${RIDER_ID}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        });
        
        const result = await response.json();
        if (result.success) {
            alert('Order completed! Great job.');
            loadActiveOrders();
            loadHistory();
            updateRiderStatus(); // Update earnings
        } else {
            alert('Failed to complete order: ' + result.message);
        }
    } catch (error) {
        console.error('Error completing order:', error);
    }
}

function clearOrdersUI() {
    const availableContainer = document.getElementById('assigned-orders-list');
    if (availableContainer) availableContainer.innerHTML = '<p class="order-empty">Go Online to receive orders.</p>';
    
    const activeContainer = document.getElementById('active-delivery-order');
    if (activeContainer) activeContainer.innerHTML = '<p class="order-empty">You are offline.</p>';
}
