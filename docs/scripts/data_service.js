

const MockDB = {

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


    restaurants: [
        {
            RestaurantID: 1,
            RestaurantName: "KFC (Keji Road Store)",
            Description: "Fried Chicken, Burgers",
            LogoImage: "https://placehold.co/600x400/orange/white?text=KFC",
            BusinessStatus: "Open",
            DeliveryFee: 5.00,
            MinimumOrderAmount: 20.00,
            DeliveryArea: "3km",
            AverageRating: 4.8
        },
        {
            RestaurantID: 2,
            RestaurantName: "McDonald's",
            Description: "Burgers, Fries",
            LogoImage: "https://placehold.co/600x400/red/white?text=MCD",
            BusinessStatus: "Open",
            DeliveryFee: 4.00,
            MinimumOrderAmount: 15.00,
            DeliveryArea: "3km",
            AverageRating: 4.7
        },
        {
            RestaurantID: 3,
            RestaurantName: "Pizza Hut",
            Description: "Pizza, Pasta",
            LogoImage: "https://placehold.co/600x400/yellow/black?text=Pizza",
            BusinessStatus: "Open",
            DeliveryFee: 6.00,
            MinimumOrderAmount: 30.00,
            DeliveryArea: "4km",
            AverageRating: 4.6
        }
    ],
    
    menuItems: [], // Populated dynamically or simplified
    orders: []
};
