# PeaseAI (New API PHP 重写版) — 产品需求文档 (PRD)

> **文档版本**: v1.0  
> **创建日期**: 2025-05-19  
> **产品名称**: PeaseAI (基于 QuantumNous/new-api 的 PHP 8.3 完整重写版)  
> **原始项目**: QuantumNous/new-api (33.6k+ stars, AGPLv3)  
> **目标架构**: PHP 8.3 + PostgreSQL (阿里云 RDS)  

---

## 目录

1. [产品概述](#1-产品概述)
2. [目标用户与使用场景](#2-目标用户与使用场景)
3. [产品定位与核心价值](#3-产品定位与核心价值)
4. [功能模块划分](#4-功能模块划分)
5. [用户角色和权限矩阵](#5-用户角色和权限矩阵)
6. [数据库与核心实体](#6-数据库与核心实体)
7. [核心业务流程](#7-核心业务流程)
8. [API 接口分类和用途](#8-api-接口分类和用途)
9. [计费系统详细设计](#9-计费系统详细设计)
10. [非功能性需求](#10-非功能性需求)
11. [安全与合规](#11-安全与合规)
12. [里程碑与实施计划](#12-里程碑与实施计划)

---

## 1. 产品概述

### 1.1 产品定义

PeaseAI 是一个 **AI 网关与资产管理系统**，作为统一的基础设施层，将来自全球 30+ AI 服务提供商的模型能力聚合为 OpenAI 兼容的统一 API 接口。它是 QuantumNous/new-api 项目的 PHP 8.3 完整重写版本，面向需要集中管理 AI 模型访问、计费、路由和分发的个人开发者、团队及企业。

### 1.2 产品愿景

> 让每一个 AI 应用只需对接一个 API，即可获得全球所有 AI 模型的能力，同时提供精细的配额管理和成本控制。

### 1.3 关键特征

| 特征 | 描述 |
|------|------|
| 统一接口 | 单一 OpenAI 兼容端点，接入所有 AI 服务 |
| 智能路由 | 多通道负载均衡、自动故障转移、权重随机分配 |
| 精细计费 | 按量/按次计费、预付费充值、多倍率配置 |
| 安全控制 | Token 权限管理、模型访问控制、API 调用审计 |
| 数据洞察 | 实时仪表板、用量统计、成本分析 |
| 多租户架构 | 支持个人、团队协作、企业级部署 |
| 协议转换 | 自动将 Claude、Gemini 等格式转为 OpenAI 兼容格式 |

---

## 2. 目标用户与使用场景

### 2.1 用户画像

| 用户类型 | 描述 | 核心需求 |
|---------|------|---------|
| **个人开发者** | 使用多个 AI 模型的独立开发者 | 统一 API、配额管理、成本控制 |
| **团队/初创公司** | 需要集中管理 AI 资源的小团队 | 多用户配额分配、团队协作、成本分摊 |
| **API 分销商** | 提供 AI API 转售服务的运营商 | 多租户计费、渠道管理、在线支付 |
| **企业用户** | 需要内部 AI 网关的大型组织 | 安全审计、高可用部署、SSO 集成 |
| **教育/研究机构** | 需要为师生分配 AI 访问权的机构 | 批量用户管理、配额限制、使用监控 |

### 2.2 使用场景

```
场景 1: 个人开发者统一接入
  用户注册 → 配置 OpenAI/Claude/Gemini 渠道 → 生成 Token → 
  在 AI 应用中使用统一端点 → 按用量自动计费

场景 2: API 分销商运营
  管理员配置上游渠道 → 设置定价倍率 → 开放注册 → 
  用户充值/使用兑换码 → Token 按配额消费 → 在线支付补充

场景 3: 企业内部分发
  管理员创建用户 → 分配不同分组 → 设置模型访问权限 → 
  监控使用日志 → 按部门统计成本
```

---

## 3. 产品定位与核心价值

### 3.1 与 One API / new-api 的关系

| 维度 | One API (原始) | new-api (演进版) | PeaseAI (本项目) |
|------|---------------|-----------------|-----------------|
| 语言 | Go | Go | PHP 8.3 |
| 许可证 | MIT | AGPLv3 | AGPLv3 (继承) |
| Stars | ~16k | ~33.6k | 新项目 |
| 数据库 | SQLite/MySQL/PostgreSQL | SQLite/MySQL/PostgreSQL | PostgreSQL (RDS) |
| 部署 | Docker/手动 | Docker/1Panel/宝塔 | Docker/PHP-FPM |
| 核心差异 | 基础网关 | 功能全面增强 | PHP 生态集成、企业级优化 |

### 3.2 核心价值主张

1. **降本增效** — 通过智能路由和渠道复用，最大化利用多个 AI 供应商的资源
2. **简化集成** — 应用端只需对接一个 OpenAI 兼容端点
3. **可控成本** — 精确到每次调用的计费与配额控制
4. **高可用性** — 自动故障转移，多通道冗余保障

---

## 4. 功能模块划分

### 4.1 模块全景图

```
┌─────────────────────────────────────────────────────────┐
│                    PeaseAI 系统架构                       │
├──────────┬──────────┬──────────┬──────────┬──────────────┤
│  AI 网关  │ 渠道管理  │ 用户管理  │ 计费系统  │  系统管理    │
├──────────┼──────────┼──────────┼──────────┼──────────────┤
│ • 聊天    │ • 渠道CRUD│ • 用户CRUD│ • 配额管理 │ • 系统配置   │
│ • 补全    │ • 通道测试│ • 角色权限│ • 充值支付 │ • 运营设置   │
│ • 嵌入    │ • 批量操作│ • 个人设置│ • 兑换码   │ • 日志管理   │
│ • 图像    │ • 优先级  │ • 推广系统│ • 订阅     │ • 数据统计   │
│ • 音频    │ • 权重    │ • OAuth  │ • 签到     │ • 公告管理   │
│ • 视频    │ • 模型映射│ • 2FA    │ • 价格表   │ • 通知设置   │
│ • 重排序  │ • 参数覆盖│ • Passkey│           │ • 仪表盘     │
│ • 实时语音│ • 状态码  │          │           │             │
│ • 审查    │ • 映射    │          │           │             │
├──────────┼──────────┼──────────┼──────────┼──────────────┤
│           路由引擎 (智能选择渠道)                           │
├──────────┼──────────┼──────────┼──────────┼──────────────┤
│           协议转换层 (OpenAI/Claude/Gemini 互转)            │
├──────────┼──────────┼──────────┼──────────┼──────────────┤
│           下游渠道 (OpenAI/Anthropic/Google/百度/智谱/…)    │
└──────────┴──────────┴──────────┴──────────┴──────────────┘
```

### 4.2 功能清单详情

#### 模块 A: AI 网关层 (Relay)

| 编号 | 功能 | 说明 | 优先级 |
|------|------|------|--------|
| A01 | 聊天补全 (Chat Completions) | 兼容 OpenAI /v1/chat/completions | P0 |
| A02 | 传统补全 (Completions) | 兼容 /v1/completions | P1 |
| A03 | 嵌入向量 (Embeddings) | 兼容 /v1/embeddings | P0 |
| A04 | 图像生成 (Images) | 兼容 /v1/images/generations | P0 |
| A05 | 音频 (Speech/STT) | 语音合成和语音识别 | P1 |
| A06 | 实时语音 (Realtime) | OpenAI Realtime API | P2 |
| A07 | 视频生成 (Video) | Sora 等视频模型 | P2 |
| A08 | 重排序 (Rerank) | Cohere/Jina 兼容 | P1 |
| A09 | 内容审查 (Moderations) | 安全内容审核 | P1 |
| A10 | Claude 兼容 (/v1/messages) | Claude Messages API 格式 | P0 |
| A11 | Gemini 兼容 | Google Gemini 格式 | P0 |
| A12 | Responses API | OpenAI Responses 格式 | P1 |
| A13 | 模型列表 (/v1/models) | 返回可用模型列表 | P0 |
| A14 | Midjourney-Proxy | MJ 图像生成代理 | P2 |
| A15 | Suno API | AI 音乐生成 | P2 |

#### 模块 B: 渠道管理 (Channel)

| 编号 | 功能 | 说明 | 优先级 |
|------|------|------|--------|
| B01 | 渠道创建/编辑/删除 | 管理 AI 供应商通道 | P0 |
| B02 | 渠道类型管理 | 支持 20+ 种渠道类型 | P0 |
| B03 | 多 Key 支持 | 一个渠道可配置多个 API Key | P0 |
| B04 | 渠道测试 | 单渠道测试 + 全渠道批量测试 | P0 |
| B05 | 渠道状态管理 | 启用/禁用/自动禁用 | P0 |
| B06 | 优先级与权重 | Priority (先匹配) + Weight (加权随机) | P0 |
| B07 | 模型映射 (Model Mapping) | 将请求模型映射为上游实际模型 | P0 |
| B08 | 状态码映射 | 自定义上游状态码处理逻辑 | P1 |
| B09 | 参数覆盖 (Param Override) | 修改请求参数 | P1 |
| B10 | 请求头覆盖 (Header Override) | 添加/修改请求头 | P1 |
| B11 | 自动封禁 (Auto Ban) | 连续失败后自动禁用渠道 | P1 |
| B12 | 渠道标签 (Tag) | 用于分类和筛选 | P1 |
| B13 | 渠道备注 (Remark) | 管理员内部备注 | P2 |
| B14 | 渠道余额监控 | 监控上游账户余额 | P2 |
| B15 | 渠道额外设置 | thinking_to_content 等高级选项 | P1 |
| B16 | 模型预填充组 | 快速批量配置渠道支持模型 | P2 |

#### 模块 C: 用户管理 (User)

| 编号 | 功能 | 说明 | 优先级 |
|------|------|------|--------|
| C01 | 用户注册/登录 | 用户名密码方式 | P0 |
| C02 | 密码重置 | 密码修改功能 | P0 |
| C03 | 用户资料管理 | 显示名称、邮箱等 | P0 |
| C04 | OAuth 登录 | GitHub/Discord/微信/Telegram 等 | P1 |
| C05 | 2FA 双因素认证 | TOTP 验证码 | P1 |
| C06 | Passkey 认证 | WebAuthn 无密码登录 | P2 |
| C07 | Access Token 生成 | API 访问令牌 | P0 |
| C08 | 用户分组 (Group) | 将用户分组以控制模型访问 | P0 |
| C09 | 推广码系统 | 邀请码与推广奖励 | P1 |
| C10 | 签到奖励 | 每日签到获得配额 | P2 |
| C11 | 用户搜索 | 按用户名/邮箱/显示名搜索 | P1 |
| C12 | 批量用户管理 | 批量启用/禁用/删除 | P1 |
| C13 | 用户备注 | 管理员对用户添加备注 | P2 |
| C14 | Stripe 集成 | Stripe 客户关联 | P1 |

#### 模块 D: Token 管理

| 编号 | 功能 | 说明 | 优先级 |
|------|------|------|--------|
| D01 | Token 创建/删除 | 管理 API Key (sk- 格式) | P0 |
| D02 | 配额管理 | 剩余配额/无限配额 | P0 |
| D03 | 过期时间设置 | Token 有效期控制 | P0 |
| D04 | 模型限制 | 限制 Token 可调用的模型列表 | P0 |
| D05 | 分组关联 | Token 绑定到用户分组 | P0 |
| D06 | 使用统计 | 已用配额统计 | P1 |
| D07 | 批量操作 | 批量启用/禁用/删除 | P1 |
| D08 | Key 脱敏显示 | 只显示前后几位 | P0 |
| D09 | 心跳时间 | 最近访问时间跟踪 | P2 |

#### 模块 E: 计费系统 (Billing)

| 编号 | 功能 | 说明 | 优先级 |
|------|------|------|--------|
| E01 | 配额系统 (Quota) | 内部虚拟货币，500000 = $0.002/1K tokens | P0 |
| E02 | 模型倍率 (Ratio) | 不同模型的不同计费倍率 | P0 |
| E03 | Token 级别计费 | Token 维度独立配额 | P0 |
| E04 | 用户级别计费 | 用户维度总配额 | P0 |
| E05 | 按次计费 | 按调用次数计费 | P1 |
| E06 | 缓存计费 | Prompt Cache 命中时的折扣计费 | P1 |
| E07 | 充值系统 | 余额充值 | P1 |
| E08 | 在线支付 | Epay/支付宝/微信支付 | P1 |
| E09 | 兑换码系统 | 充值码/优惠码 | P1 |
| E10 | 订阅系统 | 定期配额发放 | P2 |
| E11 | 推广转账 | 推广额度转账到余额 | P2 |
| E12 | 价格表 (Pricing) | 模型价格配置 | P1 |
| E13 | 分层计费 | 按工具/功能维度计费 | P1 |

#### 模块 F: 日志与统计 (Logs & Analytics)

| 编号 | 功能 | 说明 | 优先级 |
|------|------|------|--------|
| F01 | 调用日志 | 记录每次 API 调用详情 | P0 |
| F02 | 日志筛选 | 按时间/模型/渠道筛选 | P0 |
| F03 | 用量统计 | 总请求数/总配额消耗 | P0 |
| F04 | 每日统计 | 按天统计用量趋势 | P0 |
| F05 | 仪表板 | 数据可视化面板 | P0 |
| F06 | 流式日志 | 标记流式/非流式请求 | P1 |
| F07 | Token 用量 | prompt/completion/total tokens | P0 |
| F08 | 上游请求ID | 记录上游返回的 Request ID | P1 |

#### 模块 G: 系统管理 (System)

| 编号 | 功能 | 说明 | 优先级 |
|------|------|------|--------|
| G01 | 系统初始化 | Root 用户创建引导 | P0 |
| G02 | 系统状态 | 运行状态/版本信息 | P0 |
| G03 | 全局配置 | 系统设置 (Options 表) | P0 |
| G04 | 登录注册配置 | 开关各种登录/注册方式 | P0 |
| G05 | 新用户配额 | 注册时赠送的初始配额 | P0 |
| G06 | 公告管理 | 系统公告和通知 | P1 |
| G07 | 用户协议 | 服务条款 | P2 |
| G08 | 隐私政策 | 隐私保护声明 | P2 |
| G09 | 速率限制 | 请求频率限制 | P1 |
| G10 | 失败重试 | 自动重试失败请求 | P1 |
| G11 | 多语言 | i18n 支持 | P1 |
| G12 | 主题设置 | 前端主题配置 | P2 |
| G13 | 供应商管理 (Vendor Meta) | 供应商元数据管理 | P2 |

---

## 5. 用户角色和权限矩阵

### 5.1 角色定义

| 角色 | 标识 | 值 | 描述 |
|------|------|-----|------|
| Root 管理员 | ROLE_ROOT | 100 | 超级管理员，拥有所有权限，系统初始化时创建 |
| 普通管理员 | ROLE_ADMIN | 10 | 管理用户/渠道/系统配置，但不能管理其他管理员 |
| 普通用户 | ROLE_USER | 1 | 使用 API、管理自己的 Token、查看自己的日志 |

### 5.2 权限矩阵

```
功能模块              | Root(100) | Admin(10) | User(1)  | 匿名
──────────────────────┼───────────┼───────────┼──────────┼──────
系统初始化             | ✅ 创建   | ❌        | ❌       | ✅
查看系统状态           | ✅        | ✅        | ✅       | ✅
系统设置修改           | ✅        | ✅        | ❌       | ❌
用户管理(列表/CRUD)    | ✅        | ✅        | ❌       | ❌
删除同级或更高级用户   | ❌        | ❌        | ❌       | ❌
渠道管理(全部操作)     | ✅        | ✅        | ❌       | ❌
渠道密钥查看(脱敏)     | ✅        | ✅        | ❌       | ❌
用户 Token 管理        | ✅ 全部   | ❌        | ✅ 自己的 | ❌
查看日志               | ✅ 全部   | ✅ 全部   | ✅ 自己的 | ❌
统计数据               | ✅ 全部   | ✅ 全部   | ✅ 自己的 | ❌
兑换码管理             | ✅ 全部   | ✅        | ❌       | ❌
充值/支付              | ✅        | ✅        | ✅       | ❌
API 网关调用           | —         | —         | —        | ✅ (需Token)
模型列表               | —         | —         | —        | ✅ (需Token)
注册                   | —         | —         | —        | ✅ (可开关)
登录                   | —         | —         | —        | ✅
```

### 5.3 权限约束规则

1. **角色层级约束**: 低角色不能操作高角色用户 (Admin 不能删除 Root)
2. **数据隔离约束**: 用户只能查看和管理自己的 Token、日志、配额
3. **管理员数据可见性**: Admin 可查看所有用户/渠道/日志数据
4. **密钥脱敏**: 渠道 Key 和 Token Key 在列表/详情中均做脱敏处理

---

## 6. 数据库与核心实体

### 6.1 实体关系图 (文字描述)

```
users (用户)
  ├── 1:N → tokens (API 密钥)
  ├── 1:N → logs (调用日志)
  ├── 1:N → checkins (签到记录)
  ├── 1:N → topups (充值记录)
  ├── 1:N → subscriptions (订阅)
  ├── 1:N → oauth_bindings (OAuth绑定)
  ├── 1:N → passkeys (WebAuthn密钥)
  ├── 1:1 → twofa_secrets (2FA密钥)
  ├── N:N → channels (通过 abilities 间接关联)
  └── 自引用: inviter_id → users.id (推广关系)

channels (渠道)
  ├── 1:N → logs (通过 log.channel_id)
  └── 1:N → abilities (渠道→模型→分组的映射)

abilities (能力矩阵)
  ├── 复合主键: (group, model, channel_id)
  ├── 关联: group ← users.group / tokens.group
  ├── 关联: model ← 模型名称
  └── 关联: channel_id ← channels.id

tokens (API令牌)
  ├── N:1 → users (user_id)
  └── 自管理: model_limit (JSON 模型白名单)

redemptions (兑换码)
  ├── 生成 Token: token 字段 (兑换后生成实际API Key)
  └── 绑定用户: user_id (可选，创建时指定)

options (系统配置)
  └── Key-Value 存储: 所有系统设置项
```

### 6.2 核心数据表结构

详见数据库初始化脚本 `/scripts/init_db.php`，包含 18 张表:

| 表名 | 用途 | 关键字段 |
|------|------|---------|
| users | 用户账户 | role, status, quota, group, aff_code |
| channels | 上游渠道 | type, key, models, group, priority, weight |
| tokens | API 令牌 | user_id, key, remain_quota, model_limit |
| logs | 调用日志 | user_id, channel_id, model_name, quota, tokens |
| abilities | 能力矩阵 | group, model, channel_id, enabled, priority |
| options | 系统配置 | key (PK), value |
| redemptions | 兑换码 | key, status, token, quota |
| topups | 充值记录 | user_id, amount, quota, payment_method |
| subscriptions | 订阅 | user_id, product_id, status, auto_renew |
| checkins | 签到 | user_id, quota, created_at |
| oauth_bindings | OAuth 绑定 | user_id, provider, provider_id |
| passkeys | WebAuthn | user_id, credential_id, public_key |
| twofa_secrets | 2FA 配置 | user_id, secret, enabled |
| pricing | 价格表 | model_name, unit_price, currency, type |
| prefill_groups | 预填分组 | name, models (JSON) |
| vendor_meta | 供应商元数据 | vendor_name, vendor_type, base_url |
| missing_models | 缺失模型记录 | model_name, channel_id |
| perf_metrics | 性能指标 | metric_name, metric_value |

---

## 7. 核心业务流程

### 7.1 系统初始化流程

```
1. 访问系统 → GET /api/setup
   ├─ setup = false → 进入初始化引导
   └─ setup = true → 跳转登录页

2. POST /api/setup { username, password, email }
   ├─ 验证: SetupCompleted ≠ true
   ├─ 创建 Root 用户 (role=100)
   ├─ 设置 SetupCompleted = true
   └─ 自动生成分组: "default"

3. Root 登录 → 配置系统 → 添加渠道 → 开放注册
```

### 7.2 用户注册与认证流程

```
注册流程:
  POST /api/user/register { username, password, email, aff_code? }
  ├─ 检查: RegisterEnabled = true
  ├─ 检查: 用户名不重复
  ├─ 检查: 密码长度 8-20 字符
  ├─ 检查: 邮箱不重复 (如提供)
  ├─ 处理 aff_code: 记录推广人 inviter_id
  ├─ 创建用户: role=1, quota=NewUserQuota
  ├─ 生成 aff_code
  ├─ 推广人 aff_count +1
  └─ 返回成功

登录流程:
  POST /api/user/login { username, password, otp? }
  ├─ 检查: PasswordLoginEnabled = true
  ├─ 验证用户名密码
  ├─ 检查: user.status = 1 (启用)
  ├─ 检查: 2FA (如启用则需要 OTP)
  ├─ 创建 Session
  ├─ 更新 last_login_at
  └─ 返回用户信息

API 认证 (Token 方式):
  请求头: Authorization: Bearer sk-xxxxx
  ├─ 查找 Token by key
  ├─ 检查 Token 状态: 未禁用
  ├─ 检查 Token 过期: 未过期
  ├─ 检查关联用户状态: 已启用
  └─ 注入: user_id, user, token 到请求上下文
```

### 7.3 API 网关调用完整流程 (核心)

```
客户端请求 → POST /v1/chat/completions
  │
  ▼
[1] Token 认证 (Middleware::tokenAuth)
  ├─ 提取 API Key (Authorization / x-api-key)
  ├─ 查找 Token 记录
  ├─ 验证: 状态正常 + 未过期
  ├─ 查找关联 User
  ├─ 验证: 用户状态正常
  └─ 注入上下文: user_id, user, token
  │
  ▼
[2] 模型限制检查 (TokenController/RelayController)
  ├─ 获取 Token 的 model_limit
  ├─ 如有设置: 检查请求模型是否在白名单中
  └─ 如不在白名单 → 返回 403
  │
  ▼
[3] 配额检查
  ├─ 检查 Token.remain_quota (或 unlimited_quota)
  ├─ 如配额不足 → 返回 429 (insufficient_quota)
  └─ 继续
  │
  ▼
[4] 渠道选择 (findChannel)
  ├─ 确定用户分组: token.group → user.group → "default"
  ├─ 查询 abilities 表: 匹配 model + group + enabled=true
  ├─ 关联 channels 表: status=1
  ├─ 排序: priority DESC → weight DESC
  ├─ 选择第一个渠道
  ├─ 如渠道有多个 Key: 随机选择一个
  └─ 如无可用渠道 → 返回 404 (model_not_found)
  │
  ▼
[5] 请求转发 (sendToUpstream)
  ├─ 构建上游 URL (base_url + path)
  ├─ 设置认证头 (Bearer/x-api-key/key param)
  ├─ 应用模型映射 (model_mapping)
  ├─ 应用参数覆盖 (param_override)
  ├─ 发送 HTTP 请求到上游
  └─ 接收上游响应
  │
  ▼
[6] 响应处理
  ├─ 非流式: 解析 JSON → 提取 usage → 计算配额 → 扣费 → 记录日志
  └─ 流式: 透传 SSE → 提取最终 usage → 计算配额 → 扣费 → 记录日志
  │
  ▼
[7] 配额扣除 (consumeQuota)
  ├─ 计算: tokens × QUOTA_PER_UNIT / 1000 × ratio
  ├─ 扣除 Token.remain_quota
  ├─ 增加 Token.used_quota
  ├─ 扣除 User.quota
  ├─ 增加 User.used_quota
  └─ 增加 User.request_count
  │
  ▼
[8] 日志记录
  ├─ 创建 Log 记录
  ├─ 记录: user_id, channel_id, model, tokens, quota
  └─ 记录: request_id, is_stream, content (截断2000字符)
  │
  ▼
[9] 返回响应给客户端
```

### 7.4 智能路由算法

```
输入: model_name, user_group
输出: 选中的渠道 (Channel)

算法:
1. 查询 abilities 表
   WHERE model = ? AND enabled = TRUE AND channel.status = 1
   AND (group = ? OR group = '')

2. 排序
   ORDER BY abilities.priority DESC, 
            abilities.weight DESC,
            channels.priority DESC, 
            channels.weight DESC

3. 选择第一个匹配项

4. 如渠道有多个 API Key
   随机选择其中一个 (array_rand)

5. 返回选中的渠道信息
```

### 7.5 充值与支付流程

```
用户发起充值:
  POST /api/user/pay { amount, payment_method }
  ├─ 创建 Topup 记录 (status=PENDING)
  ├─ 调用支付网关 (Epay/支付宝/微信)
  └─ 返回支付跳转 URL

支付回调:
  POST /api/payment/callback
  ├─ 验证签名
  ├─ 更新 Topup 记录 (status=PAID, paid_at=now)
  ├─ 计算配额: amount × 汇率
  ├─ 增加 User.quota
  └─ 记录日志

兑换码充值:
  POST /api/user/topup { redemption_key }
  ├─ 查找 Redemption by key
  ├─ 检查: status=ENABLED, 未使用
  ├─ 更新: status=USED, redeemed_time=now
  ├─ 创建 Token (token 字段)
  ├─ 增加 User.quota
  └─ 返回 Token key
```

### 7.6 推广系统流程

```
用户 A 注册时填写推广码:
  用户 B 的 aff_code → 记录 inviter_id = B.id
  B.aff_count += 1

推广奖励 (配置驱动):
  用户 A 消费 → 按比例计入 B.aff_quota
  B.aff_history_quota 累计历史推广额度

推广转账:
  B 将 aff_quota 转账到自己的 quota
  B.aff_quota -= transfer_amount
  B.quota += transfer_amount
```

---

## 8. API 接口分类和用途

### 8.1 AI 模型 API (OpenAI 兼容, 无需管理认证)

| 方法 | 路径 | 认证 | 用途 |
|------|------|------|------|
| GET | `/v1/models` | Token | 列出可用模型 |
| GET | `/v1/models/{model}` | Token | 获取单个模型信息 |
| POST | `/v1/chat/completions` | Token | 对话补全 (核心) |
| POST | `/v1/completions` | Token | 文本补全 |
| POST | `/v1/embeddings` | Token | 文本嵌入向量 |
| POST | `/v1/images/generations` | Token | AI 图像生成 |
| POST | `/v1/images/edits` | Token | 图像编辑 |
| POST | `/v1/messages` | Token | Claude 兼容消息 API |
| POST | `/v1/responses` | Token | OpenAI Responses API |
| GET | `/v1beta/models` | Token | Gemini 兼容模型列表 |

### 8.2 管理 API (需要 Session 或 Access Token 认证)

#### 系统级 (公开/半公开)

| 方法 | 路径 | 认证 | 用途 |
|------|------|------|------|
| GET | `/api/status` | 无 | 系统状态/功能开关 |
| GET | `/api/notice` | 无 | 系统公告 |
| GET | `/api/setup` | 无 | 初始化状态检查 |
| POST | `/api/setup` | 无 | 系统初始化 |
| GET | `/api/models` | 无 | 搜索可用模型 |
| GET | `/api/ratio_config` | 无 | 获取模型倍率配置 |
| GET | `/api/user-agreement` | 无 | 用户协议 |
| GET | `/api/privacy-policy` | 无 | 隐私政策 |

#### 认证相关

| 方法 | 路径 | 认证 | 用途 |
|------|------|------|------|
| POST | `/api/user/login` | 无 | 用户登录 |
| POST | `/api/user/register` | 无 | 用户注册 |
| POST | `/api/user/logout` | Session | 退出登录 |
| GET | `/api/user/self` | Session | 获取当前用户信息 |
| PUT | `/api/user/self` | Session | 更新个人资料/密码 |
| POST | `/api/user/generate_access_token` | Session | 生成 Access Token |

#### 用户管理 (需 Admin)

| 方法 | 路径 | 认证 | 用途 |
|------|------|------|------|
| GET | `/api/users` | Admin | 用户列表 (分页/搜索) |
| GET | `/api/user/{id}` | Admin | 单个用户详情 |
| POST | `/api/user` | Admin | 创建用户 |
| PUT | `/api/user/{id}` | Admin | 更新用户信息 |
| DELETE | `/api/user/{id}` | Admin | 删除用户 |
| POST | `/api/user/manage` | Admin | 管理操作 (加/减配额) |

#### 渠道管理 (需 Admin)

| 方法 | 路径 | 认证 | 用途 |
|------|------|------|------|
| GET | `/api/channels` | Admin | 渠道列表 (分页/搜索) |
| GET | `/api/channel/{id}` | Admin | 渠道详情 |
| POST | `/api/channel` | Admin | 创建渠道 |
| PUT | `/api/channel/{id}` | Admin | 更新渠道 |
| DELETE | `/api/channel/{id}` | Admin | 删除渠道 |
| POST | `/api/channel/batch` | Admin | 批量删除 |
| POST | `/api/channel/batch/status` | Admin | 批量修改状态 |
| GET | `/api/channel/test/{id}` | Admin | 测试单个渠道 |
| GET | `/api/channel/test` | Admin | 测试全部渠道 |

#### Token 管理 (需认证, 仅管理自己的)

| 方法 | 路径 | 认证 | 用途 |
|------|------|------|------|
| GET | `/api/tokens` | User | 我的 Token 列表 |
| GET | `/api/token/{id}` | User | Token 详情 |
| POST | `/api/token` | User | 创建 Token |
| PUT | `/api/token/{id}` | User | 更新 Token |
| DELETE | `/api/token/{id}` | User | 删除 Token |
| POST | `/api/token/batch` | User | 批量删除 |
| POST | `/api/token/batch/status` | User | 批量修改状态 |

### 8.3 认证机制

```
三种认证方式:
1. Session Cookie → 用于 Web 前端管理后台
   Session 中存储: user_id, username, role, status

2. Access Token → 用于管理 API 调用
   Header: Authorization: Bearer {32位hex token}
   存储在 users.access_token 字段

3. API Key (Token) → 用于 AI 网关调用
   Header: Authorization: Bearer sk-{32位hex}
   或 Header: x-api-key: sk-{32位hex}
   存储在 tokens.key 字段
```

---

## 9. 计费系统详细设计

### 9.1 配额 (Quota) 体系

```
核心单位: Quota (内部虚拟货币)
换算关系: QUOTA_PER_UNIT = 500,000 = $0.002 / 1K tokens
即: 500,000 quota = $0.002 = 1,000 tokens (标准模型)

计费公式:
  quota消耗 = total_tokens × (QUOTA_PER_UNIT / 1000) × model_ratio

例如 GPT-4 (ratio=15):
  1,000 tokens × (500000 / 1000) × 15 = 7,500,000 quota
```

### 9.2 倍率配置 (Ratio)

存储在 `options` 表中，key 格式为 `Ratio_{model_name}`:

| 模型示例 | 默认倍率 | 说明 |
|---------|---------|------|
| gpt-3.5-turbo | 1 | 基准模型 |
| gpt-4 | 15 | 15倍基准价格 |
| gpt-4o | 5 | 5倍基准价格 |
| claude-3-sonnet | 3 | 3倍基准价格 |
| claude-3-opus | 15 | 15倍基准价格 |
| embedding 模型 | 0.25 | 较低倍率 |
| 图像生成 | 按次 | 特殊计费 |

### 9.3 两层配额控制

```
用户级配额 (users.quota):
  - 用户总的可用配额
  - 管理员分配 / 充值获得
  - 消费时同时扣除用户配额

Token级配额 (tokens.remain_quota / unlimited_quota):
  - 单个 API Key 的配额限制
  - 创建时设置，管理员可调整
  - unlimited_quota = true 时无限制 (仅记录 used_quota)
  - Token 配额不足时返回 429

消费时同步扣除:
  Token.remain_quota -= quota
  Token.used_quota += quota
  User.quota -= quota  
  User.used_quota += quota
  User.request_count += 1
```

### 9.4 计费模式

| 模式 | 说明 | 配置位置 |
|------|------|---------|
| 按 Token 计费 | 标准模式，按 token 数量 × 倍率 | Ratio_{model} |
| 按次计费 | 每次调用固定配额 | 系统设置 |
| 缓存计费 | Cache Hit 时按折扣比例计费 | Channel 设置 (0-1) |
| 分层计费 | 按工具/功能维度计费 | 模型管理 |

---

## 10. 非功能性需求

### 10.1 性能要求

| 指标 | 目标值 | 说明 |
|------|--------|------|
| API 响应延迟 (P50) | < 50ms | 不含上游调用时间 |
| API 响应延迟 (P99) | < 200ms | 不含上游调用时间 |
| 流式首字节 (P50) | < 1s | 含上游调用 |
| 并发处理能力 | > 1000 QPS | 取决于上游通道数量 |
| 数据库查询 (渠道选择) | < 10ms | abilities 表查询 |
| 流式超时 | 300s (默认) | 可配置 STREAMING_TIMEOUT |

### 10.2 可用性要求

| 指标 | 目标值 |
|------|--------|
| 系统可用性 | 99.9% |
| 自动故障转移 | 渠道失败时自动切换下一优先级 |
| 自动封禁 | 连续失败自动禁用问题渠道 |
| 数据持久化 | PostgreSQL 主从/备份 |

### 10.3 安全性要求

| 要求 | 实现方式 |
|------|---------|
| API Key 加密存储 | bcrypt 密码哈希, Key 脱敏显示 |
| 传输安全 | HTTPS (Nginx 层) |
| SQL 注入防护 | PDO 参数化查询 |
| CSRF 防护 | Session 机制 |
| 速率限制 | IP 级别滑动窗口 (60次/60秒) |
| 密钥脱敏 | mask_key() 函数: 显示前6后4位 |
| 敏感配置过滤 | Option 表中含 secret/token/password 的 key 自动隐藏 |

### 10.4 可扩展性

| 维度 | 设计 |
|------|------|
| 水平扩展 | PHP-FPM 多实例 + Nginx 负载均衡 |
| 会话存储 | 后续支持 Redis 存储 Session |
| 缓存 | Options 内存缓存 + 后续 Redis 支持 |
| 数据库 | PostgreSQL 连接池 |
| 渠道扩展 | 新增渠道类型只需添加 CHANNEL_TYPE 常量 |

### 10.5 运维要求

| 要求 | 说明 |
|------|------|
| 日志 | 访问日志 + 错误日志 + 调用日志三级 |
| 监控 | 性能指标表 (perf_metrics) |
| 健康检查 | /api/status 端点 |
| 备份 | PostgreSQL 定时备份 |

---

## 11. 安全与合规

### 11.1 合规注意事项

> ⚠️ 原文引用: "When operating this project as a public generative AI service or API resale service, 
> users should first complete all required filing, licensing, content safety, real-name verification, 
> log retention, tax, payment, and upstream authorization obligations."

### 11.2 合规要求清单

| 合规领域 | 要求 | 实现状态 |
|---------|------|---------|
| 内容安全 | Moderations API 支持 | ✅ 已实现 |
| 日志留存 | 所有调用记录在 logs 表 | ✅ 已实现 |
| 实名认证 | 用户注册信息 | ⏳ 待实现 |
| 数据加密 | HTTPS + 密码哈希 | ✅ 已实现 |
| 访问审计 | 完整调用日志 | ✅ 已实现 |
| 许可证合规 | AGPLv3 归属声明 | ✅ 需保留 |

### 11.3 许可证合规

- 原始项目使用 **AGPLv3** 许可证
- 修改版本必须保留前端归属声明: `Frontend design and design and development by New API contributors.`
- 修改版本必须保留原始项目链接: https://github.com/QuantumNous/new-api
- 作为网络服务部署时，需提供完整源代码

---

## 12. 里程碑与实施计划

### Phase 1: 核心网关 (P0) — 2-3 周

```
□ 完成基础框架搭建 (Router/Middleware/Response)
□ 实现 Token 认证中间件
□ 实现 API 中继 (RelayController) — 聊天/嵌入/图像
□ 实现渠道选择算法 (abilities 表)
□ 实现配额计算与扣除
□ 实现调用日志记录
□ 实现系统初始化
□ 实现用户注册/登录/认证
□ 实现渠道管理 CRUD
□ 实现 Token 管理 CRUD
□ 完成 PostgreSQL 数据库初始化
```

### Phase 2: 管理与运营 (P1) — 2-3 周

```
□ 完善用户管理 (Admin 端)
□ 实现渠道批量操作
□ 实现渠道测试功能
□ 实现日志查询与筛选
□ 实现数据统计 (每日/汇总)
□ 实现系统设置管理
□ 实现兑换码系统
□ 实现推广码系统
□ 实现充值/支付集成 (Epay)
□ 实现 OAuth 登录
□ 实现 2FA 认证
□ 实现速率限制
```

### Phase 3: 高级功能 (P2) — 2-3 周

```
□ 实现流式响应优化
□ 实现订阅系统
□ 实现签到系统
□ 实现 Passkey 认证
□ 实现价格表管理
□ 实现供应商管理
□ 实现多语言支持
□ 实现前端仪表板 (React/Vue)
□ 性能优化 (缓存/连接池)
□ 安全加固
□ 完整测试覆盖
□ 文档编写
```

---

## 附录 A: 渠道类型完整列表

| ID | 类型标识 | 供应商 | 默认 Base URL |
|----|---------|--------|---------------|
| 1 | OpenAI | OpenAI | https://api.openai.com |
| 3 | 智谱 (ZHIPU) | 智谱AI | https://open.bigmodel.cn |
| 4 | 百度 (BAIDU) | 百度文心 | https://aip.baidubce.com |
| 11 | 阿里 (ALI) | 阿里百炼 | https://dashscope.aliyuncs.com |
| 14 | Claude | Anthropic | https://api.anthropic.com |
| 15 | Gemini | Google | https://generativelanguage.googleapis.com |
| 16 | AWS Claude | AWS Bedrock | — |
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

## 附录 B: 中继模式 (Relay Modes)

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

## 附录 C: 系统配置项 (Options Key 参考)

| Key | 类型 | 默认值 | 说明 |
|-----|------|--------|------|
| SetupCompleted | bool | false | 系统是否已初始化 |
| RegisterEnabled | bool | true | 是否开放注册 |
| PasswordLoginEnabled | bool | true | 密码登录开关 |
| PasswordRegisterEnabled | bool | true | 密码注册开关 |
| NewUserQuota | int | 0 | 新用户初始配额 |
| GitHubOAuthEnabled | bool | false | GitHub OAuth 开关 |
| DiscordOAuthEnabled | bool | false | Discord OAuth 开关 |
| Announcement | text | "" | 系统公告 |
| TopUpLink | text | "" | 充值页面链接 |
| ChatLink | text | "" | 聊天入口链接 |
| DefaultTheme | text | "default" | 默认主题 |
| UserAgreement | text | "" | 用户协议内容 |
| PrivacyPolicy | text | "" | 隐私政策内容 |
| Notice | text | "" | 通知内容 |
| Ratio_{model} | float | 1 | 模型计费倍率 |

---

*文档结束 — PeaseAI PRD v1.0*
