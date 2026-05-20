<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-xl font-bold text-gray-900">日志查看</h2>
      <p class="mt-1 text-sm text-gray-500">查看 API 调用、登录和操作日志</p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button v-for="tab in tabs" :key="tab.key"
          @click="switchTab(tab.key)"
          :class="[activeTab === tab.key ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- API Logs -->
    <div v-if="activeTab === 'api'" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
      <div class="p-4 border-b border-gray-200 flex flex-wrap items-center gap-3">
        <input v-model="apiSearch" type="text" placeholder="搜索模型或用户名..." class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" @keyup.enter="loadApiLogs" />
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
            <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ fmtTime(l.login_time || l.created_at) }}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ l.username || '-' }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ l.login_ip || l.ip || '-' }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ getLocation(l.login_ip || l.ip) }}</td>
            <td class="px-4 py-3 text-sm text-gray-500 truncate max-w-[200px]" :title="l.user_agent || ''">{{ l.user_agent || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Admin Logs -->
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
import { ref, onMounted } from 'vue'

const activeTab = ref('api')
const loading = ref(false)
const logs = ref([])
const loginLogs = ref([])
const adminLogs = ref([])
const apiSearch = ref('')
const ipCache = ref({})

const tabs = [
  { key: 'api', name: '📡 API 日志' },
  { key: 'login', name: '🔑 登录日志' },
  { key: 'admin', name: '📋 操作日志' },
]

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
  return new Date(t).toLocaleString('zh-CN')
}

function typeLabel(t) {
  return { 1: 'API 调用', 2: '管理操作', 3: '系统事件' }[t] || t || '-'
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
    const token = localStorage.getItem('token')
    const res = await fetch(`/api/logs?page=1&per_page=50&search=${apiSearch.value}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    const json = await res.json()
    if (json.success) logs.value = json.data?.items || json.data || []
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

async function loadLoginLogs() {
  loading.value = true
  try {
    const token = localStorage.getItem('token')
    const res = await fetch('/api/login-logs?page=1&per_page=50', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    const json = await res.json()
    if (json.success) {
      loginLogs.value = json.data?.items || json.data || []
      // 批量解析 IP
      await resolveIPs()
    }
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

async function resolveIPs() {
  const ips = [...new Set(loginLogs.value.map(l => l.login_ip || l.ip).filter(Boolean))]
  if (ips.length === 0) return
  
  const uncached = ips.filter(ip => ipCache.value[ip] === undefined)
  if (uncached.length === 0) return

  try {
    const token = localStorage.getItem('token')
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
      Object.assign(ipCache.value, json.data)
    }
  } catch (e) { console.error('IP resolve failed:', e) }
}

async function loadAdminLogs() {
  loading.value = true
  try {
    const token = localStorage.getItem('token')
    const res = await fetch('/api/admin-logs?page=1&per_page=50', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    const json = await res.json()
    if (json.success) adminLogs.value = json.data?.items || json.data || []
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

onMounted(() => loadApiLogs())
</script>
