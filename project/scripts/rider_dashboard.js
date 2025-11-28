var riderId = 4001;
var isOnline = false;

// Initialize Mock Data for Demo if needed
// We ensure there is at least one order available to accept
if (typeof MockDB !== 'undefined' && MockDB.orders) {
    if (!MockDB.orders.find(o => o.OrderStatus === 'ReadyForPickup')) {
        MockDB.orders.push({
            OrderID: 1005,
            UserID: 12345,
            RestaurantID: 1,
            RiderID: null, // No rider yet
            OrderStatus: "ReadyForPickup",
            TotalAmount: 45.00,
            OrderDate: "2025-11-28 11:15:00",
            Items: [{ MenuItemID: 101, ItemName: "Demo Burger", Quantity: 2, Price: 22.50 }]
        });
    }
}

async function fetchRiderStatusDB() {
    console.log('[DB CALL] Fetching status and orders for Rider ' + riderId + '...');
    
    const rider = MockDB.getRider(riderId);
    isOnline = rider ? (rider.CurrentStatus === 'Online') : false;

    // "Assigned" orders in this context will be orders ready for pickup but no rider, 
    // OR orders specifically assigned to this rider but not yet "Delivering".
    // For this demo, let's show "ReadyForPickup" orders as available to accept.
    const availableOrders = MockDB.orders.filter(o => o.OrderStatus === 'ReadyForPickup' && o.RiderID === null);

    const activeOrder = MockDB.orders.find(o => o.RiderID === riderId && o.OrderStatus === 'Delivering');
    const completedOrders = MockDB.orders.filter(o => o.RiderID === riderId && o.OrderStatus === 'Completed');

    // Map to UI format
    return {
        is_online: isOnline,
        assigned_orders: availableOrders.map(o => ({
            id: o.OrderID,
            restaurant: MockDB.restaurants.find(r => r.RestaurantID === o.RestaurantID)?.RestaurantName || "Unknown",
            pickup_address: MockDB.restaurants.find(r => r.RestaurantID === o.RestaurantID)?.DeliveryArea || "Unknown Loc", // Mock address
            distance: "1.5km", // Mock
            total_amount: o.TotalAmount
        })),
        active_delivery: activeOrder ? {
            id: activeOrder.OrderID,
            customer: "Customer " + activeOrder.UserID,
            delivery_address: "Customer Address", // Mock
            status: activeOrder.OrderStatus,
            restaurant: MockDB.restaurants.find(r => r.RestaurantID === activeOrder.RestaurantID)?.RestaurantName
        } : null,
        completed_orders: completedOrders.map(o => ({
            id: o.OrderID,
            total_amount: o.TotalAmount
        }))
    };
}

async function toggleRiderStatusDB(newStatus) {
    console.log('[DB CALL] Updating Rider ' + riderId + ' status to: ' + (newStatus ? 'Online' : 'Offline'));
    MockDB.updateRiderStatus(riderId, newStatus ? 'Online' : 'Offline');
    return true;
}

async function acceptOrderDB(orderId) {
    console.log('[DB CALL] Rider ' + riderId + ' accepting Order ' + orderId + '...');
    // Assign rider and set to Delivering
    MockDB.assignOrderToRider(orderId, riderId);
    return true;
}

async function completeOrderDB(orderId) {
    console.log('[DB CALL] Completing Order ' + orderId);
    MockDB.updateOrderStatus(orderId, 'Completed');
    return true;
}

// UI Logic
async function toggleRiderStatus() {
    var newStatus = !isOnline;
    var success = await toggleRiderStatusDB(newStatus);
    if (success) {
        isOnline = newStatus;
        updateUI(); 
        alert(isOnline ? "You are now ONLINE and ready to accept orders!" : "You are now OFFLINE.");
    }
}

async function handleAcceptOrder(orderId) {
    if (!isOnline) {
        alert("You must be ONLINE to accept orders!");
        return;
    }
    await acceptOrderDB(orderId);
    updateUI();
    alert("Order accepted! Proceed to pickup.");
}

async function handleCompleteOrder(orderId) {
    if (!isOnline) {
        alert("You must be ONLINE to complete orders!");
        return;
    }
    await completeOrderDB(orderId);
    updateUI();
    alert("Order delivered!");
}

async function updateUI() {
    var data = await fetchRiderStatusDB();
    isOnline = data.is_online;
    
    var statusBox = document.getElementById('rider-status-display');
    var toggleBtn = document.getElementById('toggle-status-btn');
    var assignedList = document.getElementById('assigned-orders-list');
    var activeDelivery = document.getElementById('active-delivery-order');
    var completedList = document.getElementById('completed-orders-list');
    
    // Update Status Box
    if (isOnline) {
        statusBox.textContent = "Current: ONLINE - Ready to Dispatch";
        statusBox.className = "status-box status-online"; // Ensure CSS exists or inline style
        statusBox.style.color = "green";
        toggleBtn.textContent = "Go Offline";
        toggleBtn.style.backgroundColor = '#dc3545';
    } else {
        statusBox.textContent = "Current: OFFLINE";
        statusBox.className = "status-box status-offline";
        statusBox.style.color = "red";
        toggleBtn.textContent = "Go Online";
        toggleBtn.style.backgroundColor = '#28a745';
    }
    
    // Update Assigned/Available Orders
    if (data.assigned_orders.length > 0 && isOnline) {
        var html = '';
        for (var i = 0; i < data.assigned_orders.length; i++) {
            var order = data.assigned_orders[i];
            html += '<div class="card" style="margin-bottom:10px; padding:10px; border:1px solid #eee;">' +
                '<h4>Order #' + order.id + ' (Value: ¥' + order.total_amount.toFixed(2) + ')</h4>' +
                '<p>Restaurant: ' + order.restaurant + '</p>' +
                '<p>Pickup: ' + order.pickup_address + '</p>' +
                '<button onclick="handleAcceptOrder(' + order.id + ')" class="btn btn-primary" style="margin-top:5px;">Accept Order</button>' +
            '</div>';
        }
        assignedList.innerHTML = html;
    } else if (isOnline) {
        assignedList.innerHTML = '<p class="order-empty">Searching for new orders...</p>';
    } else {
        assignedList.innerHTML = '<p class="order-empty">Go Online to receive orders.</p>';
    }

    // Update Active Delivery
    if (data.active_delivery) {
        var order = data.active_delivery;
        activeDelivery.innerHTML = '<div class="card" style="background:#e6f7ff; padding:15px;">' +
            '<h4>Current Delivery: Order #' + order.id + '</h4>' +
            '<p><strong>Restaurant:</strong> ' + order.restaurant + '</p>' +
            '<p><strong>Status:</strong> ' + order.status + '</p>' +
            '<button onclick="handleCompleteOrder(' + order.id + ')" class="btn btn-primary" style="background:green; margin-top:10px;">Mark Delivered</button>' +
        '</div>';
    } else {
        activeDelivery.innerHTML = '<p class="order-empty">No active deliveries.</p>';
    }

    // Update Completed Orders
    if (data.completed_orders.length > 0) {
        var html = '';
        for (var i = 0; i < data.completed_orders.length; i++) {
            var order = data.completed_orders[i];
            html += '<div style="padding:5px; border-bottom:1px solid #eee;">Order #' + order.id + ' - ¥' + order.total_amount.toFixed(2) + '</div>';
        }
        completedList.innerHTML = html;
    } else {
        completedList.innerHTML = '<p class="order-empty">No history yet.</p>';
    }
}

// Initial Load
document.addEventListener('DOMContentLoaded', updateUI);