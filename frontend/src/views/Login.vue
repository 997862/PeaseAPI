<template>
  <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-primary-50 to-indigo-100 px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="flex justify-center mb-8">
        <div class="flex items-center gap-3">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-600 shadow-lg">
            <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <span class="text-2xl font-bold text-gray-900">PeaseAI</span>
        </div>
      </div>

      <!-- Login card -->
      <div class="rounded-2xl bg-white p-8 shadow-xl">
        <h2 class="text-center text-2xl font-bold text-gray-900 mb-6">登录管理后台</h2>

        <form class="space-y-5" @submit.prevent="handleLogin">
          <!-- Error message -->
          <div v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700 border border-red-200">
            {{ error }}
          </div>

          <!-- Username -->
          <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
            <input
              id="username"
              v-model="form.username"
              type="text"
              required
              autocomplete="username"
              class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none transition"
              placeholder="请输入用户名"
            />
          </div>

          <!-- Password -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">密码</label>
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              required
              autocomplete="current-password"
              class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none transition"
              placeholder="请输入密码"
            />
          </div>

          <!-- Show password toggle -->
          <div class="flex items-center">
            <input
              id="show-password"
              v-model="showPassword"
              type="checkbox"
              class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            <label for="show-password" class="ml-2 text-sm text-gray-600">显示密码</label>
          </div>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="loading"
            class="flex w-full justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            {{ loading ? '登录中...' : '登录' }}
          </button>
        </form>

        <!-- Register link -->
        <div class="mt-6 text-center text-sm text-gray-600">
          还没有账号？
          <router-link to="/register" class="font-medium text-primary-600 hover:text-primary-500">
            立即注册
          </router-link>
        </div>
      </div>

      <!-- Footer -->
      <p class="mt-8 text-center text-xs text-gray-500">
        &copy; 2026 PeaseAI. All rights reserved.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/store/auth'
import { authAPI } from '@/api'

const router = useRouter()
const route = useRoute()
const { setToken, setUser } = useAuth()

const form = reactive({
  username: '',
  password: '',
})

const loading = ref(false)
const error = ref('')
const showPassword = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true

  try {
    const res = await authAPI.login(form)

    if (res.success) {
      const { access_token, ...userData } = res.data
      setToken(access_token)
      setUser(userData)

      // Redirect to original destination or dashboard
      const redirect = route.query.redirect || '/'
      router.push(redirect)
    } else {
      error.value = res.message || '登录失败'
    }
  } catch (err) {
    error.value = err.message || '网络连接失败'
  } finally {
    loading.value = false
  }
}
</script>
