import axios from 'axios'

const api = axios.create({ baseURL: '/api', headers: { Accept: 'application/json' } })

api.interceptors.request.use((cfg) => {
  const token = localStorage.getItem('token')
  if (token) cfg.headers.Authorization = `Bearer ${token}`
  return cfg
})

api.interceptors.response.use(
  (r) => r,
  (err) => {
    // Expired or revoked token: drop it and send the person to sign in.
    if (err.response?.status === 401 && localStorage.getItem('token')) {
      localStorage.removeItem('token')
      if (!location.pathname.startsWith('/login')) {
        location.assign(`/login?redirect=${encodeURIComponent(location.pathname)}`)
      }
    }
    return Promise.reject(err)
  },
)

export const apiError = (e, fallback = 'Something went wrong. Please try again.') =>
  e.response?.data?.message ?? fallback

export const fieldErrors = (e) => e.response?.data?.errors ?? {}

export default api
