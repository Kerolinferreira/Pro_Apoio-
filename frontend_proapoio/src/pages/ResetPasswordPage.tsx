import { useEffect, useMemo, useRef, useState } from 'react';
import { useLocation, useNavigate, Link } from 'react-router-dom';
import api from '../services/api';
import { Mail, Lock, Eye, EyeOff, Loader2, ArrowLeft } from 'lucide-react';
import { parseApiError } from '../utils/errorHandler';
import { logger } from '../utils/logger'; 

// ===================================
// UTILITÁRIOS E FUNÇÕES DE VALIDAÇÃO
// ===================================

function senhaValida(s: string) {
  // Mínimo 8, ao menos uma letra e um número [cite: Documentação final.docx]
  return /^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(s);
}

// Componente auxiliar para texto de erro
const ErrorText: React.FC<{ id: string; message?: string }> = ({ id, message }) => (
    message ? (<p id={id} className="error-text">{message}</p>) : null
);

// ===================================
// LÓGICA DA PÁGINA
// ===================================

export default function ResetPasswordPage() {
  const [email, setEmail] = useState('');
  const [token, setToken] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [showPwd, setShowPwd] = useState(false);
  const [showPwd2, setShowPwd2] = useState(false);
  const [loading, setLoading] = useState(false);

  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Erros por campo (Simplificados para uso com ErrorText)
  const [eEmail, setEEmail] = useState('');
  const [eToken, setEToken] = useState('');
  const [ePwd, setEPwd] = useState('');
  const [ePwd2, setEPwd2] = useState('');

  const location = useLocation();
  const navigate = useNavigate();

  // A11y Refs
  const liveRef = useRef<HTMLParagraphElement>(null);
  const alertRef = useRef<HTMLDivElement>(null);
  const h1Ref = useRef<HTMLHeadingElement>(null);
  // Refs para focar nos campos em caso de erro
  const emailRef = useRef<HTMLInputElement>(null);
  const tokenRef = useRef<HTMLInputElement>(null);
  const pwdRef = useRef<HTMLInputElement>(null);
  const pwd2Ref = useRef<HTMLInputElement>(null);


  useEffect(() => {
    // Extrai email e token da URL (vindos do link de recuperação)
    const params = new URLSearchParams(location.search);
    const t = params.get('token') || '';
    const e = params.get('email') || '';
    if (t) setToken(t);
    if (e) setEmail(e);
    
    // Foca no título da página ao carregar (Acessibilidade)
    setTimeout(() => h1Ref.current?.focus(), 0);
  }, [location.search]);

  // Lógica de Medidor de Força de Senha
  const pwdScore = useMemo(() => {
    let s = 0;
    if (password.length >= 8) s++;
    if (/[A-Z]/.test(password)) s++;
    if (/[a-z]/.test(password)) s++;
    if (/[0-9]/.test(password)) s++;
    if (/[^A-Za-z0-9]/.test(password)) s++;
    return s; 
  }, [password]);

  const descricaoForca = useMemo(() => {
    if (pwdScore <= 2) return 'força baixa';
    if (pwdScore === 3) return 'força média';
    return 'força alta';
  }, [pwdScore]);

  function announce(msg: string) {
    if (liveRef.current) {
      liveRef.current.textContent = msg;
    }
  }

  function limparErrosCampos() {
    setEEmail('');
    setEToken('');
    setEPwd('');
    setEPwd2('');
  }

  function validarCampos(): boolean {
    limparErrosCampos();
    let ok = true;
    let firstErrorRef: React.RefObject<HTMLInputElement> | null = null;


    if (!email.trim()) {
      setEEmail('Informe o e-mail.');
      ok = false;
        if (!firstErrorRef) firstErrorRef = emailRef;
    }
    if (!token.trim()) {
      setEToken('Informe o token.');
      ok = false;
        if (!firstErrorRef) firstErrorRef = tokenRef;
    }
    if (!senhaValida(password)) {
      setEPwd('Mínimo de oito caracteres com letras e números.');
      ok = false;
        if (!firstErrorRef) firstErrorRef = pwdRef;
    }
    if (password !== passwordConfirmation) {
      setEPwd2('As senhas não conferem.');
      ok = false;
        if (!firstErrorRef) firstErrorRef = pwd2Ref;
    }
    
    // Foca no primeiro campo com erro encontrado
    if (!ok) {
        setTimeout(() => firstErrorRef?.current?.focus(), 0);
    }
    
    return ok;
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setSuccess('');

    const ok = validarCampos();
    if (!ok) {
      setTimeout(() => alertRef.current?.focus(), 0);
      // Construir mensagem específica com campos com erro
      const camposComErro: string[] = [];
      if (eEmail) camposComErro.push('E-mail');
      if (eToken) camposComErro.push('Token');
      if (ePwd) camposComErro.push('Senha');
      if (ePwd2) camposComErro.push('Confirmação de Senha');
      const mensagem = camposComErro.length > 0
        ? `Por favor, corrija os seguintes campos: ${camposComErro.join(', ')}.`
        : 'Erros no formulário. Por favor, verifique os campos.';
      announce(mensagem);
      return;
    }

    setLoading(true);
    try {
      // POST /auth/reset-password [cite: Documentação final.docx]
      await api.post('/auth/reset-password', {
        email,
        token,
        password,
        password_confirmation: passwordConfirmation, // Mapeamento correto para o Laravel Request
      });
      
      setSuccess('Sua senha foi alterada com sucesso! Você será redirecionado para o login.');
      announce('Senha redefinida com sucesso.');
      setTimeout(() => navigate('/login'), 2000);
      
    } catch (err: any) {
      logger.error('Erro ao redefinir senha:', err);

      // Usa o helper para parsear erros da API
      const { generalMessage } = parseApiError(err);
      const status = err?.response?.status;
      let msg = generalMessage;

      if (status === 400) {
        msg = 'O token de recuperação é inválido ou expirou. Por favor, solicite um novo link de recuperação.';
      } else if (status === 422) {
        msg = err?.response?.data?.message || 'A senha não atende aos requisitos de segurança. Use no mínimo 8 caracteres com letras e números.';
      } else if (status === 429) {
        msg = 'Muitas tentativas de redefinição de senha. Por favor, aguarde alguns minutos e tente novamente.';
      } else if (status === 404) {
        msg = 'E-mail não encontrado no sistema. Verifique o e-mail informado.';
      } else if (!err.response) {
        msg = 'Não foi possível conectar ao servidor. Verifique sua conexão com a internet.';
      }

      setError(msg);
      announce(msg);
      setTimeout(() => alertRef.current?.focus(), 0);
      
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className="auth-container" aria-labelledby="titulo-reset" role="main">
        <div className="card-auth">
            <h1
                id="titulo-reset"
                className="heading-secondary mb-md text-center"
                ref={h1Ref}
                tabIndex={-1}
            >
                Redefinir Senha
            </h1>
            <p ref={liveRef} className="sr-only" aria-live="polite" />

            {/* Mensagem de Erro/Sucesso */}
            {(error || success) && (
                <div
                    ref={alertRef}
                    role={error ? 'alert' : 'status'}
                    tabIndex={-1}
                    className={error ? 'alert alert-error mb-md' : 'alert alert-success mb-md'}
                >
                    {error || success}
                </div>
            )}

            <form onSubmit={handleSubmit} noValidate className="space-y-md" aria-describedby="ajuda">
                <p id="ajuda" className="text-sm text-muted text-center">
                    Cole o token ou acesse o link completo enviado por e-mail.
                </p>

                {/* Campo E-mail */}
                <div className="form-group">
                    <label htmlFor="email" className="form-label">
                        E-mail
                    </label>
                    <div className="form-input-icon-wrapper">
                        <Mail size={20} className="form-icon" />
                        <input
                            ref={emailRef}
                            id="email"
                            type="email"
                            value={email}
                            onChange={(e) => {
                                setEmail(e.target.value);
                                if (eEmail) setEEmail('');
                            }}
                            inputMode="email"
                            autoComplete="email"
                            required
                            aria-invalid={!!eEmail}
                            aria-describedby={eEmail ? 'erro-email' : undefined}
                            className="form-input with-icon"
                        />
                    </div>
                    <ErrorText id="erro-email" message={eEmail} />
                </div>

                {/* Campo Token */}
                <div className="form-group">
                    <label htmlFor="token" className="form-label">
                        Token
                    </label>
                    <div className="form-input-icon-wrapper">
                        <Lock size={20} className="form-icon" />
                        <input
                            ref={tokenRef}
                            id="token"
                            type="text"
                            value={token}
                            onChange={(e) => {
                                setToken(e.target.value);
                                if (eToken) setEToken('');
                            }}
                            required
                            aria-invalid={!!eToken}
                            aria-describedby={eToken ? 'erro-token' : undefined}
                            className="form-input with-icon"
                        />
                    </div>
                    <ErrorText id="erro-token" message={eToken} />
                </div>

                {/* Nova Senha */}
                <div className="form-group">
                    <label htmlFor="pwd" className="form-label">
                        Nova senha
                    </label>
                    <div className="form-input-icon-wrapper">
                        <Lock size={20} className="form-icon" />
                        <input
                            ref={pwdRef}
                            id="pwd"
                            type={showPwd ? 'text' : 'password'}
                            value={password}
                            onChange={(e) => {
                                setPassword(e.target.value);
                                if (ePwd) setEPwd('');
                            }}
                            autoComplete="new-password"
                            required
                            aria-invalid={!!ePwd}
                            aria-describedby={ePwd ? 'erro-pwd ajuda-pwd' : 'ajuda-pwd'}
                            className="form-input with-icon pr-lg"
                        />
                        <button
                            type="button"
                            className="form-input-suffix btn-link"
                            aria-pressed={showPwd}
                            aria-controls="pwd"
                            aria-label={showPwd ? 'Ocultar senha' : 'Mostrar senha'}
                            onClick={() => setShowPwd((v) => !v)}
                        >
                            {showPwd ? <EyeOff size={20} /> : <Eye size={20} />}
                        </button>
                    </div>

                    {/* Medidor de força */}
                    <div
                        className="pwd-strength-bar-bg mt-xs"
                        role="progressbar"
                        aria-valuemin={0}
                        aria-valuemax={5}
                        aria-valuenow={pwdScore}
                        aria-label="Força da senha"
                        aria-describedby="texto-forca"
                    >
                        <div
                            className={`pwd-strength-bar ${
                                pwdScore <= 2 ? 'pwd-low' : pwdScore === 3 ? 'pwd-medium' : 'pwd-high'
                            }`}
                            style={{ width: `${(pwdScore / 5) * 100}%` }}
                        />
                    </div>
                    <p id="texto-forca" className="sr-only">
                        {descricaoForca}
                    </p>

                    <ErrorText id="erro-pwd" message={ePwd} />
                    <p id="ajuda-pwd" className="text-xs text-muted mt-xs">
                        Mínimo oito caracteres. Use letras e números. Símbolos aumentam a força.
                    </p>
                </div>

                {/* Confirmar Nova Senha */}
                <div className="form-group">
                    <label htmlFor="pwd2" className="form-label">
                        Confirmar nova senha
                    </label>
                    <div className="form-input-icon-wrapper">
                        <Lock size={20} className="form-icon" />
                        <input
                            ref={pwd2Ref}
                            id="pwd2"
                            type={showPwd2 ? 'text' : 'password'}
                            value={passwordConfirmation}
                            onChange={(e) => {
                                setPasswordConfirmation(e.target.value);
                                if (ePwd2) setEPwd2('');
                            }}
                            autoComplete="new-password"
                            required
                            aria-invalid={!!ePwd2}
                            aria-describedby={ePwd2 ? 'erro-pwd2' : undefined}
                            className="form-input with-icon pr-lg"
                        />
                        <button
                            type="button"
                            className="form-input-suffix btn-link"
                            aria-pressed={showPwd2}
                            aria-controls="pwd2"
                            aria-label={showPwd2 ? 'Ocultar confirmação' : 'Mostrar confirmação'}
                            onClick={() => setShowPwd2((v) => !v)}
                        >
                            {showPwd2 ? <EyeOff size={20} /> : <Eye size={20} />}
                        </button>
                    </div>
                    <ErrorText id="erro-pwd2" message={ePwd2} />
                </div>

                <button
                    type="submit"
                    disabled={loading}
                    aria-busy={loading}
                    className="btn-primary w-full mt-sm"
                >
                    {loading ? <Loader2 size={20} className="icon-spin mr-sm" /> : <Lock size={20} className="mr-sm" />}
                    {loading ? 'Alterando…' : 'Alterar Senha'}
                </button>

                <p className="text-sm text-center mt-md">
                    Lembrou a senha?{' '}
                    <Link to="/login" className="btn-link">
                        Voltar ao login
                    </Link>
                </p>
            </form>
        </div>
    </main>
  );
}