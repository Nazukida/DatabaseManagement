var riderId = 4001;
var isOnline = false;
window.__assignedMockOrders = window.__assignedMockOrders || [
    { id: 1005, restaurant: "Pizza Palace", pickup_address: "123 Main St", distance: "2.1km", total_amount: 35.50 },
    { id: 1006, restaurant: "Taco Express", pickup_address: "45 Market Rd", distance: "0.9km", total_amount: 18.00 }
];
window.__activeMockOrder = window.__activeMockOrder || null;
window.__completedMockOrders = window.__completedMockOrders || [];

async function fetchRiderStatusDB() {
    console.log('[DB CALL] Fetching status and orders for Rider ' + riderId + '...');
    var mockData = {
        is_online: isOnline,
        assigned_orders: isOnline ? window.__assignedMockOrders : [],
        active_delivery: window.__activeMockOrder,
        completed_orders: window.__completedMockOrders
    };
    return mockData; 
}

async function toggleRiderStatusDB(newStatus) {
    console.log('[DB CALL] Updating Rider ' + riderId + ' status to: ' + (newStatus ? 'ONLINE' : 'OFFLINE'));
    return true;
}
async function acceptOrderDB(orderId) {
    console.log('[DB CALL] Rider ' + riderId + ' accepting Order ' + orderId + '...');
    var index = -1;
    for (var i = 0; i < window.__assignedMockOrders.length; i++) {
        if (window.__assignedMockOrders[i].id === orderId) {
            index = i;
            break;
        }
    }
    if (index !== -1) {
        var picked = window.__assignedMockOrders[index];
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
    console.log('[DB CALL] Updating Order ' + orderId + ' status to: ' + newStatus);
    return true;
}
async function toggleRiderStatus() {
    var newStatus = !isOnline;
    
    var success = await toggleRiderStatusDB(newStatus);
    
    if (success) {
        isOnline = newStatus;
        updateUI(); 
        alert(isOnline ? "You are now ONLINE and ready to accept orders!" : "You are now OFFLINE.");
    } else {
        alert("Failed to update status. Please try again.");
    }
}

async function updateUI() {
    var data = await fetchRiderStatusDB();
    isOnline = data.is_online;
    
    var statusBox = document.getElementById('rider-status-display');
    var toggleBtn = document.getElementById('toggle-status-btn');
    var assignedList = document.getElementById('assigned-orders-list');
    var activeDelivery = document.getElementById('active-delivery-order');
    var completedList = document.getElementById('completed-orders-list');
    
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
        var html = '';
        for (var i = 0; i < data.assigned_orders.length; i++) {
            var order = data.assigned_orders[i];
            html += '<div class="order-card">' +
                '<h4>Order #' + order.id + ' (Value: $' + order.total_amount.toFixed(2) + ')</h4>' +
                '<p>Restaurant: ' + order.restaurant + '</p>' +
                '<p>Pickup Address: ' + order.pickup_address + '</p>' +
                '<p>Distance: ' + order.distance + '</p>' +
                '<button onclick="handleAcceptOrder(' + order.id + ')" style="background-color: #007bff;">Accept Order</button>' +
            '</div>';
        }
        assignedList.innerHTML = html;
    } else if (isOnline) {
        assignedList.innerHTML = '<p>No new orders assigned at this time.</p>';
    } else {
        assignedList.innerHTML = '<p>Please go online to receive new assignments.</p>';
    }
    
    if (data.active_delivery) {
        var order = data.active_delivery;
        var pickupBtn = '';
        if (order.status === 'AWAITING_PICKUP') {
            pickupBtn = '<button onclick="handleStatusUpdate(' + order.id + ', \'IN_TRANSIT\')" style="background-color: #ffc107; color: #343a40;">Mark as: Picked Up (In Transit)</button>';
        }

        activeDelivery.innerHTML = 
            '<div class="order-card active-delivery-appear" style="border-color: #007bff; border-width: 2px;">' +
                '<h4>Active Delivery: Order #' + order.id + '</h4>' +
                '<p>Restaurant: ' + order.restaurant + '</p>' +
                '<p>Customer: ' + order.customer + '</p>' +
                '<p>Delivery Address: <strong>' + order.delivery_address + '</strong></p>' +
                '<p>Current Status: <strong>' + order.status.replace('_', ' ') + '</strong></p>' +
                pickupBtn +
                '<button onclick="handleCompleteOrder(' + order.id + ')" style="background-color: #28a745; margin-top: 8px;">Complete Order</button>' +
            '</div>';
    } else {
        activeDelivery.innerHTML = '<p>You currently have no active deliveries.</p>';
    }

    if (data.completed_orders && data.completed_orders.length > 0) {
        var html = '';
        for (var i = 0; i < data.completed_orders.length; i++) {
            var order = data.completed_orders[i];
            html += '<div class="order-card">' +
                '<h4>Completed Order #' + order.id + '</h4>' +
                '<p>Restaurant: ' + (order.restaurant || 'N/A') + '</p>' +
                '<p>Customer: ' + (order.customer || 'N/A') + '</p>' +
                '<p>Delivered To: <strong>' + (order.delivery_address || 'N/A') + '</strong></p>' +
                '<p>Final Status: <strong>' + (order.status || 'DELIVERED') + '</strong></p>' +
            '</div>';
        }
        completedList.innerHTML = html;
    } else {
        completedList.innerHTML = '<p class="order-empty">No orders have been completed yet.</p>';
    }
}

function handleAcceptOrder(orderId) {
    if (!isOnline) {
        alert("You must be ONLINE to accept an order.");
        return;
    }
    if (confirm('Confirm acceptance of Order #' + orderId + '?')) {
        acceptOrderDB(orderId).then(function(success) {
            if (success) {
                alert('Order #' + orderId + ' successfully accepted. Please proceed to pickup.');
                updateUI();
            } else {
                alert("Failed to accept order. It might have been taken by another rider.");
            }
        });
    }
}

function handleCompleteOrder(orderId) {
    var current = window.__activeMockOrder;
    if (!current || current.id !== orderId) {
        alert("No matching active order found to complete.");
        return;
    }

    if (!confirm('Confirm marking Order #' + orderId + ' as DELIVERED and move it to Completed Orders?')) {
        return;
    }

    updateOrderStatusDB(orderId, 'DELIVERED').then(function(success) {
        if (!success) {
            alert("Status update failed. Please try again.");
            return;
        }

        var completedCopy = {
            id: current.id,
            restaurant: current.restaurant,
            customer: current.customer,
            delivery_address: current.delivery_address,
            status: 'DELIVERED'
        };

        window.__completedMockOrders.push(completedCopy);
        window.__activeMockOrder = null;

        alert('Order #' + orderId + ' has been delivered and moved to Completed Orders.');
        updateUI();
    });
}

function handleStatusUpdate(orderId, newStatus) {
    if (confirm('Confirm updating Order #' + orderId + ' status to "' + newStatus + '"?')) {
        updateOrderStatusDB(orderId, newStatus).then(function(success) {
            if (success) {
                // Update local mock state so UI reflects the change
                if (window.__activeMockOrder && window.__activeMockOrder.id === orderId) {
                    window.__activeMockOrder.status = newStatus;
                }
                alert('Order #' + orderId + ' status updated to ' + newStatus + '.');
                updateUI();
            } else {
                alert("Status update failed. Please check your connection.");
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', updateUI);