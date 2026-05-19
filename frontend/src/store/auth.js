import { ref, computed } from 'vue'

// State
const user = ref(null)
const isLoggedIn = ref(false)
const token = ref('')

// Load from sessionStorage on init
function initAuth() {
  const storedToken = sessionStorage.getItem('access_token')
  const storedUser = sessionStorage.getItem('user')
  if (storedToken) {
    token.value = storedToken
    isLoggedIn.value = true
    if (storedUser) {
      try {
        user.value = JSON.parse(storedUser)
      } catch {
        user.value = null
      }
    }
  }
}

function setUser(userData) {
  user.value = userData
  sessionStorage.setItem('user', JSON.stringify(userData))
}

function setToken(tokenValue) {
  token.value = tokenValue
  sessionStorage.setItem('access_token', tokenValue)
  isLoggedIn.value = true
}

function clearAuth() {
  user.value = null
  token.value = ''
  isLoggedIn.value = false
  sessionStorage.removeItem('access_token')
  sessionStorage.removeItem('user')
}

// Computed
const currentUser = computed(() => user.value)
const isRoot = computed(() => user.value?.role === 100)
const isAdmin = computed(() => user.value?.role >= 10)
const currentRole = computed(() => {
  if (!user.value) return 0
  return user.value.role
})

export function useAuth() {
  initAuth()

  return {
    user,
    token,
    isLoggedIn,
    currentUser,
    isRoot,
    isAdmin,
    currentRole,
    setUser,
    setToken,
    clearAuth,
    initAuth,
  }
}

export default useAuth
