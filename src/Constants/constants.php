<?php

namespace NewApi\Constants;

// Version
define('APP_VERSION', '1.0.0');
define('SYSTEM_NAME', 'New API');

// User Roles
define('ROLE_ROOT_USER', 100);
define('ROLE_ADMIN_USER', 10);
define('ROLE_COMMON_USER', 1);

// User Status
define('USER_STATUS_ENABLED', 1);
define('USER_STATUS_DISABLED', 2);

// Channel Types
define('CHANNEL_TYPE_OPENAI', 1);
define('CHANNEL_TYPE_AZURE', 22);
define('CHANNEL_TYPE_CLAUDE', 14);
define('CHANNEL_TYPE_GEMINI', 15);
define('CHANNEL_TYPE_BAIDU', 4);
define('CHANNEL_TYPE_ZHIPU', 3);
define('CHANNEL_TYPE_ALI', 11);
define('CHANNEL_TYPE_AWS_CLAUDE', 16);
define('CHANNEL_TYPE_COHERE', 23);
define('CHANNEL_TYPE_COZE', 24);
define('CHANNEL_TYPE_DIFY', 25);
define('CHANNEL_TYPE_GROQ', 26);
define('CHANNEL_TYPE_JINA', 27);
define('CHANNEL_TYPE_MINIMAX', 28);
define('CHANNEL_TYPE_MISTRAL', 29);
define('CHANNEL_TYPE_MIDJOURNEY', 30);
define('CHANNEL_TYPE_MOONSHOT', 31);
define('CHANNEL_TYPE_OLLAMA', 32);
define('CHANNEL_TYPE_PERPLEXITY', 33);
define('CHANNEL_TYPE_REPLICATE', 34);
define('CHANNEL_TYPE_SILICONFLOW', 35);
define('CHANNEL_TYPE_DEEPSEEK', 41);

// Channel Status
define('CHANNEL_STATUS_ENABLED', 1);
define('CHANNEL_STATUS_DISABLED', 2);
define('CHANNEL_STATUS_MANUALLY_DISABLED', 3);
define('CHANNEL_STATUS_AUTO_DISABLED', 4);

// Token Status
define('TOKEN_STATUS_ENABLED', 1);
define('TOKEN_STATUS_DISABLED', 2);

// Log Types
define('LOG_TYPE_TEXT', 1);
define('LOG_TYPE_IMAGE', 2);
define('LOG_TYPE_AUDIO', 3);
define('LOG_TYPE_EMBEDDING', 4);

// Relay Modes
define('RELAY_MODE_CHAT_COMPLETIONS', 1);
define('RELAY_MODE_COMPLETIONS', 2);
define('RELAY_MODE_EMBEDDINGS', 3);
define('RELAY_MODE_IMAGES_GENERATIONS', 4);
define('RELAY_MODE_AUDIO_SPEECH', 5);
define('RELAY_MODE_AUDIO_TRANSLATION', 6);
define('RELAY_MODE_AUDIO_TRANSCRIPTION', 7);
define('RELAY_MODE_RERANK', 14);
define('RELAY_MODE_RESPONSES', 16);
define('RELAY_MODE_RESPONSES_COMPACT', 17);

// Error Codes
define('ERROR_CODE_AUTH_FAILED', 1);
define('ERROR_CODE_TOKEN_INVALID', 2);
define('ERROR_CODE_CHANNEL_DISABLED', 3);
define('ERROR_CODE_CHANNEL_NOT_FOUND', 4);
define('ERROR_CODE_QUOTA_EXHAUSTED', 5);
define('ERROR_CODE_MODEL_NOT_FOUND', 6);
define('ERROR_CODE_UPSTREAM_ERROR', 7);
define('ERROR_CODE_RATE_LIMIT', 8);
define('ERROR_CODE_INVALID_REQUEST', 9);
define('ERROR_CODE_CONVERT_FAILED', 10);

// Quota
define('QUOTA_PER_UNIT', 500000); // $0.002 / 1K tokens

// Context Keys
define('CONTEXT_KEY_USER_ID', 'user_id');
define('CONTEXT_KEY_USER', 'user');
define('CONTEXT_KEY_TOKEN', 'token');
define('CONTEXT_KEY_TOKEN_ID', 'token_id');
define('CONTEXT_KEY_CHANNEL', 'channel');
define('CONTEXT_KEY_CHANNEL_ID', 'channel_id');
define('CONTEXT_KEY_MODEL', 'model');
define('CONTEXT_KEY_GROUP', 'group');
define('CONTEXT_KEY_REQUEST_ID', 'request_id');
define('CONTEXT_KEY_START_TIME', 'start_time');
