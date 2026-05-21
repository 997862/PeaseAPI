-- =============================================
-- PeaseAPI (豌豆API) v3.0 — 数据库初始化脚本
-- =============================================
-- 数据库要求：PostgreSQL 12+
-- 使用方法：
--   1. 创建数据库: CREATE DATABASE peaseapi;
--   2. 执行脚本:  psql -U youruser -d peaseapi -f install.sql
--   3. 初始化数据: php scripts/init_db.php
-- =============================================

CREATE TABLE public.abilities (
    "group" character varying(64) NOT NULL,
    model character varying(255) NOT NULL,
    channel_id integer NOT NULL,
    enabled boolean DEFAULT true,
    priority bigint DEFAULT 0,
    weight integer DEFAULT 0,
    tag character varying(255) DEFAULT NULL::character varying
);


--
-- Name: admin_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.admin_logs (
    id integer NOT NULL,
    admin_id integer NOT NULL,
    admin_username character varying(255) NOT NULL,
    action character varying(100) NOT NULL,
    target_type character varying(50),
    target_id integer,
    old_value text,
    new_value text,
    ip character varying(45),
    user_agent text,
    created_at integer NOT NULL
);


--
-- Name: admin_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.admin_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: admin_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.admin_logs_id_seq OWNED BY public.admin_logs.id;


--
-- Name: channels; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.channels (
    id integer NOT NULL,
    type smallint DEFAULT 1 NOT NULL,
    key text NOT NULL,
    openai_organization character varying(255) DEFAULT ''::character varying,
    test_model character varying(128) DEFAULT ''::character varying,
    status smallint DEFAULT 1 NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    weight integer DEFAULT 0,
    created_time bigint DEFAULT 0 NOT NULL,
    test_time bigint DEFAULT 0,
    response_time integer DEFAULT 0,
    base_url character varying(1024) DEFAULT ''::character varying,
    other text,
    balance double precision DEFAULT 0,
    balance_updated_time bigint DEFAULT 0,
    models text DEFAULT ''::text,
    "group" character varying(64) DEFAULT 'default'::character varying,
    used_quota bigint DEFAULT 0,
    model_mapping text,
    status_code_mapping character varying(1024) DEFAULT ''::character varying,
    priority bigint DEFAULT 0,
    auto_ban smallint DEFAULT 1,
    other_info text,
    tag character varying(255) DEFAULT NULL::character varying,
    setting text,
    param_override text,
    header_override text,
    remark character varying(255) DEFAULT ''::character varying,
    channel_info jsonb,
    settings text
);


--
-- Name: channels_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.channels_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: channels_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.channels_id_seq OWNED BY public.channels.id;


--
-- Name: checkins; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.checkins (
    id integer NOT NULL,
    user_id integer NOT NULL,
    quota integer DEFAULT 0 NOT NULL,
    created_at bigint DEFAULT 0 NOT NULL
);


--
-- Name: checkins_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.checkins_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: checkins_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.checkins_id_seq OWNED BY public.checkins.id;


