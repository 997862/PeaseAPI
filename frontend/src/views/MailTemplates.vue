<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-900">邮件模板</h2>
        <p class="mt-1 text-sm text-gray-500">管理系统邮件模板</p>
      </div>
    </div>

    <!-- Template List -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
      <div class="p-4 border-b border-gray-200 flex flex-wrap gap-3">
        <input v-model="search" type="text" placeholder="搜索模板..." class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" @keyup.enter="loadTemplates" />
        <button @click="loadTemplates" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 whitespace-nowrap">刷新</button>
      </div>

      <div v-if="loading" class="py-8 text-center text-gray-500">加载中...</div>
      <div v-else-if="templates.length === 0" class="py-8 text-center text-gray-500">暂无邮件模板</div>

      <div v-else class="divide-y divide-gray-200">
        <div v-for="t in templates" :key="t.id" class="p-4 hover:bg-gray-50">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-gray-900">{{ t.name }}</h3>
                <span v-if="t.is_system" class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">系统</span>
                <span :class="`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${t.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`">
                  {{ t.is_active ? '启用' : '禁用' }}
                </span>
              </div>
              <p class="mt-1 text-xs text-gray-500">标识: <code class="bg-gray-100 px-1 rounded">{{ t.slug }}</code></p>
              <p class="mt-1 text-sm text-gray-700">主题: {{ t.subject }}</p>
              <p class="mt-1 text-xs text-gray-400">{{ t.description }}</p>
            </div>
            <div class="flex gap-2 ml-4">
              <button @click="editTemplate(t)" class="text-sm text-primary-600 hover:text-primary-500">编辑</button>
              <button @click="previewTemplate(t)" class="text-sm text-gray-600 hover:text-gray-500">预览</button>
              <button @click="testSend(t)" class="text-sm text-green-600 hover:text-green-500">测试</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="editing" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">编辑模板: {{ editing.name }}</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">模板名称</label>
            <input v-model="editForm.name" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">邮件主题</label>
            <input v-model="editForm.subject" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">邮件内容 (HTML)</label>
            <textarea v-model="editForm.content" rows="12" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm font-mono focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"></textarea>
          </div>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button @click="editing = null" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">取消</button>
          <button @click="saveTemplate" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 whitespace-nowrap">保存</button>
        </div>
      </div>
    </div>

    <!-- Preview Modal -->
    <div v-if="previewing" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">邮件预览</h3>
          <button @click="previewing = null" class="text-gray-400 hover:text-gray-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 mb-4">
          <p class="text-sm text-gray-600"><strong>主题:</strong> {{ previewData?.subject }}</p>
        </div>
        <div class="border border-gray-200 rounded-lg overflow-hidden" v-html="previewData?.content"></div>
      </div>
    </div>

    <!-- Test Send Modal -->
    <div v-if="testing" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">测试发送: {{ testing.name }}</h3>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">收件邮箱</label>
          <input v-model="testEmail" type="email" class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" placeholder="test@example.com" />
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button @click="testing = null" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">取消</button>
          <button @click="doTestSend" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 whitespace-nowrap">发送测试</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const templates = ref([])
const loading = ref(true)
const search = ref('')
const editing = ref(null)
const editForm = ref({})
const previewing = ref(false)
const previewData = ref(null)
const testing = ref(null)
const testEmail = ref('')

async function loadTemplates() {
  loading.value = true
  try {
    const token = localStorage.getItem('token')
    const res = await fetch(`/api/mail/templates?page=1&per_page=50&search=${search.value}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    const json = await res.json()
    if (json.code === 200) {
      templates.value = json.data?.items || json.data || []
    } else {
      console.error('Failed to load templates:', json.message)
    }
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

function editTemplate(t) {
  editing.value = t
  editForm.value = { name: t.name, subject: t.subject, content: t.content }
}

async function saveTemplate() {
  try {
    const token = localStorage.getItem('token')
    const res = await fetch(`/api/mail/templates/${editing.value.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
      body: JSON.stringify(editForm.value)
    })
    const json = await res.json()
    if (json.code === 200) {
      editing.value = null
      loadTemplates()
    } else {
      alert('保存失败: ' + (json.message || '未知错误'))
    }
  } catch (e) { alert('网络错误: ' + e.message) }
}

function previewTemplate(t) {
  previewData.value = { subject: t.subject, content: t.content }
  previewing.value = true
}

function testSend(t) {
  testing.value = t
  testEmail.value = ''
}

async function doTestSend() {
  if (!testEmail.value) { alert('请输入邮箱地址'); return }
  try {
    const token = localStorage.getItem('token')
    const res = await fetch(`/api/mail/templates/${testing.value.id}/test`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
      body: JSON.stringify({ to: testEmail.value })
    })
    const json = await res.json()
    if (json.code === 200) {
      alert('测试邮件已发送，请检查收件箱')
      testing.value = null
    } else {
      alert('发送失败: ' + (json.message || '未知错误'))
    }
  } catch (e) { alert('网络错误: ' + e.message) }
}

onMounted(() => loadTemplates())
</script>
