# PeaseAPI 宝塔面板部署指南

> 本文档适用于宝塔面板 7.9+ 环境下的 PeaseAPI 生产部署

## 前置要求

| 组件 | 版本要求 |
|------|---------|
| 宝塔面板 | 7.9 或更高 |
| PHP | **8.3**（必须） |
| Nginx | 1.20+ |
| 数据库 | PostgreSQL 14+ 或 MySQL 8.0+ |
| Node.js | 18+（用于前端构建） |

## 部署步骤

### 1. 创建站点
- 宝塔面板 → **网站** → **添加站点**
- 填写域名，创建数据库（推荐 PostgreSQL）
- 记录数据库账号、密码、端口

### 2. 上传代码
```bash
cd /www/wwwroot/www.yourdomain.com
git clone https://github.com/997862/PeaseAPI.git .
```

### 3. 配置 PHP
- 宝塔 → 网站 → 设置 → **PHP 版本** → 选择 **PHP 8.3**
- PHP 管理 → 安装扩展：`pdo_pgsql`（或 `pdo_mysql`）、`gd`、`fileinfo`

### 4. 配置 Nginx
> ⚠️ 以下为完整的 Nginx 规则，请替换宝塔自动生成的配置文件

宝塔 → 网站 → 设置 → **配置文件**，粘贴以下规则：

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name www.peaseapi.com peaseapi.com;
    index index.html index.php;
    root /www/wwwroot/www.peaseapi.com/public/;

    # SSL（宝塔自动生成）
    # ssl_certificate /www/server/panel/vhost/cert/.../fullchain.pem;
    # ssl_certificate_key /www/server/panel/vhost/cert/.../privkey.pem;

    # 禁止访问敏感目录
    location ~* /(\.git|\.svn|\.env.*|node_modules|runtime)/ {
        return 404;
    }

    # Let\'s Encrypt 验证
    location ~ \.well-known {
        allow all;
    }

    # PHP-FPM 处理
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-83.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PHP_VALUE "open_basedir=/www/wwwroot/www.peaseapi.com:/tmp/:/proc/";
        include fastcgi_params;
    }

    # ====== 核心路由规则 ======

    # API 接口
    location ^~ /api/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 兼容旧版 v1 API
    location ^~ /v1/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 管理员后台 SPA（/app/ 子路径）
    location ^~ /app/ {
        alias /www/wwwroot/www.peaseapi.com/public/app/;
        index index.html;
        try_files $uri $uri/ /app/index.html;
        add_header Cache-Control "no-cache, no-store, must-revalidate";
        add_header Pragma "no-cache";
    }

    # 普通用户中心
    location /user/ {
        try_files $uri $uri/ /user/index.html;
    }

    # 登录页 → 用户中心
    location = /login {
        return 302 /user/;
    }

    # 旧后台路径 → 新管理后台
    location ^~ /admin/ {
        return 301 /app/;
    }

    # 根目录 Landing 页
    location / {
        try_files $uri $uri/ /index.html;
    }

    access_log  /www/wwwlogs/www.peaseapi.com.log;
    error_log  /www/wwwlogs/www.peaseapi.com.error.log;
}
```

### 5. 前端构建
```bash
cd /www/wwwroot/www.peaseapi.com/frontend
npm install
npm run build
```

构建产物将输出到 `public/app/`，即管理员后台。

### 6. 初始化系统
1. 访问 `https://www.peaseapi.com/`
2. 系统自动引导初始化
3. 默认管理员：`root` / `admin123456`
4. **立即修改密码！**

### 7. 配置系统设置
登录管理后台后，进入 **系统设置** 配置：
- 🔧 系统基本信息
- 💳 支付宝 / 微信支付
- 📧 SMTP 邮件服务
- 📱 短信服务（阿里云/腾讯云）
- 🔗 OAuth 登录（GitHub/Google/QQ/微信）
- ⚙️ 登录方式开关

## 常见问题

### Q: 访问 /app/ 白页？
- 确认已执行 `npm run build`
- 确认 `public/app/` 目录存在 `index.html`
- 强制刷新浏览器（Ctrl+F5）

### Q: API 返回 404？
- 检查 Nginx 规则是否正确
- 确认 `location ^~ /api/` 存在
- 重启 Nginx：`nginx -s reload`

### Q: PHP 报错？
- 确认 PHP 版本为 **8.3**
- 检查扩展是否安装完整
- 查看日志：`/www/wwwlogs/www.peaseapi.com.error.log`

## 更新升级

```bash
cd /www/wwwroot/www.peaseapi.com
git pull origin main
cd frontend && npm run build
# 构建完成后，刷新后台即可
```
