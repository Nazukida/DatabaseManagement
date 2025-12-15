// scripts/dev_auto_setup.js
// 这是一个开发辅助脚本，用于在页面加载时自动确保数据库用户和权限已正确配置。
// 在生产环境中应移除此脚本。

(function() {
    console.log('Dev: Auto-running database security setup...');
    
    // 使用 fetch API 异步请求 setup 脚本
    fetch('php/setup_security_users.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            // 简单的检查，看是否包含成功信息
            if (data.includes('[SUCCESS]')) {
                console.log('Dev: Database setup verified/completed successfully.');
            } else {
                console.warn('Dev: Database setup script returned unexpected output:', data);
            }
        })
        .catch(error => {
            console.error('Dev: Failed to run database setup script:', error);
        });
})();
