<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-xl font-bold text-gray-900">个人资料</h2>
      <p class="mt-1 text-sm text-gray-500">管理你的个人信息和账户设置</p>
    </div>

    <!-- Profile Card -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">基本信息</h3>
      </div>
      <div class="p-6 space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
            <input v-model="profile.username" disabled class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm bg-gray-50 text-gray-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">邮箱</label>
            <input v-model="profile.email" type="email" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="your@email.com" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">手机号</label>
            <div class="flex gap-2">
              <input v-model="profile.phone" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" :placeholder="profile.phone ? '' : '绑定手机号'" />
              <button v-if="!phoneCodeSent" @click="sendPhoneCode" :disabled="saving || !isValidPhone" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50 whitespace-nowrap">
                获取验证码
              </button>
              <button v-else @click="bindPhone" :disabled="saving || !phoneCode" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50 whitespace-nowrap">
                {{ phoneCountdown > 0 ? phoneCountdown + 's' : '确认绑定' }}
              </button>
            </div>
            <div v-if="phoneCodeSent" class="mt-2">
              <input v-model="phoneCode" type="text" maxlength="6" class="block w-32 rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="6位验证码" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">角色</label>
            <input :value="roleLabel" disabled class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm bg-gray-50 text-gray-500" />
          </div>
        </div>
        <div class="flex justify-end pt-2">
          <button @click="saveProfile" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition">
            <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            保存资料
          </button>
        </div>
      </div>
    </div>

    <!-- OAuth Bindings -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">第三方账号绑定</h3>
        <p class="mt-1 text-sm text-gray-500">绑定第三方账号，快捷登录</p>
      </div>
      <div class="p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <!-- GitHub -->
          <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div class="flex items-center gap-3">
              <img :src="oauthIcons.github" alt="GitHub" class="w-8 h-8" />
              <div>
                <p class="text-sm font-medium text-gray-900">GitHub</p>
                <p class="text-xs text-gray-500">{{ oauthStatus.github ? '已绑定' : '未绑定' }}</p>
              </div>
            </div>
            <button @click="bindOAuth('github')" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
              {{ oauthStatus.github ? '解绑' : '绑定' }}
            </button>
          </div>
          <!-- Google -->
          <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div class="flex items-center gap-3">
              <img :src="oauthIcons.google" alt="Google" class="w-8 h-8" />
              <div>
                <p class="text-sm font-medium text-gray-900">Google</p>
                <p class="text-xs text-gray-500">{{ oauthStatus.google ? '已绑定' : '未绑定' }}</p>
              </div>
            </div>
            <button @click="bindOAuth('google')" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
              {{ oauthStatus.google ? '解绑' : '绑定' }}
            </button>
          </div>
          <!-- QQ -->
          <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div class="flex items-center gap-3">
              <img :src="oauthIcons.qq" alt="QQ" class="w-8 h-8" />
              <div>
                <p class="text-sm font-medium text-gray-900">QQ</p>
                <p class="text-xs text-gray-500">{{ oauthStatus.qq ? '已绑定' : '未绑定' }}</p>
              </div>
            </div>
            <button @click="bindOAuth('qq')" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
              {{ oauthStatus.qq ? '解绑' : '绑定' }}
            </button>
          </div>
          <!-- WeChat -->
          <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div class="flex items-center gap-3">
              <img :src="oauthIcons.wechat" alt="微信" class="w-8 h-8" />
              <div>
                <p class="text-sm font-medium text-gray-900">微信</p>
                <p class="text-xs text-gray-500">{{ oauthStatus.wechat ? '已绑定' : '未绑定' }}</p>
              </div>
            </div>
            <button @click="bindOAuth('wechat')" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
              {{ oauthStatus.wechat ? '解绑' : '绑定' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- API Keys -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
      <div class="p-6 border-b border-gray-200 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">API Keys</h3>
          <p class="mt-1 text-sm text-gray-500">管理你的 API 访问令牌</p>
        </div>
        <button @click="generateKey" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          生成 Key
        </button>
      </div>
      <div class="p-6">
        <div v-if="apiKeys.length === 0" class="text-center py-8 text-gray-500">
          暂无 API Key，点击上方按钮生成
        </div>
        <div v-else class="space-y-3">
          <div v-for="key in apiKeys" :key="key.id" class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div>
              <p class="text-sm font-medium text-gray-900">{{ key.name || '默认 Key' }}</p>
              <p class="text-xs text-gray-500 font-mono mt-1">{{ maskKey(key.key) }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="copyKey(key.key)" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">复制</button>
              <button @click="deleteKey(key.id)" class="rounded-lg border border-red-300 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50">删除</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Password Change -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">修改密码</h3>
      </div>
      <div class="p-6 space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">当前密码</label>
            <input v-model="passwordForm.current" type="password" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="输入当前密码" />
          </div>
          <div></div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">新密码</label>
            <input v-model="passwordForm.new" type="password" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="输入新密码" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">确认新密码</label>
            <input v-model="passwordForm.confirm" type="password" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="再次输入新密码" />
          </div>
        </div>
        <div class="flex justify-end pt-2">
          <button @click="changePassword" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition">
            修改密码
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuth } from '@/store/auth'

const { currentUser, setUser } = useAuth()
const saving = ref(false)

const profile = reactive({
  username: '',
  email: '',
  phone: '',
})

const passwordForm = reactive({
  current: '',
  new: '',
  confirm: '',
})

const oauthStatus = reactive({
  github: false,
  google: false,
  qq: false,
  wechat: false,
})

// OAuth 图标 URL
const oauthIcons = {
  github: 'https://upload.o51.com/ico-svg/ico/github.svg',
  google: 'https://upload.o51.com/ico-svg/ico/google.svg',
  qq: 'https://upload.o51.com/ico-svg/ico/qq.svg',
  wechat: 'https://upload.o51.com/ico-svg/ico/weixin.svg',
}

const apiKeys = ref([])

// 手机号绑定相关
const phoneCodeSent = ref(false)
const phoneCode = ref('')
const phoneCountdown = ref(0)
let phoneTimer = null

const isValidPhone = computed(() => /^1[3-9]\d{9}$/.test(profile.phone))

const roleLabel = computed(() => {
  const role = currentUser.value?.role
  if (role === 100) return 'Root 管理员'
  if (role === 10) return '管理员'
  return '普通用户'
})

onMounted(async () => {
  await loadUserProfile()
  loadApiKeys()
  loadOAuthStatus()
})

async function loadUserProfile() {
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/user/self', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    const json = await res.json()
    if (json.success && json.data) {
      profile.username = json.data.username || ''
      profile.email = json.data.email || ''
      profile.phone = json.data.phone || ''
      setUser({ ...currentUser.value, ...json.data })
    }
  } catch (e) {
    console.error('Failed to load user profile:', e)
  }
}

async function saveProfile() {
  saving.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/user/self', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
      body: JSON.stringify({ email: profile.email })
    })
    const json = await res.json()
    if (json.success) {
      setUser({ ...currentUser.value, email: profile.email })
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

async function sendPhoneCode() {
  if (!isValidPhone.value) { alert('请输入正确的手机号'); return }
  saving.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/sms/send-code', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
      body: JSON.stringify({ phone: profile.phone, type: 'bind' })
    })
    const json = await res.json()
    if (json.success) {
      phoneCodeSent.value = true
      phoneCountdown.value = 60
      phoneTimer = setInterval(() => {
        phoneCountdown.value--
        if (phoneCountdown.value <= 0) { clearInterval(phoneTimer); phoneTimer = null }
      }, 1000)
      alert('验证码已发送')
    } else {
      alert('发送失败: ' + (json.message || '未知错误'))
    }
  } catch (e) {
    alert('网络错误: ' + e.message)
  } finally { saving.value = false }
}

