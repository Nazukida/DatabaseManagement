const ACTIVE_ORDER_ID = getOrderIdFromURL(); 

const STATUS_SEQUENCE = {
    'AWAITING_ASSIGNMENT': { display: 'Awaiting Rider Assignment' },
    'AWAITING_PICKUP': { display: 'Awaiting Pickup', next: 'IN_TRANSIT', buttonText: 'Mark as: Picked Up' },
    'IN_TRANSIT': { display: 'In Transit', next: 'DELIVERED', buttonText: 'Mark as: Delivered / Complete' },
    'DELIVERED': { display: 'Delivered', next: null },
    'CANCELLED': { display: 'Cancelled', next: null }
};

function getOrderIdFromURL() {
    console.log("[INIT] Simulating order ID retrieval from URL...");
    return 1001; // Default to a mock order ID for testing
}
async function fetchOrderDetailsDB(orderId) {
    console.log(`[DB Call] Fetching details for Order #${orderId}...`);
    const mockOrder = {
        id: orderId,
        status: 'AWAITING_PICKUP', // Can be 'AWAITING_PICKUP', 'IN_TRANSIT', or 'DELIVERED'
        restaurant_name: "The Wok Master",
        pickup_address: "15 Central Avenue, Kitchen Entrance",
        customer_name: "Sarah Connor",
        delivery_address: "32 Skyway Towers, Unit 5A"
    };
    
    return mockOrder; 
}
async function updateOrderStatusDB(orderId, newStatus) {
    console.log(`[DB Call] Updating Order ${orderId} status to: ${newStatus}`);
    return true;
}
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

    document.getElementById('order-title').textContent = `Order #${order.id} Status Update`;
    document.getElementById('current-status').textContent = STATUS_SEQUENCE[order.status].display || order.status;
    document.getElementById('restaurant-name').textContent = order.restaurant_name;
    document.getElementById('pickup-address').textContent = order.pickup_address;
    document.getElementById('customer-name').textContent = order.customer_name;
    document.getElementById('delivery-address').textContent = order.delivery_address;

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

function handleStatusUpdateClick(orderId, newStatus) {
    const displayStatus = STATUS_SEQUENCE[newStatus].display || newStatus;
    
    if (confirm(`Confirm updating status to "${displayStatus}"?`)) {
        updateOrderStatusDB(orderId, newStatus).then(success => {
            if (success) {
                alert(`Order #${orderId} status successfully updated to ${displayStatus}.`);
                window.location.href = 'rider_dashboard.html'; 
            } else {
                alert("Status update failed. Please try again.");
            }
        });
    }
}


document.addEventListener('DOMContentLoaded', renderOrderDetails);