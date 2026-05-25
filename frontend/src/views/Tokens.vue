<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-900">Token 管理</h2>
        <p class="mt-1 text-sm text-gray-500">管理你的 API 访问令牌</p>
      </div>
      <button @click="showCreate = true" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 transition">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        创建 Token
      </button>
    </div>

    <!-- Token List -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">名称</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Token</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">额度</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">已用</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">状态</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">过期时间</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">操作</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="loading"><td colspan="7" class="py-8 text-center text-gray-500">加载中...</td></tr>
          <tr v-else-if="tokens.length === 0"><td colspan="7" class="py-8 text-center text-gray-500">暂无 Token，请创建</td></tr>
          <tr v-for="t in tokens" :key="t.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ t.name || '-' }}</td>
            <td class="px-4 py-3 text-sm">
              <code class="bg-gray-100 px-2 py-0.5 rounded text-xs">{{ t.key }}</code>
              <button @click="copyToken(t.key)" class="ml-2 text-xs text-primary-600 hover:text-primary-500">复制</button>
            </td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ fq(t.remain_quota) }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ fq(t.used_quota) }}</td>
            <td class="px-4 py-3 text-sm">
              <span :class="`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${t.status === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`">
                {{ t.status === 1 ? '启用' : '禁用' }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ formatExpiredTime(t.expired_time) }}</td>
            <td class="px-4 py-3 text-right text-sm">
              <button @click="editToken(t)" class="text-primary-600 hover:text-primary-500 mr-3">编辑</button>
              <button @click="deleteToken(t.id)" class="text-red-600 hover:text-red-500">删除</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreate || editingToken" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ editingToken ? '编辑 Token' : '创建 Token' }}</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">名称</label>
            <input v-model="form.name" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="默认 Token" />
          </div>
          <div v-if="!editingToken">
            <label class="block text-sm font-medium text-gray-700 mb-1">额度</label>
            <input v-model.number="form.quota" type="number" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="-1 为无限" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">过期时间</label>
            <input v-model="form.expired_time" type="datetime-local" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" />
          </div>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button @click="closeModal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">取消</button>
          <button @click="saveToken" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500">保存</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const tokens = ref([])
const loading = ref(true)
const showCreate = ref(false)
const editingToken = ref(null)
const form = ref({ name: '', quota: -1, expired_time: '' })

function fq(v) {
  if (v === null || v === undefined) return '0'
  if (v === -1 || v === 0) return '无限制'
  const n = Number(v)
  if (isNaN(n)) return '0'
  if (n >= 1e9) return (n / 1e9).toFixed(2) + 'B'
  if (n >= 1e6) return (n / 1e6).toFixed(2) + 'M'
  if (n >= 1e3) return (n / 1e3).toFixed(2) + 'K'
  return n.toFixed(2)
}

function formatExpiredTime(t) {
  if (!t || t === 0) return '永不过期'
  // 后端返回的是秒级 Unix 时间戳
  const ts = t < 9999999999 ? t * 1000 : t
  return new Date(ts).toLocaleString('zh-CN', {
    year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit', hour12: false
  })
}

function copyToken(key) {
  navigator.clipboard.writeText(key)
  alert('已复制到剪贴板')
}

async function loadTokens() {
  loading.value = true
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch('/api/token/self', { headers: { 'Authorization': `Bearer ${token}` } })
    const json = await res.json()
    if (json.success) {
      // 后端返回格式：{ success: true, data: { rows: [...], count: N } }
      const data = json.data
      tokens.value = data?.rows || data?.items || (Array.isArray(data) ? data : []) || []
    }
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

function editToken(t) {
  editingToken.value = t
  form.value = { name: t.name || '', quota: t.remain_quota || -1, expired_time: t.expired_time || '' }
}

function closeModal() {
  showCreate.value = false
  editingToken.value = null
  form.value = { name: '', quota: -1, expired_time: '' }
}

async function saveToken() {
  try {
    const token = sessionStorage.getItem('access_token')
    const url = editingToken.value ? `/api/token/self/${editingToken.value.id}` : '/api/token/self'
    const method = editingToken.value ? 'PUT' : 'POST'
    
    const body = {
      name: form.value.name,
      remain_quota: form.value.quota,
      unlimited_quota: form.value.quota === -1,
    }
    if (form.value.expired_time) {
      body.expired_time = Math.floor(new Date(form.value.expired_time).getTime() / 1000)
    }
    
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
      body: JSON.stringify(body)
    })
    const json = await res.json()
    if (json.success) {
      closeModal()
      loadTokens()
    } else {
      alert('保存失败: ' + (json.message || '未知错误'))
    }
  } catch (e) { alert('网络错误: ' + e.message) }
}

async function deleteToken(id) {
  if (!confirm('确定删除此 Token？')) return
  try {
    const token = sessionStorage.getItem('access_token')
    const res = await fetch(`/api/token/self/${id}`, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${token}` }
    })
    const json = await res.json()
    if (json.success) loadTokens()
    else alert('删除失败: ' + (json.message || '未知错误'))
  } catch (e) { alert('网络错误: ' + e.message) }
}

onMounted(() => loadTokens())
</script>