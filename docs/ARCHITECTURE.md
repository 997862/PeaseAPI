# PeaseAI — 系统架构与技术设计文档

> **文档版本**: v1.0  
> **创建日期**: 2026-05-19  
> **技术栈**: PHP 8.3 + PostgreSQL 16+ + Nginx + PHP-FPM  
> **继承项目**: QuantumNous/new-api (AGPLv3)

---

## 目录

1. [系统架构总览](#1-系统架构总览)
2. [数据库架构设计](#2-数据库架构设计)
3. [完整 DDL 脚本](#3-完整-ddl-脚本)
4. [数据库关系图](#4-数据库关系图)
5. [数据字典](#5-数据字典)
6. [MVC 分层架构](#6-mvc-分层架构)
7. [核心组件设计](#7-核心组件设计)
8. [API 设计规范](#8-api-设计规范)
9. [安全设计](#9-安全设计)
10. [性能与扩展设计](#10-性能与扩展设计)

---

## 1. 系统架构总览

### 1.1 技术栈

| 层级 | 技术 | 版本 | 说明 |
|------|------|------|------|
| 运行时 | PHP | 8.3+ | JIT、只读属性、类型增强 |
| Web 服务器 | Nginx | 1.24+ | 反向代理、静态文件、SSL |
| PHP 处理器 | PHP-FPM | 8.3+ | 进程管理、连接池 |
| 数据库 | PostgreSQL | 16+ | JSONB、CTE、分区表 |
| HTTP 客户端 | Guzzle | 7.x | 上游请求、流式响应 |
| 包管理 | Composer | 2.x | 自动加载、依赖管理 |

### 1.2 架构分层图

```
┌─────────────────────────────────────────────────────────────────┐
│                        客户端层 (Client)                          │
│   Web 前端 (React/Vue) │ CLI 工具 │ 第三方 AI 应用 (OpenAI SDK)    │
└──────────────────────────┬──────────────────────────────────────┘
                           │ HTTPS
┌──────────────────────────▼──────────────────────────────────────┐
│                     反向代理层 (Nginx)                           │
│   SSL 终结 │ 静态资源 │ 请求路由 │ 速率限制 │ gzip 压缩            │
└──────────────────────────┬──────────────────────────────────────┘
                           │ FastCGI
┌──────────────────────────▼──────────────────────────────────────┐
│                    PHP-FPM 应用层                                │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                    入口: public/index.php                  │  │
│  │  ┌────────────┐  ┌────────────┐  ┌─────────────────────┐ │  │
│  │  │   Router   │→ │ Middleware │→ │    Controller       │ │  │
│  │  │  (路由)    │  │ (中间件链) │  │  (业务逻辑编排)      │ │  │
│  │  └────────────┘  └────────────┘  └────────┬────────────┘ │  │
│  └───────────────────────────────────────────┼──────────────┘  │
│                                              │                 │
│  ┌───────────────────────────────────────────▼──────────────┐  │
│  │                      Model 层                             │  │
│  │  ┌──────────┐  ┌──────────────┐  ┌────────────────────┐  │  │
│  │  │  Model   │  │ QueryBuilder │  │    Connection      │  │  │
│  │  │ (ORM基类) │  │  (查询构建器) │  │  (PDO 连接池)      │  │  │
│  │  └──────────┘  └──────────────┘  └────────────────────┘  │  │
│  └──────────────────────────────────────────────────────────┘  │
└──────────────────────────┬──────────────────────────────────────┘
                           │ PDO (参数化查询)
┌──────────────────────────▼──────────────────────────────────────┐
│                   数据存储层 (PostgreSQL)                         │
│  18 张核心表 │ 40+ 索引 │ 外键约束 │ CHECK 约束 │ JSONB 字段      │
└─────────────────────────────────────────────────────────────────┘
                           │ HTTP/HTTPS
┌──────────────────────────▼──────────────────────────────────────┐
│                外部服务层 (上游 AI 供应商)                         │
│  OpenAI │ Anthropic │ Google │ 百度 │ 智谱 │ 阿里 │ DeepSeek ...  │
└─────────────────────────────────────────────────────────────────┘
```

### 1.3 目录结构

```
/www/wwwroot/www.peaseapi.com/
├── public/
│   └── index.php                 # 单一入口文件
├── src/
│   ├── Core/                     # 核心框架
│   │   ├── Router.php            #   路由器（路径匹配 + 分组）
│   │   ├── Request.php           #   请求封装
│   │   └── Response.php          #   响应封装（JSON/流式/OpenAI错误格式）
│   ├── Database/                 # 数据库层
│   │   ├── Connection.php        #   PDO 连接管理（单例 + 连接池）
│   │   ├── Model.php             #   ORM 基类（CRUD + 类型转换）
│   │   └── QueryBuilder.php      #   链式查询构建器
│   ├── Models/                   # 数据模型（13个）
│   │   ├── User.php              #   用户模型
│   │   ├── Token.php             #   API 令牌模型
│   │   ├── Channel.php           #   渠道模型
│   │   ├── Ability.php           #   能力矩阵模型
│   │   ├── Log.php               #   日志模型
│   │   ├── Option.php            #   系统配置模型
│   │   ├── Redemption.php        #   兑换码模型
│   │   ├── Topup.php             #   充值记录模型
│   │   ├── Subscription.php      #   订阅模型
│   │   ├── Checkin.php           #   签到模型
│   │   ├── OAuthBinding.php      #   OAuth 绑定模型
│   │   ├── Passkey.php           #   WebAuthn 模型
│   │   ├── Pricing.php           #   价格表模型
│   │   ├── PrefillGroup.php      #   预填分组模型
│   │   ├── TwoFASecret.php       #   2FA 模型
│   │   ├── MissingModel.php      #   缺失模型记录
│   │   ├── PerfMetric.php        #   性能指标模型
│   │   └── VendorMeta.php        #   供应商元数据模型
│   ├── Controllers/              # 控制器（6个）
│   │   ├── AuthController.php    #   认证控制器（登录/注册）
│   │   ├── UserController.php    #   用户管理控制器
│   │   ├── TokenController.php   #   Token 管理控制器
│   │   ├── ChannelController.php #   渠道管理控制器
│   │   ├── RelayController.php   #   AI 网关中继控制器（核心）
│   │   └── SystemController.php  #   系统管理控制器
│   ├── Middleware/               # 中间件（6个）
│   │   ├── Auth.php              #   认证中间件（三种模式）
│   │   ├── CORS.php              #   跨域处理
│   │   ├── RateLimit.php         #   速率限制
│   │   ├── Logger.php            #   请求日志
│   │   ├── Stats.php             #   统计中间件
│   │   └── RequestBody.php       #   请求体解析
│   ├── Constants/                # 常量定义
│   │   └── constants.php         #   角色/状态/渠道类型/倍率等
│   └── Utils/                    # 工具函数
│       └── helpers.php           #   辅助函数
├── scripts/
│   └── init_db.php               # 数据库初始化脚本
├── docs/
│   ├── PRD.md                    # 产品需求文档
│   └── ARCHITECTURE.md           # 本架构文档
├── composer.json
├── .env                          # 环境变量配置
└── ...
```

---

## 2. 数据库架构设计

### 2.1 设计原则

| 原则 | 说明 |
|------|------|
| **PostgreSQL 原生** | 充分利用 JSONB、数组类型、CHECK 约束、序列等 PG 特性 |
| **兼容性** | 字段命名和类型与现有 `init_db.php` 完全兼容 |
| **规范化** | 核心实体遵循 3NF，高频查询字段适度冗余 |
| **可扩展** | 预留扩展字段（`setting`, `channel_info` 使用 JSONB） |
| **性能导向** | 高频查询字段建立索引，大表预留分区能力 |

### 2.2 数据类型映射

| PHP 类型 | PostgreSQL 类型 | 说明 |
|----------|----------------|------|
| `int` (小整数) | `SMALLINT` | role, status, type 等枚举字段 |
| `int` (标准) | `INTEGER` | id, user_id, token 计数等 |
| `int` (大数) | `BIGINT` | quota, used_quota, created_at, priority |
| `float/double` | `DOUBLE PRECISION` | balance, amount, unit_price |
| `string` (短) | `VARCHAR(n)` | 用户名、邮箱、名称等 |
| `string` (长) | `TEXT` | key, models, setting, content 等 |
| `bool` | `BOOLEAN` | enabled, unlimited_quota, is_stream 等 |
| `array/json` | `JSONB` | model_mapping, channel_info, param_override |
| `binary` | `BYTEA` | public_key (passkey) |
| `固定长度` | `CHAR(n)` | access_token, aff_code |

### 2.3 时间字段策略

- **统一使用 `TIMESTAMPTZ`**：替换原有的 `BIGINT` Unix 时间戳
- 优点：可读性好、支持时区、内置日期函数、可建立表达式索引
- 默认值：`DEFAULT NOW()`

### 2.4 表分类

| 分类 | 表 | 特征 |
|------|-----|------|
| **核心表** | users, channels, tokens, abilities | 高频读写，核心业务 |
| **日志表** | logs, perf_metrics | 只增不删，数据量大，可分区 |
| **配置表** | options, pricing, prefill_groups, vendor_meta | 低频写，高频读 |
| **业务表** | redemptions, topups, subscriptions, checkins | 中等频率 |
| **辅助表** | oauth_bindings, passkeys, twofa_secrets, missing_models | 低频操作 |

---

## 3. 完整 DDL 脚本

```sql
-- ============================================================
-- PeaseAI PostgreSQL 数据库 DDL
-- 版本: v1.0
-- 数据库: PostgreSQL 16+
-- 字符集: UTF-8
-- ============================================================

-- 启用必要扩展
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ============================================================
-- 1. 用户表 (users)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id              SERIAL          PRIMARY KEY,
    username        VARCHAR(20)     NOT NULL UNIQUE,
    password        VARCHAR(255)    NOT NULL,
    display_name    VARCHAR(20)     NOT NULL DEFAULT '',
    role            SMALLINT        NOT NULL DEFAULT 1,
    status          SMALLINT        NOT NULL DEFAULT 1,
    email           VARCHAR(50)     NOT NULL DEFAULT '',
    github_id       VARCHAR(64)     NOT NULL DEFAULT '',
    discord_id      VARCHAR(64)     NOT NULL DEFAULT '',
    oidc_id         VARCHAR(64)     NOT NULL DEFAULT '',
    wechat_id       VARCHAR(64)     NOT NULL DEFAULT '',
    telegram_id     VARCHAR(64)     NOT NULL DEFAULT '',
    linux_do_id     VARCHAR(64)     NOT NULL DEFAULT '',
    quota           INTEGER         NOT NULL DEFAULT 0,
    used_quota      INTEGER         NOT NULL DEFAULT 0,
    request_count   INTEGER         NOT NULL DEFAULT 0,
    "group"         VARCHAR(64)     NOT NULL DEFAULT 'default',
    aff_code        CHAR(8)         UNIQUE DEFAULT '',
    aff_count       INTEGER         NOT NULL DEFAULT 0,
    aff_quota       INTEGER         NOT NULL DEFAULT 0,
    aff_history_quota INTEGER       NOT NULL DEFAULT 0,
    inviter_id      INTEGER         DEFAULT NULL REFERENCES users(id) ON DELETE SET NULL,
    access_token    CHAR(32)        UNIQUE DEFAULT NULL,
    setting         TEXT            DEFAULT NULL,
    remark          VARCHAR(255)    NOT NULL DEFAULT '',
    stripe_customer VARCHAR(64)     NOT NULL DEFAULT '',
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    last_login_at   TIMESTAMPTZ     DEFAULT NULL,
    deleted_at      TIMESTAMPTZ     DEFAULT NULL,

    -- 角色约束: 1=普通用户, 10=管理员, 100=Root
    CONSTRAINT chk_users_role CHECK (role IN (1, 10, 100)),
    -- 状态约束: 0=禁用, 1=启用
    CONSTRAINT chk_users_status CHECK (status IN (0, 1, 2, 3)),
    -- 配额非负
    CONSTRAINT chk_users_quota CHECK (quota >= 0),
    CONSTRAINT chk_users_used_quota CHECK (used_quota >= 0),
    CONSTRAINT chk_users_request_count CHECK (request_count >= 0)
);

COMMENT ON TABLE users IS '用户账户表';
COMMENT ON COLUMN users.role IS '角色: 1=普通用户, 10=管理员, 100=Root';
COMMENT ON COLUMN users.quota IS '可用配额 (内部虚拟货币, 500000=$0.002/1K tokens)';
COMMENT ON COLUMN users.used_quota IS '已消耗配额';
COMMENT ON COLUMN users."group" IS '用户分组, 用于模型访问控制';
COMMENT ON COLUMN users.aff_code IS '推广码 (8位)';
COMMENT ON COLUMN users.inviter_id IS '推广人ID (自引用)';
COMMENT ON COLUMN users.access_token IS '管理 API 访问令牌 (32位 hex)';
COMMENT ON COLUMN users.setting IS '用户设置 (JSON)';

-- ============================================================
-- 2. 渠道表 (channels)
-- ============================================================
CREATE TABLE IF NOT EXISTS channels (
    id                  SERIAL          PRIMARY KEY,
    type                SMALLINT        NOT NULL DEFAULT 1,
    key                 TEXT            NOT NULL,
    openai_organization VARCHAR(255)    NOT NULL DEFAULT '',
    test_model          VARCHAR(128)    NOT NULL DEFAULT '',
    status              SMALLINT        NOT NULL DEFAULT 1,
    name                VARCHAR(255)    NOT NULL DEFAULT '',
    weight              INTEGER         NOT NULL DEFAULT 0,
    created_time        TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    test_time           TIMESTAMPTZ     DEFAULT NULL,
    response_time       INTEGER         NOT NULL DEFAULT 0,
    base_url            VARCHAR(1024)   NOT NULL DEFAULT '',
    other               TEXT            DEFAULT NULL,
    balance             DOUBLE PRECISION NOT NULL DEFAULT 0,
    balance_updated_time TIMESTAMPTZ    DEFAULT NULL,
    models              TEXT            NOT NULL DEFAULT '',
    "group"             VARCHAR(64)     NOT NULL DEFAULT 'default',
    used_quota          BIGINT          NOT NULL DEFAULT 0,
    model_mapping       TEXT            DEFAULT NULL,
    status_code_mapping VARCHAR(1024)   NOT NULL DEFAULT '',
    priority            BIGINT          NOT NULL DEFAULT 0,
    auto_ban            SMALLINT        NOT NULL DEFAULT 1,
    other_info          TEXT            DEFAULT NULL,
    tag                 VARCHAR(255)    DEFAULT NULL,
    setting             TEXT            DEFAULT NULL,
    param_override      TEXT            DEFAULT NULL,
    header_override     TEXT            DEFAULT NULL,
    remark              VARCHAR(255)    NOT NULL DEFAULT '',
    channel_info        JSONB           DEFAULT NULL,
    settings            TEXT            DEFAULT NULL,

    -- 状态约束: 1=启用, 2=禁用, 3=手动禁用, 4=自动禁用
    CONSTRAINT chk_channels_status CHECK (status IN (1, 2, 3, 4)),
    -- 自动封禁: 0=禁用, 1=启用
    CONSTRAINT chk_channels_auto_ban CHECK (auto_ban IN (0, 1)),
    -- 响应时间非负
    CONSTRAINT chk_channels_response_time CHECK (response_time >= 0)
);

COMMENT ON TABLE channels IS '上游 AI 渠道表';
COMMENT ON COLUMN channels.type IS '渠道类型ID (见附录: 1=OpenAI, 14=Claude, 15=Gemini 等)';
COMMENT ON COLUMN channels.key IS 'API Key (多 Key 用换行分隔)';
COMMENT ON COLUMN channels.models IS '支持的模型列表 (换行分隔)';
COMMENT ON COLUMN channels.model_mapping IS '模型映射规则 (JSON): {"请求模型": "实际模型"}';
COMMENT ON COLUMN channels.priority IS '优先级 (越高越先匹配)';
COMMENT ON COLUMN channels.weight IS '权重 (同优先级加权随机)';
COMMENT ON COLUMN channels.channel_info IS '渠道额外信息 (JSONB)';
COMMENT ON COLUMN channels.param_override IS '请求参数覆盖 (JSON)';
COMMENT ON COLUMN channels.header_override IS '请求头覆盖 (JSON)';

-- ============================================================
-- 3. API 令牌表 (tokens)
-- ============================================================
CREATE TABLE IF NOT EXISTS tokens (
    id              SERIAL          PRIMARY KEY,
    user_id         INTEGER         NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name            VARCHAR(255)    NOT NULL DEFAULT '',
    key             VARCHAR(64)     NOT NULL UNIQUE,
    created_time    TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    accessed_time   TIMESTAMPTZ     DEFAULT NULL,
    expired_time    TIMESTAMPTZ     DEFAULT NULL,
    remain_quota    BIGINT          NOT NULL DEFAULT 0,
    unlimited_quota BOOLEAN         NOT NULL DEFAULT FALSE,
    status          SMALLINT        NOT NULL DEFAULT 1,
    "group"         VARCHAR(64)     NOT NULL DEFAULT 'default',
    model_limit     TEXT            DEFAULT NULL,
    used_quota      BIGINT          NOT NULL DEFAULT 0,
    fetch_time      TIMESTAMPTZ     DEFAULT NULL,
    heartbeat_time  TIMESTAMPTZ     DEFAULT NULL,

    -- 状态约束: 1=启用, 2=禁用
    CONSTRAINT chk_tokens_status CHECK (status IN (1, 2)),
    -- 配额非负
    CONSTRAINT chk_tokens_remain_quota CHECK (remain_quota >= 0),
    CONSTRAINT chk_tokens_used_quota CHECK (used_quota >= 0)
);

COMMENT ON TABLE tokens IS '用户 API 令牌表 (sk- 格式)';
COMMENT ON COLUMN tokens.key IS 'API Key (格式: sk-{32位hex})';
COMMENT ON COLUMN tokens.remain_quota IS '剩余配额';
COMMENT ON COLUMN tokens.unlimited_quota IS '无限配额标志';
COMMENT ON COLUMN tokens.model_limit IS '模型白名单 (JSON数组)';

-- ============================================================
-- 4. 调用日志表 (logs)
-- ============================================================
CREATE TABLE IF NOT EXISTS logs (
    id                  BIGSERIAL       PRIMARY KEY,
    user_id             INTEGER         NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    channel_id          INTEGER         DEFAULT NULL REFERENCES channels(id) ON DELETE SET NULL,
    model_name          VARCHAR(255)    NOT NULL DEFAULT '',
    quota               BIGINT          NOT NULL DEFAULT 0,
    content             TEXT            DEFAULT NULL,
    request_id          VARCHAR(64)     NOT NULL DEFAULT '',
    trace               TEXT            DEFAULT NULL,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    type                SMALLINT        NOT NULL DEFAULT 1,
    is_stream           BOOLEAN         NOT NULL DEFAULT FALSE,
    original_model_name VARCHAR(255)    NOT NULL DEFAULT '',
    "group"             VARCHAR(64)     NOT NULL DEFAULT '',
    prompt_tokens       INTEGER         NOT NULL DEFAULT 0,
    completion_tokens   INTEGER         NOT NULL DEFAULT 0,
    total_tokens        INTEGER         NOT NULL DEFAULT 0,

    -- 类型约束: 1=文本, 2=图像, 3=音频, 4=嵌入
    CONSTRAINT chk_logs_type CHECK (type IN (1, 2, 3, 4)),
    -- Token 数非负
    CONSTRAINT chk_logs_tokens CHECK (prompt_tokens >= 0 AND completion_tokens >= 0 AND total_tokens >= 0)
);

COMMENT ON TABLE logs IS 'API 调用日志表 (高频写入, 建议定期归档/分区)';
COMMENT ON COLUMN logs.quota IS '本次调用消耗的配额';
COMMENT ON COLUMN logs.type IS '日志类型: 1=文本, 2=图像, 3=音频, 4=嵌入';
COMMENT ON COLUMN logs.is_stream IS '是否流式请求';
COMMENT ON COLUMN logs.content IS '请求/响应内容摘要 (截断至2000字符)';

-- 日志表分区策略 (可选, 按月分区)
-- CREATE TABLE logs_y2026m05 PARTITION OF logs
--     FOR VALUES FROM ('2026-05-01') TO ('2026-06-01');

-- ============================================================
-- 5. 能力矩阵表 (abilities) — 复合主键
-- ============================================================
CREATE TABLE IF NOT EXISTS abilities (
    "group"     VARCHAR(64)     NOT NULL,
    model       VARCHAR(255)    NOT NULL,
    channel_id  INTEGER         NOT NULL REFERENCES channels(id) ON DELETE CASCADE,
    enabled     BOOLEAN         NOT NULL DEFAULT TRUE,
    priority    BIGINT          NOT NULL DEFAULT 0,
    weight      INTEGER         NOT NULL DEFAULT 0,
    tag         VARCHAR(255)    DEFAULT NULL,

    PRIMARY KEY ("group", model, channel_id)
);

COMMENT ON TABLE abilities IS '能力矩阵: 分组×模型×渠道的映射关系';
COMMENT ON COLUMN abilities."group" IS '用户分组名 (与 users.group / tokens.group 对应)';
COMMENT ON COLUMN abilities.priority IS '渠道优先级 (用于排序)';
COMMENT ON COLUMN abilities.weight IS '渠道权重 (同优先级加权随机)';

-- ============================================================
-- 6. 系统配置表 (options) — Key-Value 存储
-- ============================================================
CREATE TABLE IF NOT EXISTS options (
    "key"   VARCHAR(128)    PRIMARY KEY,
    value   TEXT            NOT NULL DEFAULT ''
);

COMMENT ON TABLE options IS '系统全局配置 (Key-Value 存储)';
COMMENT ON COLUMN options."key" IS '配置键名 (如 SetupCompleted, Ratio_gpt-4, RegisterEnabled)';

-- ============================================================
-- 7. 兑换码表 (redemptions)
-- ============================================================
CREATE TABLE IF NOT EXISTS redemptions (
    id              SERIAL          PRIMARY KEY,
    user_id         INTEGER         DEFAULT NULL REFERENCES users(id) ON DELETE SET NULL,
    key             VARCHAR(32)     NOT NULL UNIQUE,
    status          SMALLINT        NOT NULL DEFAULT 1,
    token           VARCHAR(255)    NOT NULL UNIQUE,
    created_time    TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    redeemed_time   TIMESTAMPTZ     DEFAULT NULL,
    count           INTEGER         NOT NULL DEFAULT 1,
    quota           BIGINT          NOT NULL DEFAULT 0,

    -- 状态约束: 1=可用, 2=已使用, 3=已禁用
    CONSTRAINT chk_redemptions_status CHECK (status IN (1, 2, 3)),
    CONSTRAINT chk_redemptions_quota CHECK (quota >= 0),
    CONSTRAINT chk_redemptions_count CHECK (count >= 1)
);

COMMENT ON TABLE redemptions IS '兑换码/充值码表';
COMMENT ON COLUMN redemptions.key IS '兑换码';
COMMENT ON COLUMN redemptions.token IS '兑换后生成的 API Token Key';
COMMENT ON COLUMN redemptions.quota IS '兑换码包含的配额';

-- ============================================================
-- 8. 订阅表 (subscriptions)
-- ============================================================
CREATE TABLE IF NOT EXISTS subscriptions (
    id          SERIAL          PRIMARY KEY,
    user_id     INTEGER         NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    product_id  VARCHAR(64)     NOT NULL DEFAULT '',
    status      SMALLINT        NOT NULL DEFAULT 1,
    start_at    TIMESTAMPTZ     DEFAULT NULL,
    end_at      TIMESTAMPTZ     DEFAULT NULL,
    cancel_at   TIMESTAMPTZ     DEFAULT NULL,
    trial_at    TIMESTAMPTZ     DEFAULT NULL,
    quota       INTEGER         NOT NULL DEFAULT 0,
    auto_renew  BOOLEAN         NOT NULL DEFAULT TRUE,

    -- 状态约束: 1=活跃, 2=已取消, 3=已过期, 4=试用中
    CONSTRAINT chk_subscriptions_status CHECK (status IN (1, 2, 3, 4)),
    CONSTRAINT chk_subscriptions_quota CHECK (quota >= 0)
);

COMMENT ON TABLE subscriptions IS '用户订阅表';

-- ============================================================
-- 9. 签到表 (checkins)
-- ============================================================
CREATE TABLE IF NOT EXISTS checkins (
    id          SERIAL          PRIMARY KEY,
    user_id     INTEGER         NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    quota       INTEGER         NOT NULL DEFAULT 0,
    created_at  TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    -- 唯一约束: 每天只能签到一次
    CONSTRAINT uq_checkins_user_date UNIQUE (user_id, DATE(created_at)),
    CONSTRAINT chk_checkins_quota CHECK (quota >= 0)
);

COMMENT ON TABLE checkins IS '用户签到记录表';

-- ============================================================
-- 10. 充值记录表 (topups)
-- ============================================================
CREATE TABLE IF NOT EXISTS topups (
    id              SERIAL          PRIMARY KEY,
    user_id         INTEGER         NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    amount          DOUBLE PRECISION NOT NULL DEFAULT 0,
    quota           INTEGER         NOT NULL DEFAULT 0,
    status          SMALLINT        NOT NULL DEFAULT 0,
    payment_id      VARCHAR(128)    NOT NULL DEFAULT '',
    payment_method  VARCHAR(32)     NOT NULL DEFAULT '',
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    paid_at         TIMESTAMPTZ     DEFAULT NULL,

    -- 状态约束: 0=待支付, 1=已支付, 2=已取消, 3=已退款
    CONSTRAINT chk_topups_status CHECK (status IN (0, 1, 2, 3)),
    CONSTRAINT chk_topups_quota CHECK (quota >= 0)
);

COMMENT ON TABLE topups IS '用户充值记录表';
COMMENT ON COLUMN topups.payment_method IS '支付方式: epay, alipay, wechat, stripe 等';

-- ============================================================
-- 11. OAuth 绑定表 (oauth_bindings)
-- ============================================================
CREATE TABLE IF NOT EXISTS oauth_bindings (
    id          SERIAL          PRIMARY KEY,
    user_id     INTEGER         NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    provider    VARCHAR(32)     NOT NULL,
    provider_id VARCHAR(128)    NOT NULL,
    created_at  TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_oauth_provider UNIQUE (provider, provider_id)
);

COMMENT ON TABLE oauth_bindings IS '第三方 OAuth 登录绑定表';
COMMENT ON COLUMN oauth_bindings.provider IS '提供商: github, discord, wechat, telegram, oidc, linux_do';

-- ============================================================
-- 12. WebAuthn Passkey 表 (passkeys)
-- ============================================================
CREATE TABLE IF NOT EXISTS passkeys (
    id              SERIAL          PRIMARY KEY,
    user_id         INTEGER         NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name            VARCHAR(128)    NOT NULL DEFAULT '',
    credential_id   VARCHAR(255)    NOT NULL,
    public_key      TEXT            DEFAULT NULL,
    counter         INTEGER         NOT NULL DEFAULT 0,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_passkeys_credential UNIQUE (credential_id),
    CONSTRAINT chk_passkeys_counter CHECK (counter >= 0)
);

COMMENT ON TABLE passkeys IS 'WebAuthn Passkey 凭证表';

-- ============================================================
-- 13. 2FA 密钥表 (twofa_secrets)
-- ============================================================
CREATE TABLE IF NOT EXISTS twofa_secrets (
    id          SERIAL          PRIMARY KEY,
    user_id     INTEGER         NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    secret      VARCHAR(128)    NOT NULL,
    enabled     BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE twofa_secrets IS 'TOTP 双因素认证密钥表';

-- ============================================================
-- 14. 价格表 (pricing)
-- ============================================================
CREATE TABLE IF NOT EXISTS pricing (
    id          SERIAL          PRIMARY KEY,
    model_name  VARCHAR(128)    NOT NULL DEFAULT '',
    unit_price  DOUBLE PRECISION NOT NULL DEFAULT 0,
    currency    VARCHAR(10)     NOT NULL DEFAULT 'USD',
    type        VARCHAR(32)     NOT NULL DEFAULT 'per_token',
    created_at  TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_pricing_unit_price CHECK (unit_price >= 0)
);

COMMENT ON TABLE pricing IS '模型价格配置表';
COMMENT ON COLUMN pricing.type IS '计费类型: per_token, per_call, per_image 等';

-- ============================================================
-- 15. 预填分组表 (prefill_groups)
-- ============================================================
CREATE TABLE IF NOT EXISTS prefill_groups (
    id          SERIAL          PRIMARY KEY,
    name        VARCHAR(128)    NOT NULL DEFAULT '',
    models      JSONB           DEFAULT NULL,
    created_at  TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_prefill_groups_name UNIQUE (name)
);

COMMENT ON TABLE prefill_groups IS '模型预填分组模板';
COMMENT ON COLUMN prefill_groups.models IS '模型列表 (JSONB数组)';

-- ============================================================
-- 16. 缺失模型记录表 (missing_models)
-- ============================================================
CREATE TABLE IF NOT EXISTS missing_models (
    id          SERIAL          PRIMARY KEY,
    model_name  VARCHAR(128)    NOT NULL DEFAULT '',
    channel_id  INTEGER         NOT NULL REFERENCES channels(id) ON DELETE CASCADE,
    created_at  TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE missing_models IS '渠道测试时发现的缺失模型记录';

-- ============================================================
-- 17. 性能指标表 (perf_metrics)
-- ============================================================
CREATE TABLE IF NOT EXISTS perf_metrics (
    id              BIGSERIAL       PRIMARY KEY,
    metric_name     VARCHAR(128)    NOT NULL,
    metric_value    TEXT            NOT NULL,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE perf_metrics IS '系统性能指标记录表';
COMMENT ON COLUMN perf_metrics.metric_name IS '指标名称: qps, latency_p50, latency_p99, channel_test_time 等';
COMMENT ON COLUMN perf_metrics.metric_value IS '指标值 (JSON 或纯文本)';

-- ============================================================
-- 18. 供应商元数据表 (vendor_meta)
-- ============================================================
CREATE TABLE IF NOT EXISTS vendor_meta (
    id          SERIAL          PRIMARY KEY,
    vendor_name VARCHAR(128)    NOT NULL DEFAULT '',
    vendor_type VARCHAR(32)     NOT NULL DEFAULT '',
    base_url    VARCHAR(512)    NOT NULL DEFAULT '',
    config      TEXT            DEFAULT NULL,
    created_at  TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_vendor_meta_name UNIQUE (vendor_name)
);

COMMENT ON TABLE vendor_meta IS 'AI 供应商元数据表';
COMMENT ON COLUMN vendor_meta.config IS '供应商配置 (JSON)';
```

### 3.1 索引脚本

```sql
-- ============================================================
-- 索引创建脚本
-- ============================================================

-- --- users 表索引 ---
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_display_name ON users(display_name);
CREATE INDEX IF NOT EXISTS idx_users_inviter_id ON users(inviter_id);
CREATE INDEX IF NOT EXISTS idx_users_aff_code ON users(aff_code);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_access_token ON users(access_token);

-- --- channels 表索引 ---
CREATE INDEX IF NOT EXISTS idx_channels_name ON channels(name);
CREATE INDEX IF NOT EXISTS idx_channels_status ON channels(status);
CREATE INDEX IF NOT EXISTS idx_channels_type ON channels(type);
CREATE INDEX IF NOT EXISTS idx_channels_tag ON channels(tag);
CREATE INDEX IF NOT EXISTS idx_channels_priority ON channels(priority DESC);
CREATE INDEX IF NOT EXISTS idx_channels_group ON channels("group");

-- --- tokens 表索引 ---
CREATE INDEX IF NOT EXISTS idx_tokens_user_id ON tokens(user_id);
CREATE INDEX IF NOT EXISTS idx_tokens_key ON tokens(key);
CREATE INDEX IF NOT EXISTS idx_tokens_status ON tokens(status);
CREATE INDEX IF NOT EXISTS idx_tokens_expired_time ON tokens(expired_time);

-- --- logs 表索引 (高频查询) ---
CREATE INDEX IF NOT EXISTS idx_logs_user_id ON logs(user_id);
CREATE INDEX IF NOT EXISTS idx_logs_channel_id ON logs(channel_id);
CREATE INDEX IF NOT EXISTS idx_logs_created_at ON logs(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_logs_model_name ON logs(model_name);
CREATE INDEX IF NOT EXISTS idx_logs_type ON logs(type);
CREATE INDEX IF NOT EXISTS idx_logs_request_id ON logs(request_id);
-- 复合索引: 用户 + 时间 (最常用的日志查询场景)
CREATE INDEX IF NOT EXISTS idx_logs_user_created ON logs(user_id, created_at DESC);
-- 复合索引: 渠道 + 时间
CREATE INDEX IF NOT EXISTS idx_logs_channel_created ON logs(channel_id, created_at DESC);

-- --- abilities 表索引 (核心路由查询) ---
-- abilities 使用复合主键 (group, model, channel_id)，已包含高效查找
CREATE INDEX IF NOT EXISTS idx_abilities_model ON abilities(model);
CREATE INDEX IF NOT EXISTS idx_abilities_channel_id ON abilities(channel_id);
CREATE INDEX IF NOT EXISTS idx_abilities_enabled ON abilities(enabled);
-- 复合索引: 路由查询核心索引
CREATE INDEX IF NOT EXISTS idx_abilities_route ON abilities(model, "group", enabled) WHERE enabled = TRUE;

-- --- redemptions 表索引 ---
CREATE INDEX IF NOT EXISTS idx_redemptions_key ON redemptions(key);
CREATE INDEX IF NOT EXISTS idx_redemptions_status ON redemptions(status);
CREATE INDEX IF NOT EXISTS idx_redemptions_user_id ON redemptions(user_id);

-- --- subscriptions 表索引 ---
CREATE INDEX IF NOT EXISTS idx_subscriptions_user_id ON subscriptions(user_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status);

-- --- checkins 表索引 ---
CREATE INDEX IF NOT EXISTS idx_checkins_user_id ON checkins(user_id);
CREATE INDEX IF NOT EXISTS idx_checkins_created_at ON checkins(created_at DESC);

-- --- topups 表索引 ---
CREATE INDEX IF NOT EXISTS idx_topups_user_id ON topups(user_id);
CREATE INDEX IF NOT EXISTS idx_topups_status ON topups(status);
CREATE INDEX IF NOT EXISTS idx_topups_payment_id ON topups(payment_id);

-- --- oauth_bindings 表索引 ---
CREATE INDEX IF NOT EXISTS idx_oauth_user_id ON oauth_bindings(user_id);

-- --- pricing 表索引 ---
CREATE INDEX IF NOT EXISTS idx_pricing_model_name ON pricing(model_name);

-- --- passkeys 表索引 ---
CREATE INDEX IF NOT EXISTS idx_passkeys_user_id ON passkeys(user_id);
CREATE INDEX IF NOT EXISTS idx_passkeys_credential_id ON passkeys(credential_id);

-- --- twofa_secrets 表索引 ---
-- user_id 已有 UNIQUE 约束，无需额外索引

-- --- missing_models 表索引 ---
CREATE INDEX IF NOT EXISTS idx_missing_models_model_name ON missing_models(model_name);
CREATE INDEX IF NOT EXISTS idx_missing_models_channel_id ON missing_models(channel_id);

-- --- perf_metrics 表索引 ---
CREATE INDEX IF NOT EXISTS idx_perf_metrics_metric_name ON perf_metrics(metric_name);
CREATE INDEX IF NOT EXISTS idx_perf_metrics_created_at ON perf_metrics(created_at DESC);

-- --- vendor_meta 表索引 ---
CREATE INDEX IF NOT EXISTS idx_vendor_meta_vendor_name ON vendor_meta(vendor_name);
```

### 3.2 初始化数据

```sql
-- ============================================================
-- 初始化数据
-- ============================================================

-- 系统配置初始值
INSERT INTO options ("key", value) VALUES
    ('SetupCompleted', 'false'),
    ('RegisterEnabled', 'true'),
    ('PasswordLoginEnabled', 'true'),
    ('PasswordRegisterEnabled', 'true'),
    ('NewUserQuota', '0'),
    ('GitHubOAuthEnabled', 'false'),
    ('DiscordOAuthEnabled', 'false'),
    ('WeChatOAuthEnabled', 'false'),
    ('TelegramOAuthEnabled', 'false'),
    ('OIDCOAuthEnabled', 'false'),
    ('LinuxDoOAuthEnabled', 'false'),
    ('Announcement', ''),
    ('TopUpLink', ''),
    ('ChatLink', ''),
    ('DefaultTheme', 'default'),
    ('UserAgreement', ''),
    ('PrivacyPolicy', ''),
    ('Notice', ''),
    ('QuotaPerUnit', '500000'),
    ('DisplayInCurrencyEnabled', 'true'),
    ('DisplayInCurrencyType', 'USD'),
    ('StreamQueueTimeout', '300'),
    ('ModelTestEnabled', 'false'),
    ('ChannelTestEnabled', 'false'),
    ('ChannelAutoBanEnabled', 'true'),
    ('ChannelBanThreshold', '10')
ON CONFLICT ("key") DO NOTHING;

-- 默认模型倍率
INSERT INTO options ("key", value) VALUES
    ('Ratio_gpt-3.5-turbo', '1'),
    ('Ratio_gpt-3.5-turbo-16k', '1.125'),
    ('Ratio_gpt-4', '15'),
    ('Ratio_gpt-4o', '5'),
    ('Ratio_gpt-4o-mini', '0.625'),
    ('Ratio_claude-3-haiku', '1.25'),
    ('Ratio_claude-3-sonnet', '3'),
    ('Ratio_claude-3-opus', '15'),
    ('Ratio_claude-3.5-sonnet', '5'),
    ('Ratio_gemini-pro', '1'),
    ('Ratio_text-embedding-3-small', '0.25'),
    ('Ratio_text-embedding-3-large', '0.25'),
    ('Ratio_text-embedding-ada-002', '0.25'),
    ('Ratio_embedding', '0.25'),
    ('Ratio_dall-e-3', '0'),
    ('Ratio_midjourney', '0'),
    ('Ratio_suno', '0'),
    ('Ratio_gpt-4-turbo', '5'),
    ('Ratio_deepseek-chat', '0.5'),
    ('Ratio_deepseek-reasoner', '1'),
    ('Ratio_qwen-turbo', '0.25'),
    ('Ratio_qwen-plus', '1'),
    ('Ratio_qwen-max', '2'),
    ('Ratio_ernie-bot', '1'),
    ('Ratio_ernie-bot-4', '5'),
    ('Ratio_glm-4', '1'),
    ('Ratio_glm-4v', '1'),
    ('Ratio_moonshot-v1-8k', '1'),
    ('Ratio_moonshot-v1-32k', '2'),
    ('Ratio_moonshot-v1-128k', '5')
ON CONFLICT ("key") DO NOTHING;
```

---

## 4. 数据库关系图

### 4.1 实体关系总览

```
                            ┌─────────────────────────────┐
                            │         options             │
                            │  (系统配置 Key-Value)        │
                            └─────────────────────────────┘
                                      ▲
                                      │  读取配置
                                      │
┌──────────┐    1:N     ┌──────────┐    1:N    ┌──────────┐
│  users   │───────────→│  tokens  │          │ channels │
│ (用户)   │            │ (令牌)   │           │ (渠道)   │
└────┬─────┘            └──────────┘           └────┬─────┘
     │                                              │
     │ 1:N                                          │ 1:N
     ├─────────────→ ┌──────────┐ ←─────────────────┤
     │               │ abilities│                    │
     │               │ (能力矩阵)│                    │
     │               └──────────┘                    │
     │                    ▲                          │
     │                    │ N:1                      │
     │ 1:N               │                          │
     ├─────────────→ ┌──────────┐                   │
     │               │   logs   │←──────────────────┘
     │               │  (日志)  │
     │               └──────────┘
     │
     │ 1:N
     ├─────────→ ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
     │           │  redemptions │  │    topups    │  │ subscriptions│
     │           │  (兑换码)    │  │  (充值)      │  │   (订阅)     │
     │           └──────────────┘  └──────────────┘  └──────────────┘
     │
     │ 1:N                        1:1
     ├─────────→ ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
     │           │  checkins    │  │ twofa_secrets│  │ oauth_bindings│
     │           │  (签到)      │  │  (2FA)       │  │  (OAuth)     │
     │           └──────────────┘  └──────────────┘  └──────────────┘
     │
     │ 1:N
     ├─────────→ ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
     │           │  passkeys    │  │ prefill_groups│ │ missing_models│
     │           │ (WebAuthn)   │  │ (预填分组)   │  │ (缺失模型)   │
     │           └──────────────┘  └──────────────┘  └──────┬───────┘
     │                                                      │
     │                                                  N:1 │
     │                                                      │
     │               ┌──────────────┐                  ┌────▼─────┐
     └──────────────→│  perf_metrics│                  │ channels │
                     │ (性能指标)   │                  └──────────┘
                     └──────────────┘

自引用:
  users.inviter_id → users.id (推广关系)

外部依赖:
  channels → 上游 AI 供应商 (HTTP 调用, 非数据库关系)
```

### 4.2 核心关系说明

| 关系 | 类型 | 说明 |
|------|------|------|
| users → tokens | 1:N (CASCADE) | 一个用户可创建多个 API Token；用户删除时级联删除 Token |
| users → logs | 1:N (CASCADE) | 一个用户有多条调用日志；用户删除时级联删除日志 |
| users → inviter_id | N:1 (SET NULL) | 推广人自引用；推广人删除时设为 NULL |
| channels → abilities | 1:N (CASCADE) | 一个渠道可服务于多个分组/模型组合 |
| channels → logs | 1:N (SET NULL) | 日志记录使用的渠道；渠道删除时设为 NULL |
| tokens → abilities | 间接关联 | 通过 tokens.group → abilities.group 间接关联 |
| users → redemptions | 1:N (SET NULL) | 可选绑定用户；用户删除时设为 NULL |
| users → twofa_secrets | 1:1 (CASCADE) | 每个用户最多一个 2FA 密钥 |

### 4.3 能力矩阵路由机制

```
请求: model=gpt-4, user.group="vip"

查询路径:
  abilities WHERE model='gpt-4' AND group='vip' AND enabled=TRUE
    → JOIN channels ON abilities.channel_id=channels.id
    → WHERE channels.status=1
    → ORDER BY abilities.priority DESC, abilities.weight DESC,
               channels.priority DESC, channels.weight DESC
    → LIMIT 1

  若 group='vip' 无结果:
    abilities WHERE model='gpt-4' AND group='' AND enabled=TRUE
    → (同上，group='' 表示全局可用)
```

---

## 5. 数据字典

### 5.1 users — 用户账户表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | 用户唯一标识 |
| username | VARCHAR(20) | NOT NULL, UNIQUE | — | 登录用户名 (最大20字符) |
| password | VARCHAR(255) | NOT NULL | — | bcrypt 哈希密码 |
| display_name | VARCHAR(20) | NOT NULL | '' | 显示名称 |
| role | SMALLINT | NOT NULL, CHECK | 1 | 角色: 1=用户, 10=管理员, 100=Root |
| status | SMALLINT | NOT NULL, CHECK | 1 | 状态: 0=禁用, 1=启用 |
| email | VARCHAR(50) | NOT NULL | '' | 邮箱地址 |
| github_id | VARCHAR(64) | NOT NULL | '' | GitHub OAuth ID |
| discord_id | VARCHAR(64) | NOT NULL | '' | Discord OAuth ID |
| oidc_id | VARCHAR(64) | NOT NULL | '' | OIDC OAuth ID |
| wechat_id | VARCHAR(64) | NOT NULL | '' | 微信 OAuth ID |
| telegram_id | VARCHAR(64) | NOT NULL | '' | Telegram OAuth ID |
| linux_do_id | VARCHAR(64) | NOT NULL | '' | LinuxDo OAuth ID |
| quota | INTEGER | NOT NULL, CHECK ≥0 | 0 | 可用配额 |
| used_quota | INTEGER | NOT NULL, CHECK ≥0 | 0 | 已消耗配额 |
| request_count | INTEGER | NOT NULL, CHECK ≥0 | 0 | 总请求次数 |
| group | VARCHAR(64) | NOT NULL | 'default' | 用户分组 |
| aff_code | CHAR(8) | UNIQUE | '' | 推广码 |
| aff_count | INTEGER | NOT NULL, DEFAULT 0 | 0 | 推广人数 |
| aff_quota | INTEGER | NOT NULL, DEFAULT 0 | 0 | 可转账推广配额 |
| aff_history_quota | INTEGER | NOT NULL, DEFAULT 0 | 0 | 历史累计推广配额 |
| inviter_id | INTEGER | FK → users(id), ON DELETE SET NULL | NULL | 推广人 ID |
| access_token | CHAR(32) | UNIQUE | NULL | 管理 API 访问令牌 |
| setting | TEXT | — | NULL | 用户设置 (JSON) |
| remark | VARCHAR(255) | NOT NULL | '' | 管理员备注 |
| stripe_customer | VARCHAR(64) | NOT NULL | '' | Stripe 客户 ID |
| created_at | TIMESTAMPTZ | NOT NULL | NOW() | 创建时间 |
| last_login_at | TIMESTAMPTZ | — | NULL | 最后登录时间 |
| deleted_at | TIMESTAMPTZ | — | NULL | 软删除时间 |

### 5.2 channels — 上游渠道表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | 渠道唯一标识 |
| type | SMALLINT | NOT NULL, DEFAULT 1 | 1 | 渠道类型: 1=OpenAI, 14=Claude, 15=Gemini... |
| key | TEXT | NOT NULL | — | API Key (多 Key 换行分隔) |
| openai_organization | VARCHAR(255) | NOT NULL | '' | OpenAI 组织 ID |
| test_model | VARCHAR(128) | NOT NULL | '' | 测试用模型名 |
| status | SMALLINT | NOT NULL, CHECK | 1 | 1=启用, 2=禁用, 3=手动禁用, 4=自动禁用 |
| name | VARCHAR(255) | NOT NULL | '' | 渠道名称 |
| weight | INTEGER | NOT NULL | 0 | 权重 (同优先级随机分配) |
| created_time | TIMESTAMPTZ | NOT NULL | NOW() | 创建时间 |
| test_time | TIMESTAMPTZ | — | NULL | 最后测试时间 |
| response_time | INTEGER | NOT NULL, CHECK ≥0 | 0 | 响应时间 (ms) |
| base_url | VARCHAR(1024) | NOT NULL | '' | 上游基础 URL |
| other | TEXT | — | NULL | 其他信息 (JSON) |
| balance | DOUBLE PRECISION | NOT NULL | 0 | 上游账户余额 |
| balance_updated_time | TIMESTAMPTZ | — | NULL | 余额更新时间 |
| models | TEXT | NOT NULL | '' | 支持的模型 (换行分隔) |
| group | VARCHAR(64) | NOT NULL | 'default' | 渠道分组 |
| used_quota | BIGINT | NOT NULL | 0 | 渠道累计消耗配额 |
| model_mapping | TEXT | — | NULL | 模型映射 (JSON) |
| status_code_mapping | VARCHAR(1024) | NOT NULL | '' | 状态码映射 (JSON) |
| priority | BIGINT | NOT NULL | 0 | 优先级 (越高越先匹配) |
| auto_ban | SMALLINT | NOT NULL, CHECK | 1 | 自动封禁开关 |
| other_info | TEXT | — | NULL | 额外信息 (JSON) |
| tag | VARCHAR(255) | — | NULL | 标签 |
| setting | TEXT | — | NULL | 渠道设置 (JSON) |
| param_override | TEXT | — | NULL | 参数覆盖 (JSON) |
| header_override | TEXT | — | NULL | 请求头覆盖 (JSON) |
| remark | VARCHAR(255) | NOT NULL | '' | 备注 |
| channel_info | JSONB | — | NULL | 渠道额外信息 (JSONB) |
| settings | TEXT | — | NULL | 渠道额外设置 (JSON) |

### 5.3 tokens — API 令牌表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | 令牌唯一标识 |
| user_id | INTEGER | NOT NULL, FK → users(id) CASCADE | — | 所属用户 |
| name | VARCHAR(255) | NOT NULL | '' | 令牌名称 |
| key | VARCHAR(64) | NOT NULL, UNIQUE | — | API Key (sk-{32hex}) |
| created_time | TIMESTAMPTZ | NOT NULL | NOW() | 创建时间 |
| accessed_time | TIMESTAMPTZ | — | NULL | 最后访问时间 |
| expired_time | TIMESTAMPTZ | — | NULL | 过期时间 (NULL=永不过期) |
| remain_quota | BIGINT | NOT NULL, CHECK ≥0 | 0 | 剩余配额 |
| unlimited_quota | BOOLEAN | NOT NULL | FALSE | 无限配额 |
| status | SMALLINT | NOT NULL, CHECK | 1 | 1=启用, 2=禁用 |
| group | VARCHAR(64) | NOT NULL | 'default' | 令牌分组 |
| model_limit | TEXT | — | NULL | 模型白名单 (JSON数组) |
| used_quota | BIGINT | NOT NULL | 0 | 已使用配额 |
| fetch_time | TIMESTAMPTZ | — | NULL | 获取时间 |
| heartbeat_time | TIMESTAMPTZ | — | NULL | 心跳时间 |

### 5.4 logs — 调用日志表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | BIGSERIAL | PK | auto | 日志唯一标识 |
| user_id | INTEGER | NOT NULL, FK → users(id) CASCADE | — | 调用用户 |
| channel_id | INTEGER | FK → channels(id) SET NULL | NULL | 使用的渠道 |
| model_name | VARCHAR(255) | NOT NULL | '' | 请求的模型名 |
| quota | BIGINT | NOT NULL | 0 | 消耗配额 |
| content | TEXT | — | NULL | 请求/响应内容摘要 (截断2000) |
| request_id | VARCHAR(64) | NOT NULL | '' | 请求唯一 ID |
| trace | TEXT | — | NULL | 追踪信息 |
| created_at | TIMESTAMPTZ | NOT NULL | NOW() | 创建时间 |
| type | SMALLINT | NOT NULL, CHECK | 1 | 1=文本, 2=图像, 3=音频, 4=嵌入 |
| is_stream | BOOLEAN | NOT NULL | FALSE | 是否流式 |
| original_model_name | VARCHAR(255) | NOT NULL | '' | 原始模型名 (映射前) |
| group | VARCHAR(64) | NOT NULL | '' | 用户分组 |
| prompt_tokens | INTEGER | NOT NULL, CHECK ≥0 | 0 | Prompt token 数 |
| completion_tokens | INTEGER | NOT NULL, CHECK ≥0 | 0 | Completion token 数 |
| total_tokens | INTEGER | NOT NULL, CHECK ≥0 | 0 | 总 token 数 |

### 5.5 abilities — 能力矩阵表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| group | VARCHAR(64) | PK 组成部分 | — | 分组名 |
| model | VARCHAR(255) | PK 组成部分 | — | 模型名 |
| channel_id | INTEGER | PK 组成部分, FK → channels(id) CASCADE | — | 渠道 ID |
| enabled | BOOLEAN | NOT NULL | TRUE | 是否启用 |
| priority | BIGINT | NOT NULL | 0 | 优先级 |
| weight | INTEGER | NOT NULL | 0 | 权重 |
| tag | VARCHAR(255) | — | NULL | 标签 |

### 5.6 options — 系统配置表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| key | VARCHAR(128) | PK | — | 配置键名 |
| value | TEXT | NOT NULL | '' | 配置值 |

### 5.7 redemptions — 兑换码表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | — |
| user_id | INTEGER | FK → users(id) SET NULL | NULL | 指定用户 (可选) |
| key | VARCHAR(32) | NOT NULL, UNIQUE | — | 兑换码 |
| status | SMALLINT | NOT NULL, CHECK | 1 | 1=可用, 2=已使用, 3=已禁用 |
| token | VARCHAR(255) | NOT NULL, UNIQUE | — | 兑换后生成的 Token |
| created_time | TIMESTAMPTZ | NOT NULL | NOW() | 创建时间 |
| redeemed_time | TIMESTAMPTZ | — | NULL | 兑换时间 |
| count | INTEGER | NOT NULL, CHECK ≥1 | 1 | 可用次数 |
| quota | BIGINT | NOT NULL, CHECK ≥0 | 0 | 包含配额 |

### 5.8 subscriptions — 订阅表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | — |
| user_id | INTEGER | NOT NULL, FK → users(id) CASCADE | — | 所属用户 |
| product_id | VARCHAR(64) | NOT NULL | '' | 产品 ID |
| status | SMALLINT | NOT NULL, CHECK | 1 | 1=活跃, 2=取消, 3=过期, 4=试用 |
| start_at | TIMESTAMPTZ | — | NULL | 开始时间 |
| end_at | TIMESTAMPTZ | — | NULL | 结束时间 |
| cancel_at | TIMESTAMPTZ | — | NULL | 取消时间 |
| trial_at | TIMESTAMPTZ | — | NULL | 试用开始时间 |
| quota | INTEGER | NOT NULL, CHECK ≥0 | 0 | 订阅配额 |
| auto_renew | BOOLEAN | NOT NULL | TRUE | 自动续费 |

### 5.9 checkins — 签到表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | — |
| user_id | INTEGER | NOT NULL, FK → users(id) CASCADE | — | 用户 ID |
| quota | INTEGER | NOT NULL, CHECK ≥0 | 0 | 签到获得配额 |
| created_at | TIMESTAMPTZ | NOT NULL, UNIQUE(user_id, DATE) | NOW() | 签到时间 |

### 5.10 topups — 充值记录表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | — |
| user_id | INTEGER | NOT NULL, FK → users(id) CASCADE | — | 用户 ID |
| amount | DOUBLE PRECISION | NOT NULL | 0 | 充值金额 (元) |
| quota | INTEGER | NOT NULL, CHECK ≥0 | 0 | 获得配额 |
| status | SMALLINT | NOT NULL, CHECK | 0 | 0=待支付, 1=已支付, 2=取消, 3=退款 |
| payment_id | VARCHAR(128) | NOT NULL | '' | 支付平台订单号 |
| payment_method | VARCHAR(32) | NOT NULL | '' | 支付方式 |
| created_at | TIMESTAMPTZ | NOT NULL | NOW() | 创建时间 |
| paid_at | TIMESTAMPTZ | — | NULL | 支付完成时间 |

### 5.11 oauth_bindings — OAuth 绑定表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | — |
| user_id | INTEGER | NOT NULL, FK → users(id) CASCADE | — | 用户 ID |
| provider | VARCHAR(32) | NOT NULL | — | 提供商 |
| provider_id | VARCHAR(128) | NOT NULL | — | 第三方用户 ID |
| created_at | TIMESTAMPTZ | NOT NULL | NOW() | 绑定时间 |

### 5.12 passkeys — WebAuthn 凭证表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | — |
| user_id | INTEGER | NOT NULL, FK → users(id) CASCADE | — | 用户 ID |
| name | VARCHAR(128) | NOT NULL | '' | 设备名称 |
| credential_id | VARCHAR(255) | NOT NULL, UNIQUE | — | 凭证 ID |
| public_key | TEXT | — | NULL | 公钥 |
| counter | INTEGER | NOT NULL, CHECK ≥0 | 0 | 签名计数器 |
| created_at | TIMESTAMPTZ | NOT NULL | NOW() | 创建时间 |

### 5.13 twofa_secrets — 2FA 密钥表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | — |
| user_id | INTEGER | NOT NULL, UNIQUE, FK → users(id) CASCADE | — | 用户 ID |
| secret | VARCHAR(128) | NOT NULL | — | TOTP 密钥 |
| enabled | BOOLEAN | NOT NULL | FALSE | 是否启用 |
| created_at | TIMESTAMPTZ | NOT NULL | NOW() | 创建时间 |

### 5.14 pricing — 价格表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | — |
| model_name | VARCHAR(128) | NOT NULL | '' | 模型名称 |
| unit_price | DOUBLE PRECISION | NOT NULL, CHECK ≥0 | 0 | 单价 |
| currency | VARCHAR(10) | NOT NULL | 'USD' | 货币单位 |
| type | VARCHAR(32) | NOT NULL | 'per_token' | 计费类型 |
| created_at | TIMESTAMPTZ | NOT NULL | NOW() | 创建时间 |
| updated_at | TIMESTAMPTZ | NOT NULL | NOW() | 更新时间 |

### 5.15 prefill_groups — 预填分组表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | — |
| name | VARCHAR(128) | NOT NULL, UNIQUE | '' | 分组名称 |
| models | JSONB | — | NULL | 模型列表 (JSONB数组) |
| created_at | TIMESTAMPTZ | NOT NULL | NOW() | 创建时间 |

### 5.16 missing_models — 缺失模型记录表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | — |
| model_name | VARCHAR(128) | NOT NULL | '' | 缺失的模型名 |
| channel_id | INTEGER | NOT NULL, FK → channels(id) CASCADE | — | 渠道 ID |
| created_at | TIMESTAMPTZ | NOT NULL | NOW() | 发现时间 |

### 5.17 perf_metrics — 性能指标表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | BIGSERIAL | PK | auto | — |
| metric_name | VARCHAR(128) | NOT NULL | — | 指标名称 |
| metric_value | TEXT | NOT NULL | — | 指标值 |
| created_at | TIMESTAMPTZ | NOT NULL | NOW() | 记录时间 |

### 5.18 vendor_meta — 供应商元数据表

| 字段 | 类型 | 约束 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | SERIAL | PK | auto | — |
| vendor_name | VARCHAR(128) | NOT NULL, UNIQUE | '' | 供应商名称 |
| vendor_type | VARCHAR(32) | NOT NULL | '' | 供应商类型 |
| base_url | VARCHAR(512) | NOT NULL | '' | 基础 URL |
| config | TEXT | — | NULL | 配置 (JSON) |
| created_at | TIMESTAMPTZ | NOT NULL | NOW() | 创建时间 |

---

## 6. MVC 分层架构

### 6.1 架构模式

PeaseAI 采用 **精简 MVC 模式**，适配 PHP 8.3 原生实现，无重型框架依赖：

```
┌─────────────────────────────────────────────────────┐
│                    public/index.php                  │
│                     (单一入口)                        │
└────────────────────────┬────────────────────────────┘
                         │
           ┌─────────────▼─────────────┐
           │     Core\Router           │
           │   (路由匹配 + 分发)        │
           └─────────────┬─────────────┘
                         │
           ┌─────────────▼─────────────┐
           │   Middleware Pipeline     │
           │   CORS → RateLimit → Auth │
           └─────────────┬─────────────┘
                         │
           ┌─────────────▼─────────────┐
           │     Controllers           │
           │   (业务逻辑编排)           │
           └─────────────┬─────────────┘
                         │
           ┌─────────────▼─────────────┐
           │       Models              │
           │   (数据访问 + 业务规则)    │
           └─────────────┬─────────────┘
                         │
           ┌─────────────▼─────────────┐
           │    Database Layer         │
           │  Connection → QueryBuilder│
           └───────────────────────────┘
```

### 6.2 层次职责

| 层次 | 目录 | 职责 | 限制 |
|------|------|------|------|
| **入口层** | `public/index.php` | 加载自动加载、创建 Router、注册路由、启动分发 | 不含业务逻辑 |
| **路由层** | `Core/Router.php` | URL 模式匹配、参数提取、中间件绑定、请求分发 | 不处理请求体 |
| **中间件层** | `Middleware/` | 横切关注点：认证、CORS、限流、日志、统计 | 不访问数据库直查 |
| **控制器层** | `Controllers/` | 请求验证、业务编排、调用 Model、构造响应 | 不直接写 SQL |
| **模型层** | `Models/` | 数据访问封装、业务规则验证、类型转换 | 不处理 HTTP 请求 |
| **数据层** | `Database/` | 连接管理、查询构建、SQL 执行、结果映射 | 不含业务逻辑 |

### 6.3 控制器分类

| 控制器 | 路由前缀 | 认证方式 | 说明 |
|--------|---------|---------|------|
| `SystemController` | `/api/status`, `/api/setup` | 无 (公开) | 系统状态、初始化、公告 |
| `AuthController` | `/api/user/login`, `/api/user/register` | 无 (公开) | 登录注册、OAuth |
| `UserController` | `/api/users`, `/api/user/self` | Session/Access Token | 用户管理 (Admin) + 个人资料 |
| `TokenController` | `/api/tokens` | Session/Access Token | Token CRUD (用户自己的) |
| `ChannelController` | `/api/channels` | Session/Access Token (Admin) | 渠道管理 |
| `RelayController` | `/v1/*`, `/v1beta/*` | API Key (Token) | AI 网关中继 (核心) |

---

## 7. 核心组件设计

### 7.1 Router (路由器)

**文件**: `src/Core/Router.php`

```php
namespace NewApi\Core;

class Router
{
    private array $routes = [];          // 已注册路由
    private array $groups = [];          // 路由分组前缀栈
    private array $globalMiddleware = []; // 全局中间件

    // 路由注册
    public function get(string $path, callable $handler): void;
    public function post(string $path, callable $handler): void;
    public function put(string $path, callable $handler): void;
    public function delete(string $path, callable $handler): void;
    public function patch(string $path, callable $handler): void;
    public function any(string $path, callable $handler): void;

    // 路由分组
    public function group(string $prefix, callable $callback, array $middleware = []): void;

    // 请求分发
    public function dispatch(Request $request): Response;

    // 启动运行
    public function run(): void;
}
```

**设计要点**:

- **模式匹配**: 支持 `{param}` 参数化路径，如 `/api/user/{id}`
- **分组路由**: `group('/api', fn($r) => ...)` 自动添加前缀
- **中间件链**: 全局中间件 + 分组中间件 + 路由中间件，按序执行
- **短路机制**: 中间件返回 `Response` 则终止链，否则继续
- **启动入口**: `run()` 自动创建 `Request`、处理 CORS OPTIONS、捕获异常

**PHP 8.3 增强建议**:

```php
// 使用 typed class constants (PHP 8.3)
final class Router
{
    public const int METHOD_GET = 1;
    public const int METHOD_POST = 2;
    // ...

    // 使用 property hooks (PHP 8.4 前瞻)
    // private array $routes { get => $this->routes; }
}
```

### 7.2 Middleware (中间件)

**接口约定**:

```php
// 中间件签名
function (Request $request, callable $next): ?Response
```

**中间件清单**:

| 中间件 | 文件 | 职责 | 优先级 |
|--------|------|------|--------|
| `CORS` | `Middleware/CORS.php` | 设置跨域头，处理 OPTIONS 预检 | 1 (最先) |
| `RateLimit` | `Middleware/RateLimit.php` | IP 级滑动窗口限流 (60次/60秒) | 2 |
| `Auth::userAuth` | `Middleware/Auth.php` | Session / Access Token 认证 | 3 |
| `Auth::adminAuth` | `Middleware/Auth.php` | Admin 权限校验 | 3 |
| `Auth::tokenAuth` | `Middleware/Auth.php` | API Key 认证 (网关用) | 3 |
| `Logger` | `Middleware/Logger.php` | 请求/响应日志 | 4 |
| `Stats` | `Middleware/Stats.php` | 性能指标收集 | 5 |
| `RequestBody` | `Middleware/RequestBody.php` | JSON 请求体解析 | 2 |

### 7.3 Model (数据模型)

**基类**: `src/Database/Model.php`

```php
abstract class Model
{
    protected static string $table = '';          // 表名
    protected static string $primaryKey = 'id';   // 主键字段
    protected static array $fillable = [];        // 可填充字段
    protected static array $casts = [];           // 类型转换
    protected static array $defaults = [];        // 默认值
    protected static bool $isPostgres = true;     // PostgreSQL 标志

    protected array $attributes = [];             // 属性存储
    protected bool $exists = false;               // 是否已持久化

    // 数据操作
    public function fill(array $attributes): static;
    public function save(): bool;
    public function delete(): bool;

    // 查询操作
    public static function find(int $id): ?static;
    public static function findWhere(array $conditions): ?static;
    public static function findAll(array $conditions = [], string $orderBy = '', int $limit = 0, int $offset = 0): array;
    public static function count(array $conditions = []): int;
    public static function paginate(int $page = 1, int $perPage = 10, array $conditions = [], string $orderBy = ''): array;
    public static function where(string $column, mixed $value): array;
    public static function firstWhere(string $column, mixed $value): ?static;

    // 批量操作
    public static function create(array $attributes): static;
    public static function updateWhere(array $conditions, array $data): int;
    public static function deleteWhere(array $conditions): int;
    public static function increment(string $column, int $amount = 1, array $conditions = []): int;
    public static function decrement(string $column, int $amount = 1, array $conditions = []): int;
    public static function pluck(string $column, array $conditions = []): array;

    // 序列化
    public function toArray(): array;
    public function toJson(): string;
}
```

**类型转换 (Casts)**:

| Cast 类型 | 数据库 → PHP | PHP → 数据库 |
|-----------|-------------|-------------|
| `int/integer` | `(int)` | 保持 |
| `float/double` | `(float)` | 保持 |
| `bool/boolean` | `(bool)` | `(bool)` |
| `string` | `(string)` | 保持 |
| `array/json` | `json_decode()` | `json_encode()` |
| `timestamp` | `(int)` | `date()` |

### 7.4 QueryBuilder (查询构建器)

**文件**: `src/Database/QueryBuilder.php`

```php
class QueryBuilder
{
    // 链式方法
    public function select(array $columns): static;
    public function where(string $column, mixed $operator, mixed $value): static;
    public function whereIn(string $column, array $values): static;
    public function whereNotIn(string $column, array $values): static;
    public function whereLike(string $column, string $value): static;
    public function whereNull(string $column): static;
    public function whereNotNull(string $column): static;
    public function whereRaw(string $raw, array $bindings = []): static;
    public function join(string $table, string $first, string $operator = '=', ?string $second = null): static;
    public function leftJoin(string $table, string $first, string $operator = '=', ?string $second = null): static;
    public function orderBy(string $column, string $direction = 'ASC'): static;
    public function groupBy(string $column): static;
    public function limit(int $limit): static;
    public function offset(int $offset): static;

    // 执行方法
    public function get(): array;
    public function first(): array|false;
    public function find(int $id): array|false;
    public function count(string $column = '*'): int;
    public function exists(): bool;
    public function pluck(string $column): array;
    public function value(string $column): mixed;
    public function update(array $data): int;
    public function delete(): int;
    public function insert(array $data): int|string;
    public function insertMultiple(array $data): int;
}
```

### 7.5 Connection (数据库连接)

**文件**: `src/Database/Connection.php`

**关键特性**:

- **单例模式**: `getInstance()` 全局共享 PDO 实例
- **双连接**: 主连接 (`getInstance`) + 日志连接 (`getLogInstance`)，支持日志库分离
- **持久连接**: `PDO::ATTR_PERSISTENT => true` 启用连接池
- **参数化查询**: `PDO::ATTR_EMULATE_PREPARES => false` 禁用模拟预处理
- **事务支持**: `beginTransaction()`, `commit()`, `rollBack()`, `transaction(callable)`

**PHP 8.3 增强建议**:

```php
// 使用 readonly 属性 (PHP 8.3)
final readonly class ConnectionOptions {
    public function __construct(
        public string $dsn,
        public ?string $username = null,
        public ?string $password = null,
        public array $options = [],
    ) {}
}
```

### 7.6 Request / Response

**Request** (`src/Core/Request.php`):

- 封装 `$_GET`, `$_POST`, `$_SERVER`, `$_COOKIE`
- 路径提取: `getPath()` → 路由匹配
- 方法获取: `getMethod()` → HTTP 动词
- 参数绑定: `setParams()`, `getParams()`, `getParam()`
- 属性注入: `setAttribute()`, `getAttribute()` (中间件传递上下文)
- 请求头: `getHeader()`, `getHeaders()`
- 请求体: `getBody()` → JSON 解析

**Response** (`src/Core/Response.php`):

- `Response::json(mixed $data, int $status = 200)` → JSON 响应
- `Response::success(mixed $data, string $message)` → 成功格式
- `Response::error(string $message, int $status, int $code)` → 错误格式
- `Response::openaiError(...)` → OpenAI 兼容错误格式
- `Response::stream(callable $generator)` → SSE 流式响应

---

## 8. API 设计规范

### 8.1 RESTful 规范

| 原则 | 说明 | 示例 |
|------|------|------|
| **资源命名** | 使用复数名词，小写 + 连字符 | `/api/users`, `/api/channels` |
| **HTTP 动词** | GET=查询, POST=创建, PUT=全量更新, PATCH=部分更新, DELETE=删除 | `GET /api/users` |
| **嵌套资源** | 主子资源通过路径嵌套 | `/api/user/{id}/tokens` |
| **分页** | 查询参数 `?page=1&per_page=10` | 返回 `{items, total, page, per_page, last_page}` |
| **排序** | 查询参数 `?sort=-created_at` (前缀 - 表示降序) | — |
| **筛选** | 查询参数 `?status=1&keyword=xxx` | — |

### 8.2 响应格式

**成功响应**:

```json
{
    "success": true,
    "message": "success",
    "data": { ... }
}
```

**错误响应**:

```json
{
    "success": false,
    "message": "Error description",
    "code": 1001
}
```

**OpenAI 兼容错误**:

```json
{
    "error": {
        "message": "Incorrect API key provided.",
        "type": "invalid_request_error",
        "param": null,
        "code": "invalid_api_key"
    }
}
```

**分页响应**:

```json
{
    "success": true,
    "message": "success",
    "data": {
        "items": [...],
        "total": 150,
        "page": 1,
        "per_page": 10,
        "last_page": 15
    }
}
```

### 8.3 认证机制

| 认证方式 | 传输方式 | 适用场景 | 中间件 |
|---------|---------|---------|--------|
| **Session Cookie** | `Cookie: PHPSESSID=...` | Web 前端管理后台 | `Auth::userAuth()` |
| **Access Token** | `Authorization: Bearer {32hex}` | 管理 API 调用 | `Auth::userAuth()` |
| **API Key** | `Authorization: Bearer sk-{32hex}` 或 `x-api-key: sk-{32hex}` | AI 网关调用 | `Auth::tokenAuth()` |

**认证流程**:

```
1. 提取凭证:
   - Session: session_start() → $_SESSION['user_id']
   - Header: Authorization / x-api-key
   - Bearer 前缀剥离

2. 查找记录:
   - Token: tokens.key → Token 模型
   - Access Token: users.access_token → User 模型
   - Session: users.id → User 模型

3. 验证状态:
   - Token 是否启用 + 未过期
   - 用户是否启用 (status=1)
   - 角色是否满足 (Admin 端需要 role ≥ 10)

4. 注入上下文:
   - request.setAttribute('user_id', ...)
   - request.setAttribute('user', ...)
   - request.setAttribute('token', ...)  (网关调用)
```

### 8.4 错误码体系

| 错误码 | 常量 | 说明 | HTTP 状态 |
|--------|------|------|----------|
| 1 | `ERROR_CODE_AUTH_FAILED` | 认证失败 | 401 |
| 2 | `ERROR_CODE_TOKEN_INVALID` | Token 无效 | 401 |
| 3 | `ERROR_CODE_CHANNEL_DISABLED` | 渠道已禁用 | 403 |
| 4 | `ERROR_CODE_CHANNEL_NOT_FOUND` | 渠道不存在 | 404 |
| 5 | `ERROR_CODE_QUOTA_EXHAUSTED` | 配额耗尽 | 429 |
| 6 | `ERROR_CODE_MODEL_NOT_FOUND` | 模型不可用 | 404 |
| 7 | `ERROR_CODE_UPSTREAM_ERROR` | 上游服务错误 | 502 |
| 8 | `ERROR_CODE_RATE_LIMIT` | 速率限制 | 429 |
| 9 | `ERROR_CODE_INVALID_REQUEST` | 请求参数错误 | 400 |
| 10 | `ERROR_CODE_CONVERT_FAILED` | 格式转换失败 | 400 |

### 8.5 路由注册模式

```php
$router = new Router();

// 全局中间件
$router->use(Middleware\CORS::handle());
$router->use(Middleware\RequestBody::parse());

// 系统公开接口
$router->group('/api', function ($r) {
    $r->get('/status', [SystemController::class, 'status']);
    $r->get('/notice', [SystemController::class, 'notice']);
    $r->get('/setup', [SystemController::class, 'setupStatus']);
    $r->post('/setup', [SystemController::class, 'setup']);
});

// 认证接口
$router->group('/api/user', function ($r) {
    $r->post('/login', [AuthController::class, 'login']);
    $r->post('/register', [AuthController::class, 'register']);
    $r->post('/logout', [AuthController::class, 'logout']);
}, [Middleware\Auth::userAuth()]);

// AI 网关 (Token 认证)
$router->group('/v1', function ($r) {
    $r->get('/models', [RelayController::class, 'listModels']);
    $r->get('/models/{model}', [RelayController::class, 'retrieveModel']);
    $r->post('/chat/completions', fn($req) => (new RelayController())->relay($req, RELAY_MODE_CHAT_COMPLETIONS));
    $r->post('/completions', fn($req) => (new RelayController())->relay($req, RELAY_MODE_COMPLETIONS));
    $r->post('/embeddings', fn($req) => (new RelayController())->relay($req, RELAY_MODE_EMBEDDINGS));
    $r->post('/images/generations', fn($req) => (new RelayController())->relay($req, RELAY_MODE_IMAGES_GENERATIONS));
}, [Middleware\Auth::tokenAuth()]);

// 管理员接口
$router->group('/api', function ($r) {
    $r->group('/users', function ($r) {
        $r->get('', [UserController::class, 'list']);
        $r->post('', [UserController::class, 'create']);
        $r->get('/{id}', [UserController::class, 'get']);
        $r->put('/{id}', [UserController::class, 'update']);
        $r->delete('/{id}', [UserController::class, 'delete']);
    });
    $r->group('/channels', function ($r) {
        $r->get('', [ChannelController::class, 'list']);
        $r->post('', [ChannelController::class, 'create']);
        $r->get('/{id}', [ChannelController::class, 'get']);
        $r->put('/{id}', [ChannelController::class, 'update']);
        $r->delete('/{id}', [ChannelController::class, 'delete']);
        $r->get('/test/{id}', [ChannelController::class, 'test']);
        $r->get('/test', [ChannelController::class, 'testAll']);
    });
}, [Middleware\Auth::adminAuth()]);
```

---

## 9. 安全设计

### 9.1 SQL 注入防护

| 层级 | 防护措施 | 实现 |
|------|---------|------|
| **PDO 层** | 禁用模拟预处理 | `PDO::ATTR_EMULATE_PREPARES = false` |
| **查询构建** | 参数化查询 (所有值通过 `?` 绑定) | `prepare()` + `execute([$value])` |
| **模型层** | 列名自动引号 (PostgreSQL) | `"$col"` 双引号包裹列名 |
| **列名白名单** | `$fillable` 限制可写字段 | 不在白名单的字段被忽略 |

**关键代码**:

```php
// ✅ 安全: 参数化查询
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);

// ✅ 安全: QueryBuilder 参数绑定
$query->where('username', $username)->get();

// ⚠️ 注意: LIMIT/OFFSET 使用整数类型转换 (非字符串拼接)
// 现有代码使用 LIMIT $limit 直接拼接，需确保 $limit 是 int
```

### 9.2 XSS 防护

| 层级 | 防护措施 | 实现 |
|------|---------|------|
| **响应层** | 统一 JSON 响应 | `Content-Type: application/json` |
| **输出编码** | JSON 编码自动转义 | `json_encode()` 处理特殊字符 |
| **输入清洗** | 前端框架 (React/Vue) 自动编码 | 前端渲染层防护 |
| **内容存储** | 日志内容截断 | `substr($content, 0, 2000)` |

**未来增强**:

```php
// HTML 输出场景 (管理后台页面)
function html_escape(string $input): string {
    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// CSP 头
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'");
```

### 9.3 CSRF 防护

| 场景 | 防护方式 | 说明 |
|------|---------|------|
| **Session 请求** | PHP Session + SameSite Cookie | `session.cookie_samesite = Strict` |
| **API Token 请求** | 不需要 CSRF (非 Cookie 认证) | Bearer Token 天然免疫 |
| **表单提交** | 同步令牌 (Synchronizer Token) | 管理后台表单需添加 CSRF Token |

**Session 安全配置** (`php.ini`):

```ini
session.cookie_httponly = 1
session.cookie_secure = 1        ; 仅 HTTPS
session.cookie_samesite = Strict
session.use_strict_mode = 1
session.sid_length = 48          ; 增强熵值
```

### 9.4 其他安全措施

| 安全项 | 实现方式 | 状态 |
|--------|---------|------|
| **密码哈希** | `password_hash()` / `password_verify()` (bcrypt) | ✅ 已实现 |
| **密钥脱敏** | `mask_key()` 显示前6后4位 | ✅ 已实现 |
| **敏感配置过滤** | Options 中 secret/token/password 键自动隐藏 | ✅ 已实现 |
| **速率限制** | IP 级滑动窗口 (60次/60秒) | ✅ 已实现 |
| **HTTPS** | Nginx 层 SSL 终结 | ⏳ 部署时配置 |
| **2FA** | TOTP (twofa_secrets 表) | 📋 表已就绪 |
| **Passkey** | WebAuthn (passkeys 表) | 📋 表已就绪 |
| **软删除** | `deleted_at` 字段 | ✅ users 表已添加 |
| **审计日志** | logs 表完整记录 | ✅ 已实现 |

---

## 10. 性能与扩展设计

### 10.1 缓存策略

| 缓存对象 | 策略 | 实现 |
|---------|------|------|
| **Options 配置** | 内存静态缓存 | 首次加载后存于 `static::$cache` |
| **渠道选择结果** | 短期缓存 (TTL 30s) | 后续引入 Redis 支持 |
| **模型列表** | 随 abilities 查询实时获取 | 高频走索引，无需缓存 |
| **用户信息** | Session 自带缓存 | `$_SESSION` 存储 |

**Options 缓存模式**:

```php
class Option extends Model
{
    private static array $cache = [];

    public static function get(string $key, string $default = ''): string
    {
        if (!isset(self::$cache[$key])) {
            $option = self::firstWhere('key', $key);
            self::$cache[$key] = $option ? $option->value : $default;
        }
        return self::$cache[$key];
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
```

### 10.2 数据库优化

| 优化项 | 说明 |
|--------|------|
| **持久连接** | PDO `ATTR_PERSISTENT` 启用，复用 TCP 连接 |
| **连接池** | PHP-FPM `pm.max_children` 控制并发连接数 |
| **索引覆盖** | 高频查询使用复合索引减少回表 |
| **部分索引** | `abilities` 表使用 `WHERE enabled = TRUE` 部分索引 |
| **日志分区** | `logs` 和 `perf_metrics` 表支持按月分区 (可选) |
| **JSONB 索引** | `channel_info`, `models` (prefill_groups) 支持 GIN 索引 |
| **查询计划** | 使用 `EXPLAIN ANALYZE` 定期分析慢查询 |

### 10.3 水平扩展

```
┌──────────────────────────────────────────────────────────┐
│                     Nginx (负载均衡)                      │
│    ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│    │ PHP-FPM 1│  │ PHP-FPM 2│  │ PHP-FPM 3│  ...       │
│    └────┬─────┘  └────┬─────┘  └────┬─────┘             │
│         └──────────────┼──────────────┘                   │
│                        │                                  │
│              ┌─────────▼─────────┐                        │
│              │  PostgreSQL (RDS)  │                        │
│              │  主从复制 / 只读副本 │                        │
│              └───────────────────┘                        │
└──────────────────────────────────────────────────────────┘
```

### 10.4 PHP 8.3 特性利用

| 特性 | 使用场景 | 状态 |
|------|---------|------|
| **Typed Class Constants** | Router 方法常量、Model 状态常量 | ✅ 可在 constants.php 中增强 |
| **Dynamic Class Constant Fetch** | 配置驱动常量访问 | ✅ 可用于动态倍率查找 |
| **Readonly Anonymous Classes** | 不可变 DTO 对象 | 📋 可用于请求/响应 DTO |
| **JSON Validation** | `json_validate()` 验证 JSON 输入 | 📋 可用于 model_mapping 等 JSON 字段 |
| **Random Extension** | `Random\Randomizer` 替代 `random_bytes` | 📋 可用于 Token 生成 |
| **Deep-clone Readonly Properties** | 不可变配置对象 | 📋 可用于 Option 缓存 |

**代码示例 — PHP 8.3 JSON 验证**:

```php
// 验证模型映射 JSON
if (isset($data['model_mapping']) && !json_validate($data['model_mapping'])) {
    return Response::error('Invalid model_mapping JSON', 400);
}

// PHP 8.3 Randomizer
use Random\Randomizer;
$randomizer = new Randomizer();
$apiKey = 'sk-' . bin2hex($randomizer->getBytes(16));
```

### 10.5 监控与运维

| 监控项 | 实现 | 阈值 |
|--------|------|------|
| **系统健康** | `GET /api/status` | 返回版本、运行时间、DB 状态 |
| **性能指标** | `perf_metrics` 表 | QPS、P50/P99 延迟 |
| **渠道测试** | `GET /api/channel/test` | 响应时间、可用性 |
| **错误日志** | `error_log()` + Nginx 错误日志 | 5xx 错误率 < 1% |
| **日志归档** | `logs` 表按月分区 + 定期清理 | 保留 90 天 |
| **数据库备份** | PostgreSQL `pg_dump` 定时任务 | 每日全量 + WAL 增量 |

---

## 附录

### A. 渠道类型完整列表

| ID | 类型标识 | 供应商 | 默认 Base URL |
|----|---------|--------|---------------|
| 1 | OpenAI | OpenAI | https://api.openai.com |
| 3 | ZHIPU | 智谱AI | https://open.bigmodel.cn |
| 4 | BAIDU | 百度文心 | https://aip.baidubce.com |
| 11 | ALI | 阿里百炼 | https://dashscope.aliyuncs.com |
| 14 | Claude | Anthropic | https://api.anthropic.com |
| 15 | Gemini | Google | https://generativelanguage.googleapis.com |
| 16 | AWS_CLAUDE | AWS Bedrock | — |
| 22 | Azure | Azure OpenAI | — |
| 23 | Cohere | Cohere | — |
| 24 | Coze | 扣子 | — |
| 25 | Dify | Dify | — |
| 26 | Groq | Groq | — |
| 27 | Jina | Jina AI | — |
| 28 | MiniMax | MiniMax | — |
| 29 | Mistral | Mistral AI | — |
| 30 | Midjourney | MJ Proxy | — |
| 31 | Moonshot | 月之暗面 | https://api.moonshot.cn |
| 32 | Ollama | Ollama | — |
| 33 | Perplexity | Perplexity | — |
| 34 | Replicate | Replicate | — |
| 35 | SiliconFlow | 硅基流动 | https://api.siliconflow.cn |
| 41 | DeepSeek | DeepSeek | https://api.deepseek.com |

### B. 中继模式列表

| ID | 模式 | 对应端点 |
|----|------|---------|
| 1 | Chat Completions | /v1/chat/completions |
| 2 | Completions | /v1/completions |
| 3 | Embeddings | /v1/embeddings |
| 4 | Images Generations | /v1/images/generations |
| 5 | Audio Speech | /v1/audio/speech |
| 6 | Audio Translation | /v1/audio/translations |
| 7 | Audio Transcription | /v1/audio/transcriptions |
| 14 | Rerank | Rerank API |
| 16 | Responses | /v1/responses |
| 17 | Responses Compact | /v1/responses/compact |

### C. 与现有代码的兼容性说明

| 方面 | 兼容性 | 说明 |
|------|--------|------|
| **字段命名** | ✅ 完全兼容 | 所有字段名与 `init_db.php` 一致 |
| **数据类型** | ✅ 兼容 | BIGINT→BIGINT, INTEGER→INTEGER, TEXT→TEXT |
| **时间字段** | ⚠️ 变更 | `BIGINT` → `TIMESTAMPTZ`，需 Model 层适配 |
| **Model 类** | ✅ 兼容 | `Model::$isPostgres = true` 自动处理列引号 |
| **QueryBuilder** | ✅ 兼容 | 使用 `?` 占位符，PDO 参数化查询兼容 PG |
| **现有 Controller** | ✅ 兼容 | 接口签名不变，内部逻辑适配时间格式 |
| **Constants** | ✅ 兼容 | 所有常量值保持不变 |

**时间字段迁移适配**:

```php
// Model 层适配: 在 save() 前后处理时间字段
protected function prepareForDatabase(array $data): array
{
    foreach ($data as $key => $value) {
        if (isset(static::$casts[$key]) && static::$casts[$key] === 'timestamp') {
            // 将 Unix 时间戳转为 PostgreSQL TIMESTAMPTZ
            if (is_int($value)) {
                $data[$key] = date('Y-m-d H:i:s', $value);
            }
        }
        // JSON 编码处理
        if (isset(static::$casts[$key]) && in_array(static::$casts[$key], ['array', 'json'])) {
            $data[$key] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
        }
    }
    return $data;
}
```

---

*文档结束 — PeaseAI 系统架构与技术设计文档 v1.0*