async function bindPhone() {
  if (!phoneCode.value || phoneCode.value.length !== 6) { alert('请输入6位验证码'); return }
  saving.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    const verifyRes = await fetch('/api/sms/verify-code', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
      body: JSON.stringify({ phone: profile.phone, code: phoneCode.value, type: 'bind' })
    })
    const verifyJson = await verifyRes.json()
    if (verifyJson.success) {
      const res = await fetch('/api/user/self', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
        body: JSON.stringify({ phone: profile.phone })
      })
      const json = await res.json()
      if (json.success) {
        setUser({ ...currentUser.value, phone: profile.phone })
        alert('手机号绑定成功！')
        phoneCodeSent.value = false; phoneCode.value = ''; phoneCountdown.value = 0
      } else {
        alert('绑定失败: ' + (json.message || '未知错误'))
      }
    } else {
      alert('验证码错误: ' + (verifyJson.message || '未知错误'))
    }
  } catch (e) {
    alert('网络错误: ' + e.message)
  } finally { saving.value = false }
}

function bindOAuth(provider) {
  if (oauthStatus[provider]) { alert('解绑功能开发中'); return }
  window.location.href = `/api/oauth/${provider}`
}

async function loadOAuthStatus() {
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/user/self', { headers: { 'Authorization': `Bearer ${token}` } })
    const json = await res.json()
    if (json.success && json.data) {
      const providers = json.data.oauth_providers || []
      oauthStatus.github = providers.includes('github')
      oauthStatus.google = providers.includes('google')
      oauthStatus.qq = providers.includes('qq')
      oauthStatus.wechat = providers.includes('wechat')
    }
  } catch (e) { console.error('Failed to load OAuth status:', e) }
}

