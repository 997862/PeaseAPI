<template>
  <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-primary-50 to-indigo-100 px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="flex justify-center mb-8">
        <div class="flex items-center gap-3">
          <img :src="logoUrl" alt="PeaseAI" class="h-12 w-12 rounded-xl shadow-lg" />
          <span class="text-2xl font-bold text-gray-900">PeaseAI</span>
        </div>
      </div>

      <!-- Register card -->
      <div class="rounded-2xl bg-white p-8 shadow-xl">
        <h2 class="text-center text-2xl font-bold text-gray-900 mb-6">注册新账号</h2>

        <form class="space-y-4" @submit.prevent="handleRegister">
          <div v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700 border border-red-200">{{ error }}</div>

          <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
            <input id="username" v-model="form.username" type="text" required autocomplete="username" class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none transition" placeholder="请输入用户名" />
          </div>

          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">邮箱</label>
            <input id="email" v-model="form.email" type="email" required autocomplete="email" class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none transition" placeholder="请输入邮箱" />
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">密码</label>
            <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'" required autocomplete="new-password" class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none transition" placeholder="请输入密码（至少6位）" />
          </div>

          <div class="flex items-center">
            <input id="show-password" v-model="showPassword" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            <label for="show-password" class="ml-2 text-sm text-gray-600">显示密码</label>
          </div>

          <button type="submit" :disabled="loading" class="flex w-full justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed transition">
            <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
            {{ loading ? '注册中...' : '注册' }}
          </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-600">
          已有账号？<router-link to="/login" class="font-medium text-primary-600 hover:text-primary-500">立即登录</router-link>
        </div>
      </div>

      <p class="mt-8 text-center text-xs text-gray-500">&copy; 2026 PeaseAI. All rights reserved.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { authAPI } from '@/api'

const router = useRouter()
const logoUrl = '/peaseapi-logo.png'

const form = reactive({ username: '', email: '', password: '' })
const loading = ref(false)
const error = ref('')
const showPassword = ref(false)

async function handleRegister() {
  error.value = ''
  loading.value = true
  try {
    const res = await authAPI.register(form)
    if (res.success) {
      router.push({ path: '/login', query: { registered: '1' } })
    } else {
      error.value = res.message || '注册失败'
    }
  } catch (err) {
    error.value = err.message || '网络连接失败'
  } finally {
    loading.value = false
  }
}
</script>
