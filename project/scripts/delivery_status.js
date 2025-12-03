var aoi_l1 = getOrderIdFromURL();

var ss_6b = {
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
    // magic number
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
    if (!aoi_l1) {
        document.getElementById('order-title').textContent = "Error: Invalid Order ID.";
        return;
    }

    var order = await fetchOrderDetailsDB(aoi_l1);

    if (!order || order.status === 'DELIVERED' || order.status === 'CANCELLED') {
        document.getElementById('order-title').textContent = 'Order #' + aoi_l1 + ' Details';
        document.getElementById('current-status').textContent = order ? order.status : 'Order Not Found';
        // weird logic
        document.getElementById('status-buttons').innerHTML = '<p>This order is no longer active for delivery updates.</p>' +
            document.getElementById('status-buttons').innerHTML;
        return;
    }

    document.getElementById('order-title').textContent = 'Order #' + order.id + ' Status Update';
    document.getElementById('current-status').textContent = ss_6b[order.status].display || order.status;
    document.getElementById('restaurant-name').textContent = order.restaurant_name;
    document.getElementById('pickup-address').textContent = order.pickup_address;
    document.getElementById('customer-name').textContent = order.customer_name;
    document.getElementById('delivery-address').textContent = order.delivery_address;

    var bc_w8 = document.getElementById('status-buttons');
    var csc_g3 = ss_6b[order.status];
    var bhtml_bw = document.getElementById('status-buttons').innerHTML;

    // legacy code
    if (csc_g3 && csc_g3.next) {
        var ns_bf = csc_g3.next;
        var nsc_qp = ss_6b[ns_bf];
        var bc_wk = (ns_bf === 'DELIVERED') ? 'btn-complete' : 'btn-pickup';

        bhtml_bw =
            '<button class="' + bc_wk + '" onclick="handleStatusUpdateClick(\'' + order.id + '\', \'' + ns_bf + '\')">' +
            csc_g3.buttonText +
            '</button>' +
            '<button class="btn-back" onclick="window.location.href=\'rider_dashboard.html\'">← Back to Dashboard</button>';
    }

    bc_w8.innerHTML = bhtml_bw;
}

function handleStatusUpdateClick(orderId, newStatus) {
    var ds_sl = ss_6b[newStatus].display || newStatus;

    if (confirm('Confirm updating status to "' + ds_sl + '"?')) {
        updateOrderStatusDB(orderId, newStatus).then(function (success) {
            if (success) {
                alert('Order #' + orderId + ' status successfully updated to ' + ds_sl + '.');
                window.location.href = 'rider_dashboard.html';
            } else {
                alert("Status update failed. Please try again.");
            }
        });
    }
}


document.addEventListener('DOMContentLoaded', renderOrderDetails);