async function loadApiKeys() {
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/token/self', { headers: { 'Authorization': `Bearer ${token}` } })
    const json = await res.json()
    if (json.success && json.data) {
      // TokenController 返回 {items: [...], total, page, ...}
      apiKeys.value = json.data.items || json.data.rows || []
    }
  } catch (e) {
    console.error('Failed to load API keys:', e)
  }
}

async function generateKey() {
  saving.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/token/self', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
      body: JSON.stringify({ name: '默认 Key', unlimited_quota: true })
    })
    const json = await res.json()
    if (json.success) {
      alert('Key 生成成功！请复制并妥善保管')
      await loadApiKeys()
    } else {
      alert('生成失败: ' + (json.message || '未知错误'))
    }
  } catch (e) {
    alert('网络错误: ' + e.message)
  } finally { saving.value = false }
}

function maskKey(key) {
  if (!key) return ''
  return key.substring(0, 8) + '...' + key.substring(key.length - 4)
}

function copyKey(key) {
  navigator.clipboard.writeText(key)
  alert('已复制到剪贴板')
}

async function deleteKey(id) {
  if (!confirm('确定删除此 Key？删除后将无法使用')) return
  saving.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch(`/api/token/self/${id}`, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${token}` }
    })
    const json = await res.json()
    if (json.success) { alert('删除成功'); await loadApiKeys() }
    else { alert('删除失败: ' + (json.message || '未知错误')) }
  } catch (e) {
    alert('网络错误: ' + e.message)
  } finally { saving.value = false }
}

async function changePassword() {
  if (!passwordForm.current || !passwordForm.new || !passwordForm.confirm) { alert('请填写完整密码信息'); return }
  if (passwordForm.new !== passwordForm.confirm) { alert('两次输入的新密码不一致'); return }
  if (passwordForm.new.length < 6) { alert('密码长度不能少于6位'); return }
  saving.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/user/self', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
      body: JSON.stringify({ old_password: passwordForm.current, new_password: passwordForm.new })
    })
    const json = await res.json()
    if (json.success) {
      alert('密码修改成功！')
      passwordForm.current = ''; passwordForm.new = ''; passwordForm.confirm = ''
    } else {
      alert('修改失败: ' + (json.message || '未知错误'))
    }
  } catch (e) {
    alert('网络错误: ' + e.message)
  } finally { saving.value = false }
}
</script>