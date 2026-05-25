import axios from 'axios'

const api = axios.create({
  baseURL: '',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    const token = sessionStorage.getItem('access_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => {
    return response.data
  },
  (error) => {
    if (error.response) {
      const { status, data } = error.response
      if (status === 401) {
        sessionStorage.removeItem('access_token')
        sessionStorage.removeItem('user')
        // Don't redirect if already on login page
        if (!window.location.pathname.includes('/login')) {
          window.location.href = import.meta.env.BASE_URL + 'login'
        }
        return Promise.reject(new Error('登录已过期，请重新登录'))
      }
      if (status === 403) {
        return Promise.reject(new Error(data.message || '没有权限执行此操作'))
      }
      return Promise.reject(new Error(data.message || '请求失败'))
    }
    return Promise.reject(new Error('网络连接失败'))
  }
)

// ==========================================
// Auth APIs
// ==========================================
export const authAPI = {
  login: (data) => api.post('/api/user/login', data),
  register: (data) => api.post('/api/user/register', data),
  logout: () => api.post('/api/user/logout'),
  getSelf: () => api.get('/api/user/self'),
  updateSelf: (data) => api.put('/api/user/self', data),
  generateAccessToken: () => api.post('/api/user/generate_access_token'),
}

// ==========================================
// System APIs
// ==========================================
export const systemAPI = {
  getStatus: () => api.get('/api/status'),
  getNotice: () => api.get('/api/notice'),
  getSetup: () => api.get('/api/setup'),
  setup: (data) => api.post('/api/setup', data),
  getOptions: () => api.get('/api/option'),
  updateOption: (key, value) => api.put(`/api/option`, { key, value }),
  updateOptions: (data) => api.put('/api/option', data),
  getLogs: (params) => api.get('/api/logs', { params }),
  getStats: (params) => api.get('/api/log/stat', { params }),
}

// ==========================================
// User APIs
// ==========================================
export const userAPI = {
  list: (params) => api.get('/api/users', { params }),
  get: (id) => api.get(`/api/user/${id}`),
  create: (data) => api.post('/api/user', data),
  update: (id, data) => api.put(`/api/user/${id}`, data),
  delete: (id) => api.delete(`/api/user/${id}`),
  manage: (data) => api.post('/api/user/manage', data),
}

// ==========================================
// Channel APIs
// ==========================================
export const channelAPI = {
  list: (params) => api.get('/api/channels', { params }),
  get: (id) => api.get(`/api/channel/${id}`),
  create: (data) => api.post('/api/channel', data),
  update: (id, data) => api.put(`/api/channel/${id}`, data),
  delete: (id) => api.delete(`/api/channel/${id}`),
  test: (id) => api.get(`/api/channel/test/${id}`),
  testAll: () => api.get('/api/channel/test'),
  batchDelete: (data) => api.post('/api/channel/batch', data),
  batchUpdateStatus: (data) => api.post('/api/channel/batch/status', data),
}

// ==========================================
// Token APIs
// ==========================================
export const tokenAPI = {
  list: (params) => api.get('/api/tokens', { params }),
  get: (id) => api.get(`/api/token/${id}`),
  create: (data) => api.post('/api/token', data),
  update: (id, data) => api.put(`/api/token/${id}`, data),
  delete: (id) => api.delete(`/api/token/${id}`),
  batchDelete: (data) => api.post('/api/token/batch', data),
  batchUpdateStatus: (data) => api.post('/api/token/batch/status', data),
  // User's own tokens
  getSelf: (params) => api.get('/api/token/self', { params }),
  createSelf: (data) => api.post('/api/token/self', data),
  deleteSelf: (id) => api.delete(`/api/token/self/${id}`),
}

// ==========================================
// Log APIs
// ==========================================
export const logAPI = {
  // Admin: all logs
  list: (params) => api.get('/api/logs', { params }),
  stat: (params) => api.get('/api/log/stat', { params }),
  search: (params) => api.get('/api/log/search', { params }),
  // User: own logs
  getSelf: (params) => api.get('/api/log/self', { params }),
  // Login logs (admin only)
  getLogin: (params) => api.get('/api/login-logs', { params }),
  // Admin operation logs (admin only)
  getAdmin: (params) => api.get('/api/admin-logs', { params }),
}

// ==========================================
// Mail Template APIs
// ==========================================
export const mailAPI = {
  list: (params) => api.get('/api/mail/templates', { params }),
  get: (id) => api.get(`/api/mail/templates/${id}`),
  update: (id, data) => api.put(`/api/mail/templates/${id}`, data),
  test: (id, data) => api.post(`/api/mail/templates/${id}/test`, data),
  create: (data) => api.post('/api/mail/templates', data),
  delete: (id) => api.delete(`/api/mail/templates/${id}`),
}

export default api