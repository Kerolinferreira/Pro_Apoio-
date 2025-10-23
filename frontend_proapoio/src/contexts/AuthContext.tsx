import React, { createContext, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from 'react'
import api from '../services/api'

/**
 * Contexto de autenticação com:
 * - Login com opção "lembrar neste dispositivo" (localStorage) ou sessão (sessionStorage)
 * - Interceptores: anexa Authorization e tenta refresh automático em 401 uma vez
 * - Persistência segura com prefixo e limpeza centralizada
 * - Exposição de utilitários: isAuthenticated, login, logout, user, token
 */

export interface LoginOptions { remember?: boolean }

interface AuthContextProps {
  token: string | null
  user: any
  isAuthenticated: boolean
  login: (email: string, password: string, options?: LoginOptions) => Promise<any>
  logout: () => Promise<void>
  setUser: React.Dispatch<React.SetStateAction<any>>
}

const AuthContext = createContext<AuthContextProps | undefined>(undefined)

const KEY_PREFIX = 'proapoio'
const KEY_TOKEN = `${KEY_PREFIX}:token`
const KEY_USER = `${KEY_PREFIX}:user`
const KEY_REFRESH = `${KEY_PREFIX}:refresh`
const KEY_STORAGE = `${KEY_PREFIX}:storage` // "local" | "session"

function readFromStorage() {
  // Prioriza sessionStorage para sessões não lembradas; fallback em local
  const storageType = sessionStorage.getItem(KEY_STORAGE) || localStorage.getItem(KEY_STORAGE)
  const storage = storageType === 'session' ? sessionStorage : localStorage
  const token = storage.getItem(KEY_TOKEN)
  const userStr = storage.getItem(KEY_USER)
  const refresh = storage.getItem(KEY_REFRESH)
  const user = userStr ? safeJSON(userStr) : null
  return { storageType: storageType as 'local' | 'session' | null, storage, token, user, refresh }
}

function writeToStorage(kind: 'local' | 'session', token: string, user: any, refresh?: string | null) {
  const s = kind === 'local' ? localStorage : sessionStorage
  // Limpa qualquer storage oposto para evitar estados conflitantes
  ;[localStorage, sessionStorage].forEach((store) => {
    store.removeItem(KEY_TOKEN)
    store.removeItem(KEY_USER)
    store.removeItem(KEY_REFRESH)
    store.removeItem(KEY_STORAGE)
  })
  s.setItem(KEY_STORAGE, kind)
  s.setItem(KEY_TOKEN, token)
  s.setItem(KEY_USER, JSON.stringify(user ?? null))
  if (refresh) s.setItem(KEY_REFRESH, refresh)
}

function clearAllStorage() {
  ;[localStorage, sessionStorage].forEach((s) => {
    s.removeItem(KEY_TOKEN)
    s.removeItem(KEY_USER)
    s.removeItem(KEY_REFRESH)
    s.removeItem(KEY_STORAGE)
  })
}

function safeJSON(str: string) {
  try { return JSON.parse(str) } catch { return null }
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const boot = useMemo(() => readFromStorage(), [])
  const [token, setToken] = useState<string | null>(boot.token || null)
  const [user, setUser] = useState<any>(boot.user || null)
  const [storageKind, setStorageKind] = useState<'local' | 'session'>(boot.storageType || 'local')
  const refreshLock = useRef<Promise<string | null> | null>(null)

  const isAuthenticated = !!token

  // Interceptor: Authorization
  useEffect(() => {
    const reqId = api.interceptors.request.use((config) => {
      if (token) {
        config.headers = config.headers || {}
        config.headers.Authorization = `Bearer ${token}`
      }
      return config
    })
    return () => { api.interceptors.request.eject(reqId) }
  }, [token])

  // Interceptor: 401 -> tenta refresh uma vez
  useEffect(() => {
    const resId = api.interceptors.response.use(
      (r) => r,
      async (error) => {
        const status = error?.response?.status
        const original = error?.config
        const hasTried = original && (original as any)._retry
        if (status === 401 && !hasTried) {
          ;(original as any)._retry = true
          const newToken = await ensureRefreshedToken()
          if (newToken) {
            original.headers = original.headers || {}
            original.headers.Authorization = `Bearer ${newToken}`
            return api(original)
          }
        }
        return Promise.reject(error)
      }
    )
    return () => { api.interceptors.response.eject(resId) }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [storageKind])

  async function ensureRefreshedToken(): Promise<string | null> {
    if (refreshLock.current) return refreshLock.current
    const currentStorage = storageKind === 'session' ? sessionStorage : localStorage
    const storedRefresh = currentStorage.getItem(KEY_REFRESH)
    if (!storedRefresh) return null
    refreshLock.current = (async () => {
      try {
        const resp = await api.post('/auth/refresh', { refresh_token: storedRefresh })
        const newToken = resp.data?.token || resp.data?.access_token
        const newRefresh = resp.data?.refresh_token || storedRefresh
        if (newToken) {
          setToken(newToken)
          writeToStorage(storageKind, newToken, user, newRefresh)
          return newToken
        }
        return null
      } catch {
        await logout()
        return null
      } finally {
        refreshLock.current = null
      }
    })()
    return refreshLock.current
  }

  async function login(email: string, password: string, options?: LoginOptions) {
    const remember = options?.remember !== false // padrão: lembrar
    const storageTarget: 'local' | 'session' = remember ? 'local' : 'session'
    const resp = await api.post('/auth/login', { email, password })
    const accessToken = resp.data?.token || resp.data?.access_token
    const userData = resp.data?.user ?? resp.data?.dados ?? resp.data
    const refresh = resp.data?.refresh_token || null
    setToken(accessToken)
    setUser(userData)
    setStorageKind(storageTarget)
    writeToStorage(storageTarget, accessToken, userData, refresh)
    // Opcional: sincroniza perfil com /profile/me se existir
    try {
      const me = await api.get('/profile/me')
      if (me?.data) {
        setUser(me.data)
        writeToStorage(storageTarget, accessToken, me.data, refresh)
      }
    } catch { /* ignora se rota não existir */ }
    return userData
  }

  async function logout() {
    try { await api.post('/auth/logout') } catch { /* ignora */ }
    setToken(null)
    setUser(null)
    clearAllStorage()
  }

  // Boot: se tem token, tenta carregar /profile/me para manter user atualizado
  useEffect(() => {
    async function hydrate() {
      if (!token) return
      try {
        const me = await api.get('/profile/me')
        if (me?.data) setUser(me.data)
      } catch { /* ignora */ }
    }
    hydrate()
  }, [token])

  const value = useMemo(() => ({ token, user, isAuthenticated, login, logout, setUser }), [token, user])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
