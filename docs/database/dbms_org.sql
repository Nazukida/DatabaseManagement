-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-12-14 16:54:50
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `dbms`
--

-- --------------------------------------------------------

--
-- 表的结构 `admin`
--

CREATE TABLE `admin` (
  `AdminID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `LastLogin` datetime DEFAULT NULL,
  `profile_picture` mediumblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `audit_logs`
--

CREATE TABLE `audit_logs` (
  `ReviewAuditID` int(11) NOT NULL,
  `AuditID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `RiderID` int(11) DEFAULT NULL,
  `RestaurantID` int(11) DEFAULT NULL,
  `ReviewID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `category`
--

CREATE TABLE `category` (
  `CategoryID` int(11) NOT NULL,
  `CategoryName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `contain`
--

CREATE TABLE `contain` (
  `MenuItemID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `delivery_addresses`
--

CREATE TABLE `delivery_addresses` (
  `AddressID` int(11) NOT NULL,
  `Label` varchar(50) DEFAULT NULL,
  `IsDefault` tinyint(1) DEFAULT NULL,
  `ContactName` varchar(50) DEFAULT NULL,
  `ContactPhone` varchar(20) DEFAULT NULL,
  `FullAddress` varchar(255) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `menu_items`
--

CREATE TABLE `menu_items` (
  `MenuItemID` int(11) NOT NULL,
  `ItemName` varchar(100) NOT NULL,
  `ItemImage` varchar(255) DEFAULT NULL,
  `ItemDescription` text DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `MonthlySales` int(11) DEFAULT NULL,
  `RestaurantID` int(11) DEFAULT NULL,
  `CategoryID` int(11) DEFAULT NULL,
  `StockStatus` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `order`
--

CREATE TABLE `order` (
  `OrderID` int(11) NOT NULL,
  `OrderTime` datetime DEFAULT NULL,
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `FinalAmount` decimal(10,2) DEFAULT NULL,
  `OrderStatus` varchar(20) DEFAULT NULL,
  `DeliveryFee` decimal(10,2) DEFAULT NULL,
  `ExpectedDeliveryTime` datetime DEFAULT NULL,
  `PaymentMethod` varchar(20) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `RestaurantID` int(11) DEFAULT NULL,
  `RiderID` int(11) DEFAULT NULL,
  `AddressID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 触发器 `order`
--
DELIMITER $$
CREATE TRIGGER `AfterOrderComplete` AFTER UPDATE ON `order` FOR EACH ROW BEGIN
    -- Only execute when status changes to 'Completed'
    -- 仅在订单状态变为'Completed'时触发
    IF NEW.OrderStatus = 'Completed' AND OLD.OrderStatus != 'Completed' THEN
        
        -- Update rider's total earnings
        -- 累加该订单运费到骑手总收入
        UPDATE riders
        SET TotalEarnings = TotalEarnings + NEW.DeliveryFee
        WHERE RiderID = NEW.RiderID;
        
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_rider_orders` AFTER UPDATE ON `order` FOR EACH ROW BEGIN
    IF NEW.OrderStatus = 'Completed' AND OLD.OrderStatus != 'Completed' THEN
        UPDATE `riders` 
        SET TotalOrders = TotalOrders + 1 
        WHERE RiderID = NEW.RiderID;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- 表的结构 `order_items`
--

CREATE TABLE `order_items` (
  `OrderID` int(11) NOT NULL,
  `MenuItemID` int(11) NOT NULL,
  `Quantity` int(11) DEFAULT NULL,
  `UnitPrice` decimal(10,2) DEFAULT NULL,
  `Correspond` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `payment`
--

CREATE TABLE `payment` (
  `PaymentID` int(11) NOT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `PaymentStatus` varchar(20) DEFAULT NULL,
  `PaymentTime` datetime DEFAULT NULL,
  `TransationID` varchar(100) DEFAULT NULL,
  `OrderID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `provide`
--

CREATE TABLE `provide` (
  `RestaurantID` int(11) NOT NULL,
  `MenuItemID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `restaurants`
--

CREATE TABLE `restaurants` (
  `RestaurantID` int(11) NOT NULL,
  `RestaurantName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `LogoImage` varchar(255) DEFAULT NULL,
  `BusinessStatus` varchar(20) DEFAULT NULL,
  `DeliveryFee` decimal(10,2) DEFAULT NULL,
  `MinimumOrderAmount` decimal(10,2) DEFAULT NULL,
  `DeliveryArea` varchar(255) DEFAULT NULL,
  `AverageRating` decimal(3,2) DEFAULT NULL,
  `PasswordHash` varchar(255) NOT NULL DEFAULT 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `review`
--

CREATE TABLE `review` (
  `ReviewID` int(11) NOT NULL,
  `ReviewTime` datetime DEFAULT NULL,
  `CommentText` text DEFAULT NULL,
  `RiderRating` int(11) DEFAULT NULL,
  `RestaurantRating` int(11) DEFAULT NULL,
  `OrderID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `riders`
--

CREATE TABLE `riders` (
  `RiderID` int(11) NOT NULL,
  `Name` varchar(50) DEFAULT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `IDNumber` varchar(50) DEFAULT NULL,
  `AverageRating` decimal(3,2) DEFAULT NULL,
  `LastKnownLocation` varchar(255) DEFAULT NULL,
  `CurrentStatus` varchar(20) DEFAULT NULL,
  `PasswordHash` varchar(255) NOT NULL DEFAULT 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3',
  `TotalOrders` int(11) DEFAULT 0,
  `TotalEarnings` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `RegistrationDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`AdminID`);

--
-- 表的索引 `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`ReviewAuditID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `RiderID` (`RiderID`),
  ADD KEY `RestaurantID` (`RestaurantID`),
  ADD KEY `ReviewID` (`ReviewID`);

--
-- 表的索引 `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`CategoryID`);

--
-- 表的索引 `contain`
--
ALTER TABLE `contain`
  ADD PRIMARY KEY (`MenuItemID`,`OrderID`),
  ADD KEY `OrderID` (`OrderID`);

--
-- 表的索引 `delivery_addresses`
--
ALTER TABLE `delivery_addresses`
  ADD PRIMARY KEY (`AddressID`),
  ADD KEY `UserID` (`UserID`);

--
-- 表的索引 `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`MenuItemID`),
  ADD KEY `RestaurantID` (`RestaurantID`),
  ADD KEY `CategoryID` (`CategoryID`);

--
-- 表的索引 `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `RestaurantID` (`RestaurantID`),
  ADD KEY `RiderID` (`RiderID`),
  ADD KEY `AddressID` (`AddressID`);

--
-- 表的索引 `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`OrderID`,`MenuItemID`),
  ADD KEY `MenuItemID` (`MenuItemID`);

--
-- 表的索引 `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `OrderID` (`OrderID`);

--
-- 表的索引 `provide`
--
ALTER TABLE `provide`
  ADD PRIMARY KEY (`RestaurantID`,`MenuItemID`),
  ADD KEY `MenuItemID` (`MenuItemID`);

--
-- 表的索引 `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`RestaurantID`);

