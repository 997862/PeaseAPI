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
              <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
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
              <svg class="w-8 h-8" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
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
              <svg class="w-8 h-8" viewBox="0 0 24 24" fill="#12B7F5"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
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
              <svg class="w-8 h-8" viewBox="0 0 24 24" fill="#07C160"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 01.213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 00.167-.054l1.903-1.114a.864.864 0 01.717-.098 10.16 10.16 0 002.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 01-1.162 1.178A1.17 1.17 0 014.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 01-1.162 1.178 1.17 1.17 0 01-1.162-1.178c0-.651.52-1.18 1.162-1.18zm3.35 3.947c-3.568 0-6.464 2.426-6.464 5.42 0 2.993 2.896 5.42 6.464 5.42.773 0 1.513-.116 2.207-.328a.642.642 0 01.532.073l1.412.826a.242.242 0 00.124.04c.119 0 .215-.098.215-.218 0-.054-.021-.106-.035-.159l-.29-1.094a.437.437 0 01.158-.494C21.07 18.522 22 16.853 22 15.358c0-2.994-2.896-5.42-6.464-5.42h-.588zm-1.78 2.735c.477 0 .864.394.864.878a.872.872 0 01-.864.878.872.872 0 01-.864-.878c0-.484.387-.878.864-.878zm3.56 0c.477 0 .864.394.864.878a.872.872 0 01-.864.878.872.872 0 01-.864-.878c0-.484.387-.878.864-.878z"/></svg>
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
  // 从 API 加载完整用户信息
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
      // 更新 auth store 中的用户信息
      setUser({
        ...currentUser.value,
        ...json.data,
      })
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
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        email: profile.email,
      })
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
  if (!isValidPhone.value) {
    alert('请输入正确的手机号')
    return
  }
  saving.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/sms/send-code', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        phone: profile.phone,
        type: 'bind'
      })
    })
    const json = await res.json()
    if (json.success) {
      phoneCodeSent.value = true
      phoneCountdown.value = 60
      phoneTimer = setInterval(() => {
        phoneCountdown.value--
        if (phoneCountdown.value <= 0) {
          clearInterval(phoneTimer)
          phoneTimer = null
        }
      }, 1000)
      alert('验证码已发送')
    } else {
      alert('发送失败: ' + (json.message || '未知错误'))
    }
  } catch (e) {
    alert('网络错误: ' + e.message)
  } finally {
    saving.value = false
  }
}

async function bindPhone() {
  if (!phoneCode.value || phoneCode.value.length !== 6) {
    alert('请输入6位验证码')
    return
  }
  saving.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    // 先验证验证码
    const verifyRes = await fetch('/api/sms/verify-code', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        phone: profile.phone,
        code: phoneCode.value,
        type: 'bind'
      })
    })
    const verifyJson = await verifyRes.json()
    if (verifyJson.success) {
      // 验证码正确，绑定手机号
      const res = await fetch('/api/user/self', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ phone: profile.phone })
      })
      const json = await res.json()
      if (json.success) {
        setUser({ ...currentUser.value, phone: profile.phone })
        alert('手机号绑定成功！')
        phoneCodeSent.value = false
        phoneCode.value = ''
        phoneCountdown.value = 0
      } else {
        alert('绑定失败: ' + (json.message || '未知错误'))
      }
    } else {
      alert('验证码错误: ' + (verifyJson.message || '未知错误'))
    }
  } catch (e) {
    alert('网络错误: ' + e.message)
  } finally {
    saving.value = false
  }
}

function bindOAuth(provider) {
  if (oauthStatus[provider]) {
    alert('解绑功能开发中')
    return
  }
  window.location.href = `/api/oauth/${provider}`
}

async function loadOAuthStatus() {
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/user/self', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    const json = await res.json()
    if (json.success && json.data) {
      const providers = json.data.oauth_providers || []
      oauthStatus.github = providers.includes('github')
      oauthStatus.google = providers.includes('google')
      oauthStatus.qq = providers.includes('qq')
      oauthStatus.wechat = providers.includes('wechat')
    }
  } catch (e) {
    console.error('Failed to load OAuth status:', e)
  }
}

async function loadApiKeys() {
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/token/self', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    const json = await res.json()
    if (json.success) {
      const data = json.data
      apiKeys.value = data?.rows || data?.items || (Array.isArray(data) ? data : []) || []
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
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        name: '默认 Key',
        unlimited_quota: true,
      })
    })
    const json = await res.json()
    if (json.success) {
      alert('Key 生成成功！请复制并妥善保管')
      loadApiKeys()
    } else {
      alert('生成失败: ' + (json.message || '未知错误'))
    }
  } catch (e) {
    alert('网络错误: ' + e.message)
  } finally {
    saving.value = false
  }
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
    if (json.success) {
      alert('删除成功')
      loadApiKeys()
    } else {
      alert('删除失败: ' + (json.message || '未知错误'))
    }
  } catch (e) {
    alert('网络错误: ' + e.message)
  } finally {
    saving.value = false
  }
}

async function changePassword() {
  if (!passwordForm.current || !passwordForm.new || !passwordForm.confirm) {
    alert('请填写完整密码信息')
    return
  }
  if (passwordForm.new !== passwordForm.confirm) {
    alert('两次输入的新密码不一致')
    return
  }
  if (passwordForm.new.length < 6) {
    alert('密码长度不能少于6位')
    return
  }
  saving.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/user/self', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        old_password: passwordForm.current,
        new_password: passwordForm.new,
      })
    })
    const json = await res.json()
    if (json.success) {
      alert('密码修改成功！')
      passwordForm.current = ''
      passwordForm.new = ''
      passwordForm.confirm = ''
    } else {
      alert('修改失败: ' + (json.message || '未知错误'))
    }
  } catch (e) {
    alert('网络错误: ' + e.message)
  } finally {
    saving.value = false
  }
}
</script>