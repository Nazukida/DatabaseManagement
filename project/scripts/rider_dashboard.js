const RIDER_ID = 4001;
let isOnline = false;
window.__assignedMockOrders = window.__assignedMockOrders || [
    { id: 1005, restaurant: "Pizza Palace", pickup_address: "123 Main St", distance: "2.1km", total_amount: 35.50 },
    { id: 1006, restaurant: "Taco Express", pickup_address: "45 Market Rd", distance: "0.9km", total_amount: 18.00 }
];
window.__activeMockOrder = window.__activeMockOrder || null;

async function fetchRiderStatusDB() {
    console.log(`[DB CALL] Fetching status and orders for Rider ${RIDER_ID}...`);
    const mockData = {
        is_online: isOnline,
        assigned_orders: isOnline ? window.__assignedMockOrders : [],
        active_delivery: window.__activeMockOrder
    };
    return mockData; 
}

async function toggleRiderStatusDB(newStatus) {
    console.log(`[DB CALL] Updating Rider ${RIDER_ID} status to: ${newStatus ? 'ONLINE' : 'OFFLINE'}`);
    return true;
}
async function acceptOrderDB(orderId) {
    console.log(`[DB CALL] Rider ${RIDER_ID} accepting Order ${orderId}...`);
    const index = window.__assignedMockOrders.findIndex(o => o.id === orderId);
    if (index !== -1) {
        const picked = window.__assignedMockOrders[index];
        window.__assignedMockOrders.splice(index, 1);
        window.__activeMockOrder = {
            id: picked.id,
            customer: 'Assigned Customer',
            delivery_address: picked.pickup_address,
            status: 'AWAITING_PICKUP',
            restaurant: picked.restaurant
        };
    }
    return true;
}
async function updateOrderStatusDB(orderId, newStatus) {
    console.log(`[DB CALL] Updating Order ${orderId} status to: ${newStatus}`);
    return true;
}
async function toggleRiderStatus() {
    const newStatus = !isOnline;
    
    const success = await toggleRiderStatusDB(newStatus);
    
    if (success) {
        isOnline = newStatus;
        updateUI(); 
        alert(isOnline ? "You are now ONLINE and ready to accept orders!" : "You are now OFFLINE.");
    } else {
        alert("Failed to update status. Please try again.");
    }
}

async function updateUI() {
    const data = await fetchRiderStatusDB();
    isOnline = data.is_online;
    
    const statusBox = document.getElementById('rider-status-display');
    const toggleBtn = document.getElementById('toggle-status-btn');
    const assignedList = document.getElementById('assigned-orders-list');
    const activeDelivery = document.getElementById('active-delivery-order');
    
    if (isOnline) {
        statusBox.textContent = "Current: ONLINE - Ready to Dispatch";
        statusBox.className = "status-box status-online";
        toggleBtn.textContent = "Go Offline";
        toggleBtn.style.backgroundColor = '#dc3545'; // Red
    } else {
        statusBox.textContent = "Current: OFFLINE";
        statusBox.className = "status-box status-offline";
        toggleBtn.textContent = "Go Online";
        toggleBtn.style.backgroundColor = '#28a745'; // Green
    }
    
    if (data.assigned_orders.length > 0 && isOnline) {
        assignedList.innerHTML = data.assigned_orders.map(order => `
            <div class="order-card">
                <h4>Order #${order.id} (Value: $${order.total_amount.toFixed(2)})</h4>
                <p>Restaurant: ${order.restaurant}</p>
                <p>Pickup Address: ${order.pickup_address}</p>
                <p>Distance: ${order.distance}</p>
                <button onclick="handleAcceptOrder(${order.id})" style="background-color: #007bff;">Accept Order</button>
            </div>
        `).join('');
    } else if (isOnline) {
        assignedList.innerHTML = '<p>No new orders assigned at this time.</p>';
    } else {
        assignedList.innerHTML = '<p>Please go online to receive new assignments.</p>';
    }
    
    if (data.active_delivery) {
        const order = data.active_delivery;
        activeDelivery.innerHTML = `
            <div class="order-card active-delivery-appear" style="border-color: #007bff; border-width: 2px;">
                <h4>Active Delivery: Order #${order.id}</h4>
                <p>Restaurant: ${order.restaurant}</p>
                <p>Customer: ${order.customer}</p>
                <p>Delivery Address: <strong>${order.delivery_address}</strong></p>
                <p>Current Status: <strong>${order.status.replace('_', ' ')}</strong></p>
                
                ${order.status === 'AWAITING_PICKUP' 
                    ? `<button onclick="handleStatusUpdate(${order.id}, 'IN_TRANSIT')" style="background-color: #ffc107; color: #343a40;">Mark as: Picked Up (In Transit)</button>` 
                    : ''}
                
                ${order.status === 'IN_TRANSIT' 
                    ? `<button onclick="handleStatusUpdate(${order.id}, 'DELIVERED')" style="background-color: #28a745;">Mark as: Delivered / Complete</button>` 
                    : ''}
                
            </div>
        `;
    } else {
        activeDelivery.innerHTML = '<p>You currently have no active deliveries.</p>';
    }
}

function handleAcceptOrder(orderId) {
    if (!isOnline) {
        alert("You must be ONLINE to accept an order.");
        return;
    }
    if (confirm(`Confirm acceptance of Order #${orderId}?`)) {
        acceptOrderDB(orderId).then(success => {
            if (success) {
                alert(`Order #${orderId} successfully accepted. Please proceed to pickup.`);
                updateUI();
            } else {
                alert("Failed to accept order. It might have been taken by another rider.");
            }
        });
    }
}

function handleStatusUpdate(orderId, newStatus) {
    if (confirm(`Confirm updating Order #${orderId} status to "${newStatus}"?`)) {
        updateOrderStatusDB(orderId, newStatus).then(success => {
            if (success) {
                alert(`Order #${orderId} status updated to ${newStatus}.`);
                updateUI();
            } else {
                alert("Status update failed. Please check your connection.");
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', updateUI);