<template>
  <div class="space-y-6">
    <!-- Page header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-gray-900">渠道管理</h2>
        <p class="mt-1 text-sm text-gray-500">管理 AI 上游渠道配置</p>
      </div>
      <div class="flex gap-2">
        <button
          class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-orange-400 transition"
          @click="testAllChannels"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
          批量测试
        </button>
        <button
          class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 transition"
          @click="showCreateModal = true"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          创建渠道
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
          <input
            v-model="searchKeyword"
            type="text"
            placeholder="搜索渠道名称..."
            class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
            @keyup.enter="loadChannels"
          />
        </div>
        <select
          v-model="filterType"
          class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
          @change="loadChannels"
        >
          <option value="">全部类型</option>
          <option v-for="t in channelTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
        </select>
        <select
          v-model="filterStatus"
          class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
          @change="loadChannels"
        >
          <option value="">全部状态</option>
          <option value="1">启用</option>
          <option value="2">禁用</option>
          <option value="3">手动禁用</option>
          <option value="4">自动禁用</option>
        </select>
        <button
          class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition"
          @click="loadChannels"
        >
          搜索
        </button>
      </div>
    </div>

    <!-- Channels table -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">名称</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">类型</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">模型数</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">优先级</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">响应时间</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-if="loading">
              <td colspan="8" class="py-8 text-center text-gray-500">加载中...</td>
            </tr>
            <tr v-else-if="channels.length === 0">
              <td colspan="8" class="py-8 text-center text-gray-500">暂无渠道数据</td>
            </tr>
            <tr v-for="ch in channels" :key="ch.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-500">{{ ch.id }}</td>
              <td class="px-4 py-3">
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ ch.name || '-' }}</p>
                  <p class="text-xs text-gray-500 truncate max-w-48">{{ maskKey(ch.key) }}</p>
                </div>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700">
                  {{ getTypeLabel(ch.type) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span :class="`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getChStatusColor(ch.status)}`">
                  {{ getChStatusLabel(ch.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ getModelCount(ch.models) }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ ch.priority || 0 }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ ch.response_time ? ch.response_time + 'ms' : '-' }}</td>
              <td class="px-4 py-3 text-right text-sm font-medium whitespace-nowrap">
                <button class="text-primary-600 hover:text-primary-500 mr-2" @click="testChannel(ch.id)">测试</button>
                <button class="text-primary-600 hover:text-primary-500 mr-2" @click="editChannel(ch)">编辑</button>
                <button class="text-red-600 hover:text-red-500" @click="deleteChannel(ch.id)">删除</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3">
        <p class="text-sm text-gray-500">共 {{ total }} 条记录，第 {{ page }} / {{ lastPage || 1 }} 页</p>
        <div class="flex gap-2">
          <button
            :disabled="page <= 1"
            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="changePage(page - 1)"
          >上一页</button>
          <button
            :disabled="page >= lastPage"
            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="changePage(page + 1)"
          >下一页</button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Modal :show="showCreateModal || editingChannel" :title="editingChannel ? '编辑渠道' : '创建渠道'" @close="closeModal">
      <form class="space-y-4" @submit.prevent="saveChannel">
        <div v-if="modalError" class="rounded-lg bg-red-50 p-3 text-sm text-red-700 border border-red-200">{{ modalError }}</div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">渠道名称</label>
            <input v-model="chForm.name" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">渠道类型 <span class="text-red-500">*</span></label>
            <select v-model="chForm.type" required class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
              <option v-for="t in channelTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">API Key <span class="text-red-500">*</span></label>
          <textarea
            v-model="chForm.key"
            required
            rows="3"
            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none font-mono"
            placeholder="单个 Key 或多个 Key（每行一个）"
          ></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Base URL</label>
          <input v-model="chForm.base_url" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="https://api.openai.com" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">支持的模型</label>
          <textarea
            v-model="chForm.models"
            rows="3"
            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none font-mono"
            placeholder="每行一个模型名称，如 gpt-4"
          ></textarea>
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">优先级</label>
            <input v-model.number="chForm.priority" type="number" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">权重</label>
            <input v-model.number="chForm.weight" type="number" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">分组</label>
            <input v-model="chForm.group" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">状态</label>
            <select v-model="chForm.status" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
              <option :value="1">启用</option>
              <option :value="2">禁用</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">自动封禁</label>
            <select v-model="chForm.auto_ban" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
              <option :value="1">启用</option>
              <option :value="0">禁用</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">测试模型</label>
          <input v-model="chForm.test_model" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="留空则使用第一个模型" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
          <input v-model="chForm.remark" type="text" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" />
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="closeModal">取消</button>
          <button type="submit" :disabled="modalLoading" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50">
            {{ modalLoading ? '保存中...' : '保存' }}
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { channelAPI } from '@/api'
import Modal from '@/components/Modal.vue'

const channels = ref([])
const loading = ref(true)
const total = ref(0)
const page = ref(1)
const lastPage = ref(1)
const searchKeyword = ref('')
const filterType = ref('')
const filterStatus = ref('')

const showCreateModal = ref(false)
const editingChannel = ref(null)
const modalLoading = ref(false)
const modalError = ref('')

const channelTypes = [
  { value: 1, label: 'OpenAI' },
  { value: 14, label: 'Claude (Anthropic)' },
  { value: 15, label: 'Google Gemini' },
  { value: 16, label: '百度文心' },
  { value: 17, label: '智谱清言' },
  { value: 18, label: '阿里通义' },
  { value: 19, label: 'Moonshot' },
  { value: 20, label: 'DeepSeek' },
  { value: 21, label: 'Ollama' },
  { value: 22, label: 'Groq' },
  { value: 23, label: 'Azure OpenAI' },
  { value: 24, label: '自定义代理' },
]

const chForm = reactive({
  name: '',
  type: 1,
  key: '',
  base_url: '',
  models: '',
  priority: 0,
  weight: 0,
  group: 'default',
  status: 1,
  auto_ban: 1,
  test_model: '',
  remark: '',
})

async function loadChannels() {
  loading.value = true
  try {
    const params = { page: page.value, per_page: 10 }
    if (searchKeyword.value) params.keyword = searchKeyword.value
    if (filterType.value) params.type = filterType.value
    if (filterStatus.value) params.status = filterStatus.value

    const res = await channelAPI.list(params)
    if (res.success) {
      channels.value = res.data.items || []
      total.value = res.data.total || 0
      page.value = res.data.page || 1
      lastPage.value = res.data.last_page || 1
    }
  } catch {
    channels.value = []
  } finally {
    loading.value = false
  }
}

function changePage(p) {
  page.value = p
  loadChannels()
}

function editChannel(ch) {
  editingChannel.value = ch
  Object.assign(chForm, {
    name: ch.name || '',
    type: ch.type || 1,
    key: ch.key || '',
    base_url: ch.base_url || '',
    models: ch.models || '',
    priority: ch.priority || 0,
    weight: ch.weight || 0,
    group: ch.group || 'default',
    status: ch.status ?? 1,
    auto_ban: ch.auto_ban ?? 1,
    test_model: ch.test_model || '',
    remark: ch.remark || '',
  })
  modalError.value = ''
}

function closeModal() {
  showCreateModal.value = false
  editingChannel.value = null
  modalError.value = ''
  resetForm()
}

function resetForm() {
  chForm.name = ''
  chForm.type = 1
  chForm.key = ''
  chForm.base_url = ''
  chForm.models = ''
  chForm.priority = 0
  chForm.weight = 0
  chForm.group = 'default'
  chForm.status = 1
  chForm.auto_ban = 1
  chForm.test_model = ''
  chForm.remark = ''
}

async function saveChannel() {
  modalLoading.value = true
  modalError.value = ''

  try {
    if (editingChannel.value) {
      const res = await channelAPI.update(editingChannel.value.id, chForm)
      if (res.success) { closeModal(); loadChannels() }
      else { modalError.value = res.message || '更新失败' }
    } else {
      const res = await channelAPI.create(chForm)
      if (res.success) { closeModal(); loadChannels() }
      else { modalError.value = res.message || '创建失败' }
    }
  } catch (err) {
    modalError.value = err.message || '操作失败'
  } finally {
    modalLoading.value = false
  }
}

async function deleteChannel(id) {
  if (!confirm('确定要删除该渠道吗？')) return
  try {
    const res = await channelAPI.delete(id)
    if (res.success) loadChannels()
    else alert(res.message || '删除失败')
  } catch (err) {
    alert(err.message || '删除失败')
  }
}

async function testChannel(id) {
  try {
    const res = await channelAPI.test(id)
    if (res.success) alert('测试通过！')
    else alert('测试失败: ' + (res.message || '未知错误'))
  } catch (err) {
    alert('测试失败: ' + err.message)
  }
}

async function testAllChannels() {
  if (!confirm('确定要批量测试所有渠道吗？这可能需要一些时间。')) return
  try {
    const res = await channelAPI.testAll()
    alert(res.message || '测试完成')
    loadChannels()
  } catch (err) {
    alert('测试失败: ' + err.message)
  }
}

function maskKey(key) {
  if (!key) return ''
  const lines = key.split('\n').filter(Boolean)
  if (lines.length <= 1) {
    const k = key.trim()
    return k.length > 20 ? k.slice(0, 8) + '...' + k.slice(-4) : k
  }
  return `${lines[0].slice(0, 8)}... (${lines.length} keys)`
}

function getModelCount(models) {
  if (!models) return 0
  return models.split('\n').filter(Boolean).length
}

function getTypeLabel(type) {
  const t = channelTypes.find(t => t.value === type)
  return t ? t.label : '其他'
}

function getChStatusLabel(status) {
  const map = { 1: '启用', 2: '禁用', 3: '手动禁用', 4: '自动禁用' }
  return map[status] || '未知'
}

function getChStatusColor(status) {
  const map = {
    1: 'bg-green-100 text-green-700',
    2: 'bg-gray-100 text-gray-700',
    3: 'bg-orange-100 text-orange-700',
    4: 'bg-red-100 text-red-700',
  }
  return map[status] || 'bg-gray-100 text-gray-700'
}

onMounted(() => loadChannels())
</script>
