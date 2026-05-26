<img src="https://upload.o51.com/ico-svg/ico/favicon.png" alt="PeaseAPI Logo" width="64" height="64" />

# PeaseAPI (豌豆API) — v3.0

> **PHP 8.3 高性能 AI API 网关管理系统** — 超越 One-API / New-API 的企业级解决方案

[![Version](https://img.shields.io/badge/Version-3.0.0-6366F1)](https://github.com/997862/PeaseAPI)
[![PHP Version](https://img.shields.io/badge/PHP-8.3-blue)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/Database-PostgreSQL-336791)](https://www.postgresql.org/)
[![Vue 3](https://img.shields.io/badge/Frontend-Vue3-4FC08D)](https://vuejs.org/)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## 📌 版本迭代历程

PeaseAPI 自 2026 年启动以来，经历了三个重大版本的迭代，每一步都标志着产品能力的跨越式提升：

### 🏗️ v1.0 — 奠基 (2026年 Q1)
**目标：对标 New-API / One-API，打造完整 AI 网关基础架构**

v1.0 全面吸收了 One-API 和 New-API 的核心设计理念，构建了稳固的 API 网关底座：
- ✅ API 渠道管理（OpenAI / Azure / Claude / 智谱 / 通义千问 / 文心一言）
- ✅ Token / API Key 管理与计费
- ✅ 用户系统（注册 / 登录 / 密码找回）
- ✅ 基础配额管理
- ✅ API 调用日志记录
- ✅ GitHub OAuth 第三方登录
- ✅ 响应式管理后台

### 🎨 v2.0 — 进化 (2026年 Q1)
**目标：UI/UX 全面优化，排查并修复架构缺陷**

v2.0 对 v1.0 进行了全方位的打磨和优化：
- 🎨 管理后台全面采用 Vue 3 + TailwindCSS 重构，UI 现代化
- 🐛 修复 Nginx 路由冲突、缓存、构建产物错位等核心问题
- ⚡ 优化数据库查询性能，引入分页和索引优化
- 🔒 增强安全机制：Token IP 限制、操作审计、登录日志
- 📊 新增仪表盘实时指标监控（QPS / 延迟 / 错误率）
- 🌐 中英双语国际化支持
- 📋 新增管理员操作日志（CRUD 前后值记录）
- 🚔 登录日志取证（IP / 端口 / UA / 时间戳）
- 👥 用户分组管理
- 💰 手动充值功能
- 🎁 邀请返利系统
- 👑 角色权限体系（普通用户 / VIP / 管理员 / Root）

### 🚀 v3.0 — 超越 (2026年 Q2) ← **当前版本**
**目标：功能超越竞品，打造企业级 AI 网关旗舰**

v3.0 在 v2.0 基础上大幅扩展，引入多项 One-API / New-API 均不具备的旗舰级功能：
- 📱 **短信验证码系统** — 阿里云 + 腾讯云双运营商容灾轮询
- 📧 **SMTP 邮件系统** — 完整邮件发送 + 4 套预置模板（验证/重置/欢迎/充值通知）
- 🔗 **多平台 OAuth 登录** — GitHub / Google / QQ / 微信 / 飞书
- ⚙️ **精细化登录控制** — 用户名密码 / 邮箱 / 手机号登录独立开关
- ✅ **注册验证机制** — 邮箱验证 / 短信验证可独立开启，未验证用户无法激活
- 💳 **支付系统集成** — 支付宝 + 微信支付配置
- 📦 **后台系统设置** — 7 大配置模块（系统/支付/SMTP/短信/OAuth/其他）
- 🔌 **短信测试功能** — 后台直接测试短信发送
- 🏷️ **UID 标识系统** — 用户管理以 UID 编号替代首字母头像，精准定位
- ⏰ **完整时间戳** — 用户注册时间精确到秒，支持审计追溯
- 🌟 **全新品牌标识** — 统一 Logo 展示，强化品牌形象

---

## 🌟 功能对比：PeaseAPI vs 竞品

### 核心能力矩阵

| 功能模块 | PeaseAPI v3.0 | One-API | New-API |
|---------|:---:|:---:|:---:|
| **认证体系** | | | |
| 用户名密码登录 | ✅ | ✅ | ✅ |
| 邮箱登录 | ✅ | ❌ | ❌ |
| 手机号+验证码登录 | ✅ | ❌ | ❌ |
| GitHub OAuth | ✅ | ✅ | ✅ |
| Google OAuth | ✅ | ❌ | ❌ |
| QQ OAuth | ✅ | ❌ | ❌ |
| 微信 OAuth | ✅ | ❌ | ❌ |
| 飞书 OAuth | ✅ | ❌ | ❌ |
| **注册与验证** | | | |
| 邮箱验证（注册激活） | ✅ | ❌ | ❌ |
| 短信验证（注册激活） | ✅ | ❌ | ❌ |
| 邀请码注册 | ✅ | ❌ | ❌ |
| 新用户初始配额 | ✅ | ✅ | 部分 |
| **消息系统** | | | |
| 阿里云短信 | ✅ | ❌ | ❌ |
| 腾讯云短信 | ✅ | ❌ | ❌ |
| 双运营商容灾 | ✅ | ❌ | ❌ |
| SMTP 邮件 | ✅ | ❌ | ❌ |
| 邮件模板管理 | ✅ | ❌ | ❌ |
| IP 频率限制 | ✅ | ❌ | ❌ |
| 60 秒冷却 | ✅ | ❌ | ❌ |
| **渠道与模型** | | | |
| OpenAI 兼容 | ✅ | ✅ | ✅ |
| Azure OpenAI | ✅ | ✅ | ✅ |
| Anthropic Claude | ✅ | ✅ | 部分 |
| 智谱 AI | ✅ | ✅ | ✅ |
| 通义千问 | ✅ | ✅ | ❌ |
| 文心一言 | ✅ | ✅ | ❌ |
| 模型倍率配置 | ✅ | ✅ | ✅ |
| 渠道优先级 | ✅ | ✅ | ✅ |
| 渠道健康检测 | ✅ | ✅ | ❌ |
| **用户与权限** | | | |
| 角色权限体系 | ✅ | ❌ | 基础 |
| 用户分组 | ✅ | ❌ | ❌ |
| 批量操作 | ✅ | ❌ | ❌ |
| 手动充值 | ✅ | ❌ | ❌ |
| UID 精准标识 | ✅ | ❌ | ❌ |
| **计费与支付** | | | |
| Token 计费 | ✅ | ✅ | ✅ |
| Token IP 限制 | ✅ | 部分 | ❌ |
| 支付宝支付 | ✅ | ❌ | ❌ |
| 微信支付 | ✅ | ❌ | ❌ |
| 手动充值 | ✅ | ❌ | ❌ |
| **监控与日志** | | | |
| 实时指标监控 | ✅ | ❌ | ❌ |
| API 调用日志 | ✅ | ✅ | ✅ |
| 管理员操作日志 | ✅ | ❌ | ❌ |
| 登录日志取证 | ✅ | ❌ | ❌ |
| QPS 统计 | ✅ | ❌ | ❌ |
| 错误率分析 | ✅ | ❌ | ❌ |
| **部署与架构** | | | |
| Nginx + PHP-FPM | ✅ | ❌ | ❌ |
| Docker 部署 | ✅ | ❌ | 部分 |
| 宝塔面板 | ✅ | ❌ | ❌ |
| 多节点同步 | ✅ | ❌ | ❌ |
| PostgreSQL | ✅ | ❌ | ❌ |
| MySQL | ✅ | ✅ | ✅ |
| **UI/UX** | | | |
| Vue 3 SPA | ✅ | ❌ | ❌ |
| TailwindCSS | ✅ | ❌ | ❌ |
| 移动端适配 | ✅ | 部分 | ❌ |
| 中英双语 | ✅ | ❌ | 部分 |

**总计**：PeaseAPI v3.0 支持 **50+** 项功能，One-API 约 **25** 项，New-API 约 **20** 项。

---

## 🚀 核心功能详解

### 🔐 认证与授权
- **多方式登录** — 用户名密码 / 邮箱 / 手机号+验证码
- **OAuth 第三方登录** — GitHub、Google、QQ、微信、飞书
- **API Token 认证** — Bearer Token / API Key，支持 IP 白名单
- **登录方式独立控制** — 后台可分别启用/禁用各登录方式

### ✅ 注册与验证
- **注册验证机制** — 可选开启邮箱验证或短信验证
- **未验证用户隔离** — 未完成验证的用户无法激活使用
- **邀请码系统** — 邀请码注册，双向奖励配额

### 📱 消息系统
- **阿里云短信** — 支持验证码发送，配置签名和模板
- **腾讯云短信** — 双运营商容灾轮询
- **SMTP 邮件** — 完整邮件发送服务
- **邮件模板** — 4 套预置模板（验证/重置/欢迎/充值通知）
- **安全防护** — IP 频率限制 + 60 秒冷却

### 📊 监控与日志
- **实时指标** — QPS、平均延迟、错误率、Token 消耗
- **API 调用日志** — 完整的请求/响应记录
- **操作审计** — 管理员 CRUD 操作前后值记录
- **登录日志** — IP/端口/UA/时间戳，支持公安取证

### 💻 管理后台
- **7 大设置模块** — 系统/支付/SMTP/短信/OAuth/其他
- **仪表盘** — 实时数据可视化（Chart.js）
- **UID 标识** — 用户以 UID 编号精准定位
- **完整时间戳** — 注册/登录时间精确到秒
- **中英双语** — 一键切换语言

---

## 📦 快速开始

### 宝塔面板部署（推荐生产环境）

> **前置要求**：已安装宝塔面板（推荐 7.9+），PHP 8.3，Nginx

#### 第 1 步：创建站点
1. 登录宝塔面板 → 网站 → 添加站点
2. 填写域名（如 `www.peaseapi.com`）
3. 创建数据库（PostgreSQL 推荐，MySQL 也可）
4. 记录数据库账号密码

#### 第 2 步：部署代码
```bash
# SSH 到服务器
cd /www/wwwroot/www.yourdomain.com

# 方式 A：Git 克隆（推荐）
git clone https://github.com/997862/PeaseAPI.git .

# 方式 B：上传 ZIP 包后解压
unzip PeaseAPI.zip
```

#### 第 3 步：配置 PHP
1. 宝塔面板 → 网站 → 对应站点 → 设置 → PHP 版本
2. 选择 **PHP 8.3**（必须）
3. 安装必要扩展：`pdo_pgsql` 或 `pdo_mysql`、`gd`、`fileinfo`

#### 第 4 步：配置 Nginx

宝塔面板 → 网站 → 对应站点 → 设置 → **配置文件**，替换为以下规则：

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name www.peaseapi.com peaseapi.com;
    index index.html index.php;
    root /www/wwwroot/www.peaseapi.com/public/;

    # SSL 证书（宝塔自动生成）
    # ssl_certificate ...
    # ssl_certificate_key ...

    # 安全：禁止访问敏感目录
    location ~* /(\.git|\.svn|\.env.*|node_modules|runtime)/ {
        return 404;
    }

    # Let\'s Encrypt 验证
    location ~ \.well-known {
        allow all;
    }

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-83.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PHP_VALUE "open_basedir=/www/wwwroot/www.peaseapi.com:/tmp/:/proc/";
        include fastcgi_params;
    }

    # API 路由
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

    # 登录页重定向
    location = /login {
        return 302 /user/;
    }

    # 旧后台跳转
    location ^~ /admin/ {
        return 301 /app/;
    }

    # 根目录（Landing 页）
    location / {
        try_files $uri $uri/ /index.html;
    }

    access_log  /www/wwwlogs/www.peaseapi.com.log;
    error_log  /www/wwwlogs/www.peaseapi.com.error.log;
}
```

#### 第 5 步：前端构建
```bash
cd /www/wwwroot/www.peaseapi.com/frontend
npm install
npm run build
# 构建产物输出到 public/app/
```

#### 第 6 步：初始化
1. 访问 `https://www.peaseapi.com/`
2. 系统自动引导完成初始化配置
3. 默认管理员：`root` / `admin123456`
4. 登录后立即修改密码

---

## ⚙️ 环境变量

| 变量名 | 说明 | 示例 |
|--------|------|------|
| `DB_TYPE` | 数据库类型 | `postgres` / `mysql` / `sqlite` |
| `DB_HOST` | 数据库地址 | `127.0.0.1` |
| `DB_PORT` | 数据库端口 | `5432` |
| `DB_DATABASE` | 数据库名 | `peaseapi` |
| `DB_USERNAME` | 数据库用户 | `peaseapi` |
| `DB_PASSWORD` | 数据库密码 | `your-password` |
| `SESSION_SECRET` | Session 密钥 | 随机字符串 |
| `REDIS_ENABLED` | 启用 Redis | `true` / `false` |

---

## 🔧 技术栈

- **后端**: PHP 8.3 + PostgreSQL / MySQL
- **前端**: Vue 3 + TailwindCSS + Chart.js
- **部署**: Nginx + PHP-FPM / Docker
- **缓存**: Redis (可选)
- **短信**: 阿里云 / 腾讯云
- **支付**: 支付宝 / 微信支付

---

## 💬 交流与反馈

- **QQ 交流群**：10662299
- **问题反馈**：[www.peaseapi.net](https://www.peaseapi.net)
- **商务合作**：联系群主或发送邮件至 [791777@gmail.com](mailto:791777@gmail.com)
- **开源地址**：[github.com/997862/PeaseAPI](https://github.com/997862/PeaseAPI)

---

## 📄 许可证

MIT License

---

<p align="center">
  <img src="https://upload.o51.com/ico-svg/ico/favicon.png" alt="PeaseAPI" width="32" height="32" />
  <br />
  <strong>PeaseAPI Team</strong> · 持续进化，超越期待
</p>
