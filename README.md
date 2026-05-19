# 🫛 PeaseAPI (豌豆API)

> **PHP 8.3 高性能 AI API 网关管理系统** — 基于 New API 架构重写的企业级解决方案

[![PHP Version](https://img.shields.io/badge/PHP-8.3-blue)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/Database-PostgreSQL-336791)](https://www.postgresql.org/)
[![Vue 3](https://img.shields.io/badge/Frontend-Vue3-4FC08D)](https://vuejs.org/)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## 🌟 为什么选择 PeaseAPI？

相比同类 AI 网关项目，PeaseAPI 拥有以下 **独家优势**：

| 功能 | PeaseAPI | New API | One API | 其他开源项目 |
|------|:---:|:---:|:---:|:---:|
| 🇨🇳 中文原生支持 | ✅ | ❌ | ❌ | 部分 |
| 📱 短信验证码登录 | ✅ | ❌ | ❌ | ❌ |
| 💬 QQ / 微信 OAuth | ✅ | ❌ | ❌ | ❌ |
| 🔍 Google OAuth | ✅ | ❌ | ❌ | 部分 |
| 🐙 GitHub OAuth | ✅ | ✅ | ✅ | ✅ |
| 📧 SMTP 邮件系统 | ✅ | ❌ | ❌ | ❌ |
| 📨 邮件模板管理 | ✅ | ❌ | ❌ | ❌ |
| 🎁 邀请返利系统 | ✅ | ❌ | ❌ | ❌ |
| 👑 角色权限管理 | ✅ | 基础 | ❌ | ❌ |
| 👥 用户分组管理 | ✅ | ❌ | ❌ | ❌ |
| 🌐 多节点部署 | ✅ | ❌ | 部分 | ❌ |
| 🐳 Docker 一键部署 | ✅ | ❌ | ❌ | ❌ |
| 📊 实时指标监控 | ✅ | ❌ | ❌ | ❌ |
| 🔒 Token IP 限制 | ✅ | 基础 | ❌ | ❌ |
| 👥 批量用户操作 | ✅ | ❌ | ❌ | ❌ |
| 💰 手动充值 | ✅ | ❌ | ❌ | ❌ |
| 📋 管理员操作日志 | ✅ | 基础 | ❌ | ❌ |
| 🚔 登录日志取证 | ✅ | ❌ | ❌ | ❌ |
| 🌍 中英双语切换 | ✅ | ❌ | ❌ | 部分 |

---

## 🚀 核心功能

### 🔐 认证与授权
- **密码登录** — 传统用户名密码认证
- **短信验证码** — 手机号验证码登录/注册
- **OAuth 第三方登录** — GitHub、Google、QQ、微信、飞书
- **API Token 认证** — Bearer Token / API Key
- **Token IP 限制** — 限制 Token 仅允许特定 IP 使用

### 👥 用户管理
- **角色权限体系** — 普通用户、VIP、管理员、超级管理员
- **用户分组** — 按配额/频率限制分组管理
- **批量操作** — 批量启用/禁用/充值/重置/删除/修改角色
- **手动充值** — 管理员直接为用户充值配额
- **邀请返利** — 邀请码注册，双向奖励配额

### 📱 消息系统
- **阿里云短信** — 支持验证码发送
- **腾讯云短信** — 双运营商容灾轮询
- **SMTP 邮件** — 完整邮件发送服务
- **邮件模板管理** — 4 套预置模板（验证/重置/欢迎/充值通知）

### 📊 监控与日志
- **实时指标** — QPS、平均延迟、错误率、Token 消耗
- **API 调用日志** — 完整的请求记录
- **管理员操作日志** — CRUD 操作前后值记录
- **登录日志** — IP/端口/UA/时间，支持公安取证

### 🌐 部署与架构
- **多节点部署** — 支持多服务器自动同步配置
- **Docker 一键部署** — 完整 docker-compose 配置
- **Nginx + PHP-FPM** — 高性能生产环境
- **PostgreSQL / MySQL / SQLite** — 多数据库支持

### 💻 管理后台
- **Vue 3 单页应用** — 无需构建工具，CDN 加载
- **仪表盘** — 实时数据可视化（Chart.js）
- **用户/渠道/Token 管理** — 完整 CRUD
- **系统设置** — 注册/登录/公告/支付等
- **中英双语** — 一键切换语言

---

## 📦 快速开始

### Docker 部署（推荐）

```bash
# 1. 拉取镜像
docker pull registry.cn-hangzhou.aliyuncs.com/peaseapi/peaseapi:latest

# 2. 创建 docker-compose.yml
cat > docker-compose.yml << 'EOF'
version: '3.8'
services:
  peaseapi:
    image: registry.cn-hangzhou.aliyuncs.com/peaseapi/peaseapi:latest
    container_name: peaseapi
    restart: always
    ports:
      - "3000:3000"
    environment:
      - DB_TYPE=postgres
      - DB_HOST=your-db-host
      - DB_PORT=5432
      - DB_DATABASE=peaseapi
      - DB_USERNAME=peaseapi
      - DB_PASSWORD=your-password
      - SESSION_SECRET=your-secret-key
    volumes:
      - ./data:/app/data
EOF

# 3. 启动服务
docker-compose up -d
```

### 宝塔面板部署

1. 创建站点，绑定域名
2. 设置 PHP 版本为 **PHP 8.3**
3. 上传项目文件到站点目录
4. 配置 Nginx 伪静态规则：
```nginx
location /api/ { try_files $uri $uri/ /index.php?$query_string; }
location /v1/ { try_files $uri $uri/ /index.php?$query_string; }
location / { try_files $uri $uri/ /admin/index.html; }
```
5. 访问 `https://yourdomain.com/` 完成初始化

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

- **后端**: PHP 8.3 + PostgreSQL
- **前端**: Vue 3 + TailwindCSS (CDN)
- **图表**: Chart.js
- **部署**: Nginx + PHP-FPM / Docker
- **缓存**: Redis (可选)

---

## 📋 更新日志

### v2.0 (当前版本)
- ✨ 新增短信验证码系统（阿里云/腾讯云）
- ✨ 新增 SMTP 邮件系统 + 4 套预置模板
- ✨ 新增 QQ/微信/Google OAuth 登录
- ✨ 新增角色权限管理系统
- ✨ 新增用户分组管理
- ✨ 新增邀请返利系统
- ✨ 新增多节点部署与自动同步
- ✨ 新增 Docker 一键部署
- ✨ 新增实时指标监控（QPS/延迟/错误率）
- ✨ 新增批量用户操作
- ✨ 新增手动充值功能
- ✨ 新增 Token IP 限制
- ✨ 新增中英双语切换
- ✨ 新增管理员操作日志
- ✨ 新增登录日志（公安取证）
- 🎨 全面优化管理后台 UI/UX

---

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📄 许可证

MIT License

---

<p align="center">
  Made with 🫛 by PeaseAPI Team
</p>
