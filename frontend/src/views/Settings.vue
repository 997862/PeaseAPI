<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-900">系统设置</h2>
        <p class="mt-1 text-sm text-gray-500">管理系统配置、支付和邮件服务</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button v-for="tab in tabs" :key="tab.key"
          @click="activeTab = tab.key"
          :class="[activeTab === tab.key ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- System Settings -->
    <div v-if="activeTab === 'system'" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 space-y-4">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">系统名称</label>
          <input v-model="config.SystemName" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="PeaseAPI" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">前端URL</label>
          <input v-model="config.FrontendURL" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="https://www.peaseapi.com" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Logo URL</label>
          <input v-model="config.LogoURL" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="/logo.png" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">主题色</label>
          <input v-model="config.ThemeColor" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="#6366F1" />
        </div>
      </div>
      <div class="flex justify-end pt-4">
        <button @click="saveConfig('system')" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition whitespace-nowrap">
          <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          保存设置
        </button>
      </div>
    </div>

    <!-- Payment Settings -->
    <div v-if="activeTab === 'payment'" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 space-y-6">
      <!-- Alipay -->
      <div class="border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">支付宝配置</h3>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="config.AlipayEnabled" :true-value="'true'" :false-value="'false'" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            <span class="text-sm text-gray-700">启用支付宝</span>
          </label>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">App ID</label>
            <input v-model="config.AlipayAppId" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="2021xxxxxxxxxxxx" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">应用私钥</label>
            <textarea v-model="config.AlipayPrivateKey" rows="3" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none font-mono" placeholder="MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcw..."></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">支付宝公钥</label>
            <textarea v-model="config.AlipayPublicKey" rows="3" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none font-mono" placeholder="MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgK..."></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">回调URL</label>
            <input v-model="config.AlipayNotifyUrl" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="https://www.peaseapi.com/api/payment/alipay/notify" />
          </div>
        </div>
      </div>

      <!-- WeChat Pay -->
      <div class="border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">微信支付配置</h3>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="config.WechatPayEnabled" :true-value="'true'" :false-value="'false'" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            <span class="text-sm text-gray-700">启用微信支付</span>
          </label>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">商户号 (MCH ID)</label>
            <input v-model="config.WechatPayMchId" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="1234567890" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">API 密钥</label>
            <input v-model="config.WechatPayApiKey" type="password" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="32位API密钥" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">回调URL</label>
            <input v-model="config.WechatPayNotifyUrl" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="https://www.peaseapi.com/api/payment/wechat/notify" />
          </div>
        </div>
      </div>

      <div class="flex justify-end">
        <button @click="saveConfig('payment')" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition whitespace-nowrap">
          <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          保存支付配置
        </button>
      </div>
    </div>

    <!-- SMTP Settings -->
    <div v-if="activeTab === 'smtp'" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">SMTP 邮件服务</h3>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="config.SmtpEnabled" :true-value="'true'" :false-value="'false'" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
          <span class="text-sm text-gray-700">启用SMTP</span>
        </label>
      </div>
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">SMTP 服务器</label>
          <input v-model="config.SmtpHost" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="smtp.example.com" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">端口</label>
          <select v-model="config.SmtpPort" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
            <option value="25">25 (普通)</option>
            <option value="465">465 (SSL)</option>
            <option value="587">587 (TLS)</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
          <input v-model="config.SmtpUsername" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="noreply@example.com" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">密码</label>
          <input v-model="config.SmtpPassword" type="password" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="授权码或密码" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">发件人邮箱</label>
          <input v-model="config.SmtpFromEmail" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="noreply@example.com" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">发件人名称</label>
          <input v-model="config.SmtpFromName" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="PeaseAPI" />
        </div>
      </div>
      <div class="flex justify-end gap-3 pt-4">
        <button @click="testSmtp" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 transition whitespace-nowrap">
          测试连接
        </button>
        <button @click="saveConfig('smtp')" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition whitespace-nowrap">
          <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          保存 SMTP 配置
        </button>
      </div>
    </div>

    <!-- OAuth Settings -->
    <div v-if="activeTab === 'oauth'" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 space-y-6">
      <!-- GitHub -->
      <div class="border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">🐙 GitHub</h3>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="config.GitHubOAuthEnabled" :true-value="'true'" :false-value="'false'" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            <span class="text-sm text-gray-700">启用 GitHub 登录</span>
          </label>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div><label class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
            <input v-model="config.GitHubClientId" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="Iv1.xxxx" /></div>
          <div><label class="block text-sm font-medium text-gray-700 mb-1">Client Secret</label>
            <input v-model="config.GitHubClientSecret" type="password" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="密钥" /></div>
          <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">回调 URL</label>
            <input v-model="config.GitHubRedirectUri" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="https://www.peaseapi.com/api/oauth/github/callback" /></div>
        </div>
      </div>
      <!-- Google -->
      <div class="border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">🔵 Google</h3>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="config.GoogleOAuthEnabled" :true-value="'true'" :false-value="'false'" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            <span class="text-sm text-gray-700">启用 Google 登录</span>
          </label>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div><label class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
            <input v-model="config.GoogleClientId" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="xxx.apps.googleusercontent.com" /></div>
          <div><label class="block text-sm font-medium text-gray-700 mb-1">Client Secret</label>
            <input v-model="config.GoogleClientSecret" type="password" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="密钥" /></div>
          <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">回调 URL</label>
            <input v-model="config.GoogleRedirectUri" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="https://www.peaseapi.com/api/oauth/google/callback" /></div>
        </div>
      </div>
      <!-- QQ -->
      <div class="border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">🐧 QQ</h3>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="config.QQOAuthEnabled" :true-value="'true'" :false-value="'false'" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            <span class="text-sm text-gray-700">启用 QQ 登录</span>
          </label>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div><label class="block text-sm font-medium text-gray-700 mb-1">App ID</label>
            <input v-model="config.QQClientId" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="101xxx" /></div>
          <div><label class="block text-sm font-medium text-gray-700 mb-1">App Key</label>
            <input v-model="config.QQClientSecret" type="password" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="密钥" /></div>
          <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">回调 URL</label>
            <input v-model="config.QQRedirectUri" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="https://www.peaseapi.com/api/oauth/qq/callback" /></div>
        </div>
      </div>
      <!-- WeChat -->
      <div class="border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">💬 微信</h3>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="config.WechatOAuthEnabled" :true-value="'true'" :false-value="'false'" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            <span class="text-sm text-gray-700">启用微信登录</span>
          </label>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div><label class="block text-sm font-medium text-gray-700 mb-1">App ID</label>
            <input v-model="config.WechatClientId" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="wx..." /></div>
          <div><label class="block text-sm font-medium text-gray-700 mb-1">App Secret</label>
            <input v-model="config.WechatClientSecret" type="password" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="密钥" /></div>
          <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">回调 URL</label>
            <input v-model="config.WechatRedirectUri" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="https://www.peaseapi.com/api/oauth/wechat/callback" /></div>
        </div>
      </div>
      <div class="flex justify-end pt-4">
        <button @click="saveConfig('oauth')" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition whitespace-nowrap">
          <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          保存 OAuth 配置
        </button>
      </div>
    </div>

    <!-- SMS Settings -->
    <div v-if="activeTab === 'sms'" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 space-y-6">
      <!-- Aliyun SMS -->
      <div class="border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">阿里云短信</h3>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="config.SmsAliyunEnabled" :true-value="'true'" :false-value="'false'" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            <span class="text-sm text-gray-700">启用阿里云短信</span>
          </label>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div><label class="block text-sm font-medium text-gray-700 mb-1">AccessKey ID</label>
            <input v-model="config.SmsAliyunAccessKeyId" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="LTAIxxxxxxxx" /></div>
          <div><label class="block text-sm font-medium text-gray-700 mb-1">AccessKey Secret</label>
            <input v-model="config.SmsAliyunAccessKeySecret" type="password" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="密钥" /></div>
          <div><label class="block text-sm font-medium text-gray-700 mb-1">短信签名</label>
            <input v-model="config.SmsAliyunSignName" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="码农软件" /></div>
          <div><label class="block text-sm font-medium text-gray-700 mb-1">验证码模板 CODE</label>
            <input v-model="config.SmsAliyunTemplateCode" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="SMS_123456789" /></div>
        </div>
      </div>
      <!-- Tencent SMS -->
      <div class="border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">腾讯云短信</h3>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="config.SmsTencentEnabled" :true-value="'true'" :false-value="'false'" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            <span class="text-sm text-gray-700">启用腾讯云短信</span>
          </label>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div><label class="block text-sm font-medium text-gray-700 mb-1">SecretId</label>
            <input v-model="config.SmsTencentSecretId" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="AKIDxxxxxxxx" /></div>
          <div><label class="block text-sm font-medium text-gray-700 mb-1">SecretKey</label>
            <input v-model="config.SmsTencentSecretKey" type="password" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="密钥" /></div>
          <div><label class="block text-sm font-medium text-gray-700 mb-1">SDK AppID</label>
            <input v-model="config.SmsTencentSdkAppId" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="1400xxxxxx" /></div>
          <div><label class="block text-sm font-medium text-gray-700 mb-1">短信签名</label>
            <input v-model="config.SmsTencentSignName" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="码农软件" /></div>
          <div><label class="block text-sm font-medium text-gray-700 mb-1">验证码模板 ID</label>
            <input v-model="config.SmsTencentTemplateId" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="123456" /></div>
        </div>
      </div>
      <div class="flex justify-end gap-3 pt-4">
        <button @click="testSms" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 transition whitespace-nowrap">📱 测试短信</button>
        <button @click="saveConfig('sms')" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition whitespace-nowrap">
          <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          保存短信配置</button>
      </div>
    </div>

    <!-- Other Settings -->
    <div v-if="activeTab === 'other'" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 space-y-4">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
          <div>
            <h4 class="font-medium text-gray-900">开放注册</h4>
            <p class="text-sm text-gray-500">允许新用户注册</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="config.RegisterEnabled" :true-value="'true'" :false-value="'false'" class="sr-only peer" />
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
          </label>
        </div>
        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
          <div>
            <h4 class="font-medium text-gray-900">邮箱验证</h4>
            <p class="text-sm text-gray-500">注册时必须验证邮箱</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="config.EmailVerificationEnabled" :true-value="'true'" :false-value="'false'" class="sr-only peer" />
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
          </label>
        </div>
        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
          <div>
            <h4 class="font-medium text-gray-900">短信验证</h4>
            <p class="text-sm text-gray-500">注册时必须验证手机号</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="config.SmsVerificationEnabled" :true-value="'true'" :false-value="'false'" class="sr-only peer" />
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
          </label>
        </div>
        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
          <div>
            <h4 class="font-medium text-gray-900">用户名密码登录</h4>
            <p class="text-sm text-gray-500">允许使用用户名+密码登录</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="config.PasswordLoginEnabled" :true-value="'true'" :false-value="'false'" class="sr-only peer" />
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
          </label>
        </div>
        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
          <div>
            <h4 class="font-medium text-gray-900">邮箱登录</h4>
            <p class="text-sm text-gray-500">允许使用邮箱+密码登录</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="config.EmailLoginEnabled" :true-value="'true'" :false-value="'false'" class="sr-only peer" />
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
          </label>
        </div>
        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
          <div>
            <h4 class="font-medium text-gray-900">手机号登录</h4>
            <p class="text-sm text-gray-500">允许使用手机号+验证码登录</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="config.SmsLoginEnabled" :true-value="'true'" :false-value="'false'" class="sr-only peer" />
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
          </label>
        </div>
      </div>
      <div class="flex justify-end pt-4">
        <button @click="saveConfig('other')" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition whitespace-nowrap">
          保存设置
        </button>
      </div>
    </div>
    <div v-if="activeTab === 'other'" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 space-y-4">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
          <div>
            <h4 class="font-medium text-gray-900">开放注册</h4>
            <p class="text-sm text-gray-500">允许新用户注册</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="config.RegisterEnabled" :true-value="'true'" :false-value="'false'" class="sr-only peer" />
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
          </label>
        </div>
        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
          <div>
            <h4 class="font-medium text-gray-900">邮箱验证</h4>
            <p class="text-sm text-gray-500">注册时需要验证邮箱</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="config.EmailVerificationEnabled" :true-value="'true'" :false-value="'false'" class="sr-only peer" />
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
          </label>
        </div>
      </div>
      <div class="flex justify-end pt-4">
        <button @click="saveConfig('other')" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition whitespace-nowrap">
          保存设置
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const activeTab = ref('system')
const saving = ref(false)
const config = ref({})

