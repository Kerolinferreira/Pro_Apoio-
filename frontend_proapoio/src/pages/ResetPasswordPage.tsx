import { useEffect, useMemo, useRef, useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import api from '../services/api'

export default function ResetPasswordPage() {
  const [email, setEmail] = useState('')
  const [token, setToken] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [showPwd, setShowPwd] = useState(false)
  const [showPwd2, setShowPwd2] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')

  const location = useLocation()
  const navigate = useNavigate()
  const liveRef = useRef<HTMLParagraphElement>(null)

  useEffect(() => {
    const params = new URLSearchParams(location.search)
    const t = params.get('token') || ''
    const e = params.get('email') || ''
    if (t) setToken(t)
    if (e) setEmail(e)
  }, [location.search])

  const pwdScore = useMemo(() => {
    let s = 0
    if (password.length >= 8) s++
    if (/[A-Z]/.test(password)) s++
    if (/[a-z]/.test(password)) s++
    if (/[0-9]/.test(password)) s++
    if (/[^A-Za-z0-9]/.test(password)) s++
    return s
  }, [password])

  function announce(msg: string) {
    if (liveRef.current) {
      liveRef.current.textContent = msg
      setTimeout(() => { if (liveRef.current) liveRef.current.textContent = '' }, 2000)
    }
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError('')
    setSuccess('')

    if (!email.trim()) return setError('Informe o e-mail.')
    if (!token.trim()) return setError('Informe o token.')
    if (password.length < 8) return setError('A nova senha deve ter pelo menos 8 caracteres.')
    if (password !== passwordConfirmation) return setError('As senhas não conferem.')

    setLoading(true)
    try {
      await api.post('/auth/reset-password', {
        email,
        token,
        password,
        password_confirmation: passwordConfirmation,
      })
      setSuccess('Senha alterada. Você pode entrar agora.')
      announce('Senha redefinida com sucesso.')
      navigate('/login')
    } catch (err: any) {
      const status = err?.response?.status
      const msg = status === 400 || status === 422
        ? (err?.response?.data?.message || 'Token inválido ou expirado.')
        : status === 429
        ? 'Muitas tentativas. Aguarde um minuto.'
        : 'Não foi possível redefinir a senha.'
      setError(msg)
      announce(msg)
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="max-w-md mx-auto p-4" aria-labelledby="titulo-reset">
      <h1 id="titulo-reset" className="text-2xl font-extrabold mb-4">Redefinir senha</h1>
      <p ref={liveRef} className="sr-only" aria-live="polite" />

      {error && <div role="alert" className="mb-3 rounded border border-red-200 bg-red-50 p-3 text-red-800">{error}</div>}
      {success && <div role="status" className="mb-3 rounded border border-green-200 bg-green-50 p-3 text-green-800">{success}</div>}

      <form onSubmit={handleSubmit} noValidate className="space-y-4" aria-describedby="ajuda">
        <p id="ajuda" className="text-sm text-zinc-600">Cole o token recebido por e‑mail ou acesse pelo link do e‑mail, que já preenche os campos.</p>

        <div>
          <label htmlFor="email" className="block text-sm font-medium">E‑mail</label>
          <input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} inputMode="email" autoComplete="email" required className="mt-1 border p-2 w-full rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700" />
        </div>

        <div>
          <label htmlFor="token" className="block text-sm font-medium">Token</label>
          <input id="token" type="text" value={token} onChange={(e) => setToken(e.target.value)} required className="mt-1 border p-2 w-full rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700" />
        </div>

        <div>
          <label htmlFor="pwd" className="block text-sm font-medium">Nova senha</label>
          <div className="mt-1 relative">
            <input id="pwd" type={showPwd ? 'text' : 'password'} value={password} onChange={(e) => setPassword(e.target.value)} autoComplete="new-password" required className="border p-2 w-full rounded pr-24 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700" />
            <button type="button" className="absolute inset-y-0 right-0 px-3 text-sm underline underline-offset-4" aria-pressed={showPwd} aria-label={showPwd ? 'Ocultar senha' : 'Mostrar senha'} onClick={() => setShowPwd((v) => !v)}>
              {showPwd ? 'Ocultar' : 'Mostrar'}
            </button>
          </div>
          <div className="mt-1 h-1 rounded bg-zinc-200" aria-hidden>
            <div className={`h-1 rounded ${pwdScore <= 2 ? 'bg-red-500 w-1/4' : pwdScore === 3 ? 'bg-amber-500 w-2/4' : 'bg-green-600 w-3/4'}`} />
          </div>
          <p className="text-xs text-zinc-600 mt-1">Mínimo 8 caracteres. Use letras, números e símbolos.</p>
        </div>

        <div>
          <label htmlFor="pwd2" className="block text-sm font-medium">Confirmar nova senha</label>
          <div className="mt-1 relative">
            <input id="pwd2" type={showPwd2 ? 'text' : 'password'} value={passwordConfirmation} onChange={(e) => setPasswordConfirmation(e.target.value)} autoComplete="new-password" required className="border p-2 w-full rounded pr-24 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700" />
            <button type="button" className="absolute inset-y-0 right-0 px-3 text-sm underline underline-offset-4" aria-pressed={showPwd2} aria-label={showPwd2 ? 'Ocultar confirmação' : 'Mostrar confirmação'} onClick={() => setShowPwd2((v) => !v)}>
              {showPwd2 ? 'Ocultar' : 'Mostrar'}
            </button>
          </div>
        </div>

        <button type="submit" disabled={loading} aria-busy={loading} className="bg-blue-700 text-white px-4 py-2 rounded w-full font-semibold disabled:opacity-60">
          {loading ? 'Alterando…' : 'Alterar senha'}
        </button>

        <p className="text-sm text-center mt-2">Lembrou a senha? <a href="/login" className="underline underline-offset-4">Voltar ao login</a></p>
      </form>
    </main>
  )
}
