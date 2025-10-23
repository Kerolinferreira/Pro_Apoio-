import { useEffect, useMemo, useRef, useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'

export default function LoginPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState<string>('')
  const [loading, setLoading] = useState(false)
  const [remember, setRemember] = useState(true)

  const navigate = useNavigate()
  const location = useLocation()
  const { login, user } = useAuth()

  const redirectTo = useMemo(() => {
    const params = new URLSearchParams(location.search)
    return params.get('redirect') || ''
  }, [location.search])

  const liveRef = useRef<HTMLParagraphElement>(null)

  useEffect(() => {
    if (user) {
      // usuário já autenticado
      goToHomeByRole(user)
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function goToHomeByRole(u: any) {
    if (redirectTo) {
      navigate(redirectTo, { replace: true })
      return
    }
    if (u?.tipo_usuario === 'instituicao') navigate('/perfil/instituicao', { replace: true })
    else navigate('/perfil/candidato', { replace: true })
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const logged = await login(email, password, { remember })
      const resolvedUser = logged || JSON.parse(localStorage.getItem('user') || 'null')
      if (!resolvedUser) throw new Error('Sem dados do usuário')
      goToHomeByRole(resolvedUser)
    } catch (e: any) {
      const message = e?.response?.status === 429
        ? 'Muitas tentativas. Aguarde um minuto e tente novamente.'
        : 'Credenciais inválidas'
      setError(message)
      if (liveRef.current) {
        liveRef.current.textContent = message
        setTimeout(() => { if (liveRef.current) liveRef.current.textContent = '' }, 2000)
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="max-w-md mx-auto p-4" aria-labelledby="titulo-login">
      <h1 id="titulo-login" className="text-2xl font-extrabold mb-4">Login</h1>

      {/* Erros visíveis e para leitores de tela */}
      {error && (
        <div className="mb-3 rounded border border-red-200 bg-red-50 p-3 text-red-800" role="alert">
          {error}
        </div>
      )}
      <p ref={liveRef} className="sr-only" aria-live="assertive" />

      <form onSubmit={handleSubmit} noValidate className="space-y-4" aria-describedby="dicas-form">
        <p id="dicas-form" className="text-sm text-zinc-600">Campos obrigatórios. Use seu email cadastrado.</p>

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

        <div>
          <label htmlFor="password" className="block text-sm font-medium">Senha</label>
          <div className="mt-1 relative">
            <input
              id="password"
              name="password"
              type={showPassword ? 'text' : 'password'}
              autoComplete="current-password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="border p-2 w-full rounded pr-24 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700"
            />
            <button
              type="button"
              className="absolute inset-y-0 right-0 px-3 text-sm underline underline-offset-4"
              aria-pressed={showPassword}
              aria-label={showPassword ? 'Ocultar senha' : 'Mostrar senha'}
              onClick={() => setShowPassword((v) => !v)}
            >
              {showPassword ? 'Ocultar' : 'Mostrar'}
            </button>
          </div>
        </div>

        <div className="flex items-center justify-between">
          <label className="inline-flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              className="h-4 w-4"
              checked={remember}
              onChange={(e) => setRemember(e.target.checked)}
            />
            Manter conectado neste dispositivo
          </label>
          <a href="/recuperar-senha" className="text-sm underline underline-offset-4">Esqueci minha senha</a>
        </div>

        <button
          type="submit"
          disabled={loading}
          className="bg-blue-700 text-white px-4 py-2 rounded w-full font-semibold disabled:opacity-60"
          aria-busy={loading}
        >
          {loading ? 'Entrando…' : 'Entrar'}
        </button>
      </form>

      <p className="mt-4 text-sm text-center">
        Não possui conta? <a href="/register" className="underline underline-offset-4">Cadastrar</a>
      </p>
    </main>
  )
}
