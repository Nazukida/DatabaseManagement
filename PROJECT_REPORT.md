# 校园外卖管理系统 (Campus Food Delivery System) - 项目文档

## 1. 项目概述 (Project Overview)

本项目是一个基于 Web 的校园外卖管理系统，旨在为校园内的学生（顾客）、商家、骑手和管理员提供一个高效、便捷的在线订餐和配送平台。系统实现了从用户浏览菜单、下单、支付到商家接单、骑手配送的全流程管理。

### 核心角色
*   **顾客 (Customer)**: 浏览餐厅和菜单，管理购物车，下订单，支付，查看订单状态，评价。
*   **商家 (Merchant)**: 管理餐厅信息，管理菜单（上架/下架菜品），处理订单（接单/出餐），查看销售数据。
*   **骑手 (Rider)**: 查看待配送订单，接单，更新配送状态（取餐/送达），查看收入统计。
*   **管理员 (Admin)**: 系统维护，用户管理，审核商家/骑手，查看系统日志。

---

## 2. 系统架构 (System Architecture)

本系统采用经典的 B/S (Browser/Server) 架构：

*   **前端 (Frontend)**:
    *   技术栈: HTML5, CSS3 (Fancy-kit UI), JavaScript (原生 ES6+).
    *   特点: 响应式设计，通过 `fetch` API 与后端进行异步数据交互，实现无刷新体验。
*   **后端 (Backend)**:
    *   技术栈: PHP 7.4+.
    *   特点: RESTful 风格接口，面向过程与部分面向对象结合，封装了数据库操作类。
*   **数据库 (Database)**:
    *   技术栈: MySQL 8.0.
    *   特点: 使用复杂的 E-R 关系设计，包含存储过程、触发器 (Triggers) 和视图 (Views) 以实现业务逻辑自动化。
*   **安全 (Security)**:
    *   数据库层面: 实现了基于角色的访问控制 (RBAC)，不同角色的 PHP 脚本使用不同的数据库用户 (`app_customer`, `app_merchant` 等) 连接，最小化权限。
    *   应用层面: 密码采用 SHA-256 哈希存储。

---

## 3. 数据库设计 (Database Design)

数据库是本系统的核心，包含多张关联表以支撑复杂的业务流程。

### 3.1 主要数据表 (Key Tables)

1.  **用户体系**:
    *   `users`: 存储顾客基本信息。
    *   `restaurants`: 存储商家信息（含配送费、起送价、评分等）。
    *   `riders`: 存储骑手信息（含状态、总收入、评分等）。
    *   `admin`: 管理员表。

2.  **核心业务**:
    *   `menu_items`: 菜品表，关联 `restaurants` 和 `category`。
    *   `orders`: 订单主表，记录总价、状态、时间、涉及的三方（顾客、商家、骑手）。
    *   `order_items`: 订单详情表，记录每个订单包含的具体菜品及数量。
    *   `payment`: 支付记录表。
    *   `delivery_addresses`: 顾客收货地址。

3.  **辅助功能**:
    *   `review`: 评价表，关联订单、顾客、商家和骑手。
    *   `audit_logs`: 审计日志，记录关键操作。

### 3.2 关键触发器 (Triggers)

系统利用 MySQL 触发器实现了部分业务逻辑的自动化，保证数据一致性：

*   **`AfterOrderComplete`**:
    *   **触发时机**: 当订单状态更新为 'Completed' (已送达) 后。
    *   **功能**: 自动计算该订单金额，累加到对应骑手的 `TotalEarnings` (总收入) 和 `TotalOrders` (总单量) 字段中。
*   **`update_rider_orders`**:
    *   **触发时机**: 当订单分配给骑手时。
    *   **功能**: 实时更新骑手的当前状态。

---

## 4. 核心功能模块解析 (Key Modules)

### 4.1 购物车与下单流程 (Cart & Checkout)
*   **前端**: `customer_cart.html` 使用 JavaScript 动态渲染购物车内容。
*   **后端**: `php/cart_handler.php` 处理购物车的增删改查。
*   **同步机制**: 早期版本仅使用 `localStorage`，现已升级为 **数据库同步模式**。用户登录后，购物车数据会保存到数据库，保证多设备同步。
*   **下单**: `php/checkout_handler.php` 负责创建订单。它会开启一个数据库事务 (Transaction)，同时向 `orders` 表插入主订单和向 `order_items` 表插入明细，确保原子性。

### 4.2 数据库权限隔离 (Database Security)
这是一个技术亮点。系统不使用单一的 `root` 账号连接数据库，而是通过 `php/setup_security_users.php` 脚本初始化了多个受限账号：

*   **`app_customer`**: 仅拥有查看菜单、管理自己购物车 (`INSERT`, `UPDATE`, `DELETE` on `cart`) 和下单 (`INSERT` on `orders`) 的权限。
*   **`app_merchant`**: 拥有管理自家菜品 (`UPDATE`, `INSERT` on `menu_items`) 和接单的权限。
*   **`app_rider`**: 仅拥有查询待配送订单和更新订单状态的权限。

这种设计极大地提高了系统的安全性，即使某个模块被注入攻击，攻击者也无法破坏整个数据库。

---

## 5. 项目总结与展望

本项目成功构建了一个功能完备的校园外卖系统。
*   **优点**: 结构清晰，数据库设计规范（满足 3NF），安全性设计超前（数据库级权限控制），用户体验流畅。
*   **改进空间**: 目前前端主要使用原生 JS，未来可考虑引入 Vue.js 或 React 框架以提升开发效率；后端可引入 Laravel 或 ThinkPHP 框架以增强路由和中间件支持。
