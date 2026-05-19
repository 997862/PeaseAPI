<template>
  <div class="space-y-6">
    <!-- Page header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-gray-900">用户管理</h2>
        <p class="mt-1 text-sm text-gray-500">管理系统中的所有用户账号</p>
      </div>
      <button
        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 transition"
        @click="showCreateModal = true"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        创建用户
      </button>
    </div>

    <!-- Filters -->
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
          <input
            v-model="searchKeyword"
            type="text"
            placeholder="搜索用户名、邮箱或显示名称..."
            class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
            @keyup.enter="loadUsers"
          />
        </div>
        <select
          v-model="filterStatus"
          class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
          @change="loadUsers"
        >
          <option value="">全部状态</option>
          <option value="1">启用</option>
          <option value="0">禁用</option>
          <option value="2">未激活</option>
          <option value="3">审核中</option>
        </select>
        <select
          v-model="filterRole"
          class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
          @change="loadUsers"
        >
          <option value="">全部角色</option>
          <option value="1">普通用户</option>
          <option value="10">管理员</option>
          <option value="100">Root</option>
        </select>
        <button
          class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition"
          @click="loadUsers"
        >
          搜索
        </button>
      </div>
    </div>

    <!-- Users table -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">角色</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">配额</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">已用配额</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">分组</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">注册时间</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-if="loading">
              <td colspan="8" class="py-8 text-center text-gray-500">加载中...</td>
            </tr>
            <tr v-else-if="users.length === 0">
              <td colspan="8" class="py-8 text-center text-gray-500">暂无用户数据</td>
            </tr>
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-medium text-gray-600">
                    {{ user.username?.charAt(0).toUpperCase() || 'U' }}
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ user.username }}</p>
                    <p class="text-xs text-gray-500">{{ user.email || '-' }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3">
                <span :class="`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getRoleColor(user.role)}`">
                  {{ getRoleLabel(user.role) }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ formatQuota(user.quota) }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ formatQuota(user.used_quota) }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ user.group || 'default' }}</td>
              <td class="px-4 py-3">
                <span :class="`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getStatusColor(user.status)}`">
                  {{ getStatusLabel(user.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(user.created_at) }}</td>
              <td class="px-4 py-3 text-right text-sm font-medium">
                <button class="text-primary-600 hover:text-primary-500 mr-3" @click="editUser(user)">编辑</button>
                <button class="text-red-600 hover:text-red-500" @click="deleteUser(user.id)">删除</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3">
        <p class="text-sm text-gray-500">
          共 {{ total }} 条记录，第 {{ page }} / {{ lastPage || 1 }} 页
        </p>
        <div class="flex gap-2">
          <button
            :disabled="page <= 1"
            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="changePage(page - 1)"
          >
            上一页
          </button>
          <button
            :disabled="page >= lastPage"
            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="changePage(page + 1)"
          >
            下一页
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Modal
      :show="showCreateModal || editingUser"
      :title="editingUser ? '编辑用户' : '创建用户'"
      @close="closeModal"
    >
      <form class="space-y-4" @submit.prevent="saveUser">
        <div v-if="modalError" class="rounded-lg bg-red-50 p-3 text-sm text-red-700 border border-red-200">
          {{ modalError }}
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">用户名 <span class="text-red-500">*</span></label>
          <input
            v-model="userForm.username"
            type="text"
            required
            :disabled="!!editingUser"
            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none disabled:bg-gray-100"
          />
        </div>

        <div v-if="!editingUser">
          <label class="block text-sm font-medium text-gray-700 mb-1">密码 <span class="text-red-500">*</span></label>
          <input
            v-model="userForm.password"
            type="password"
            :required="!editingUser"
            minlength="8"
            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">邮箱</label>
          <input
            v-model="userForm.email"
            type="email"
            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">角色</label>
            <select v-model="userForm.role" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
              <option :value="1">普通用户</option>
              <option :value="10">管理员</option>
              <option :value="100">Root</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">状态</label>
            <select v-model="userForm.status" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
              <option :value="1">启用</option>
              <option :value="0">禁用</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">配额</label>
            <input
              v-model.number="userForm.quota"
              type="number"
              min="0"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">分组</label>
            <input
              v-model="userForm.group"
              type="text"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
          <input
            v-model="userForm.remark"
            type="text"
            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
          />
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
import { userAPI } from '@/api'
import Modal from '@/components/Modal.vue'

const users = ref([])
const loading = ref(true)
const total = ref(0)
const page = ref(1)
const lastPage = ref(1)
const searchKeyword = ref('')
const filterStatus = ref('')
const filterRole = ref('')

const showCreateModal = ref(false)
const editingUser = ref(null)
const modalLoading = ref(false)
const modalError = ref('')

const userForm = reactive({
  username: '',
  password: '',
  email: '',
  role: 1,
  status: 1,
  quota: 0,
  group: 'default',
  remark: '',
})

async function loadUsers() {
  loading.value = true
  try {
    const params = {
      page: page.value,
      per_page: 10,
    }
    if (searchKeyword.value) params.keyword = searchKeyword.value
    if (filterStatus.value) params.status = filterStatus.value
    if (filterRole.value) params.role = filterRole.value

    const res = await userAPI.list(params)
    if (res.success) {
      const data = res.data
      users.value = data.items || []
      total.value = data.total || 0
      page.value = data.page || 1
      lastPage.value = data.last_page || 1
    }
  } catch (err) {
    users.value = []
  } finally {
    loading.value = false
  }
}

function changePage(p) {
  page.value = p
  loadUsers()
}

function editUser(user) {
  editingUser.value = user
  Object.assign(userForm, {
    username: user.username || '',
    password: '',
    email: user.email || '',
    role: user.role || 1,
    status: user.status ?? 1,
    quota: user.quota || 0,
    group: user.group || 'default',
    remark: user.remark || '',
  })
  modalError.value = ''
}

function closeModal() {
  showCreateModal.value = false
  editingUser.value = null
  modalError.value = ''
  resetForm()
}

function resetForm() {
  userForm.username = ''
  userForm.password = ''
  userForm.email = ''
  userForm.role = 1
  userForm.status = 1
  userForm.quota = 0
  userForm.group = 'default'
  userForm.remark = ''
}

async function saveUser() {
  modalLoading.value = true
  modalError.value = ''

  try {
    if (editingUser.value) {
      const { username, password, ...updateData } = userForm
      const res = await userAPI.update(editingUser.value.id, updateData)
      if (res.success) {
        closeModal()
        loadUsers()
      } else {
        modalError.value = res.message || '更新失败'
      }
    } else {
      const res = await userAPI.create(userForm)
      if (res.success) {
        closeModal()
        loadUsers()
      } else {
        modalError.value = res.message || '创建失败'
      }
    }
  } catch (err) {
    modalError.value = err.message || '操作失败'
  } finally {
    modalLoading.value = false
  }
}

async function deleteUser(id) {
  if (!confirm('确定要删除该用户吗？此操作不可撤销。')) return

  try {
    const res = await userAPI.delete(id)
    if (res.success) {
      loadUsers()
    } else {
      alert(res.message || '删除失败')
    }
  } catch (err) {
    alert(err.message || '删除失败')
  }
}

function formatQuota(quota) {
  const num = Number(quota) || 0
  if (num >= 1000000) return (num / 1000000).toFixed(2) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('zh-CN')
}

function getRoleLabel(role) {
  const map = { 1: '用户', 10: '管理员', 100: 'Root' }
  return map[role] || '用户'
}

function getRoleColor(role) {
  const map = {
    1: 'bg-gray-100 text-gray-700',
    10: 'bg-blue-100 text-blue-700',
    100: 'bg-red-100 text-red-700',
  }
  return map[role] || 'bg-gray-100 text-gray-700'
}

function getStatusLabel(status) {
  const map = { 0: '禁用', 1: '启用', 2: '未激活', 3: '审核中' }
  return map[status] || '未知'
}

function getStatusColor(status) {
  const map = {
    0: 'bg-red-100 text-red-700',
    1: 'bg-green-100 text-green-700',
    2: 'bg-yellow-100 text-yellow-700',
    3: 'bg-orange-100 text-orange-700',
  }
  return map[status] || 'bg-gray-100 text-gray-700'
}

onMounted(() => {
  loadUsers()
})
</script>
