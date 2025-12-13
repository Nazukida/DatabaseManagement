CREATE TABLE Users (
    UserID INT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    PhoneNumber VARCHAR(20),
    Email VARCHAR(100),
    RegistrationDate DATETIME
);

CREATE TABLE Delivery_Addresses (
    AddressID INT PRIMARY KEY,
    Label VARCHAR(50),
    IsDefault BOOLEAN,
    ContactName VARCHAR(50),
    ContactPhone VARCHAR(20),
    FullAddress VARCHAR(255),
    UserID INT,
    FOREIGN KEY (UserID) REFERENCES Users(UserID)
);

CREATE TABLE Restaurants (
    RestaurantID INT PRIMARY KEY,
    RestaurantName VARCHAR(100) NOT NULL,
    Description TEXT,
    LogoImage VARCHAR(255),
    BusinessStatus VARCHAR(20),
    DeliveryFee DECIMAL(10,2),
    MinimumOrderAmount DECIMAL(10,2),
    DeliveryArea VARCHAR(255),
    AverageRating DECIMAL(3,2)
);

CREATE TABLE Category (
    CategoryID INT PRIMARY KEY,
    CategoryName VARCHAR(50) NOT NULL
);

CREATE TABLE Menu_Items (
    MenuItemID INT PRIMARY KEY,
    ItemName VARCHAR(100) NOT NULL,
    ItemImage VARCHAR(255),
    ItemDescription TEXT,
    Price DECIMAL(10,2),
    MonthlySales INT,
    RestaurantID INT,
    CategoryID INT,
    StockStatus VARCHAR(20),
    FOREIGN KEY (RestaurantID) REFERENCES Restaurants(RestaurantID),
    FOREIGN KEY (CategoryID) REFERENCES Category(CategoryID)
);

CREATE TABLE Provide (
    RestaurantID INT,
    MenuItemID INT,
    PRIMARY KEY (RestaurantID, MenuItemID),
    FOREIGN KEY (RestaurantID) REFERENCES Restaurants(RestaurantID),
    FOREIGN KEY (MenuItemID) REFERENCES Menu_Items(MenuItemID)
);

CREATE TABLE Riders (
    RiderID INT PRIMARY KEY,
    Name VARCHAR(50),
    PhoneNumber VARCHAR(20),
    IDNumber VARCHAR(50),
    AverageRating DECIMAL(3,2),
    LastKnownLocation VARCHAR(255),
    CurrentStatus VARCHAR(20)
);

CREATE TABLE `Order` (
    OrderID INT PRIMARY KEY,
    OrderTime DATETIME,
    TotalAmount DECIMAL(10,2),
    FinalAmount DECIMAL(10,2),
    OrderStatus VARCHAR(20),
    DeliveryFee DECIMAL(10,2),
    ExpectedDeliveryTime DATETIME,
    PaymentMethod VARCHAR(20),
    UserID INT,
    RestaurantID INT,
    RiderID INT,
    AddressID INT,
    FOREIGN KEY (UserID) REFERENCES Users(UserID),
    FOREIGN KEY (RestaurantID) REFERENCES Restaurants(RestaurantID),
    FOREIGN KEY (RiderID) REFERENCES Riders(RiderID),
    FOREIGN KEY (AddressID) REFERENCES Delivery_Addresses(AddressID)
);

CREATE TABLE Order_Items (
    OrderID INT,
    MenuItemID INT,
    Quantity INT,
    UnitPrice DECIMAL(10,2),
    Correspond VARCHAR(50),
    PRIMARY KEY (OrderID, MenuItemID),
    FOREIGN KEY (OrderID) REFERENCES `Order`(OrderID),
    FOREIGN KEY (MenuItemID) REFERENCES Menu_Items(MenuItemID)
);

CREATE TABLE Payment (
    PaymentID INT PRIMARY KEY,
    Amount DECIMAL(10,2),
    PaymentStatus VARCHAR(20),
    PaymentTime DATETIME,
    TransationID VARCHAR(100),
    OrderID INT,
    FOREIGN KEY (OrderID) REFERENCES `Order`(OrderID)
);

CREATE TABLE Review (
    ReviewID INT PRIMARY KEY,
    ReviewTime DATETIME,
    CommentText TEXT,
    RiderRating INT,
    RestaurantRating INT,
    OrderID INT,
    FOREIGN KEY (OrderID) REFERENCES `Order`(OrderID)
);

CREATE TABLE Audit_Logs (
    ReviewAuditID INT PRIMARY KEY,
    AuditID INT,
    UserID INT,
    RiderID INT,
    RestaurantID INT,
    ReviewID INT,
    FOREIGN KEY (UserID) REFERENCES Users(UserID),
    FOREIGN KEY (RiderID) REFERENCES Riders(RiderID),
    FOREIGN KEY (RestaurantID) REFERENCES Restaurants(RestaurantID),
    FOREIGN KEY (ReviewID) REFERENCES Review(ReviewID)
);

CREATE TABLE Admin (
    AdminID INT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    LastLogin DATETIME
);


CREATE TABLE Contain (
     MenuItemID INT,
     OrderID INT,
     PRIMARY KEY (MenuItemID, OrderID),
     FOREIGN KEY (MenuItemID) REFERENCES Menu_Items(MenuItemID),
     FOREIGN KEY (OrderID) REFERENCES `Order`(OrderID)
 );