--
-- 表的索引 `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`ReviewID`),
  ADD KEY `OrderID` (`OrderID`);

--
-- 表的索引 `riders`
--
ALTER TABLE `riders`
  ADD PRIMARY KEY (`RiderID`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`);

--
-- 限制导出的表
--

--
-- 限制表 `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `audit_logs_ibfk_2` FOREIGN KEY (`RiderID`) REFERENCES `riders` (`RiderID`),
  ADD CONSTRAINT `audit_logs_ibfk_3` FOREIGN KEY (`RestaurantID`) REFERENCES `restaurants` (`RestaurantID`),
  ADD CONSTRAINT `audit_logs_ibfk_4` FOREIGN KEY (`ReviewID`) REFERENCES `review` (`ReviewID`);

--
-- 限制表 `contain`
--
ALTER TABLE `contain`
  ADD CONSTRAINT `contain_ibfk_1` FOREIGN KEY (`MenuItemID`) REFERENCES `menu_items` (`MenuItemID`),
  ADD CONSTRAINT `contain_ibfk_2` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`);

--
-- 限制表 `delivery_addresses`
--
ALTER TABLE `delivery_addresses`
  ADD CONSTRAINT `delivery_addresses_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- 限制表 `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`RestaurantID`) REFERENCES `restaurants` (`RestaurantID`),
  ADD CONSTRAINT `menu_items_ibfk_2` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`);

--
-- 限制表 `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `order_ibfk_2` FOREIGN KEY (`RestaurantID`) REFERENCES `restaurants` (`RestaurantID`),
  ADD CONSTRAINT `order_ibfk_3` FOREIGN KEY (`RiderID`) REFERENCES `riders` (`RiderID`),
  ADD CONSTRAINT `order_ibfk_4` FOREIGN KEY (`AddressID`) REFERENCES `delivery_addresses` (`AddressID`);

--
-- 限制表 `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`MenuItemID`) REFERENCES `menu_items` (`MenuItemID`);

--
-- 限制表 `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`);

--
-- 限制表 `provide`
--
ALTER TABLE `provide`
  ADD CONSTRAINT `provide_ibfk_1` FOREIGN KEY (`RestaurantID`) REFERENCES `restaurants` (`RestaurantID`),
  ADD CONSTRAINT `provide_ibfk_2` FOREIGN KEY (`MenuItemID`) REFERENCES `menu_items` (`MenuItemID`);

--
-- 限制表 `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
