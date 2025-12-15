<?php
// db.php - Database Connection File

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'dbms');
define('DB_USER', 'root');
define('DB_PASS', '');

// Charset Setting
define('DB_CHARSET', 'utf8mb4');

/**
 * Database Connection
 */
class Database {
    private static $connection = null;
    
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                self::$connection = new PDO($dsn, DB_USER, DB_PASS);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch (PDOException $e) {
                error_log("Database Connection Failed: " . $e->getMessage());
                die("Database connection failed.");
            }
        }
        return self::$connection;
    }
    
    public static function closeConnection() {
        self::$connection = null;
    }
}

class UserModel {
    
    public static function login($username, $password) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT UserID, Username, PasswordHash, Email, PhoneNumber, 
                       FullName, RegistrationDate, AccountStatus
                FROM users 
                WHERE Username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['PasswordHash'])) {
                unset($user['PasswordHash']);
                return $user;
            }
            return false;
        } catch (PDOException $e) {
            error_log("User Login Error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function getUserAddresses($userId) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT * FROM delivery_addresses 
                WHERE UserID = ? 
                ORDER BY IsDefault DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get User Address Error: " . $e->getMessage());
            return [];
        }
    }
    
    public static function getUserOrders($userId, $limit = 20) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT o.*, r.RestaurantName, ri.Name as RiderName
                FROM `order` o
                LEFT JOIN restaurants r ON o.RestaurantID = r.RestaurantID
                LEFT JOIN riders ri ON o.RiderID = ri.RiderID
                WHERE o.UserID = ? 
                ORDER BY o.OrderTime DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get User Orders Error: " . $e->getMessage());
            return [];
        }
    }
}

class RestaurantModel {
    
    public static function login($restaurantName, $password) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT RestaurantID, RestaurantName, PasswordHash, 
                       ContactPhone, Address, BusinessStatus
                FROM restaurants 
                WHERE RestaurantName = ? OR ContactPhone = ?
            ");
            $stmt->execute([$restaurantName, $restaurantName]);
            $restaurant = $stmt->fetch();
            
