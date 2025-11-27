var activeOrderId = getOrderIdFromURL(); 

var statusSequence = {
    'AWAITING_ASSIGNMENT': { display: 'Awaiting Rider Assignment' },
    'AWAITING_PICKUP': { display: 'Awaiting Pickup', next: 'IN_TRANSIT', buttonText: 'Mark as: Picked Up' },
    'IN_TRANSIT': { display: 'In Transit', next: 'DELIVERED', buttonText: 'Mark as: Delivered / Complete' },
    'DELIVERED': { display: 'Delivered', next: null },
    'CANCELLED': { display: 'Cancelled', next: null }
};

function getOrderIdFromURL() {
    var params = new URLSearchParams(window.location.search);
    var id = params.get('id');
    console.log('[INIT] Retrieved Order ID from URL: ' + (id || 'Defaulting to 1001'));
    return id ? parseInt(id) : 1001; 
}
async function fetchOrderDetailsDB(orderId) {
    console.log('[DB Call] Fetching details for Order #' + orderId + '...');
    var mockOrder = {
        id: orderId,
        status: 'AWAITING_PICKUP', 
        restaurant_name: "The Wok Master",
        pickup_address: "15 Central Avenue, Kitchen Entrance",
        customer_name: "Sarah Connor",
        delivery_address: "32 Skyway Towers, Unit 5A"
    };
    
    return mockOrder; 
}
async function updateOrderStatusDB(orderId, newStatus) {
    console.log('[DB Call] Updating Order ' + orderId + ' status to: ' + newStatus);
    return true;
}
async function renderOrderDetails() {
    if (!activeOrderId) {
        document.getElementById('order-title').textContent = "Error: Invalid Order ID.";
        return;
    }

    var order = await fetchOrderDetailsDB(activeOrderId);

    if (!order || order.status === 'DELIVERED' || order.status === 'CANCELLED') {
        document.getElementById('order-title').textContent = 'Order #' + activeOrderId + ' Details';
        document.getElementById('current-status').textContent = order ? order.status : 'Order Not Found';
        document.getElementById('status-buttons').innerHTML = '<p>This order is no longer active for delivery updates.</p>' + 
            document.getElementById('status-buttons').innerHTML;
        return;
    }

    document.getElementById('order-title').textContent = 'Order #' + order.id + ' Status Update';
    document.getElementById('current-status').textContent = statusSequence[order.status].display || order.status;
    document.getElementById('restaurant-name').textContent = order.restaurant_name;
    document.getElementById('pickup-address').textContent = order.pickup_address;
    document.getElementById('customer-name').textContent = order.customer_name;
    document.getElementById('delivery-address').textContent = order.delivery_address;

    var buttonsContainer = document.getElementById('status-buttons');
    var currentStatusConfig = statusSequence[order.status];
    var buttonsHTML = document.getElementById('status-buttons').innerHTML; 

    if (currentStatusConfig && currentStatusConfig.next) {
        var nextStatus = currentStatusConfig.next;
        var nextStatusConfig = statusSequence[nextStatus];
        var buttonClass = (nextStatus === 'DELIVERED') ? 'btn-complete' : 'btn-pickup';
        
        buttonsHTML = 
            '<button class="' + buttonClass + '" onclick="handleStatusUpdateClick(\'' + order.id + '\', \'' + nextStatus + '\')">' +
                currentStatusConfig.buttonText +
            '</button>' +
            '<button class="btn-back" onclick="window.location.href=\'rider_dashboard.html\'">← Back to Dashboard</button>';
    }
    
    buttonsContainer.innerHTML = buttonsHTML;
}

function handleStatusUpdateClick(orderId, newStatus) {
    var displayStatus = statusSequence[newStatus].display || newStatus;
    
    if (confirm('Confirm updating status to "' + displayStatus + '"?')) {
        updateOrderStatusDB(orderId, newStatus).then(function(success) {
            if (success) {
                alert('Order #' + orderId + ' status successfully updated to ' + displayStatus + '.');
                window.location.href = 'rider_dashboard.html'; 
            } else {
                alert("Status update failed. Please try again.");
            }
        });
    }
}


document.addEventListener('DOMContentLoaded', renderOrderDetails);