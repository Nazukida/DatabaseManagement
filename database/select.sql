---顾客（Customer/Users）登录
SELECT UserID, Username, PasswordHash, Email, PhoneNumber
FROM users
WHERE Username = ?;
-- 用于校验用户名和后续取用户ID，密码校验前端用 hash 校验

--商家（Restaurant）登录
SELECT RestaurantID, RestaurantName, PasswordHash
FROM restaurants
WHERE RestaurantName = ?;
-- 或将登录名字段单独维护，如 RestaurantUserName

--骑手（Rider）登录
--建议骑手用手机号或ID登录
SELECT RiderID, Name, PasswordHash, PhoneNumber
FROM riders
WHERE PhoneNumber = ?;
-- 或 RiderID = ?

--管理员（Admin）登录
SELECT AdminID, Username, PasswordHash
FROM admin
WHERE Username = ?;

--1. 顾客端
--查询我的所有收货地址
SELECT * FROM delivery_addresses WHERE UserID = ?;

--查询我的所有订单
SELECT * FROM orders WHERE UserID = ? ORDER BY OrderTime DESC;
--查询订单详情（含菜品）
SELECT o.*, oi.MenuItemID, oi.Quantity, oi.UnitPrice, mi.ItemName
FROM orders o
JOIN order_items oi ON o.OrderID = oi.OrderID
JOIN menu_item mi ON oi.MenuItemID = mi.MenuItemID
WHERE o.OrderID = ?;
--查询全部餐厅（商家）列表
SELECT * FROM restaurants WHERE BusinessStatus = 'Open';
--浏览餐厅菜单
SELECT m.*, c.CategoryName
FROM menu_item m
JOIN category c ON m.CategoryID = c.CategoryID
WHERE m.RestaurantID = ? AND m.StockStatus = 'In Stock';
--2. 商家端
--查询所有本店订单
SELECT * FROM orders WHERE RestaurantID = ? ORDER BY OrderTime DESC;
--查询指定订单详情
SELECT o.*, u.Username, a.FullAddress
FROM orders o
LEFT JOIN users u ON o.UserID = u.UserID
LEFT JOIN delivery_addresses a ON o.AddressID = a.AddressID
WHERE o.OrderID = ?;
--查询本店所有商品菜单
SELECT * FROM menu_item WHERE RestaurantID = ?;
--3. 骑手端
--查询分配给我的所有订单
SELECT * FROM orders WHERE RiderID = ? AND OrderStatus IN ('Delivering','Pending');
--查询单个订单送达地
SELECT o.OrderID, a.FullAddress, a.ContactName, a.ContactPhone
FROM orders o
JOIN delivery_addresses a ON o.AddressID = a.AddressID
WHERE o.OrderID = ? AND o.RiderID = ?;
--4. 管理员端
--查询所有用户
SELECT * FROM users ORDER BY RegistrationDate DESC LIMIT 100;
--查询所有商家
SELECT * FROM restaurants ORDER BY RestaurantID DESC LIMIT 100;
--查询所有订单
SELECT * FROM orders ORDER BY OrderTime DESC LIMIT 100;
--查询后台所有审核日志
SELECT al.*, u.Username, r.Name AS RiderName, re.RestaurantName, rv.CommentText
FROM audit_logs al
LEFT JOIN users u ON al.UserID = u.UserID
LEFT JOIN riders r ON al.RiderID = r.RiderID
LEFT JOIN restaurants re ON al.RestaurantID = re.RestaurantID
LEFT JOIN review rv ON al.ReviewID = rv.ReviewID
ORDER BY al.ReviewAuditID DESC LIMIT 100;
--三、附加查询（可拓展用）
--查询某用户所有支付记录
SELECT p.*, o.OrderTime, r.RestaurantName
FROM payment p
JOIN orders o ON p.OrderID = o.OrderID
JOIN restaurants r ON o.RestaurantID = r.RestaurantID
WHERE o.UserID = ?
ORDER BY p.PaymentTime DESC;
--查询某菜品的月销售量
SELECT MonthlySales FROM menu_item WHERE MenuItemID = ?;
--查询商家菜品评论
SELECT rv.*, u.Username
FROM review rv
JOIN orders o ON rv.OrderID = o.OrderID
JOIN users u ON o.UserID = u.UserID
WHERE o.RestaurantID = ?
ORDER BY rv.ReviewTime DESC;