<template>
  <div class="space-y-6">
    <!-- Welcome banner -->
    <div class="rounded-xl bg-gradient-to-r from-primary-600 to-indigo-600 p-6 text-white shadow-lg">
      <h2 class="text-xl font-bold">欢迎回来，{{ currentUser?.username || '用户' }}！</h2>
      <p class="mt-1 text-primary-100">这是你的 API 管理平台仪表盘</p>
      <div class="mt-4 flex flex-wrap gap-4">
        <div class="rounded-lg bg-white/10 px-4 py-2 backdrop-blur-sm">
          <span class="text-xs text-primary-200">当前配额</span>
          <p class="text-lg font-bold">{{ formatQuota(currentUser?.quota || 0) }}</p>
        </div>
        <div class="rounded-lg bg-white/10 px-4 py-2 backdrop-blur-sm">
          <span class="text-xs text-primary-200">已用配额</span>
          <p class="text-lg font-bold">{{ formatQuota(currentUser?.used_quota || 0) }}</p>
        </div>
        <div class="rounded-lg bg-white/10 px-4 py-2 backdrop-blur-sm">
          <span class="text-xs text-primary-200">请求次数</span>
          <p class="text-lg font-bold">{{ currentUser?.request_count || 0 }}</p>
        </div>
      </div>
    </div>

    <!-- Stats grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="stat in stats" :key="stat.name" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">{{ stat.name }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ stat.value }}</p>
          </div>
          <div :class="`flex h-12 w-12 items-center justify-center rounded-lg ${stat.color}`">
            <component :is="stat.icon" class="h-6 w-6 text-white" />
          </div>
        </div>
        <p v-if="stat.change" class="mt-2 text-xs" :class="stat.changeType === 'up' ? 'text-green-600' : 'text-red-600'">
          {{ stat.change }}
        </p>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">快捷操作</h3>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <router-link
          v-for="action in quickActions"
          :key="action.name"
          :to="action.to"
          class="flex flex-col items-center gap-2 rounded-lg border border-gray-200 p-4 text-center text-sm font-medium text-gray-700 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 transition"
        >
          <component :is="action.icon" class="h-6 w-6" />
          {{ action.name }}
        </router-link>
      </div>
    </div>

    <!-- Recent logs -->
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">最近调用日志</h3>
        <router-link to="/dashboard/logs" class="text-sm font-medium text-primary-600 hover:text-primary-500">
          查看全部 →
        </router-link>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">时间</th>
              <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">模型</th>
              <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token 数</th>
              <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">配额</th>
              <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">类型</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-if="logsLoading">
              <td colspan="5" class="py-8 text-center text-gray-500">加载中...</td>
            </tr>
            <tr v-else-if="recentLogs.length === 0">
              <td colspan="5" class="py-8 text-center text-gray-500">暂无日志记录</td>
            </tr>
            <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-gray-50">
              <td class="whitespace-nowrap py-3 text-sm text-gray-500">{{ formatTime(log.created_at) }}</td>
              <td class="whitespace-nowrap py-3 text-sm font-medium text-gray-900">{{ log.model_name || '-' }}</td>
              <td class="whitespace-nowrap py-3 text-sm text-gray-500">{{ log.total_tokens || 0 }}</td>
              <td class="whitespace-nowrap py-3 text-sm text-gray-500">{{ formatQuota(log.quota) }}</td>
              <td class="whitespace-nowrap py-3 text-sm">
                <span :class="`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getTypeColor(log.type)}`">
                  {{ getTypeLabel(log.type) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue'
import { useAuth } from '@/store/auth'
import { logAPI } from '@/api'

const { currentUser, isAdmin } = useAuth()

const recentLogs = ref([])
const logsLoading = ref(true)

const stats = computed(() => {
  const quota = currentUser.value?.quota || 0
  const usedQuota = currentUser.value?.used_quota || 0
  const requestCount = currentUser.value?.request_count || 0
  const totalQuota = quota + usedQuota

  return [
    {
      name: '可用配额',
      value: formatQuota(quota),
      color: 'bg-green-500',
      icon: WalletIcon,
    },
    {
      name: '已用配额',
      value: formatQuota(usedQuota),
      color: 'bg-blue-500',
      icon: ChartIcon,
    },
    {
      name: '总请求数',
      value: requestCount.toLocaleString(),
      color: 'bg-purple-500',
      icon: RequestIcon,
    },
    {
      name: '使用率',
      value: totalQuota > 0 ? ((usedQuota / totalQuota) * 100).toFixed(1) + '%' : '0%',
      color: 'bg-orange-500',
      icon: PercentIcon,
    },
  ]
})

const quickActions = computed(() => {
  const actions = [
    { name: 'Token 管理', to: '/dashboard/tokens', icon: KeyIcon },
    { name: '日志查看', to: '/dashboard/logs', icon: LogIcon },
  ]
  if (isAdmin.value) {
    actions.splice(0, 0,
      { name: '用户管理', to: '/dashboard/users', icon: UsersIcon },
      { name: '渠道管理', to: '/dashboard/channels', icon: ChannelIcon },
    )
  }
  return actions
})

async function loadLogs() {
  logsLoading.value = true
  try {
    const res = await logAPI.list({ page: 1, per_page: 5, sort: '-created_at' })
    if (res.success) {
      recentLogs.value = res.data.items || []
    }
  } catch {
    recentLogs.value = []
  } finally {
    logsLoading.value = false
  }
}

function formatQuota(quota) {
  const num = Number(quota)
  if (num >= 1000000) return (num / 1000000).toFixed(2) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

function formatTime(time) {
  if (!time) return '-'
  const date = new Date(time)
  return date.toLocaleString('zh-CN', {
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getTypeLabel(type) {
  const map = { 1: '文本', 2: '图像', 3: '音频', 4: '嵌入' }
  return map[type] || '文本'
}

function getTypeColor(type) {
  const map = {
    1: 'bg-blue-100 text-blue-700',
    2: 'bg-purple-100 text-purple-700',
    3: 'bg-orange-100 text-orange-700',
    4: 'bg-green-100 text-green-700',
  }
  return map[type] || 'bg-gray-100 text-gray-700'
}

// Icon components
function WalletIcon(props) {
  return h('svg', { ...props, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, [h('path', { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: "2", d: "M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" })])
}
function ChartIcon(props) {
  return h('svg', { ...props, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, [h('path', { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: "2", d: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" })])
}
function RequestIcon(props) {
  return h('svg', { ...props, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, [h('path', { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: "2", d: "M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" })])
}
function PercentIcon(props) {
  return h('svg', { ...props, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, [h('path', { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: "2", d: "M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" }), h('path', { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: "2", d: "M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" })])
}
function KeyIcon(props) {
  return h('svg', { ...props, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, [h('path', { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: "2", d: "M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 0119 9z" })])
}
function LogIcon(props) {
  return h('svg', { ...props, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, [h('path', { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: "2", d: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" })])
}
function UsersIcon(props) {
  return h('svg', { ...props, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, [h('path', { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: "2", d: "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" })])
}
function ChannelIcon(props) {
  return h('svg', { ...props, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, [h('path', { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: "2", d: "M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0" })])
}

onMounted(() => {
  loadLogs()
})
</script>
