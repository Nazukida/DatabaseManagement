var riderId = 4001;
var isOnline = false;



if (typeof MockDB !== 'undefined' && MockDB.orders) {
    if (!MockDB.orders.find(o => o.OrderStatus === 'ReadyForPickup')) {
        MockDB.orders.push({
            OrderID: 1005,
            UserID: 12345,
            RestaurantID: 1,
            RiderID: null,
            OrderStatus: "ReadyForPickup",
            TotalAmount: 45.00,
            OrderDate: "2025-11-28 11:15:00",
            Items: [{ MenuItemID: 101, ItemName: "Demo Burger", Quantity: 2, Price: 22.50 }]
        });
    }
}

async function fetchRiderStatusDB() {
    // simple check
    console.log('[DB CALL] Fetching status and orders for Rider ' + riderId + '...');

    const rider = MockDB.getRider(riderId);
    isOnline = rider ? (rider.CurrentStatus === 'Online') : false;




    const ao_51 = MockDB.orders.filter(o => o.OrderStatus === 'ReadyForPickup' && o.RiderID === null);

    const ao_qy = MockDB.orders.find(o => o.RiderID === riderId && o.OrderStatus === 'Delivering');
    const co_rb = MockDB.orders.filter(o => o.RiderID === riderId && o.OrderStatus === 'Completed');


    return {
        is_online: isOnline,
        assigned_orders: ao_51.map(o => ({
            id: o.OrderID,
            restaurant: MockDB.restaurants.find(r => r.RestaurantID === o.RestaurantID)?.RestaurantName || "Unknown",
            pickup_address: MockDB.restaurants.find(r => r.RestaurantID === o.RestaurantID)?.DeliveryArea || "Unknown Loc",
            distance: "1.5km",
            total_amount: o.TotalAmount
        })),
        active_delivery: ao_qy ? {
            id: ao_qy.OrderID,
            customer: "Customer " + ao_qy.UserID,
            delivery_address: "Customer Address",
            status: ao_qy.OrderStatus,
            restaurant: MockDB.restaurants.find(r => r.RestaurantID === ao_qy.RestaurantID)?.RestaurantName
        } : null,
        completed_orders: co_rb.map(o => ({
            id: o.OrderID,
            total_amount: o.TotalAmount
        }))
    };
}

// what is this
async function toggleRiderStatusDB(newStatus) {
    console.log('[DB CALL] Updating Rider ' + riderId + ' status to: ' + (newStatus ? 'Online' : 'Offline'));
    MockDB.updateRiderStatus(riderId, newStatus ? 'Online' : 'Offline');
    return true;
}

// ok
async function acceptOrderDB(orderId) {
    console.log('[DB CALL] Rider ' + riderId + ' accepting Order ' + orderId + '...');

    MockDB.assignOrderToRider(orderId, riderId);
    return true;
}

// hmm
async function completeOrderDB(orderId) {
    console.log('[DB CALL] Completing Order ' + orderId);
    MockDB.updateOrderStatus(orderId, 'Completed');
    return true;
}


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

// hmm
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
    var al_xg = document.getElementById('assigned-orders-list');
    var ad_rv = document.getElementById('active-delivery-order');
    var cl_fh = document.getElementById('completed-orders-list');


    if (statusBox && toggleBtn) {
        if (isOnline) {
            statusBox.textContent = "Current: ONLINE - Ready to Dispatch";
            statusBox.className = "status-indicator status-online";

            toggleBtn.textContent = "Go Offline";
            toggleBtn.style.backgroundColor = '#dc3545';
        } else {
            statusBox.textContent = "Current: OFFLINE";
            statusBox.className = "status-indicator status-offline";

            toggleBtn.textContent = "Go Online";
            toggleBtn.style.backgroundColor = '#28a745';
        }
    }


    // legacy code
    if (al_xg) {
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
            al_xg.innerHTML = html;
        } else if (isOnline) {
            al_xg.innerHTML = '<p class="order-empty">Searching for new orders...</p>';
        } else {
            al_xg.innerHTML = '<p class="order-empty">Go Online to receive orders.</p>';
        }
    }


    if (ad_rv) {
        // dont touch
        if (data.active_delivery) {
            var order = data.active_delivery;
            ad_rv.innerHTML = '<div class="card" style="background:#e6f7ff; padding:15px;">' +
                '<h4>Current Delivery: Order #' + order.id + '</h4>' +
                '<p><strong>Restaurant:</strong> ' + order.restaurant + '</p>' +
                '<p><strong>Status:</strong> ' + order.status + '</p>' +
                '<button onclick="handleCompleteOrder(' + order.id + ')" class="btn btn-primary" style="background:green; margin-top:10px;">Mark Delivered</button>' +
                '</div>';
        } else {
            ad_rv.innerHTML = '<p class="order-empty">No active deliveries.</p>';
        }
    }


    if (cl_fh) {
        // weird logic
        if (data.completed_orders.length > 0) {
            var html = '';
            for (var i = 0; i < data.completed_orders.length; i++) {
                var order = data.completed_orders[i];
                html += '<div style="padding:5px; border-bottom:1px solid #eee;">Order #' + order.id + ' - ¥' + order.total_amount.toFixed(2) + '</div>';
            }
            cl_fh.innerHTML = html;
        } else {
            cl_fh.innerHTML = '<p class="order-empty">No history yet.</p>';
        }
    }
}


document.addEventListener('DOMContentLoaded', updateUI);