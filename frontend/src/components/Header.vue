<template>
  <header class="sticky top-0 z-30 flex h-16 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
    <!-- Mobile menu button -->
    <button
      class="lg:hidden -m-2.5 p-2.5 text-gray-700 hover:text-gray-900"
      @click="$emit('toggle-sidebar')"
    >
      <span class="sr-only">打开侧边栏</span>
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>

    <!-- Page title -->
    <div class="flex flex-1 items-center gap-x-4 self-stretch lg:gap-x-6">
      <h1 class="text-lg font-semibold text-gray-900">{{ pageTitle }}</h1>
    </div>

    <!-- Right side -->
    <div class="flex items-center gap-x-4">
      <!-- Notice badge -->
      <button class="relative rounded-full p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100">
        <span class="sr-only">查看通知</span>
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <span v-if="hasNotice" class="absolute top-1 right-1 h-2 w-2 rounded-full bg-red-500" />
      </button>

      <!-- User dropdown -->
      <div class="relative" ref="dropdownRef">
        <button
          class="flex items-center gap-x-2 rounded-full bg-white p-1 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50"
          @click="dropdownOpen = !dropdownOpen"
        >
          <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-primary-700">
            {{ userInitial }}
          </div>
          <span class="hidden sm:block">{{ currentUser?.username }}</span>
          <svg class="hidden h-5 w-5 text-gray-400 sm:block" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
          </svg>
        </button>

        <!-- Dropdown menu -->
        <div
          v-if="dropdownOpen"
          class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5"
        >
          <div class="px-4 py-2 border-b border-gray-100">
            <p class="text-sm font-medium text-gray-900">{{ currentUser?.username }}</p>
            <p class="text-xs text-gray-500">{{ roleLabel }}</p>
          </div>
          <router-link
            to="/dashboard/profile"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
            @click="dropdownOpen = false"
          >
            个人资料
          </router-link>
          <button
            class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"
            @click="handleLogout"
          >
            退出登录
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/store/auth'
import { authAPI } from '@/api'

defineEmits(['toggle-sidebar'])

const router = useRouter()
const route = useRoute()
const { currentUser, clearAuth, isAdmin } = useAuth()

const dropdownOpen = ref(false)
const dropdownRef = ref(null)
const hasNotice = ref(false)

const userInitial = computed(() => {
  const name = currentUser.value?.username || 'U'
  return name.charAt(0).toUpperCase()
})

const roleLabel = computed(() => {
  const role = currentUser.value?.role
  if (role === 100) return 'Root 管理员'
  if (role === 10) return '管理员'
  return '普通用户'
})

const pageTitle = computed(() => route.meta?.title || 'PeaseAI')

async function handleLogout() {
  dropdownOpen.value = false
  try {
    await authAPI.logout()
  } catch {
    // Ignore logout errors
  }
  clearAuth()
  router.push('/login')
}

function handleClickOutside(event) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    dropdownOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>
