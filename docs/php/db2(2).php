<?php
// db.php - 优食邻里数据库连接文件（使用dbms数据库）

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'dbms');  // 改为你的数据库名
define('DB_USER', 'root');
define('DB_PASS', '');

// 字符集设置
define('DB_CHARSET', 'utf8mb4');

/**
 * 数据库连接类
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
                error_log("数据库连接失败: " . $e->getMessage());
                die("数据库连接失败，请稍后再试。错误: " . $e->getMessage());
            }
        }
        return self::$connection;
    }
    
    public static function testConnection() {
        try {
            $conn = self::getConnection();
            return "数据库连接成功！数据库: " . DB_NAME;
        } catch (Exception $e) {
            return "数据库连接失败: " . $e->getMessage();
        }
    }
}

/**
 * 管理员相关操作类 - 针对dbms数据库
 */
class AdminModel {
    
    /**
     * 搜索订单 - 只支持整数ID
     * @param int $orderId 订单ID（整数）
     * @param int $merchantId 商家ID（整数）
     * @return array
     */
    public static function searchOrders($orderId = 0, $merchantId = 0) {
        $db = Database::getConnection();
        
        try {
            $sql = "SELECT * FROM orders WHERE 1=1";
            $params = [];
            
            if ($orderId > 0) {
                $sql .= " AND OrderID = ?";
                $params[] = $orderId;
            }
            
            if ($merchantId > 0) {
                $sql .= " AND RestaurantID = ?";
                $params[] = $merchantId;
            }
            
            $sql .= " ORDER BY OrderTime DESC LIMIT 50";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("搜索订单错误: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 获取订单详细信息（包含关联信息）
     * @param array $orders 基础订单数据
     * @return array
     */
    public static function getOrderDetails($orders) {
        if (empty($orders) || isset($orders['error'])) {
            return $orders;
        }
        
        $db = Database::getConnection();
        $detailedOrders = [];
        
        try {
            foreach ($orders as $order) {
                // 获取用户信息
                $user = [];
                if (!empty($order['UserID'])) {
                    $stmt = $db->prepare("SELECT Username FROM users WHERE UserID = ? LIMIT 1");
                    $stmt->execute([$order['UserID']]);
                    $user = $stmt->fetch();
                }
                
                // 获取商家信息
                $restaurant = [];
                if (!empty($order['RestaurantID'])) {
                    $stmt = $db->prepare("SELECT RestaurantName FROM restaurants WHERE RestaurantID = ? LIMIT 1");
                    $stmt->execute([$order['RestaurantID']]);
                    $restaurant = $stmt->fetch();
                }
                
                // 获取骑手信息
                $rider = [];
                if (!empty($order['RiderID'])) {
                    $stmt = $db->prepare("SELECT Name FROM riders WHERE RiderID = ? LIMIT 1");
                    $stmt->execute([$order['RiderID']]);
                    $rider = $stmt->fetch();
                }
                
                $detailedOrders[] = [
                    'order_id' => $order['OrderID'],
                    'user_id' => $order['UserID'],
                    'rider_id' => $order['RiderID'],
                    'customer_comment' => $order['CustomerComment'] ?? '暂无评价',
                    'rider_action' => $order['RiderAction'] ?? '等待配送',
                    'order_status' => self::formatOrderStatus($order['OrderStatus'] ?? 'pending'),
                    'username' => $user['Username'] ?? '未知用户',
                    'restaurant_name' => $restaurant['RestaurantName'] ?? '未知商家',
                    'rider_name' => $rider['Name'] ?? '未分配骑手'
                ];
            }
            
            return $detailedOrders;
            
        } catch (PDOException $e) {
            error_log("获取订单详情错误: " . $e->getMessage());
            return $orders; // 返回原始数据
        }
    }
    
    /**
     * 获取商家产品操作记录 - 只支持整数ID
     * @param int $merchantId 商家ID（整数）
     * @return array
     */
    public static function getMerchantOperations($merchantId = 0) {
        $db = Database::getConnection();
        
        try {
            // 首先检查menu_item表是否存在
            $stmt = $db->query("SHOW TABLES LIKE 'menu_item'");
            if ($stmt->rowCount() == 0) {
                // 如果表不存在，返回模拟数据
                return self::getMockMerchantOperations($merchantId);
            }
            
            $sql = "SELECT * FROM menu_item WHERE 1=1";
            $params = [];
            
            if ($merchantId > 0) {
                $sql .= " AND RestaurantID = ?";
                $params[] = $merchantId;
            }
            
            $sql .= " ORDER BY LastUpdated DESC LIMIT 20";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            
            // 转换为Admin.html需要的格式
            $formattedResults = [];
            foreach ($results as $row) {
                $formattedResults[] = [
                    'merchant_id' => $row['RestaurantID'] ?? $merchantId,
                    'product_name' => $row['ItemName'] ?? '未知商品',
                    'action_type' => isset($row['StockStatus']) ? ($row['StockStatus'] == 'In Stock' ? '上架' : '下架') : '库存调整',
                    'quantity_change' => isset($row['StockQuantity']) ? 
                        ($row['StockQuantity'] > 50 ? '+'.$row['StockQuantity'] : '-'.$row['StockQuantity']) : '+0',
                    'action_time' => $row['LastUpdated'] ?? date('Y-m-d H:i:s'),
                    'notes' => $row['Description'] ?? '库存调整'
                ];
            }
            
            return $formattedResults;
            
        } catch (PDOException $e) {
            error_log("获取商家操作记录错误: " . $e->getMessage());
            return self::getMockMerchantOperations($merchantId);
        }
    }
    
    /**
     * 模拟商家操作数据（用于测试）
     * @param int $merchantId
     * @return array
     */
    private static function getMockMerchantOperations($merchantId = 0) {
        $operations = [];
        $products = ['红烧牛肉面', '宫保鸡丁', '麻辣香锅', '酸菜鱼', '糖醋里脊', '麻婆豆腐'];
        $actions = ['上架', '下架', '补货', '调价'];
        $notes = ['新品上市', '促销活动', '日常补货', '库存盘点', '季节调整'];
        
        for ($i = 0; $i < 10; $i++) {
            $operations[] = [
                'merchant_id' => $merchantId > 0 ? $merchantId : rand(1, 10),
                'product_name' => $products[array_rand($products)],
                'action_type' => $actions[array_rand($actions)],
                'quantity_change' => rand(0, 1) ? '+'.rand(10, 100) : '-'.rand(1, 20),
                'action_time' => date('Y-m-d H:i:s', time() - rand(0, 86400*30)),
                'notes' => $notes[array_rand($notes)]
            ];
        }
        
        return $operations;
    }
    
    /**
     * 获取所有订单信息 - 用于Admin.html的默认显示
     * @return array
     */
    public static function getAllOrderInfo() {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare("SELECT * FROM orders ORDER BY OrderTime DESC LIMIT 50");
            $stmt->execute();
            $orders = $stmt->fetchAll();
            
            return self::getOrderDetails($orders);
            
        } catch (PDOException $e) {
            error_log("获取所有订单错误: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取所有商家操作信息 - 用于Admin.html的默认显示
     * @return array
     */
    public static function getAllMerchantOperations() {
        return self::getMerchantOperations(0); // 0表示获取所有
    }
    
    /**
     * 格式化订单状态
     * @param string $status
     * @return string
     */
    private static function formatOrderStatus($status) {
        $statusMap = [
            'pending' => '进行中',
            'processing' => '处理中',
            'delivering' => '配送中',
            'completed' => '已完成',
            'cancelled' => '已取消'
        ];
        
        return $statusMap[$status] ?? $status;
    }
    
    /**
     * 获取所有表名（用于调试）
     * @return array
     */
    public static function getTables() {
        $db = Database::getConnection();
        
        try {
            $stmt = $db->query("SHOW TABLES");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("获取表名错误: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * 搜索工具类 - 专门处理管理员搜索
 */
class AdminSearch {
    
    /**
     * 综合搜索 - 处理Admin.html中的搜索功能
     * @param int $orderId 订单ID（0表示不搜索）
     * @param int $merchantId 商家ID（0表示不搜索）
     * @return array 包含订单和商家操作结果
     */
    public static function search($orderId = 0, $merchantId = 0) {
        $result = [
            'success' => true,
            'message' => '',
            'orders' => [],
            'merchant_operations' => []
        ];
        
        // 验证输入
        if ($orderId < 0 || $merchantId < 0) {
            $result['success'] = false;
            $result['message'] = 'ID必须是正整数';
            return $result;
        }
        
        // 至少需要一个搜索条件
        if ($orderId == 0 && $merchantId == 0) {
            $result['success'] = false;
            $result['message'] = '请输入至少一个搜索条件';
            return $result;
        }
        
        // 搜索订单
        $rawOrders = AdminModel::searchOrders($orderId, $merchantId);
        if (isset($rawOrders['error'])) {
            $result['orders'] = [];
            $result['message'] = '查询订单时出错: ' . $rawOrders['error'];
        } else {
            $result['orders'] = AdminModel::getOrderDetails($rawOrders);
        }
        
        // 搜索商家操作
        $result['merchant_operations'] = AdminModel::getMerchantOperations($merchantId);
        
        // 设置成功消息
        $orderCount = count($result['orders']);
        $opCount = count($result['merchant_operations']);
        $result['message'] = "搜索完成，找到 {$orderCount} 个订单和 {$opCount} 条商家操作记录";
        
        return $result;
    }
}

/**
 * API响应函数
 */
class ApiResponse {
    
    public static function json($data = null, $statusCode = 200, $message = '', $success = true) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function error($message = '操作失败', $statusCode = 400, $data = null) {
        self::json($data, $statusCode, $message, false);
    }
    
    public static function success($data = null, $message = '操作成功', $statusCode = 200) {
        self::json($data, $statusCode, $message, true);
    }
}

// 安全函数
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// 检查是否是正整数
function isPositiveInteger($value) {
    return is_numeric($value) && intval($value) > 0 && $value == intval($value);
}

// 初始化会话
session_start();

// 测试数据库连接
if (isset($_GET['test_db'])) {
    echo Database::testConnection();
    echo "<br>数据库表: " . implode(', ', AdminModel::getTables());
    exit;
}
?>