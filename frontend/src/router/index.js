import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@/store/auth'

const routes = [
  {
    path: '/',
    name: 'Landing',
    component: () => import('@/views/Landing.vue'),
    meta: { requiresAuth: false, title: 'PeaseAI - API 管理平台' },
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { requiresAuth: false, title: '登录' },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/Register.vue'),
    meta: { requiresAuth: false, title: '注册' },
  },
  {
    path: '/dashboard',
    component: () => import('@/components/Layout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/Dashboard.vue'),
        meta: { title: '仪表盘' },
      },
      {
        path: 'users',
        name: 'Users',
        component: () => import('@/views/Users.vue'),
        meta: { title: '用户管理', requiresAdmin: true },
      },
      {
        path: 'channels',
        name: 'Channels',
        component: () => import('@/views/Channels.vue'),
        meta: { title: '渠道管理', requiresAdmin: true },
      },
      {
        path: 'tokens',
        name: 'Tokens',
        component: () => import('@/views/Tokens.vue'),
        meta: { title: 'Token 管理' },
      },
      {
        path: 'logs',
        name: 'Logs',
        component: () => import('@/views/Logs.vue'),
        meta: { title: '日志查看' },
      },
      {
        path: 'settings',
        name: 'Settings',
        component: () => import('@/views/Settings.vue'),
        meta: { title: '系统设置', requiresAdmin: true },
      },
      {
        path: 'mail-templates',
        name: 'MailTemplates',
        component: () => import('@/views/MailTemplates.vue'),
        meta: { title: '邮件模板', requiresAdmin: true },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory('/'),
  routes,
})

router.beforeEach((to, from, next) => {
  const { initAuth, isLoggedIn, isAdmin } = useAuth()
  initAuth()

  if (to.meta.requiresAuth !== false && !isLoggedIn.value) {
    next({ name: 'Login', query: { redirect: to.fullPath } })
    return
  }

  if (to.meta.requiresAdmin && !isAdmin.value) {
    next({ name: 'Dashboard' })
    return
  }

  if (to.meta.title && to.name !== 'Landing') {
    document.title = `${to.meta.title} - PeaseAPI 管理平台`
  }

  next()
})

export default router
