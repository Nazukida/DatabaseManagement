-- 1. 创建普通用户 (给商家/顾客/骑手用)
-- 密码设为 'user123' (你可以自己改)
CREATE USER 'linli_staff'@'localhost' IDENTIFIED BY 'user123';

-- 只赋予基本的增删改查权限，没有任何管理权限 (不能删库)
GRANT SELECT, INSERT, UPDATE, DELETE ON dbms.* TO 'linli_staff'@'localhost';


-- 2. 创建管理员用户 (给系统管理员用)
-- 密码设为 'admin888'
CREATE USER 'linli_master'@'localhost' IDENTIFIED BY 'admin888';

-- 赋予该数据库的所有权限 (包括修改表结构等)
GRANT ALL PRIVILEGES ON dbms.* TO 'linli_master'@'localhost';


-- 3. 刷新权限使生效
FLUSH PRIVILEGES;