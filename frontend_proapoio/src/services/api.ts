import axios from 'axios'

/**
 * Cliente Axios configurado.
 * - baseURL dinâmica via VITE_API_URL
 * - timeout padrão de 10s
 * - prevenção de requisições duplicadas simultâneas (mesma URL + método)
 * - exportação de instância única para uso em todo o app
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  timeout: 10000,
})

const pending = new Map<string, AbortController>()

function makeKey(config: any) {
  return `${config.method}:${config.url}`
}

// Intercepta requisições duplicadas
api.interceptors.request.use(config => {
  const key = makeKey(config)
  if (pending.has(key)) {
    pending.get(key)?.abort()
    pending.delete(key)
  }
  const controller = new AbortController()
  config.signal = controller.signal
  pending.set(key, controller)
  return config
})

// Limpa pendentes ao receber resposta ou erro
api.interceptors.response.use(
  response => {
    pending.delete(makeKey(response.config))
    return response
  },
  error => {
    if (error.config) pending.delete(makeKey(error.config))
    if (axios.isCancel(error)) return Promise.reject({ canceled: true })
    return Promise.reject(error)
  }
)

export default api