const tabs = [
  { key: 'system', name: '🔧 系统设置' },
  { key: 'payment', name: ' 支付配置' },
  { key: 'smtp', name: '📧 SMTP 配置' },
  { key: 'sms', name: '📱 短信配置' },
  { key: 'oauth', name: '🔗 OAuth 登录' },
  { key: 'other', name: '⚙️ 其他设置' },
]

async function loadConfig() {
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/options', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    const json = await res.json()
    if (json.success) {
      config.value = json.data || {}
    }
  } catch (e) {
    console.error('Failed to load config:', e)
  }
}

async function saveConfig(tab) {
  saving.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    const keys = {
      system: ['SystemName', 'FrontendURL', 'LogoURL', 'ThemeColor'],
      payment: ['AlipayEnabled', 'AlipayAppId', 'AlipayPrivateKey', 'AlipayPublicKey', 'AlipayNotifyUrl', 'WechatPayEnabled', 'WechatPayMchId', 'WechatPayApiKey', 'WechatPayNotifyUrl'],
      smtp: ['SmtpEnabled', 'SmtpHost', 'SmtpPort', 'SmtpUseSsl', 'SmtpUsername', 'SmtpPassword', 'SmtpFromEmail', 'SmtpFromName'],
      sms: ['SmsAliyunEnabled', 'SmsAliyunAccessKeyId', 'SmsAliyunAccessKeySecret', 'SmsAliyunSignName', 'SmsAliyunTemplateCode', 'SmsTencentEnabled', 'SmsTencentSecretId', 'SmsTencentSecretKey', 'SmsTencentSdkAppId', 'SmsTencentSignName', 'SmsTencentTemplateId'],
      oauth: ['GitHubOAuthEnabled', 'GitHubClientId', 'GitHubClientSecret', 'GitHubRedirectUri', 'GoogleOAuthEnabled', 'GoogleClientId', 'GoogleClientSecret', 'GoogleRedirectUri', 'QQOAuthEnabled', 'QQClientId', 'QQClientSecret', 'QQRedirectUri', 'WechatOAuthEnabled', 'WechatClientId', 'WechatClientSecret', 'WechatRedirectUri'],
      other: ['RegisterEnabled', 'EmailVerificationEnabled', 'SmsVerificationEnabled', 'PasswordLoginEnabled', 'EmailLoginEnabled', 'SmsLoginEnabled'],
    }[tab]

    const options = {}
    keys.forEach(k => {
      if (config.value[k] !== undefined) options[k] = config.value[k]
    })

    const res = await fetch('/api/options/batch', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({ options })
    })
    const json = await res.json()
    if (json.success) {
      alert('保存成功！')
    } else {
      alert('保存失败: ' + (json.message || '未知错误'))
    }
  } catch (e) {
    alert('网络错误: ' + e.message)
  } finally {
    saving.value = false
  }
}

async function testSmtp() {
  saving.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/mail/test-smtp', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(config.value)
    })
    const json = await res.json()
    if (json.success) {
      alert('SMTP 连接测试成功！')
    } else {
      alert('测试失败: ' + (json.message || '未知错误'))
    }
  } catch (e) {
    alert('网络错误: ' + e.message)
  } finally {
    saving.value = false
  }
}

async function testSms() {
  saving.value = true
  const phone = prompt("请输入接收测试短信的手机号：")
  if (!phone) { saving.value = false; return }
  const provider = confirm("点击确定测试阿里云短信，点击取消测试腾讯云短信") ? "aliyun" : "tencent"
  try {
    const token = sessionStorage.getItem("access_token")
    const res = await fetch("/api/sms/test-send", {
      method: "POST",
      headers: { "Content-Type": "application/json", "Authorization": "Bearer " + token },
      body: JSON.stringify({ phone, provider, config: config.value })
    })
    const json = await res.json()
    if (json.success) alert("测试短信已发送！请检查手机：" + phone)
    else alert("发送失败: " + (json.message || "未知错误"))
  } catch (e) { alert("网络错误: " + e.message) }
  finally { saving.value = false }
}

onMounted(() => loadConfig())
</script>
