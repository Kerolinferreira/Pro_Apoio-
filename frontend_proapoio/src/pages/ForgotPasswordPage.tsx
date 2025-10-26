import { useEffect, useRef, useState } from 'react'
import api from '../services/api'

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState('')
  const [submitted, setSubmitted] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string>('')
  const [secondsLeft, setSecondsLeft] = useState(0)
  const liveRef = useRef<HTMLParagraphElement>(null)

  useEffect(() => {
    if (!submitted || secondsLeft <= 0) return
    const t = setInterval(() => setSecondsLeft((s) => s - 1), 1000)
    return () => clearInterval(t)
  }, [submitted, secondsLeft])

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      // Endpoint conforme contrato da API
      await api.post('/auth/forgot-password', { email })
      setSubmitted(true)
      setSecondsLeft(60)
      announce('Se o e-mail existir, enviaremos instruções de recuperação.')
    } catch (err: any) {
      const message = err?.response?.status === 429
        ? 'Muitas tentativas. Aguarde um minuto e tente novamente.'
        : 'Não foi possível processar a solicitação agora.'
      setError(message)
      announce(message)
    } finally {
      setLoading(false)
    }
  }

  function announce(msg: string) {
    if (liveRef.current) {
      liveRef.current.textContent = msg
      setTimeout(() => { if (liveRef.current) liveRef.current.textContent = '' }, 2000)
    }
  }

  const canResend = submitted && secondsLeft === 0

  return (
    <main className="max-w-md mx-auto p-4" aria-labelledby="titulo-forgot">
      <h1 id="titulo-forgot" className="text-2xl font-extrabold mb-4">Recuperar acesso</h1>
      <p ref={liveRef} className="sr-only" aria-live="polite" />

      {error && (
        <div className="mb-3 rounded border border-red-200 bg-red-50 p-3 text-red-800" role="alert">{error}</div>
      )}

      {!submitted ? (
        <form onSubmit={handleSubmit} noValidate className="space-y-4" aria-describedby="dica">
          <p id="dica" className="text-sm text-zinc-600">Informe seu e-mail cadastrado. Se houver conta, enviaremos um link de redefinição.</p>
          <div>
            <label htmlFor="email" className="block text-sm font-medium">Email</label>
            <input
              id="email"
              name="email"
              type="email"
              inputMode="email"
              autoComplete="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="mt-1 border p-2 w-full rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700"
            />
          </div>
          <button
            type="submit"
            disabled={loading || !email}
            aria-busy={loading}
            className="bg-blue-700 text-white px-4 py-2 rounded w-full font-semibold disabled:opacity-60"
          >
            {loading ? 'Enviando…' : 'Enviar link de recuperação'}
          </button>
        </form>
      ) : (
        <section aria-label="Confirmação" className="space-y-3">
          <p>Se o e‑mail existir, você receberá um link de recuperação nos próximos minutos.</p>
          {!canResend ? (
            <p className="text-sm text-zinc-600">Você pode solicitar novamente em {secondsLeft} segundos.</p>
          ) : (
            <button
              onClick={(e) => handleSubmit(e as any)}
              className="underline underline-offset-4"
            >
              Reenviar link
            </button>
          )}
          <p className="text-sm">Lembrou a senha? <a href="/login" className="underline underline-offset-4">Voltar ao login</a></p>
        </section>
      )}
    </main>
  )
}
