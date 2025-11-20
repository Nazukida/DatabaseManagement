// === Configuration and State ===
const RIDER_ID = 4001; // Example: This should be securely obtained from the session upon login
let isOnline = false; // Initial state

// Simple in-memory mock state so we can see UI changes without backend
let mockAssignedOrders = [
    { id: 1005, restaurant: "Pizza Palace", pickup_address: "123 Main St", distance: "2.1km", total_amount: 35.50, accepted: false },
    { id: 1006, restaurant: "Taco Express", pickup_address: "45 Market Rd", distance: "0.9km", total_amount: 18.00, accepted: false }
];

let mockActiveDelivery = null;

// --- DATABASE INTERFACE FUNCTIONS (PLACEHOLDERS) ---
// These functions simulate asynchronous calls to your PHP backend (e.g., using fetch or AJAX).

/**
 * [DB INTERFACE] Fetches the rider's current status and assigned orders from the server.
 * @returns {Promise<Object>} Data including is_online status, assigned orders array, and active delivery object.
 */
async function fetchRiderStatusDB() {
    console.log(`[DB CALL] Fetching status and orders for Rider ${RIDER_ID}...`);
    // TODO: Implement actual AJAX/fetch request to 'api/rider_status.php' 
    // Example: const response = await fetch('api/rider_status.php?rider_id=' + RIDER_ID);

    // MOCK DATA for structure testing, now driven by in-memory state above
    const mockData = {
        is_online: isOnline,
        assigned_orders: isOnline ? mockAssignedOrders : [],
        active_delivery: mockActiveDelivery
    };
    return mockData;
}

/**
 * [DB INTERFACE] Toggles the rider's availability status in the database.
 */
async function toggleRiderStatusDB(newStatus) {
    console.log(`[DB CALL] Updating Rider ${RIDER_ID} status to: ${newStatus ? 'ONLINE' : 'OFFLINE'}`);
    // TODO: Implement actual POST request to 'api/toggle_status.php'
    return true; // Simulate success
}

/**
 * [DB INTERFACE] Accepts a newly assigned order. Updates the Orders table (RiderID and OrderStatus).
 */
async function acceptOrderDB(orderId) {
    console.log(`[DB CALL] Rider ${RIDER_ID} accepting Order ${orderId}...`);
    // In mock mode, mark the order as accepted and also set active delivery
    const order = mockAssignedOrders.find(o => o.id === orderId);
    if (order) {
        order.accepted = true;
        mockActiveDelivery = {
            id: order.id,
            customer: "Demo Customer",
            delivery_address: "789 Oak Lane, Apt 2B",
            status: "AWAITING_PICKUP",
            restaurant: order.restaurant
        };
    }
    // TODO: Implement actual POST request to 'api/accept_order.php' when backend is ready
    return true; // Simulate success
}

/**
 * [DB INTERFACE] Updates the status of an active delivery order.
 */
async function updateOrderStatusDB(orderId, newStatus) {
    console.log(`[DB CALL] Updating Order ${orderId} status to: ${newStatus}`);
    // TODO: Implement actual POST request to 'api/update_order_status.php'
    return true; // Simulate success
}

// --- CORE FUNCTIONALITY ---

/**
 * Handles the click event for the Go Online/Go Offline button.
 */
async function toggleRiderStatus() {
    const newStatus = !isOnline;
    
    // Call DB interface
    const success = await toggleRiderStatusDB(newStatus);
    
    if (success) {
        isOnline = newStatus;
        // Refresh the entire UI based on the new state
        updateUI(); 
        alert(isOnline ? "You are now ONLINE and ready to accept orders!" : "You are now OFFLINE.");
    } else {
        alert("Failed to update status. Please try again.");
    }
}

/**
 * Renders the UI based on the latest data fetched.
 */
async function updateUI() {
    const data = await fetchRiderStatusDB();
    isOnline = data.is_online; // Sync state
    
    const statusBox = document.getElementById('rider-status-display');
    const toggleBtn = document.getElementById('toggle-status-btn');
    const assignedList = document.getElementById('assigned-orders-list');
    const activeDelivery = document.getElementById('active-delivery-order');
    
    // 1. Update Status Display (text + simple color hint using fancy-kit colors)
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
    
    // 2. Render Assigned Orders List
    if (data.assigned_orders.length > 0 && isOnline) {
        assignedList.innerHTML = data.assigned_orders.map(order => `
            <div class="order-card" style="border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                <h4>Order #${order.id} (Value: $${order.total_amount.toFixed(2)})</h4>
                <p>Restaurant: ${order.restaurant}</p>
                <p>Pickup Address: ${order.pickup_address}</p>
                <p>Distance: ${order.distance}</p>
                ${order.accepted
                    ? `<button disabled class="btn" style="background: #ccc; color:#555; cursor: default;">Accepted by You</button>`
                    : `<button onclick="handleAcceptOrder(${order.id})" class="btn btn-yellow">Accept Order</button>`
                }
            </div>
        `).join('');
    } else if (isOnline) {
        assignedList.innerHTML = '<p>No new orders assigned at this time.</p>';
    } else {
        assignedList.innerHTML = '<p>Please go online to receive new assignments.</p>';
    }
    
    // 3. Render Active Delivery Order
    if (data.active_delivery) {
        const order = data.active_delivery;
        activeDelivery.innerHTML = `
            <div class="order-card" style="border-color: #007bff; border-width: 2px;">
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

// --- INTERACTION HANDLERS ---

function handleAcceptOrder(orderId) {
    if (!isOnline) {
        alert("You must be ONLINE to accept an order.");
        return;
    }
    if (confirm(`Confirm acceptance of Order #${orderId}?`)) {
        acceptOrderDB(orderId).then(success => {
            if (success) {
                alert(`Order #${orderId} successfully accepted. Please proceed to pickup.`);
                updateUI(); // Refresh UI to move the order to 'Active Delivery' section
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
                updateUI(); // Refresh UI
            } else {
                alert("Status update failed. Please check your connection.");
            }
        });
    }
}

// Initialize the dashboard upon page load
document.addEventListener('DOMContentLoaded', updateUI);