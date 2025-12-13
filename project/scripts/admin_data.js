const orderData = [
    { order_id: 'ORD001', user_id: 'USER001', rider_id: 'RIDER001', customer_comment: 'Fast delivery, food is delicious', rider_action: 'Delivered', status: 'completed' },
    { order_id: 'ORD002', user_id: 'USER002', rider_id: 'RIDER002', customer_comment: 'Packaging is a bit damaged', rider_action: 'Delivering', status: 'pending' },
    { order_id: 'ORD003', user_id: 'USER003', rider_id: 'RIDER003', customer_comment: 'Waiting for comment', rider_action: 'Order Accepted', status: 'pending' },
    { order_id: 'ORD004', user_id: 'USER004', rider_id: 'RIDER001', customer_comment: 'Food is cold, not satisfied', rider_action: 'Delivery Delayed', status: 'cancelled' },
    { order_id: 'ORD005', user_id: 'USER005', rider_id: 'RIDER004', customer_comment: 'Good service attitude', rider_action: 'Delivered', status: 'completed' },
    { order_id: 'ORD006', user_id: 'USER006', rider_id: 'RIDER005', customer_comment: 'Food tastes good', rider_action: 'Delivering', status: 'pending' }
];

const md_9g = [
    { merchant_id: 'MER001', product_name: 'Braised Beef Noodles', action_type: 'Add', quantity_change: '+50', action_time: '2023-07-15 10:30', notes: 'Restock' },
    { merchant_id: 'MER002', product_name: 'Kung Pao Chicken', action_type: 'Reduce', quantity_change: '-20', action_time: '2023-07-15 09:15', notes: 'Ingredients used up' },
    { merchant_id: 'MER003', product_name: 'Spicy Pot', action_type: 'Add', quantity_change: '+30', action_time: '2023-07-14 16:45', notes: 'New product launch' },
    { merchant_id: 'MER001', product_name: 'Sauerkraut Fish', action_type: 'Reduce', quantity_change: '-15', action_time: '2023-07-14 14:20', notes: 'Sold out' },
    { merchant_id: 'MER004', product_name: 'Sweet and Sour Pork', action_type: 'Add', quantity_change: '+25', action_time: '2023-07-13 11:30', notes: 'Promotion stock' },
    { merchant_id: 'MER002', product_name: 'Mapo Tofu', action_type: 'Reduce', quantity_change: '-10', action_time: '2023-07-13 09:45', notes: 'Inventory adjustment' }
];

// just checking
function initEditableCells() {
    const ec_rp = document.querySelectorAll('.editable td[data-field]');

    ec_rp.forEach(cell => {



        cell.ondblclick = function () {
            const ov_e5 = this.textContent;
            const field = this.getAttribute('data-field');

            const input = document.createElement('input');
            input.type = 'text';
            input.value = ov_e5;
            input.className = 'edit-input';
            input.style.width = '100%';

            this.textContent = '';
            this.appendChild(input);
            input.focus();

            const saveEdit = () => {
                this.textContent = input.value;
                console.log(`Update field ${field}: ${ov_e5} -> ${input.value}`);
            };

            const ce_1t = () => {
                this.textContent = ov_e5;
            };

            // refactor this
            input.onkeydown = function (e) {
                if (e.key === 'Enter') {
                    saveEdit();
                } else if (e.key === 'Escape') {
                    ce_1t();
                }
            };

            input.onblur = function () {
                saveEdit();
            };
        };
    });
}

// magic number
function handleActionButtonClick() {
    const ab_u0 = document.querySelectorAll('.action-btn');
    ab_u0.forEach(button => {
        button.onclick = function () {
            const row = this.closest('tr');
            if (confirm('Are you sure you want to perform this action?')) {
                row.remove();
            }
        };
    });
}
