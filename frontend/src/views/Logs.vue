<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-xl font-bold text-gray-900">日志查看</h2>
      <p class="mt-1 text-sm text-gray-500">查看 API 调用和登录日志</p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button v-for="tab in visibleTabs" :key="tab.key"
          @click="switchTab(tab.key)"
          :class="[activeTab === tab.key ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- API Logs -->
    <div v-if="activeTab === 'api'" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
      <div class="p-4 border-b border-gray-200 flex flex-wrap items-center gap-3">
        <input v-model="apiSearch" type="text" placeholder="搜索模型..." class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" @keyup.enter="loadApiLogs" />
        <button @click="loadApiLogs" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 whitespace-nowrap">刷新</button>
      </div>
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">时间</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">模型</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Token</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">配额</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">类型</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="loading"><td colspan="5" class="py-8 text-center text-gray-500">加载中...</td></tr>
          <tr v-else-if="logs.length === 0"><td colspan="5" class="py-8 text-center text-gray-500">暂无日志</td></tr>
          <tr v-for="l in logs" :key="l.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ fmtTime(l.created_at) }}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ l.model_name || '-' }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ l.total_tokens || 0 }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ fq(l.quota) }}</td>
            <td class="px-4 py-3 text-sm">{{ typeLabel(l.type) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Login Logs -->
    <div v-if="activeTab === 'login'" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
      <div class="p-4 border-b border-gray-200 flex flex-wrap items-center gap-3">
        <button @click="loadLoginLogs" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 whitespace-nowrap">刷新</button>
      </div>
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">时间</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">用户</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">位置</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">设备</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="loading"><td colspan="5" class="py-8 text-center text-gray-500">加载中...</td></tr>
          <tr v-else-if="loginLogs.length === 0"><td colspan="5" class="py-8 text-center text-gray-500">暂无登录日志</td></tr>
          <tr v-for="l in loginLogs" :key="l.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ fmtTime(l.login_time) }}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ l.username || '-' }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ l.ip || '-' }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ getLocation(l.ip) }}</td>
            <td class="px-4 py-3 text-sm text-gray-500 truncate max-w-[200px]" :title="l.user_agent || ''">{{ formatDevice(l.user_agent) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Admin Logs (仅管理员可见) -->
    <div v-if="activeTab === 'admin'" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
      <div class="p-4 border-b border-gray-200 flex flex-wrap items-center gap-3">
        <button @click="loadAdminLogs" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 whitespace-nowrap">刷新</button>
      </div>
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">时间</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">管理员</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="loading"><td colspan="4" class="py-8 text-center text-gray-500">加载中...</td></tr>
          <tr v-else-if="adminLogs.length === 0"><td colspan="4" class="py-8 text-center text-gray-500">暂无操作日志</td></tr>
          <tr v-for="l in adminLogs" :key="l.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ fmtTime(l.created_at) }}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ l.username || '-' }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ l.action || l.description || '-' }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ l.ip || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '@/store/auth'

const { isAdmin } = useAuth()

const activeTab = ref('api')
const loading = ref(false)
const logs = ref([])
const loginLogs = ref([])
const adminLogs = ref([])
const apiSearch = ref('')
const ipCache = ref({})

// 根据角色显示不同的标签页
const tabs = [
  { key: 'api', name: '📡 API 日志', adminOnly: false },
  { key: 'login', name: '🔑 登录日志', adminOnly: false },
  { key: 'admin', name: '📋 操作日志', adminOnly: true },
]

const visibleTabs = computed(() => {
  return tabs.filter(tab => !tab.adminOnly || isAdmin.value)
})

function fq(v) {
  if (v === null || v === undefined || v === 0) return '0'
  const n = Number(v)
  if (isNaN(n)) return '0'
  if (n >= 1e9) return (n / 1e9).toFixed(2) + 'B'
  if (n >= 1e6) return (n / 1e6).toFixed(2) + 'M'
  if (n >= 1e3) return (n / 1e3).toFixed(2) + 'K'
  return n.toFixed(2)
}

function fmtTime(t) {
  if (!t) return '-'
  // 后端返回的是秒级 Unix 时间戳，JS Date 需要毫秒级
  if (t < 9999999999) t = t * 1000
  return new Date(t).toLocaleString('zh-CN')
}

function typeLabel(t) {
  return { 1: 'API 调用', 2: '管理操作', 3: '系统事件' }[t] || t || '-'
}

function formatDevice(ua) {
  if (!ua) return '-'
  const browsers = [
    [/Edg\//, 'Edge'],
    [/Chrome\//, 'Chrome'],
    [/Firefox\//, 'Firefox'],
    [/Safari\//, 'Safari'],
    [/MSIE|Trident\//, 'IE'],
  ]
  let browser = 'Unknown'
  for (const [re, name] of browsers) {
    if (re.test(ua)) { browser = name; break }
  }
  const devices = [
    [/iPhone/, 'iPhone'],
    [/iPad/, 'iPad'],
    [/Android/, 'Android'],
    [/Windows/, 'Windows'],
    [/Macintosh|Mac OS X/, 'macOS'],
    [/Linux/, 'Linux'],
  ]
  let device = 'Unknown'
  for (const [re, name] of devices) {
    if (re.test(ua)) { device = name; break }
  }
  return `${device} / ${browser}`
}

function getLocation(ip) {
  if (!ip) return '-'
  if (ipCache.value[ip] !== undefined) {
    return ipCache.value[ip] || '-'
  }
  return '解析中...'
}

async function switchTab(tab) {
  activeTab.value = tab
  if (tab === 'api' && logs.value.length === 0) loadApiLogs()
  else if (tab === 'login' && loginLogs.value.length === 0) loadLoginLogs()
  else if (tab === 'admin' && adminLogs.value.length === 0) loadAdminLogs()
}

async function loadApiLogs() {
  loading.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    // 普通用户使用 /api/log/self 查看自己的日志，管理员使用 /api/logs 查看全部
    const endpoint = isAdmin.value ? '/api/logs' : '/api/log/self'
    const res = await fetch(`${endpoint}?page=1&per_page=50&search=${apiSearch.value}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    const json = await res.json()
    if (json.success) logs.value = json.data?.rows || json.data?.items || json.data || []
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

async function loadLoginLogs() {
  loading.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    // 登录日志仅管理员可查看
    if (!isAdmin.value) {
      loginLogs.value = []
      loading.value = false
      return
    }
    const res = await fetch('/api/login-logs?page=1&per_page=50', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    const json = await res.json()
    if (json.success) {
      loginLogs.value = json.data?.rows || json.data?.items || json.data || []
      await resolveIPs()
    }
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

async function resolveIPs() {
  const ips = [...new Set((loginLogs.value || []).map(l => l.login_ip || l.ip).filter(Boolean))]
  if (ips.length === 0) return
  
  const uncached = ips.filter(ip => ipCache.value[ip] === undefined)
  if (uncached.length === 0) return

  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/ip-location', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({ ips: uncached })
    })
    const json = await res.json()
    if (json.success) {
      ipCache.value = { ...ipCache.value, ...json.data }
    }
  } catch (e) { console.error('IP resolve failed:', e) }
}

async function loadAdminLogs() {
  loading.value = true
  try {
    // 操作日志仅管理员可查看
    if (!isAdmin.value) {
      adminLogs.value = []
      loading.value = false
      return
    }
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/admin-logs?page=1&per_page=50', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    const json = await res.json()
    if (json.success) adminLogs.value = json.data?.rows || json.data?.items || json.data || []
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

onMounted(() => loadApiLogs())
</script>