// src/components/NotificationBell.tsx
import { useEffect, useRef, useState } from 'react'
import api from '../services/api'

export default function NotificationBell() {
  const [count, setCount] = useState(0)
  const [loading, setLoading] = useState(false)
  const liveRef = useRef<HTMLParagraphElement>(null)

  // Mantém o valor mais recente de count sem recriar efeitos
  const countRef = useRef(count)
  useEffect(() => {
    countRef.current = count
  }, [count])

  useEffect(() => {
    // Ambiente sem DOM (SSR/testes)
    if (typeof window === 'undefined' || typeof document === 'undefined') return

    let active = true
    let controller: AbortController | null = null
    let intervalId: number | null = null

    async function fetchNotifications() {
      if (!active) return
      if (loading) return
      if (typeof document !== 'undefined' && document.hidden) return

      setLoading(true)
      controller = typeof AbortController !== 'undefined' ? new AbortController() : null

      try {
        const cfg = controller ? { signal: controller.signal } : {}
        const res = await api.get('/notificacoes', cfg as any)
        const data = Array.isArray(res.data) ? res.data : res.data?.data || []
        if (!active) return

        const newCount = data.filter((n: any) => !n?.lida).length
        if (newCount !== countRef.current) {
          setCount(newCount)
          if (newCount > 0 && liveRef.current) {
            const plural = newCount > 1
            liveRef.current.textContent = `${newCount} nova${plural ? 's' : ''} notificação${plural ? 'es' : ''}.`
            // limpa mensagem após curto período
            window.setTimeout(() => {
              if (liveRef.current) liveRef.current.textContent = ''
            }, 1500)
          }
        }
      } catch {
        // silencioso por design
      } finally {
        if (active) setLoading(false)
      }
    }

    // primeira busca imediata
    fetchNotifications()

    // polling a cada 60s
    intervalId = window.setInterval(fetchNotifications, 60000)

    // atualiza ao voltar a aba
    const onVisible = () => {
      if (typeof document !== 'undefined' && !document.hidden) fetchNotifications()
    }
    document.addEventListener('visibilitychange', onVisible)

    return () => {
      active = false
      if (controller) controller.abort()
      if (intervalId) window.clearInterval(intervalId)
      document.removeEventListener('visibilitychange', onVisible)
    }
  }, [loading])

  async function marcarComoLidas() {
    if (countRef.current === 0) return
    try {
      await api.post('/notificacoes/marcar-como-lidas')
      setCount(0)
    } catch {
      // silencioso
    }
  }

  return (
    <div className="relative inline-block">
      <p ref={liveRef} className="sr-only" aria-live="polite" />
      <button
        type="button"
        onClick={marcarComoLidas}
        aria-label={count > 0 ? `${count} notificações novas` : 'Sem novas notificações'}
        className="relative focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 p-1"
      >
        <span aria-hidden>🔔</span>
        {count > 0 && (
          <span className="absolute -top-1 -right-1 bg-red-600 text-white rounded-full text-xs px-1 animate-pulse">
            {count}
          </span>
        )}
      </button>
    </div>
  )
}