--
-- Name: invitation_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invitation_logs (
    id integer NOT NULL,
    inviter_id integer NOT NULL,
    invitee_id integer NOT NULL,
    reward_quota bigint DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: invitation_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.invitation_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: invitation_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.invitation_logs_id_seq OWNED BY public.invitation_logs.id;


--
-- Name: invitations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invitations (
    id integer NOT NULL,
    inviter_id integer,
    invitee_id integer,
    invite_code character varying(20) NOT NULL,
    reward_quota bigint DEFAULT 0,
    status integer DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: invitations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.invitations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: invitations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.invitations_id_seq OWNED BY public.invitations.id;


--
-- Name: login_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.login_logs (
    id integer NOT NULL,
    user_id integer NOT NULL,
    username character varying(255) NOT NULL,
    login_ip character varying(45) NOT NULL,
    login_port integer DEFAULT 0,
    user_agent text,
    login_time integer NOT NULL,
    status integer DEFAULT 1 NOT NULL,
    login_type character varying(50) DEFAULT 'password'::character varying
);


--
-- Name: login_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.login_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: login_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.login_logs_id_seq OWNED BY public.login_logs.id;


--
-- Name: logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.logs (
    id bigint NOT NULL,
    user_id integer NOT NULL,
    channel_id integer,
    model_name character varying(255) DEFAULT ''::character varying NOT NULL,
    quota bigint DEFAULT 0 NOT NULL,
    content text,
    request_id character varying(64) DEFAULT ''::character varying,
    trace text,
    created_at bigint DEFAULT 0 NOT NULL,
    type smallint DEFAULT 1 NOT NULL,
    is_stream boolean DEFAULT false,
    original_model_name character varying(255) DEFAULT ''::character varying,
    "group" character varying(64) DEFAULT ''::character varying,
    prompt_tokens integer DEFAULT 0,
    completion_tokens integer DEFAULT 0,
    total_tokens integer DEFAULT 0
);


--
-- Name: logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.logs_id_seq OWNED BY public.logs.id;


--
-- Name: mail_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mail_templates (
    id integer NOT NULL,
    slug character varying(100) NOT NULL,
    name character varying(200) NOT NULL,
    subject character varying(300) NOT NULL,
    content text NOT NULL,
    description text,
    variables text,
    is_active boolean DEFAULT true,
    is_system boolean DEFAULT false,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: mail_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mail_templates_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mail_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mail_templates_id_seq OWNED BY public.mail_templates.id;


--
-- Name: missing_models; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.missing_models (
    id integer NOT NULL,
    model_name character varying(128) DEFAULT ''::character varying NOT NULL,
    channel_id integer NOT NULL,
    created_at bigint DEFAULT 0
);


--
-- Name: missing_models_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.missing_models_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: missing_models_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.missing_models_id_seq OWNED BY public.missing_models.id;


--
-- Name: nodes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.nodes (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    url character varying(500) NOT NULL,
    api_key character varying(200) NOT NULL,
    status integer DEFAULT 1,
    last_sync_at timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: nodes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.nodes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: nodes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.nodes_id_seq OWNED BY public.nodes.id;


--
-- Name: oauth_bindings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.oauth_bindings (
    id integer NOT NULL,
    user_id integer NOT NULL,
    provider character varying(32) NOT NULL,
    provider_id character varying(128) NOT NULL,
    created_at bigint DEFAULT 0 NOT NULL
);


--
-- Name: oauth_bindings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.oauth_bindings_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: oauth_bindings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.oauth_bindings_id_seq OWNED BY public.oauth_bindings.id;


--
-- Name: options; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.options (
    key character varying(128) NOT NULL,
    value text DEFAULT ''::text
);


--
-- Name: passkeys; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.passkeys (
    id integer NOT NULL,
    user_id integer NOT NULL,
    name character varying(128) DEFAULT ''::character varying,
    credential_id character varying(255) NOT NULL,
    public_key text,
    counter integer DEFAULT 0,
    created_at bigint DEFAULT 0
);


--
-- Name: passkeys_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.passkeys_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: passkeys_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.passkeys_id_seq OWNED BY public.passkeys.id;


--
-- Name: perf_metrics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.perf_metrics (
    id bigint NOT NULL,
    metric_name character varying(128) NOT NULL,
    metric_value text NOT NULL,
    created_at bigint DEFAULT 0 NOT NULL
);


--
-- Name: perf_metrics_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.perf_metrics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: perf_metrics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.perf_metrics_id_seq OWNED BY public.perf_metrics.id;


--
-- Name: prefill_groups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.prefill_groups (
    id integer NOT NULL,
    name character varying(128) DEFAULT ''::character varying NOT NULL,
    models json,
    created_at bigint DEFAULT 0
);


--
-- Name: prefill_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.prefill_groups ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.prefill_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: pricing; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pricing (
    id integer NOT NULL,
    model_name character varying(128) DEFAULT ''::character varying NOT NULL,
    unit_price double precision DEFAULT 0 NOT NULL,
    currency character varying(10) DEFAULT 'USD'::character varying,
    type character varying(32) DEFAULT 'per_token'::character varying,
    created_at bigint DEFAULT 0,
    updated_at bigint DEFAULT 0
);


--
-- Name: pricing_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pricing_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pricing_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pricing_id_seq OWNED BY public.pricing.id;


--
-- Name: realtime_metrics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.realtime_metrics (
    id integer NOT NULL,
    metric_type character varying(50) NOT NULL,
    value numeric(10,4) NOT NULL,
    "timestamp" timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: realtime_metrics_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.realtime_metrics_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: realtime_metrics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.realtime_metrics_id_seq OWNED BY public.realtime_metrics.id;


--
-- Name: redemptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.redemptions (
    id integer NOT NULL,
    user_id integer,
    key character varying(32) NOT NULL,
    status smallint DEFAULT 1 NOT NULL,
    token character varying(255) NOT NULL,
    created_time bigint DEFAULT 0 NOT NULL,
    redeemed_time bigint DEFAULT 0,
    count integer DEFAULT 1 NOT NULL,
    quota bigint DEFAULT 0 NOT NULL
);


--
-- Name: redemptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.redemptions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: redemptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.redemptions_id_seq OWNED BY public.redemptions.id;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id integer NOT NULL,
    name character varying(50) NOT NULL,
    display_name character varying(100) NOT NULL,
    permissions jsonb DEFAULT '{}'::jsonb,
    min_quota bigint DEFAULT 0,
    max_quota bigint DEFAULT 0,
    description text,
    sort_order integer DEFAULT 0
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: subscriptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.subscriptions (
    id integer NOT NULL,
    user_id integer NOT NULL,
    product_id character varying(64) DEFAULT ''::character varying,
    status smallint DEFAULT 1 NOT NULL,
    start_at bigint DEFAULT 0,
    end_at bigint DEFAULT 0,
    cancel_at bigint DEFAULT 0,
    trial_at bigint DEFAULT 0,
    quota integer DEFAULT 0,
    auto_renew boolean DEFAULT true
);


--
-- Name: subscriptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.subscriptions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: subscriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.subscriptions_id_seq OWNED BY public.subscriptions.id;


--
-- Name: tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tokens (
    id integer NOT NULL,
    user_id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    key character varying(64) NOT NULL,
    created_time bigint DEFAULT 0 NOT NULL,
    accessed_time bigint DEFAULT 0,
    expired_time bigint DEFAULT 0,
    remain_quota bigint DEFAULT 0,
    unlimited_quota boolean DEFAULT false,
    status smallint DEFAULT 1 NOT NULL,
    "group" character varying(64) DEFAULT 'default'::character varying,
    model_limit text,
    used_quota bigint DEFAULT 0,
    fetch_time bigint DEFAULT 0,
    heartbeat_time bigint DEFAULT 0,
    ip_limit text DEFAULT ''::text
);


--
-- Name: tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tokens_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tokens_id_seq OWNED BY public.tokens.id;


--
-- Name: topups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.topups (
    id integer NOT NULL,
    user_id integer NOT NULL,
    amount double precision DEFAULT 0 NOT NULL,
    quota integer DEFAULT 0 NOT NULL,
    status smallint DEFAULT 0 NOT NULL,
    payment_id character varying(128) DEFAULT ''::character varying,
    payment_method character varying(32) DEFAULT ''::character varying,
    created_at bigint DEFAULT 0 NOT NULL,
    paid_at bigint DEFAULT 0,
    order_no character varying(64) DEFAULT NULL::character varying,
    method character varying(20) DEFAULT NULL::character varying,
    trade_no character varying(64) DEFAULT NULL::character varying,
    updated_at bigint DEFAULT 0
);


--
-- Name: topups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.topups_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: topups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.topups_id_seq OWNED BY public.topups.id;


--
-- Name: twofa_secrets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.twofa_secrets (
    id integer NOT NULL,
    user_id integer NOT NULL,
    secret character varying(128) NOT NULL,
    enabled boolean DEFAULT false,
    created_at bigint DEFAULT 0
);


--
-- Name: twofa_secrets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.twofa_secrets_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: twofa_secrets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.twofa_secrets_id_seq OWNED BY public.twofa_secrets.id;


--
-- Name: user_groups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_groups (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    quota_limit bigint DEFAULT 0,
    rate_limit integer DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: user_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_groups_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_groups_id_seq OWNED BY public.user_groups.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id integer NOT NULL,
    username character varying(20) NOT NULL,
    password character varying(255) NOT NULL,
    display_name character varying(20) DEFAULT ''::character varying,
    role smallint DEFAULT 1 NOT NULL,
    status smallint DEFAULT 1 NOT NULL,
    email character varying(50) DEFAULT ''::character varying,
    github_id character varying(64) DEFAULT ''::character varying,
    discord_id character varying(64) DEFAULT ''::character varying,
    oidc_id character varying(64) DEFAULT ''::character varying,
    wechat_id character varying(64) DEFAULT ''::character varying,
    telegram_id character varying(64) DEFAULT ''::character varying,
    linux_do_id character varying(64) DEFAULT ''::character varying,
    quota integer DEFAULT 0 NOT NULL,
    used_quota integer DEFAULT 0 NOT NULL,
    request_count integer DEFAULT 0 NOT NULL,
    "group" character varying(64) DEFAULT 'default'::character varying,
    aff_code character varying(32) DEFAULT ''::character varying,
    aff_count integer DEFAULT 0,
    aff_quota integer DEFAULT 0,
    aff_history_quota integer DEFAULT 0,
    inviter_id integer,
    access_token character(32) DEFAULT NULL::bpchar,
    setting text,
    remark character varying(255) DEFAULT ''::character varying,
    stripe_customer character varying(64) DEFAULT ''::character varying,
    created_at bigint DEFAULT 0,
    last_login_at bigint DEFAULT 0,
    last_login_port integer DEFAULT 0,
    registration_ip character varying(45) DEFAULT ''::character varying,
    last_login_ip character varying(45) DEFAULT ''::character varying,
    phone character varying(20) DEFAULT ''::character varying,
    invited_by integer,
    group_id integer DEFAULT 0,
    invite_code character varying(20) DEFAULT ''::character varying,
    role_id integer DEFAULT 0
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: vendor_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vendor_meta (
    id integer NOT NULL,
    vendor_name character varying(128) DEFAULT ''::character varying NOT NULL,
    vendor_type character varying(32) DEFAULT ''::character varying NOT NULL,
    base_url character varying(512) DEFAULT ''::character varying,
    config text,
    created_at bigint DEFAULT 0
);


--
-- Name: vendor_meta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vendor_meta_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vendor_meta_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vendor_meta_id_seq OWNED BY public.vendor_meta.id;


--
-- Name: admin_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_logs ALTER COLUMN id SET DEFAULT nextval('public.admin_logs_id_seq'::regclass);


--
-- Name: channels id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.channels ALTER COLUMN id SET DEFAULT nextval('public.channels_id_seq'::regclass);


--
-- Name: checkins id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.checkins ALTER COLUMN id SET DEFAULT nextval('public.checkins_id_seq'::regclass);


--
-- Name: invitation_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitation_logs ALTER COLUMN id SET DEFAULT nextval('public.invitation_logs_id_seq'::regclass);


--
-- Name: invitations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitations ALTER COLUMN id SET DEFAULT nextval('public.invitations_id_seq'::regclass);


--
-- Name: login_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_logs ALTER COLUMN id SET DEFAULT nextval('public.login_logs_id_seq'::regclass);


--
-- Name: logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.logs ALTER COLUMN id SET DEFAULT nextval('public.logs_id_seq'::regclass);


--
-- Name: mail_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_templates ALTER COLUMN id SET DEFAULT nextval('public.mail_templates_id_seq'::regclass);


--
-- Name: missing_models id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.missing_models ALTER COLUMN id SET DEFAULT nextval('public.missing_models_id_seq'::regclass);


--
-- Name: nodes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nodes ALTER COLUMN id SET DEFAULT nextval('public.nodes_id_seq'::regclass);


--
-- Name: oauth_bindings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_bindings ALTER COLUMN id SET DEFAULT nextval('public.oauth_bindings_id_seq'::regclass);


--
-- Name: passkeys id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.passkeys ALTER COLUMN id SET DEFAULT nextval('public.passkeys_id_seq'::regclass);


--
-- Name: perf_metrics id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.perf_metrics ALTER COLUMN id SET DEFAULT nextval('public.perf_metrics_id_seq'::regclass);


--
-- Name: pricing id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pricing ALTER COLUMN id SET DEFAULT nextval('public.pricing_id_seq'::regclass);


--
-- Name: realtime_metrics id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.realtime_metrics ALTER COLUMN id SET DEFAULT nextval('public.realtime_metrics_id_seq'::regclass);


--
-- Name: redemptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.redemptions ALTER COLUMN id SET DEFAULT nextval('public.redemptions_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: subscriptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscriptions ALTER COLUMN id SET DEFAULT nextval('public.subscriptions_id_seq'::regclass);


--
-- Name: tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tokens ALTER COLUMN id SET DEFAULT nextval('public.tokens_id_seq'::regclass);


--
-- Name: topups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.topups ALTER COLUMN id SET DEFAULT nextval('public.topups_id_seq'::regclass);


--
-- Name: twofa_secrets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.twofa_secrets ALTER COLUMN id SET DEFAULT nextval('public.twofa_secrets_id_seq'::regclass);


--
-- Name: user_groups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_groups ALTER COLUMN id SET DEFAULT nextval('public.user_groups_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: vendor_meta id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_meta ALTER COLUMN id SET DEFAULT nextval('public.vendor_meta_id_seq'::regclass);


--
-- Name: abilities abilities_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.abilities
    ADD CONSTRAINT abilities_pkey PRIMARY KEY ("group", model, channel_id);


--
-- Name: admin_logs admin_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_logs
    ADD CONSTRAINT admin_logs_pkey PRIMARY KEY (id);


--
-- Name: channels channels_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.channels
    ADD CONSTRAINT channels_pkey PRIMARY KEY (id);


--
-- Name: checkins checkins_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.checkins
    ADD CONSTRAINT checkins_pkey PRIMARY KEY (id);


--
-- Name: invitation_logs invitation_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitation_logs
    ADD CONSTRAINT invitation_logs_pkey PRIMARY KEY (id);


--
-- Name: invitations invitations_invite_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitations
    ADD CONSTRAINT invitations_invite_code_key UNIQUE (invite_code);


--
-- Name: invitations invitations_inviter_id_invitee_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitations
    ADD CONSTRAINT invitations_inviter_id_invitee_id_key UNIQUE (inviter_id, invitee_id);


--
-- Name: invitations invitations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitations
    ADD CONSTRAINT invitations_pkey PRIMARY KEY (id);


--
-- Name: login_logs login_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_logs
    ADD CONSTRAINT login_logs_pkey PRIMARY KEY (id);


--
-- Name: logs logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.logs
    ADD CONSTRAINT logs_pkey PRIMARY KEY (id);


--
-- Name: mail_templates mail_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_templates
    ADD CONSTRAINT mail_templates_pkey PRIMARY KEY (id);


--
-- Name: mail_templates mail_templates_slug_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_templates
    ADD CONSTRAINT mail_templates_slug_key UNIQUE (slug);


--
-- Name: missing_models missing_models_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.missing_models
    ADD CONSTRAINT missing_models_pkey PRIMARY KEY (id);


--
-- Name: nodes nodes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nodes
    ADD CONSTRAINT nodes_pkey PRIMARY KEY (id);


--
-- Name: oauth_bindings oauth_bindings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_bindings
    ADD CONSTRAINT oauth_bindings_pkey PRIMARY KEY (id);


--
-- Name: options options_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.options
    ADD CONSTRAINT options_pkey PRIMARY KEY (key);


--
-- Name: passkeys passkeys_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.passkeys
    ADD CONSTRAINT passkeys_pkey PRIMARY KEY (id);


--
-- Name: perf_metrics perf_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.perf_metrics
    ADD CONSTRAINT perf_metrics_pkey PRIMARY KEY (id);


--
-- Name: prefill_groups prefill_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prefill_groups
    ADD CONSTRAINT prefill_groups_pkey PRIMARY KEY (id);


--
-- Name: pricing pricing_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pricing
    ADD CONSTRAINT pricing_pkey PRIMARY KEY (id);


--
-- Name: realtime_metrics realtime_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.realtime_metrics
    ADD CONSTRAINT realtime_metrics_pkey PRIMARY KEY (id);


--
-- Name: redemptions redemptions_key_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.redemptions
    ADD CONSTRAINT redemptions_key_key UNIQUE (key);


--
-- Name: redemptions redemptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.redemptions
    ADD CONSTRAINT redemptions_pkey PRIMARY KEY (id);


--
-- Name: redemptions redemptions_token_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.redemptions
    ADD CONSTRAINT redemptions_token_key UNIQUE (token);


--
-- Name: roles roles_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_key UNIQUE (name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: subscriptions subscriptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscriptions
    ADD CONSTRAINT subscriptions_pkey PRIMARY KEY (id);


--
-- Name: tokens tokens_key_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tokens
    ADD CONSTRAINT tokens_key_key UNIQUE (key);


--
-- Name: tokens tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tokens
    ADD CONSTRAINT tokens_pkey PRIMARY KEY (id);


--
-- Name: topups topups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.topups
    ADD CONSTRAINT topups_pkey PRIMARY KEY (id);


--
-- Name: twofa_secrets twofa_secrets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.twofa_secrets
    ADD CONSTRAINT twofa_secrets_pkey PRIMARY KEY (id);


--
-- Name: twofa_secrets twofa_secrets_user_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.twofa_secrets
    ADD CONSTRAINT twofa_secrets_user_id_key UNIQUE (user_id);


--
-- Name: oauth_bindings unique_provider; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_bindings
    ADD CONSTRAINT unique_provider UNIQUE (provider, provider_id);


--
-- Name: user_groups user_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_groups
    ADD CONSTRAINT user_groups_pkey PRIMARY KEY (id);


--
-- Name: users users_access_token_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_access_token_key UNIQUE (access_token);


--
-- Name: users users_aff_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_aff_code_key UNIQUE (aff_code);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_key UNIQUE (username);


--
-- Name: vendor_meta vendor_meta_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_meta
    ADD CONSTRAINT vendor_meta_pkey PRIMARY KEY (id);


--
-- Name: idx_abilities_channel_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_abilities_channel_id ON public.abilities USING btree (channel_id);


--
-- Name: idx_abilities_enabled; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_abilities_enabled ON public.abilities USING btree (enabled);


--
-- Name: idx_abilities_model; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_abilities_model ON public.abilities USING btree (model);


--
-- Name: idx_admin_logs_action; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_admin_logs_action ON public.admin_logs USING btree (action);


--
-- Name: idx_admin_logs_admin_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_admin_logs_admin_id ON public.admin_logs USING btree (admin_id);


--
-- Name: idx_admin_logs_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_admin_logs_created_at ON public.admin_logs USING btree (created_at DESC);


--
-- Name: idx_channels_name; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_channels_name ON public.channels USING btree (name);


--
-- Name: idx_channels_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_channels_status ON public.channels USING btree (status);


--
-- Name: idx_channels_tag; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_channels_tag ON public.channels USING btree (tag);


--
-- Name: idx_channels_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_channels_type ON public.channels USING btree (type);


--
-- Name: idx_checkins_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_checkins_created_at ON public.checkins USING btree (created_at);


--
-- Name: idx_checkins_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_checkins_user_id ON public.checkins USING btree (user_id);


--
-- Name: idx_login_logs_login_ip; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_login_logs_login_ip ON public.login_logs USING btree (login_ip);


--
-- Name: idx_login_logs_login_time; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_login_logs_login_time ON public.login_logs USING btree (login_time DESC);


--
-- Name: idx_login_logs_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_login_logs_user_id ON public.login_logs USING btree (user_id);


--
-- Name: idx_logs_channel_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_logs_channel_id ON public.logs USING btree (channel_id);


--
-- Name: idx_logs_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_logs_created_at ON public.logs USING btree (created_at);


--
-- Name: idx_logs_model_name; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_logs_model_name ON public.logs USING btree (model_name);


--
-- Name: idx_logs_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_logs_user_id ON public.logs USING btree (user_id);


--
-- Name: idx_missing_models_channel_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_missing_models_channel_id ON public.missing_models USING btree (channel_id);


--
-- Name: idx_missing_models_model_name; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_missing_models_model_name ON public.missing_models USING btree (model_name);


--
-- Name: idx_oauth_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_oauth_user_id ON public.oauth_bindings USING btree (user_id);


--
-- Name: idx_passkeys_credential_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_passkeys_credential_id ON public.passkeys USING btree (credential_id);


--
-- Name: idx_passkeys_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_passkeys_user_id ON public.passkeys USING btree (user_id);


--
-- Name: idx_perf_metrics_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_perf_metrics_created_at ON public.perf_metrics USING btree (created_at);


--
-- Name: idx_perf_metrics_metric_name; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_perf_metrics_metric_name ON public.perf_metrics USING btree (metric_name);


--
-- Name: idx_pricing_model_name; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pricing_model_name ON public.pricing USING btree (model_name);


--
-- Name: idx_redemptions_key; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_redemptions_key ON public.redemptions USING btree (key);


--
-- Name: idx_redemptions_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_redemptions_status ON public.redemptions USING btree (status);


--
-- Name: idx_redemptions_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_redemptions_user_id ON public.redemptions USING btree (user_id);


--
-- Name: idx_subscriptions_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_subscriptions_status ON public.subscriptions USING btree (status);


--
-- Name: idx_subscriptions_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_subscriptions_user_id ON public.subscriptions USING btree (user_id);


--
-- Name: idx_tokens_key; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tokens_key ON public.tokens USING btree (key);


--
-- Name: idx_tokens_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tokens_status ON public.tokens USING btree (status);


--
-- Name: idx_tokens_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tokens_user_id ON public.tokens USING btree (user_id);


--
-- Name: idx_topups_payment_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_topups_payment_id ON public.topups USING btree (payment_id);


--
-- Name: idx_topups_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_topups_status ON public.topups USING btree (status);


--
-- Name: idx_topups_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_topups_user_id ON public.topups USING btree (user_id);


--
-- Name: idx_twofa_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_twofa_user_id ON public.twofa_secrets USING btree (user_id);


--
-- Name: idx_users_aff_code; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_users_aff_code ON public.users USING btree (aff_code);


--
-- Name: idx_users_display_name; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_users_display_name ON public.users USING btree (display_name);


--
-- Name: idx_users_email; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_users_email ON public.users USING btree (email);


--
-- Name: idx_users_inviter_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_users_inviter_id ON public.users USING btree (inviter_id);


--
-- Name: idx_users_username; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_users_username ON public.users USING btree (username);


--
-- Name: idx_vendor_meta_vendor_name; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_vendor_meta_vendor_name ON public.vendor_meta USING btree (vendor_name);


--
-- Name: invitations invitations_invitee_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitations
    ADD CONSTRAINT invitations_invitee_id_fkey FOREIGN KEY (invitee_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: invitations invitations_inviter_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitations
    ADD CONSTRAINT invitations_inviter_id_fkey FOREIGN KEY (inviter_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: users users_invited_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_invited_by_fkey FOREIGN KEY (invited_by) REFERENCES public.users(id);


--
-- PostgreSQL database dump complete
--


