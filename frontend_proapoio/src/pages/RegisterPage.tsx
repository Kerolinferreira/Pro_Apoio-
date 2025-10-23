import { useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../services/api'

export default function RegisterPage() {
  const [tipo, setTipo] = useState<'candidato' | 'instituicao'>('candidato')

  const [nome, setNome] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [showPwd, setShowPwd] = useState(false)
  const [showPwd2, setShowPwd2] = useState(false)

  // Instituição
  const [cnpj, setCnpj] = useState('')
  const [razaoSocial, setRazaoSocial] = useState('')
  const [nomeFantasia, setNomeFantasia] = useState('')
  const [codigoInep, setCodigoInep] = useState('')

  // Candidato
  const [cpf, setCpf] = useState('')
  const [cep, setCep] = useState('')
  const [logradouro, setLogradouro] = useState('')
  const [bairro, setBairro] = useState('')
  const [cidade, setCidade] = useState('')
  const [estado, setEstado] = useState('')
  const [numero, setNumero] = useState('')
  const [complemento, setComplemento] = useState('')
  const [telefone, setTelefone] = useState('')
  const [escolaridade, setEscolaridade] = useState('')
  const [nomeCurso, setNomeCurso] = useState('')
  const [nomeInstituicaoEnsino, setNomeInstituicaoEnsino] = useState('')

  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string>('')
  const [success, setSuccess] = useState<string>('')
  const liveRef = useRef<HTMLParagraphElement>(null)

  const navigate = useNavigate()

  // --- Máscaras ---
  function formatCPF(value: string) {
    const digits = value.replace(/\D/g, '').slice(0, 11)
    return digits.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2')
  }
  function formatCNPJ(value: string) {
    const digits = value.replace(/\D/g, '').slice(0, 14)
    return digits.replace(/(\d{2})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1/$2').replace(/(\d{4})(\d{1,2})$/, '$1-$2')
  }
  function formatCEP(value: string) {
    const digits = value.replace(/\D/g, '').slice(0, 8)
    return digits.replace(/(\d{5})(\d)/, '$1-$2')
  }
  function formatPhone(value: string) {
    const digits = value.replace(/\D/g, '').slice(0, 11)
    if (digits.length <= 10) return digits.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2')
    return digits.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2')
  }

  // Força de senha
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

    // Validações básicas
    if (!nome.trim()) return setError('Informe o nome.')
    if (!email.trim()) return setError('Informe o email.')
    if (password !== passwordConfirmation) return setError('As senhas não conferem.')
    if (password.length < 8) return setError('A senha deve ter pelo menos 8 caracteres.')

    setLoading(true)
    try {
      if (tipo === 'candidato') {
        await api.post('/auth/register/candidato', {
          nome,
          email,
          password,
          password_confirmation: passwordConfirmation,
          cpf: cpf.replace(/\D/g, ''),
          cep: cep.replace(/\D/g, ''),
          logradouro,
          bairro,
          cidade,
          estado,
          numero,
          complemento,
          telefone: telefone.replace(/\D/g, ''),
          escolaridade,
          nome_curso: nomeCurso,
          nome_instituicao_ensino: nomeInstituicaoEnsino,
        })
      } else {
        await api.post('/auth/register/instituicao', {
          nome,
          email,
          password,
          password_confirmation: passwordConfirmation,
          cnpj: cnpj.replace(/\D/g, ''),
          razao_social: razaoSocial,
          nome_fantasia: nomeFantasia,
          codigo_inep: codigoInep,
        })
      }
      setSuccess('Cadastro realizado. Você pode entrar agora.')
      announce('Cadastro realizado com sucesso.')
      navigate('/login')
    } catch (err: any) {
      const msg = err?.response?.data?.message || 'Não foi possível concluir o cadastro.'
      setError(msg)
      announce(msg)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    // limpar mensagens ao trocar tipo
    setError('')
    setSuccess('')
  }, [tipo])

  return (
    <main className="max-w-2xl mx-auto p-4" aria-labelledby="titulo-cadastro">
      <h1 id="titulo-cadastro" className="text-2xl font-extrabold mb-4">Cadastro</h1>
      <p ref={liveRef} className="sr-only" aria-live="polite" />

      {/* Seletor de tipo acessível */}
      <div role="tablist" aria-label="Tipo de conta" className="inline-flex rounded-lg ring-1 ring-zinc-300 overflow-hidden mb-4">
        <button
          role="tab"
          id="aba-candidato"
          aria-selected={tipo === 'candidato'}
          aria-controls="painel-candidato"
          onClick={() => setTipo('candidato')}
          className={`px-4 py-2 text-sm font-medium ${tipo === 'candidato' ? 'bg-blue-700 text-white' : 'bg-white'}`}
        >
          Sou candidato
        </button>
        <button
          role="tab"
          id="aba-instituicao"
          aria-selected={tipo === 'instituicao'}
          aria-controls="painel-instituicao"
          onClick={() => setTipo('instituicao')}
          className={`px-4 py-2 text-sm font-medium ${tipo === 'instituicao' ? 'bg-blue-700 text-white' : 'bg-white'}`}
        >
          Sou instituição
        </button>
      </div>

      <form onSubmit={handleSubmit} noValidate className="space-y-4" aria-describedby="ajuda">
        <p id="ajuda" className="text-sm text-zinc-600">Preencha os campos obrigatórios. Senha mínima de 8 caracteres.</p>

        <div>
          <label htmlFor="nome" className="block text-sm font-medium">Nome</label>
          <input id="nome" value={nome} onChange={(e) => setNome(e.target.value)} required className="mt-1 border p-2 w-full rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700" />
        </div>
        <div>
          <label htmlFor="email" className="block text-sm font-medium">Email</label>
          <input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} inputMode="email" autoComplete="email" required className="mt-1 border p-2 w-full rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700" />
        </div>

        <div>
          <label htmlFor="pwd" className="block text-sm font-medium">Senha</label>
          <div className="mt-1 relative">
            <input id="pwd" type={showPwd ? 'text' : 'password'} value={password} onChange={(e) => setPassword(e.target.value)} autoComplete="new-password" required className="border p-2 w-full rounded pr-24 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700" />
            <button type="button" className="absolute inset-y-0 right-0 px-3 text-sm underline underline-offset-4" aria-pressed={showPwd} aria-label={showPwd ? 'Ocultar senha' : 'Mostrar senha'} onClick={() => setShowPwd((v) => !v)}>
              {showPwd ? 'Ocultar' : 'Mostrar'}
            </button>
          </div>
          {/* Barra simples de força */}
          <div className="mt-1 h-1 rounded bg-zinc-200" aria-hidden>
            <div className={`h-1 rounded ${pwdScore <= 2 ? 'bg-red-500 w-1/4' : pwdScore === 3 ? 'bg-amber-500 w-2/4' : 'bg-green-600 w-3/4'}`} />
          </div>
          <p className="text-xs text-zinc-600 mt-1">Use letras maiúsculas, minúsculas, números e símbolos.</p>
        </div>

        <div>
          <label htmlFor="pwd2" className="block text-sm font-medium">Confirmar senha</label>
          <div className="mt-1 relative">
            <input id="pwd2" type={showPwd2 ? 'text' : 'password'} value={passwordConfirmation} onChange={(e) => setPasswordConfirmation(e.target.value)} autoComplete="new-password" required className="border p-2 w-full rounded pr-24 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700" />
            <button type="button" className="absolute inset-y-0 right-0 px-3 text-sm underline underline-offset-4" aria-pressed={showPwd2} aria-label={showPwd2 ? 'Ocultar confirmação' : 'Mostrar confirmação'} onClick={() => setShowPwd2((v) => !v)}>
              {showPwd2 ? 'Ocultar' : 'Mostrar'}
            </button>
          </div>
        </div>

        {/* Painéis condicionais */}
        {tipo === 'candidato' ? (
          <section role="region" aria-labelledby="h-cand" id="painel-candidato" className="space-y-3">
            <h2 id="h-cand" className="font-semibold">Dados do candidato</h2>
            <div>
              <label htmlFor="cpf" className="block text-sm font-medium">CPF</label>
              <input id="cpf" value={cpf} onChange={(e) => setCpf(formatCPF(e.target.value))} inputMode="numeric" className="mt-1 border p-2 w-full rounded" />
            </div>
            <div>
              <label htmlFor="cep" className="block text-sm font-medium">CEP</label>
              <input id="cep" value={cep} onChange={(e) => setCep(formatCEP(e.target.value))} onBlur={async () => {
                const onlyDigits = cep.replace(/\D/g, '')
                if (onlyDigits.length === 8) {
                  try {
                    const resp = await api.get(`/external/viacep/${onlyDigits}`)
                    const data = resp.data
                    if (!data?.erro) {
                      setLogradouro(data.logradouro || '')
                      setBairro(data.bairro || '')
                      setCidade(data.localidade || '')
                      setEstado(data.uf || '')
                    }
                  } catch { /* ignora */ }
                }
              }} className="mt-1 border p-2 w-full rounded" />
            </div>
            <div>
              <label htmlFor="logradouro" className="block text-sm font-medium">Logradouro</label>
              <input id="logradouro" value={logradouro} onChange={(e) => setLogradouro(e.target.value)} className="mt-1 border p-2 w-full rounded" />
            </div>
            <div>
              <label htmlFor="bairro" className="block text-sm font-medium">Bairro</label>
              <input id="bairro" value={bairro} onChange={(e) => setBairro(e.target.value)} className="mt-1 border p-2 w-full rounded" />
            </div>
            <div className="flex gap-2">
              <div className="flex-1">
                <label htmlFor="cidade" className="block text-sm font-medium">Cidade</label>
                <input id="cidade" value={cidade} onChange={(e) => setCidade(e.target.value)} className="mt-1 border p-2 w-full rounded" />
              </div>
              <div className="w-32">
                <label htmlFor="uf" className="block text-sm font-medium">UF</label>
                <input id="uf" value={estado} onChange={(e) => setEstado(e.target.value.toUpperCase())} maxLength={2} className="mt-1 border p-2 w-full rounded" />
              </div>
            </div>
            <div className="flex gap-2">
              <div className="flex-1">
                <label htmlFor="numero" className="block text-sm font-medium">Número</label>
                <input id="numero" value={numero} onChange={(e) => setNumero(e.target.value)} className="mt-1 border p-2 w-full rounded" />
              </div>
              <div className="flex-1">
                <label htmlFor="comp" className="block text-sm font-medium">Complemento</label>
                <input id="comp" value={complemento} onChange={(e) => setComplemento(e.target.value)} className="mt-1 border p-2 w-full rounded" />
              </div>
            </div>
            <div>
              <label htmlFor="tel" className="block text-sm font-medium">Telefone</label>
              <input id="tel" value={telefone} onChange={(e) => setTelefone(formatPhone(e.target.value))} inputMode="tel" className="mt-1 border p-2 w-full rounded" />
            </div>
            <div>
              <label htmlFor="escolaridade" className="block text-sm font-medium">Escolaridade</label>
              <input id="escolaridade" value={escolaridade} onChange={(e) => setEscolaridade(e.target.value)} className="mt-1 border p-2 w-full rounded" />
            </div>
            <div>
              <label htmlFor="curso" className="block text-sm font-medium">Nome do curso</label>
              <input id="curso" value={nomeCurso} onChange={(e) => setNomeCurso(e.target.value)} className="mt-1 border p-2 w-full rounded" />
            </div>
            <div>
              <label htmlFor="ies" className="block text-sm font-medium">Instituição de ensino</label>
              <input id="ies" value={nomeInstituicaoEnsino} onChange={(e) => setNomeInstituicaoEnsino(e.target.value)} className="mt-1 border p-2 w-full rounded" />
            </div>
          </section>
        ) : (
          <section role="region" aria-labelledby="h-inst" id="painel-instituicao" className="space-y-3">
            <h2 id="h-inst" className="font-semibold">Dados da instituição</h2>
            <div>
              <label htmlFor="cnpj" className="block text-sm font-medium">CNPJ</label>
              <input id="cnpj" value={cnpj} onChange={(e) => setCnpj(formatCNPJ(e.target.value))} onBlur={async () => {
                const digits = cnpj.replace(/\D/g, '')
                if (digits.length === 14) {
                  try {
                    const resp = await api.get(`/external/receitaws/${digits}`)
                    const data = resp.data
                    if (data && !data.erro) {
                      setRazaoSocial(data.razao_social || data.nome || razaoSocial)
                      setNomeFantasia(data.nome_fantasia || data.fantasia || nomeFantasia)
                    }
                  } catch { /* ignora */ }
                }
              }} className="mt-1 border p-2 w-full rounded" />
            </div>
            <div>
              <label htmlFor="razao" className="block text-sm font-medium">Razão social</label>
              <input id="razao" value={razaoSocial} onChange={(e) => setRazaoSocial(e.target.value)} className="mt-1 border p-2 w-full rounded" />
            </div>
            <div>
              <label htmlFor="fantasia" className="block text-sm font-medium">Nome fantasia</label>
              <input id="fantasia" value={nomeFantasia} onChange={(e) => setNomeFantasia(e.target.value)} className="mt-1 border p-2 w-full rounded" />
            </div>
            <div>
              <label htmlFor="inep" className="block text-sm font-medium">Código INEP</label>
              <input id="inep" value={codigoInep} onChange={(e) => setCodigoInep(e.target.value)} className="mt-1 border p-2 w-full rounded" />
            </div>
          </section>
        )}

        {error && <div role="alert" className="rounded border border-red-200 bg-red-50 p-2 text-red-800">{error}</div>}
        {success && <div role="status" className="rounded border border-green-200 bg-green-50 p-2 text-green-800">{success}</div>}

        <button type="submit" disabled={loading} aria-busy={loading} className="bg-green-700 text-white px-4 py-2 rounded font-semibold disabled:opacity-60">
          {loading ? 'Registrando…' : 'Registrar'}
        </button>
      </form>
    </main>
  )
}
