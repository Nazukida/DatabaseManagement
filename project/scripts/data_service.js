/**
 * Mock Data Service
 * Simulates backend database operations and provides data matching the SQL schema.
 */

const MockDB = {
    // Users Table
    users: [
        {
            UserID: 12345,
            Username: "Meituan User",
            PhoneNumber: "13800138000",
            Email: "user@example.com",
            PasswordHash: "hashed_password",
            RegistrationDate: "2025-01-01"
        }
    ],

    // Delivery_Addresses Table
    addresses: [
        {
            AddressID: 1,
            UserID: 12345,
            Label: "Home",
            ContactName: "Meituan User",
            ContactPhone: "13800138000",
            FullAddress: "Room 101, Building A, Keji Road",
            IsDefault: true
        },
        {
            AddressID: 2,
            UserID: 12345,
            Label: "Company",
            ContactName: "Meituan User",
            ContactPhone: "13800138000",
            FullAddress: "Floor 10, CBD Tower",
            IsDefault: false
        }
    ],

    // Restaurants Table
    restaurants: [
        {
            RestaurantID: 1,
            RestaurantName: "KFC (Keji Road Store)",
            Description: "Fried Chicken, Burgers",
            LogoImage: "https://via.placeholder.com/100",
            BusinessStatus: "Open",
            DeliveryFee: 5.00,
            MinimumOrderAmount: 20.00,
            DeliveryArea: "3km",
            AverageRating: 4.8
        },
        {
            RestaurantID: 2,
            RestaurantName: "McDonald's (Bell Tower Store)",
            Description: "Burgers, Fries",
            LogoImage: "https://via.placeholder.com/100",
            BusinessStatus: "Open",
            DeliveryFee: 6.00,
            MinimumOrderAmount: 15.00,
            DeliveryArea: "2.5km",
            AverageRating: 4.7
        },
        {
            RestaurantID: 3,
            RestaurantName: "Haidilao Hot Pot",
            Description: "Hot Pot",
            LogoImage: "https://via.placeholder.com/100",
            BusinessStatus: "Open",
            DeliveryFee: 8.00,
            MinimumOrderAmount: 50.00,
            DeliveryArea: "5km",
            AverageRating: 4.9
        },
        {
            RestaurantID: 4,
            RestaurantName: "Starbucks (CBD Store)",
            Description: "Coffee, Cake",
            LogoImage: "https://via.placeholder.com/100",
            BusinessStatus: "Open",
            DeliveryFee: 4.00,
            MinimumOrderAmount: 30.00,
            DeliveryArea: "1.8km",
            AverageRating: 4.6
        }
    ],

    // Menu_Items Table
    menuItems: [
        // KFC (ID 1)
        { MenuItemID: 101, RestaurantID: 1, ItemName: "Spicy Chicken Burger", ItemDescription: "Spicy and crispy", Price: 25.00, ItemImage: "", StockStatus: "In Stock" },
        { MenuItemID: 102, RestaurantID: 1, ItemName: "Family Bucket", ItemDescription: "Chicken & Fries", Price: 88.00, ItemImage: "", StockStatus: "In Stock" },
        { MenuItemID: 103, RestaurantID: 1, ItemName: "Coke (Large)", ItemDescription: "Ice Cold", Price: 12.00, ItemImage: "", StockStatus: "In Stock" },
        
        // McDonald's (ID 2)
        { MenuItemID: 201, RestaurantID: 2, ItemName: "Big Mac", ItemDescription: "Classic Beef Burger", Price: 30.00, ItemImage: "", StockStatus: "In Stock" },
        { MenuItemID: 202, RestaurantID: 2, ItemName: "French Fries (L)", ItemDescription: "Crispy", Price: 14.00, ItemImage: "", StockStatus: "In Stock" },
        
        // Haidilao (ID 3)
        { MenuItemID: 301, RestaurantID: 3, ItemName: "Spicy Pot Base", ItemDescription: "Very Spicy", Price: 68.00, ItemImage: "", StockStatus: "In Stock" },
        { MenuItemID: 302, RestaurantID: 3, ItemName: "Beef Slices", ItemDescription: "Fresh Beef", Price: 48.00, ItemImage: "", StockStatus: "In Stock" },

        // Starbucks (ID 4)
        { MenuItemID: 401, RestaurantID: 4, ItemName: "Latte", ItemDescription: "Hot Coffee", Price: 32.00, ItemImage: "", StockStatus: "In Stock" },
        { MenuItemID: 402, RestaurantID: 4, ItemName: "Cheesecake", ItemDescription: "Sweet", Price: 38.00, ItemImage: "", StockStatus: "In Stock" }
    ],

    // Methods to simulate DB queries
    getRestaurants: function() {
        return this.restaurants;
    },

    getMenuByRestaurantID: function(id) {
        return this.menuItems.filter(item => item.RestaurantID === parseInt(id));
    },

    addMenuItem: function(item) {
        // Generate a simple ID
        item.MenuItemID = Date.now();
        this.menuItems.push(item);
        return true;
    },

    deleteMenuItem: function(itemId) {
        const index = this.menuItems.findIndex(i => i.MenuItemID === itemId);
        if (index !== -1) {
            this.menuItems.splice(index, 1);
            return true;
        }
        return false;
    },

    getUser: function(id) {
        return this.users.find(u => u.UserID === id);
    },

    getUserAddresses: function(userId) {
        return this.addresses.filter(a => a.UserID === userId);
    },

    // --- New Methods for Riders, Orders, Admins ---

    // Riders Table
    riders: [
        { RiderID: 4001, Name: "Rider Zhang", PhoneNumber: "13900000001", CurrentStatus: "Offline", AverageRating: 4.8 }
    ],

    // Orders Table
    orders: [
        { 
            OrderID: 1001, 
            UserID: 12345, 
            RestaurantID: 1, 
            RiderID: null, 
            OrderStatus: "Pending", 
            TotalAmount: 35.50, 
            OrderDate: "2025-11-28 10:30:00",
            Items: [
                { MenuItemID: 101, ItemName: "Spicy Chicken Burger", Quantity: 1, Price: 25.00 }
            ]
        },
        { 
            OrderID: 1002, 
            UserID: 12345, 
            RestaurantID: 1, 
            RiderID: 4001, 
            OrderStatus: "Delivering", 
            TotalAmount: 88.00, 
            OrderDate: "2025-11-28 11:00:00",
            Items: [
                { MenuItemID: 102, ItemName: "Family Bucket", Quantity: 1, Price: 88.00 }
            ]
        }
    ],

    // Admins Table
    admins: [
        { AdminID: 9001, Username: "admin", PasswordHash: "admin123" }
    ],

    // Rider Methods
    getRider: function(id) {
        return this.riders.find(r => r.RiderID === id);
    },

    updateRiderStatus: function(id, status) {
        const rider = this.getRider(id);
        if (rider) rider.CurrentStatus = status;
        return !!rider;
    },

    // Order Methods
    getOrdersByRider: function(riderId) {
        return this.orders.filter(o => o.RiderID === riderId);
    },

    getAvailableOrdersForRider: function() {
        // Orders that are 'ReadyForPickup' and have no rider assigned
        return this.orders.filter(o => o.OrderStatus === 'ReadyForPickup' && !o.RiderID);
    },

    getOrdersByRestaurant: function(restaurantId) {
        return this.orders.filter(o => o.RestaurantID === restaurantId);
    },

    updateOrderStatus: function(orderId, status) {
        const order = this.orders.find(o => o.OrderID === orderId);
        if (order) {
            order.OrderStatus = status;
            return true;
        }
        return false;
    },

    assignOrderToRider: function(orderId, riderId) {
        const order = this.orders.find(o => o.OrderID === orderId);
        if (order) {
            order.RiderID = riderId;
            order.OrderStatus = 'Delivering'; // Assuming immediate pickup for simplicity
            return true;
        }
        return false;
    },
    
    // Auth Methods
    loginCustomer: function(username, password) {
        // In a real app, check hash. Here just check existence for demo.
        return this.users.find(u => u.Username === username); // Password check skipped for mock
    },

    loginRider: function(phone) {
        return this.riders.find(r => r.PhoneNumber === phone);
    },

    loginMerchant: function(restaurantId) {
        return this.restaurants.find(r => r.RestaurantID === restaurantId);
    }
};
