import { useEffect, useRef, useState } from 'react'
import api from '../services/api'

export default function NotificationBell() {
  const [count, setCount] = useState(0)
  const [loading, setLoading] = useState(false)
  const liveRef = useRef<HTMLParagraphElement>(null)

  useEffect(() => {
    let active = true
    let controller: AbortController | null = null

    async function fetchNotifications() {
      if (loading) return
      if (document.hidden) return
      setLoading(true)
      controller = new AbortController()
      try {
        const res = await api.get('/notificacoes', { signal: controller.signal })
        const data = Array.isArray(res.data) ? res.data : res.data.data || []
        if (active) {
          const newCount = data.filter((n: any) => !n.lida).length
          if (newCount !== count) {
            setCount(newCount)
            if (newCount > 0 && liveRef.current)
              liveRef.current.textContent = `${newCount} nova${newCount > 1 ? 's' : ''} notificação${newCount > 1 ? 'es' : ''}.`
          }
        }
      } catch {/* ignora */}
      finally {
        setLoading(false)
      }
    }

    fetchNotifications()
    const interval = setInterval(fetchNotifications, 60000)
    const visibilityHandler = () => { if (!document.hidden) fetchNotifications() }
    document.addEventListener('visibilitychange', visibilityHandler)

    return () => {
      active = false
      if (controller) controller.abort()
      clearInterval(interval)
      document.removeEventListener('visibilitychange', visibilityHandler)
    }
  }, [count, loading])

  async function marcarComoLidas() {
    if (count === 0) return
    try {
      await api.post('/notificacoes/marcar-como-lidas')
      setCount(0)
    } catch {/* ignora */}
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