            if ($restaurant && password_verify($password, $restaurant['PasswordHash'])) {
                unset($restaurant['PasswordHash']);
                return $restaurant;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Restaurant Login Error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function getRestaurantOrders($restaurantId) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT o.*, u.Username, a.FullAddress, 
                       ri.Name as RiderName
                FROM `order` o
                LEFT JOIN users u ON o.UserID = u.UserID
                LEFT JOIN delivery_addresses a ON o.AddressID = a.AddressID
                LEFT JOIN riders ri ON o.RiderID = ri.RiderID
                WHERE o.RestaurantID = ? 
                ORDER BY o.OrderTime DESC
            ");
            $stmt->execute([$restaurantId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get Restaurant Orders Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get Restaurant Menu
     * @param int $restaurantId Restaurant ID
     * @return array
     */
    public static function getRestaurantMenu($restaurantId) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT m.*, c.CategoryName
                FROM menu_items m
                JOIN category c ON m.CategoryID = c.CategoryID
                WHERE m.RestaurantID = ? 
                AND m.StockStatus = 'In Stock'
                ORDER BY c.CategoryOrder, m.ItemName
            ");
            $stmt->execute([$restaurantId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get Restaurant Menu Error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Rider Model Class
 */
class RiderModel {
    
    /**
     * Rider Login Verification
     * @param string $phoneNumber Phone Number
     * @param string $password Password
     * @return array|false Rider info or false
     */
    public static function login($phoneNumber, $password) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT RiderID, Name, PasswordHash, PhoneNumber, 
                       Email, AvailabilityStatus, Rating
                FROM riders 
                WHERE PhoneNumber = ?
            ");
            $stmt->execute([$phoneNumber]);
            $rider = $stmt->fetch();
            
            if ($rider && password_verify($password, $rider['PasswordHash'])) {
                unset($rider['PasswordHash']);
                return $rider;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Rider Login Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get Assigned Orders for Rider
     * @param int $riderId Rider ID
     * @return array
     */
    public static function getAssignedOrders($riderId) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT o.*, r.RestaurantName, u.Username, a.FullAddress
                FROM `order` o
                JOIN restaurants r ON o.RestaurantID = r.RestaurantID
                JOIN users u ON o.UserID = u.UserID
                JOIN delivery_addresses a ON o.AddressID = a.AddressID
                WHERE o.RiderID = ? 
                AND o.OrderStatus IN ('Delivering','Pending')
                ORDER BY o.PreparationTime ASC
            ");
            $stmt->execute([$riderId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get Rider Orders Error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Admin Model Class
 */
class AdminModel {
    
    /**
     * Admin Login Verification
     * @param string $username Username
     * @param string $password Password
     * @return array|false Admin info or false
     */
    public static function login($username, $password) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT AdminID, Username, PasswordHash, LastLogin
                FROM admin 
                WHERE Username = ?
            ");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['PasswordHash'])) {
                unset($admin['PasswordHash']);
                return $admin;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Admin Login Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get All Users
     * @param int $limit Limit count
     * @return array
     */
    public static function getAllUsers($limit = 100) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT * FROM users 
                ORDER BY RegistrationDate DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get All Users Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get All Restaurants
     * @param int $limit Limit count
     * @return array
     */
    public static function getAllRestaurants($limit = 100) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT * FROM restaurants 
                ORDER BY RestaurantID DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get All Restaurants Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get All Orders
     * @param int $limit Limit count
     * @return array
     */
    public static function getAllOrders($limit = 100) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT o.*, u.Username, r.RestaurantName, ri.Name as RiderName,
                       a.FullAddress, a.ContactPhone
                FROM `order` o
                LEFT JOIN users u ON o.UserID = u.UserID
                LEFT JOIN restaurants r ON o.RestaurantID = r.RestaurantID
                LEFT JOIN riders ri ON o.RiderID = ri.RiderID
                LEFT JOIN delivery_addresses a ON o.AddressID = a.AddressID
                ORDER BY o.OrderTime DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get All Orders Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search Orders - Fixed: Support integer ID search
     * @param mixed $orderId Order ID (integer or string)
     * @param mixed $merchantId Merchant ID (integer or string)
     * @return array
     */
    public static function searchOrders($orderId = null, $merchantId = null) {
        $db = Database::getConnection();
        
        try {
            $sql = "
                SELECT o.*, u.Username, r.RestaurantName, ri.Name as RiderName,
                       a.FullAddress, a.ContactPhone, a.ContactName,
                       rv.CommentText as CustomerComment
                FROM `order` o
                LEFT JOIN users u ON o.UserID = u.UserID
                LEFT JOIN restaurants r ON o.RestaurantID = r.RestaurantID
                LEFT JOIN riders ri ON o.RiderID = ri.RiderID
                LEFT JOIN delivery_addresses a ON o.AddressID = a.AddressID
                LEFT JOIN review rv ON o.OrderID = rv.OrderID
                WHERE 1=1
            ";
            
            $params = [];
            
            // Handle Order ID search
            if (!empty($orderId)) {
                // If numeric, search by ID directly
                if (is_numeric($orderId)) {
                    $sql .= " AND o.OrderID = ?";
                    $params[] = (int)$orderId;
                } else {
                    // If string, use LIKE fuzzy search
                    $sql .= " AND CAST(o.OrderID AS CHAR) LIKE ?";
                    $params[] = "%" . $orderId . "%";
                }
            }
            
            // Handle Merchant ID search
            if (!empty($merchantId)) {
                if (is_numeric($merchantId)) {
                    $sql .= " AND o.RestaurantID = ?";
                    $params[] = (int)$merchantId;
                } else {
                    $sql .= " AND (r.RestaurantName LIKE ? OR CAST(r.RestaurantID AS CHAR) LIKE ?)";
                    $params[] = "%" . $merchantId . "%";
                    $params[] = "%" . $merchantId . "%";
                }
            }
            
            $sql .= " ORDER BY o.OrderTime DESC LIMIT 50";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Search Orders Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get Order Details (including items)
     * @param int $orderId Order ID
     * @return array
     */
    public static function getOrderDetails($orderId) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT o.*, u.Username, u.PhoneNumber as UserPhone,
                       r.RestaurantName, r.ContactPhone as RestaurantPhone,
                       ri.Name as RiderName, ri.PhoneNumber as RiderPhone,
                       a.FullAddress, a.ContactName, a.ContactPhone as DeliveryPhone
                FROM `order` o
                LEFT JOIN users u ON o.UserID = u.UserID
                LEFT JOIN restaurants r ON o.RestaurantID = r.RestaurantID
                LEFT JOIN riders ri ON o.RiderID = ri.RiderID
                LEFT JOIN delivery_addresses a ON o.AddressID = a.AddressID
                WHERE o.OrderID = ?
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if ($order) {
                // Get order items
                $stmt2 = $db->prepare("
                    SELECT oi.*, mi.ItemName, mi.Description
                    FROM order_items oi
                    LEFT JOIN menu_items mi ON oi.MenuItemID = mi.MenuItemID
                    WHERE oi.OrderID = ?
                ");
                $stmt2->execute([$orderId]);
                $order['items'] = $stmt2->fetchAll();
            }
            
            return $order;
        } catch (PDOException $e) {
            error_log("Get Order Details Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get Merchant Operation Records - Based on select.sql and Admin.html requirements
     * @param mixed $merchantId Merchant ID (integer or string)
     * @return array
     */
    public static function getMerchantOperations($merchantId = null) {
        $db = Database::getConnection();
        
        try {
            $sql = "
                SELECT 
                    m.RestaurantID as merchant_id,
                    m.ItemName as product_name,
                    'Stock Adjustment' as action_type,
                    '0' as quantity_change,
                    NOW() as action_time,
                    m.StockStatus as notes
                FROM menu_items m
                WHERE 1=1
            ";
            
            $params = [];
            
            if (!empty($merchantId)) {
                if (is_numeric($merchantId)) {
                    $sql .= " AND m.RestaurantID = ?";
                    $params[] = (int)$merchantId;
                } else {
                    $sql .= " AND CAST(m.RestaurantID AS CHAR) LIKE ?";
                    $params[] = "%" . $merchantId . "%";
                }
            }
            
            $sql .= " ORDER BY m.MenuItemID DESC LIMIT 50";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get Merchant Operations Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get Audit Logs - Based on select.sql requirements
     * @param int $limit Limit count
     * @return array
     */
    public static function getAuditLogs($limit = 100) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT al.*, u.Username, 
                       r.Name AS RiderName, 
                       re.RestaurantName,
                       rv.CommentText
                FROM audit_logs al
                LEFT JOIN users u ON al.UserID = u.UserID
                LEFT JOIN riders r ON al.RiderID = r.RiderID
                LEFT JOIN restaurants re ON al.RestaurantID = re.RestaurantID
                LEFT JOIN review rv ON al.ReviewID = rv.ReviewID
                ORDER BY al.ReviewAuditID DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get Audit Logs Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update Order Status
     * @param int $orderId Order ID
     * @param string $status New Status
     * @param string $comment Admin Comment
     * @return bool
     */
    public static function updateOrderStatus($orderId, $status, $comment = '') {
        $db = Database::getConnection();
        
        try {
            DatabaseUtils::beginTransaction();
            
            // Update order status
            $stmt = $db->prepare("
                UPDATE `order` 
                SET OrderStatus = ?, 
                    UpdatedAt = NOW(),
                    AdminComment = ?
                WHERE OrderID = ?
            ");
            $stmt->execute([$status, $comment, $orderId]);
            
            // Log to audit logs
            /* 
            // Audit logs table structure mismatch - disabling for now
            $adminId = $_SESSION['admin_id'] ?? 0;
            $stmt2 = $db->prepare("
                INSERT INTO audit_logs 
                (UserID, RiderID, RestaurantID, ActionType, ActionDescription, AdminID, CreatedAt)
                SELECT 
                    UserID, 
                    RiderID, 
                    RestaurantID, 
                    'ORDER_UPDATE', 
                    CONCAT('Order status updated to: ', ?),
                    ?,
                    NOW()
                FROM `order` 
                WHERE OrderID = ?
            ");
            $stmt2->execute([$status, $adminId, $orderId]);
            */
            
            DatabaseUtils::commit();
            return true;
        } catch (PDOException $e) {
            DatabaseUtils::rollback();
            error_log("Update Order Status Error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * General Utility Functions
 */
class DatabaseUtils {
    
    /**
     * Execute General Query
     * @param string $sql SQL Query
     * @param array $params Parameters
     * @return array
     */
    public static function query($sql, $params = []) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Execute Update Operation
     * @param string $sql SQL Query
     * @param array $params Parameters
     * @return int|false Affected rows or false
     */
    public static function execute($sql, $params = []) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Execution Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Begin Transaction
     * @return bool
     */
    public static function beginTransaction() {
        $db = Database::getConnection();
        return $db->beginTransaction();
    }
    
    /**
     * Commit Transaction
     * @return bool
     */
    public static function commit() {
        $db = Database::getConnection();
        return $db->commit();
    }
    
    /**
     * Rollback Transaction
     * @return bool
     */
    public static function rollback() {
        $db = Database::getConnection();
        return $db->rollback();
    }
}

/**
 * Search Utility Class - Handles Admin Search
 */
class AdminSearch {
    
    /**
     * Comprehensive Search - Handles search functionality in Admin.html

     * @param string $orderId Order ID
     * @param string $merchantId Merchant ID
     * @return array Contains orders and merchant operation results
     */
    public static function search($orderId = '', $merchantId = '') {
        $result = [
            'orders' => [],
            'merchant_operations' => []
        ];
        
        $ordersById = [];
        $ordersByMerchant = [];

        // 1. Search by Order ID
        if (!empty($orderId)) {
            $ordersById = AdminModel::searchOrders($orderId, null);
        }
        
        // 2. Search by Merchant ID (for both Orders and Operations)
        if (!empty($merchantId)) {
            // Get Merchant Operations
            $result['merchant_operations'] = AdminModel::getMerchantOperations($merchantId);
            
            // Get Orders by Merchant
            $ordersByMerchant = AdminModel::searchOrders(null, $merchantId);
        }

        // Merge orders results
        if (!empty($ordersById) && !empty($ordersByMerchant)) {
             // Merge both results
             $result['orders'] = array_merge($ordersById, $ordersByMerchant);
             
             // Optional: Remove duplicates based on OrderID
             $tempOrders = [];
             foreach ($result['orders'] as $order) {
                 $tempOrders[$order['OrderID']] = $order;
             }
             $result['orders'] = array_values($tempOrders);
             
        } elseif (!empty($ordersById)) {
            $result['orders'] = $ordersById;
        } elseif (!empty($ordersByMerchant)) {
            $result['orders'] = $ordersByMerchant;
        }

        // If no criteria, return recent orders and operations
        if (empty($orderId) && empty($merchantId)) {
            $result['orders'] = AdminModel::searchOrders();
            $result['merchant_operations'] = AdminModel::getMerchantOperations();
        }
        
        return $result;
    }
    
    /**
     * Validate Search Input
     * @param string $orderId Order ID
     * @param string $merchantId Merchant ID
     * @return array [isValid, errorMessage]
     */
    public static function validateSearch($orderId, $merchantId) {
        // Allow empty search to return all results
        if (empty($orderId) && empty($merchantId)) {
            return [true, ''];
        }
        
        // Validate Order ID (can be numeric or string)
        if (!empty($orderId)) {
            // Allow numeric or string, but if numeric, ensure it's a positive integer
            if (is_numeric($orderId)) {
                $orderId = (int)$orderId;
                if ($orderId <= 0) {
                    return [false, 'Order ID must be a positive integer'];
                }
            }
        }
        
        // Validate Merchant ID
        if (!empty($merchantId)) {
            if (is_numeric($merchantId)) {
                $merchantId = (int)$merchantId;
                if ($merchantId <= 0) {
                    return [false, 'Merchant ID must be a positive integer'];
                }
            }
        }
        
        return [true, ''];
    }
}

/**
 * API Response Class
 */
class ApiResponse {
    
    /**
     * Send JSON Response
     * @param mixed $data Response Data
     * @param int $statusCode HTTP Status Code
     * @param string $message Message
     * @param bool $success Success Status
     */
    public static function json($data = null, $statusCode = 200, $message = '', $success = true) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send Error Response
     * @param string $message Error Message
     * @param int $statusCode HTTP Status Code
     * @param mixed $data Extra Data
     */
    public static function error($message = 'Operation Failed', $statusCode = 400, $data = null) {
        self::json($data, $statusCode, $message, false);
    }
    
    /**
     * Send Success Response
     * @param mixed $data Response Data
     * @param string $message Success Message
     * @param int $statusCode HTTP Status Code
     */
    public static function success($data = null, $message = 'Operation Successful', $statusCode = 200) {
        self::json($data, $statusCode, $message, true);
    }
}

// Security Functions
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^1[3-9]\d{9}$/', $phone);
}

// Initialize Session
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user role
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// Check user role
function checkRole($requiredRole) {
    if (!isLoggedIn() || getCurrentUserRole() !== $requiredRole) {
        ApiResponse::error('Insufficient Permissions', 403);
    }
}



?>