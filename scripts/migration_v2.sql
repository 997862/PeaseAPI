
-- Token IP 限制
ALTER TABLE tokens ADD COLUMN IF NOT EXISTS ip_limit TEXT DEFAULT '';

-- 用户分组
CREATE TABLE IF NOT EXISTS user_groups (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    quota_limit BIGINT DEFAULT 0,
    rate_limit INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 用户关联分组（多对多）
CREATE TABLE IF NOT EXISTS user_group_members (
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    group_id INTEGER REFERENCES user_groups(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, group_id)
);

-- 角色权限表
CREATE TABLE IF NOT EXISTS roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    permissions JSONB DEFAULT '{}',
    min_quota BIGINT DEFAULT 0,
    max_quota BIGINT DEFAULT 0,
    description TEXT,
    sort_order INTEGER DEFAULT 0
);

-- 插入默认角色
INSERT INTO roles (name, display_name, permissions, description, sort_order) VALUES
('user', '普通用户', '{"create_token": true, "view_log": true, "redeem": true}', '基础用户权限，可使用 API', 1),
('vip', 'VIP 用户', '{"create_token": true, "view_log": true, "redeem": true, "priority": true}', 'VIP 用户，更高优先级', 2),
('admin', '管理员', '{"create_token": true, "view_log": true, "redeem": true, "priority": true, "manage_users": true, "manage_channels": true}', '管理后台权限', 3),
('root', '超级管理员', '{}', '最高权限，所有功能', 4)
ON CONFLICT (name) DO NOTHING;

-- 邀请返利表
CREATE TABLE IF NOT EXISTS invitations (
    id SERIAL PRIMARY KEY,
    inviter_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    invitee_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    invite_code VARCHAR(20) NOT NULL UNIQUE,
    reward_quota BIGINT DEFAULT 0,
    status INTEGER DEFAULT 1, -- 1: 有效, 0: 已失效
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(inviter_id, invitee_id)
);

-- 邀请记录表（统计用）
CREATE TABLE IF NOT EXISTS invitation_logs (
    id SERIAL PRIMARY KEY,
    inviter_id INTEGER NOT NULL,
    invitee_id INTEGER NOT NULL,
    reward_quota BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 用户邀请码字段
ALTER TABLE users ADD COLUMN IF NOT EXISTS invite_code VARCHAR(20) DEFAULT '';
ALTER TABLE users ADD COLUMN IF NOT EXISTS invited_by INTEGER REFERENCES users(id);
ALTER TABLE users ADD COLUMN IF NOT EXISTS group_id INTEGER DEFAULT 0;

-- 多节点表
CREATE TABLE IF NOT EXISTS nodes (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    api_key VARCHAR(200) NOT NULL,
    status INTEGER DEFAULT 1,
    last_sync_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 实时指标表
CREATE TABLE IF NOT EXISTS realtime_metrics (
    id SERIAL PRIMARY KEY,
    metric_type VARCHAR(50) NOT NULL, -- qps, latency, token_usage, error_rate
    value NUMERIC(10,4) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
