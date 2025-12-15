<?php
// db.php - 优食邻里数据库连接文件

// ... [保持前面的 Database、UserModel、RestaurantModel、RiderModel 类不变]

/**
 * 管理员相关操作类 - 修复版
 */
class AdminModel {
    
    /**
     * 管理员登录验证
     * @param string $username 用户名
     * @param string $password 密码
     * @return array|false 管理员信息或false
     */
    public static function login($username, $password) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT AdminID, Username, PasswordHash, 
                       Email, PermissionLevel
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
            error_log("管理员登录错误: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取所有用户
     * @param int $limit 限制数量
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
            error_log("获取所有用户错误: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取所有商家
     * @param int $limit 限制数量
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
            error_log("获取所有商家错误: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取所有订单
     * @param int $limit 限制数量
     * @return array
     */
    public static function getAllOrders($limit = 100) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT o.*, u.Username, r.RestaurantName, ri.Name as RiderName,
                       a.FullAddress, a.ContactPhone
                FROM orders o
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
            error_log("获取所有订单错误: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 搜索订单 - 修复版：支持整数ID搜索
     * @param mixed $orderId 订单ID（可以是整数或字符串）
     * @param mixed $merchantId 商家ID（可以是整数或字符串）
     * @return array
     */
    public static function searchOrders($orderId = null, $merchantId = null) {
        $db = Database::getConnection();
        
        try {
            $sql = "
                SELECT o.*, u.Username, r.RestaurantName, ri.Name as RiderName,
                       a.FullAddress, a.ContactPhone, a.ContactName
                FROM orders o
                LEFT JOIN users u ON o.UserID = u.UserID
                LEFT JOIN restaurants r ON o.RestaurantID = r.RestaurantID
                LEFT JOIN riders ri ON o.RiderID = ri.RiderID
                LEFT JOIN delivery_addresses a ON o.AddressID = a.AddressID
                WHERE 1=1
            ";
            
            $params = [];
            
            // 处理订单ID搜索
            if (!empty($orderId)) {
                // 如果是数字，直接按数字搜索
                if (is_numeric($orderId)) {
                    $sql .= " AND o.OrderID = ?";
                    $params[] = (int)$orderId;
                } else {
                    // 如果是字符串，使用LIKE模糊搜索
                    $sql .= " AND CAST(o.OrderID AS CHAR) LIKE ?";
                    $params[] = "%" . $orderId . "%";
                }
            }
            
            // 处理商家ID搜索
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
            error_log("搜索订单错误: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取订单详情（包含订单项）
     * @param int $orderId 订单ID
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
                FROM orders o
                LEFT JOIN users u ON o.UserID = u.UserID
                LEFT JOIN restaurants r ON o.RestaurantID = r.RestaurantID
                LEFT JOIN riders ri ON o.RiderID = ri.RiderID
                LEFT JOIN delivery_addresses a ON o.AddressID = a.AddressID
                WHERE o.OrderID = ?
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if ($order) {
                // 获取订单项
                $stmt2 = $db->prepare("
                    SELECT oi.*, mi.ItemName, mi.Description
                    FROM order_items oi
                    LEFT JOIN menu_item mi ON oi.MenuItemID = mi.MenuItemID
                    WHERE oi.OrderID = ?
                ");
                $stmt2->execute([$orderId]);
                $order['items'] = $stmt2->fetchAll();
            }
            
            return $order;
        } catch (PDOException $e) {
            error_log("获取订单详情错误: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取商家产品操作记录 - 根据你的select.sql和Admin.html需求
     * @param mixed $merchantId 商家ID（整数或字符串）
     * @return array
     */
    public static function getMerchantOperations($merchantId = null) {
        $db = Database::getConnection();
        
        try {
            $sql = "
                SELECT 
                    m.RestaurantID as merchant_id,
                    m.ItemName as product_name,
                    '库存调整' as action_type,
                    m.StockQuantity as quantity_change,
                    m.LastUpdated as action_time,
                    m.StockStatus as notes
                FROM menu_item m
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
            
            $sql .= " ORDER BY m.LastUpdated DESC LIMIT 50";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("获取商家操作记录错误: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取审计日志 - 根据你的select.sql需求
     * @param int $limit 限制数量
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
            error_log("获取审计日志错误: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 更新订单状态
     * @param int $orderId 订单ID
     * @param string $status 新状态
     * @param string $comment 管理员备注
     * @return bool
     */
    public static function updateOrderStatus($orderId, $status, $comment = '') {
        $db = Database::getConnection();
        
        try {
            DatabaseUtils::beginTransaction();
            
            // 更新订单状态
            $stmt = $db->prepare("
                UPDATE orders 
                SET OrderStatus = ?, 
                    UpdatedAt = NOW(),
                    AdminComment = ?
                WHERE OrderID = ?
            ");
            $stmt->execute([$status, $comment, $orderId]);
            
            // 记录到审计日志
            $adminId = $_SESSION['admin_id'] ?? 0;
            $stmt2 = $db->prepare("
                INSERT INTO audit_logs 
                (UserID, RiderID, RestaurantID, ActionType, ActionDescription, AdminID, CreatedAt)
                SELECT 
                    UserID, 
                    RiderID, 
                    RestaurantID, 
                    'ORDER_UPDATE', 
                    CONCAT('订单状态更新为: ', ?),
                    ?,
                    NOW()
                FROM orders 
                WHERE OrderID = ?
            ");
            $stmt2->execute([$status, $adminId, $orderId]);
            
            DatabaseUtils::commit();
            return true;
        } catch (PDOException $e) {
            DatabaseUtils::rollback();
            error_log("更新订单状态错误: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * 通用工具函数
 */
class DatabaseUtils {
    
    /**
     * 执行通用查询
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return array
     */
    public static function query($sql, $params = []) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("查询错误: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 执行更新操作
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return int|false 影响的行数或false
     */
    public static function execute($sql, $params = []) {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("执行错误: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 开始事务
     * @return bool
     */
    public static function beginTransaction() {
        $db = Database::getConnection();
        return $db->beginTransaction();
    }
    
    /**
     * 提交事务
     * @return bool
     */
    public static function commit() {
        $db = Database::getConnection();
        return $db->commit();
    }
    
    /**
     * 回滚事务
     * @return bool
     */
    public static function rollback() {
        $db = Database::getConnection();
        return $db->rollback();
    }
}

/**
 * 搜索工具类 - 专门处理管理员搜索
 */
class AdminSearch {
    
    /**
     * 综合搜索 - 处理Admin.html中的搜索功能
     * @param string $orderId 订单ID
     * @param string $merchantId 商家ID
     * @return array 包含订单和商家操作结果
     */
    public static function search($orderId = '', $merchantId = '') {
        $result = [
            'orders' => [],
            'merchant_operations' => []
        ];
        
        // 搜索订单
        if (!empty($orderId)) {
            $result['orders'] = AdminModel::searchOrders($orderId, null);
        }
        
        // 搜索商家操作
        if (!empty($merchantId)) {
            $result['merchant_operations'] = AdminModel::getMerchantOperations($merchantId);
        }
        
        // 如果两个条件都有，但没找到订单，也尝试用merchantId找订单
        if (!empty($merchantId) && empty($orderId)) {
            $result['orders'] = AdminModel::searchOrders(null, $merchantId);
        }
        
        return $result;
    }
    
    /**
     * 验证搜索输入
     * @param string $orderId 订单ID
     * @param string $merchantId 商家ID
     * @return array [是否有效, 错误消息]
     */
    public static function validateSearch($orderId, $merchantId) {
        // 至少需要一个搜索条件
        if (empty($orderId) && empty($merchantId)) {
            return [false, '请输入至少一个搜索条件'];
        }
        
        // 验证订单ID（可以是数字或字符串）
        if (!empty($orderId)) {
            // 允许数字或字符串，但如果是数字，确保是正整数
            if (is_numeric($orderId)) {
                $orderId = (int)$orderId;
                if ($orderId <= 0) {
                    return [false, '订单ID必须是正整数'];
                }
            }
        }
        
        // 验证商家ID
        if (!empty($merchantId)) {
            if (is_numeric($merchantId)) {
                $merchantId = (int)$merchantId;
                if ($merchantId <= 0) {
                    return [false, '商家ID必须是正整数'];
                }
            }
        }
        
        return [true, ''];
    }
}

/**
 * API响应函数
 */
class ApiResponse {
    
    /**
     * 发送JSON响应
     * @param mixed $data 响应数据
     * @param int $statusCode HTTP状态码
     * @param string $message 消息
     * @param bool $success 是否成功
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
     * 发送错误响应
     * @param string $message 错误消息
     * @param int $statusCode HTTP状态码
     * @param mixed $data 额外数据
     */
    public static function error($message = '操作失败', $statusCode = 400, $data = null) {
        self::json($data, $statusCode, $message, false);
    }
    
    /**
     * 发送成功响应
     * @param mixed $data 响应数据
     * @param string $message 成功消息
     * @param int $statusCode HTTP状态码
     */
    public static function success($data = null, $message = '操作成功', $statusCode = 200) {
        self::json($data, $statusCode, $message, true);
    }
}

// ... [保持后面的安全函数、会话函数不变]
?>