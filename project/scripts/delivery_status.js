// --- Configuration and State ---
// In a real application, the Order ID would be passed via URL parameter (e.g., ?order_id=123)
const ACTIVE_ORDER_ID = getOrderIdFromURL(); 

// Define the delivery status sequence for button rendering
const STATUS_SEQUENCE = {
    'AWAITING_ASSIGNMENT': { display: 'Awaiting Rider Assignment' },
    'AWAITING_PICKUP': { display: 'Awaiting Pickup', next: 'IN_TRANSIT', buttonText: 'Mark as: Picked Up' },
    'IN_TRANSIT': { display: 'In Transit', next: 'DELIVERED', buttonText: 'Mark as: Delivered / Complete' },
    'DELIVERED': { display: 'Delivered', next: null },
    'CANCELLED': { display: 'Cancelled', next: null }
};

// --- HELPER FUNCTION ---

/**
 * Extracts the order ID from the URL query parameters.
 * (Placeholder function - relies on final PHP/URL structure)
 */
function getOrderIdFromURL() {
    // This is a placeholder. In PHP/JS, you'd use URLSearchParams
    // Example: new URLSearchParams(window.location.search).get('order_id');
    console.log("[INIT] Simulating order ID retrieval from URL...");
    return 1001; // Default to a mock order ID for testing
}

// --- DATABASE INTERFACE FUNCTIONS (PLACEHOLDERS) ---

/**
 * [DB INTERFACE] Fetches detailed information for a single active order.
 * @returns {Promise<Object>} The full order object.
 */
async function fetchOrderDetailsDB(orderId) {
    console.log(`[DB Call] Fetching details for Order #${orderId}...`);
    // TODO: Replace with actual PHP/Ajax call to 'api/order_details.php?id=' + orderId

    // MOCK DATA
    const mockOrder = {
        id: orderId,
        status: 'AWAITING_PICKUP', // Can be 'AWAITING_PICKUP', 'IN_TRANSIT', or 'DELIVERED'
        restaurant_name: "The Wok Master",
        pickup_address: "15 Central Avenue, Kitchen Entrance",
        customer_name: "Sarah Connor",
        delivery_address: "32 Skyway Towers, Unit 5A"
    };
    
    // Simulate updating status for testing flow
    // if (orderId % 2 === 0) mockOrder.status = 'IN_TRANSIT'; 

    return mockOrder; 
}

/**
 * [DB INTERFACE] Updates the order status in the database.
 */
async function updateOrderStatusDB(orderId, newStatus) {
    console.log(`[DB Call] Updating Order ${orderId} status to: ${newStatus}`);
    // TODO: Replace with actual PHP/Ajax POST request to 'api/update_order_status.php'
    // Should return success/fail
    return true; // Simulate success
}

// --- CORE FUNCTIONALITY ---

/**
 * Renders the order details and the status update buttons.
 */
async function renderOrderDetails() {
    if (!ACTIVE_ORDER_ID) {
        document.getElementById('order-title').textContent = "Error: Invalid Order ID.";
        return;
    }

    const order = await fetchOrderDetailsDB(ACTIVE_ORDER_ID);

    if (!order || order.status === 'DELIVERED' || order.status === 'CANCELLED') {
        document.getElementById('order-title').textContent = `Order #${ACTIVE_ORDER_ID} Details`;
        document.getElementById('current-status').textContent = order ? order.status : 'Order Not Found';
        document.getElementById('status-buttons').innerHTML = '<p>This order is no longer active for delivery updates.</p>' + 
            document.getElementById('status-buttons').innerHTML;
        return;
    }

    // Update Display Fields
    document.getElementById('order-title').textContent = `Order #${order.id} Status Update`;
    document.getElementById('current-status').textContent = STATUS_SEQUENCE[order.status].display || order.status;
    document.getElementById('restaurant-name').textContent = order.restaurant_name;
    document.getElementById('pickup-address').textContent = order.pickup_address;
    document.getElementById('customer-name').textContent = order.customer_name;
    document.getElementById('delivery-address').textContent = order.delivery_address;

    // Render Status Buttons
    const buttonsContainer = document.getElementById('status-buttons');
    const currentStatusConfig = STATUS_SEQUENCE[order.status];
    let buttonsHTML = document.getElementById('status-buttons').innerHTML; // Keep Back button

    if (currentStatusConfig && currentStatusConfig.next) {
        const nextStatus = currentStatusConfig.next;
        const nextStatusConfig = STATUS_SEQUENCE[nextStatus];
        const buttonClass = (nextStatus === 'DELIVERED') ? 'btn-complete' : 'btn-pickup';
        
        buttonsHTML = `
            <button class="${buttonClass}" onclick="handleStatusUpdateClick('${order.id}', '${nextStatus}')">
                ${currentStatusConfig.buttonText}
            </button>
            <button class="btn-back" onclick="window.location.href='rider_dashboard.html'">← Back to Dashboard</button>
        `;
    }
    
    buttonsContainer.innerHTML = buttonsHTML;
}

// --- INTERACTION HANDLER ---

/**
 * Handles the click event for status update buttons.
 */
function handleStatusUpdateClick(orderId, newStatus) {
    const displayStatus = STATUS_SEQUENCE[newStatus].display || newStatus;
    
    if (confirm(`Confirm updating status to "${displayStatus}"?`)) {
        updateOrderStatusDB(orderId, newStatus).then(success => {
            if (success) {
                alert(`Order #${orderId} status successfully updated to ${displayStatus}.`);
                // After successful update, redirect back to dashboard or refresh the details
                window.location.href = 'rider_dashboard.html'; 
            } else {
                alert("Status update failed. Please try again.");
            }
        });
    }
}


// Initialize the page
document.addEventListener('DOMContentLoaded', renderOrderDetails);