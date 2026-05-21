<template>
  <aside
    class="fixed inset-y-0 left-0 z-50 w-64 transform bg-white shadow-lg transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0"
    :class="open ? 'translate-x-0' : '-translate-x-full'"
  >
    <div class="flex h-full flex-col">
      <!-- Logo -->
      <div class="flex h-16 items-center justify-between border-b border-gray-200 px-6">
        <div class="flex items-center gap-2">
          <img :src="logoUrl" alt="PeaseAI" class="h-8 w-8 rounded-lg" />
          <span class="text-lg font-bold text-gray-900">PeaseAI</span>
        </div>
        <button class="lg:hidden text-gray-500 hover:text-gray-700" @click="$emit('close')">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto px-4 py-4">
        <div class="space-y-1">
          <router-link
            v-for="item in navItems"
            :key="item.path"
            :to="item.path"
            class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition"
            :class="{ 'bg-primary-50 text-primary-600': route.path === item.path }"
          >
            <component :is="item.icon" class="h-5 w-5" />
            {{ item.name }}
          </router-link>
        </div>
      </nav>

      <!-- Footer -->
      <div class="border-t border-gray-200 p-4">
        <div class="flex items-center gap-3">
          <img :src="logoUrl" alt="User" class="h-8 w-8 rounded-full bg-gray-200" />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ currentUser?.username }}</p>
            <p class="text-xs text-gray-500 truncate">{{ currentUser?.email }}</p>
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useAuth } from '@/store/auth'

const route = useRoute()
const { currentUser } = useAuth()
const logoUrl = '/peaseapi-logo.png'

defineProps({
  open: Boolean,
})

const navItems = [
  { path: '/dashboard', name: '仪表盘', icon: 'HomeIcon' },
  { path: '/tokens', name: 'Token 管理', icon: 'KeyIcon' },
  { path: '/logs', name: '日志查看', icon: 'DocumentTextIcon' },
]

defineEmits(['close'])
</script>
