import { useEffect, useMemo, useRef, useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuth, AuthUser } from '../contexts/AuthContext' 
import { Mail, Lock, Eye, EyeOff, Loader2, UserPlus } from 'lucide-react'; // Ícones para UX

/**
 * @component LoginPage
 * @description Tela de Login para usuários (Candidato ou Instituição).
 * Gerencia a autenticação, tratamento de erros e redirecionamento baseado no papel.
 */
export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string>('');
  const [loading, setLoading] = useState(false);
  const [remember, setRemember] = useState(true);

  const navigate = useNavigate();
  const location = useLocation();
  const { login, user } = useAuth(); // Assume-se que AuthContext fornece 'user' e 'login'

  // Ref para acessibilidade (anunciar erros/sucessos)
  const liveRef = useRef<HTMLParagraphElement>(null); 

  // Pega o parâmetro 'redirect' da URL
  const redirectTo = useMemo(() => {
    const params = new URLSearchParams(location.search);
    return params.get('redirect') || '';
  }, [location.search]);

  /**
   * @description Efeito para redirecionar imediatamente se o usuário já estiver logado.
   */
  useEffect(() => {
    // Se o user já existir, redireciona imediatamente
    if (user) {
      goToHomeByRole(user);
    }
  }, [user]); // user é a dependência correta aqui.

  /**
   * @function goToHomeByRole
   * @description Redireciona o usuário para a página de perfil apropriada.
   */
  function goToHomeByRole(u: AuthUser) {
    if (redirectTo) {
      navigate(redirectTo, { replace: true });
      return;
    }
    
    // Redireciona para o perfil específico, conforme a regra de negócio.
    if (u.tipo_usuario === 'instituicao') {
      navigate('/perfil/instituicao', { replace: true });
    } else {
      navigate('/perfil/candidato', { replace: true });
    }
  }

  /**
   * @async
   * @function handleSubmit
   * @description Lida com a submissão do formulário de login.
   */
  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (loading) return;

    setError('');
    setLoading(true);

    try {
      // Chamada à função de login do Context (POST /auth/login) [cite: Documentação final.docx]
      const loggedUser = await login(email, password, { remember }); 
      
      if (!loggedUser) {
          // Isso deve ser raro, pois 'login' geralmente lança um erro, mas é uma proteção.
          throw new Error('Não foi possível obter dados do usuário após o login.');
      }

      goToHomeByRole(loggedUser);
    } catch (e: any) {
      // Tratamento de erro aprimorado
      const status = e?.response?.status;
      let message;

      if (status === 429) {
        message = 'Muitas tentativas. Aguarde um minuto e tente novamente.';
      } else {
        message = e?.response?.data?.message || 'Credenciais inválidas. Verifique seu e-mail e senha.';
      }
        
      setError(message);
      // Feedback acessível para leitores de tela
      if (liveRef.current) {
        liveRef.current.textContent = message;
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    // Usa 'auth-container' e 'card-auth' do global.css para layout centralizado
    <main className="auth-container" aria-labelledby="titulo-login">
      
      <div className="card-auth">
        
        <h1 id="titulo-login" className="heading-secondary mb-lg text-center">Acessar Conta</h1>

        {/* Exibição de Erro */}
        {error && (
          <div className="alert alert-error mb-md" role="alert">
            {error}
          </div>
        )}
        {/* Feedback acessível para leitores de tela */}
        <p ref={liveRef} className="sr-only" aria-live="assertive" />

        <form onSubmit={handleSubmit} noValidate className="space-y-md" aria-describedby="dicas-form">
          <p id="dicas-form" className="text-sm text-muted text-center">Use seu email e senha cadastrados.</p>

          {/* Campo Email */}
          <div className="form-group">
            <label htmlFor="email" className="form-label">Email</label>
            <div className="form-input-icon-wrapper">
                <Mail size={20} className="form-icon" />
                <input
                    id="email"
                    name="email"
                    type="email"
                    inputMode="email"
                    autoComplete="email"
                    required
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className="form-input with-icon" // Classe global + ajuste para ícone
                    disabled={loading}
                />
            </div>
          </div>

          {/* Campo Senha */}
          <div className="form-group">
            <label htmlFor="password" className="form-label">Senha</label>
            <div className="form-input-icon-wrapper">
                <Lock size={20} className="form-icon" />
                <input
                    id="password"
                    name="password"
                    type={showPassword ? 'text' : 'password'}
                    autoComplete="current-password"
                    required
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    className="form-input with-icon pr-lg" // Ajustado padding-right para botão
                    disabled={loading}
                />
                <button
                    type="button"
                    className="form-input-suffix btn-link"
                    aria-pressed={showPassword}
                    aria-label={showPassword ? 'Ocultar senha' : 'Mostrar senha'}
                    onClick={() => setShowPassword((v) => !v)}
                >
                    {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
                </button>
            </div>
          </div>

          {/* Opções (Lembrar Senha e Esqueci Senha) */}
          <div className="flex-group-md-row" style={{ justifyContent: 'space-between', marginTop: 'var(--spacing-xs)' }}>
            <label className="checkbox-label text-sm">
              <input
                type="checkbox"
                className="form-checkbox" // Classe global
                checked={remember}
                onChange={(e) => setRemember(e.target.checked)}
              />
              Manter conectado
            </label>
            <Link to="/recuperar-senha" className="btn-link text-sm">Esqueci minha senha</Link>
          </div>

          {/* Botão de Submissão */}
          <button
            type="submit"
            disabled={loading || !email || !password}
            className="btn-primary w-full mt-md" // w-full e mt-md para espaçamento
            aria-busy={loading}
          >
            {loading ? <Loader2 className="icon-spin mr-sm" size={20} /> : null}
            {loading ? 'Entrando…' : 'Entrar'}
          </button>
        </form>

        {/* Link para Cadastro */}
        <p className="mt-lg text-sm text-center">
          Não possui conta? 
          <Link to="/cadastro" className="btn-link ml-xs">
            <UserPlus size={16} className="inline mr-xs" />Cadastrar
          </Link>
        </p>
      </div>
    </main>
  );
}