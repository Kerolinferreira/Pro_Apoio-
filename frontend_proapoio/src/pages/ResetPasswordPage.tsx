import { useEffect, useMemo, useRef, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import api from '../services/api';

function senhaValida(s: string) {
  // mínimo 8, ao menos uma letra e um número
  return /^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(s);
}

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

  // erros por campo
  const [eEmail, setEEmail] = useState('');
  const [eToken, setEToken] = useState('');
  const [ePwd, setEPwd] = useState('');
  const [ePwd2, setEPwd2] = useState('');

  const location = useLocation();
  const navigate = useNavigate();

  // A11y
  const liveRef = useRef<HTMLParagraphElement>(null);
  const alertRef = useRef<HTMLDivElement>(null);
  const h1Ref = useRef<HTMLHeadingElement>(null);
  const emailRef = useRef<HTMLInputElement>(null);
  const tokenRef = useRef<HTMLInputElement>(null);
  const pwdRef = useRef<HTMLInputElement>(null);
  const pwd2Ref = useRef<HTMLInputElement>(null);

  useEffect(() => {
    const params = new URLSearchParams(location.search);
    const t = params.get('token') || '';
    const e = params.get('email') || '';
    if (t) setToken(t);
    if (e) setEmail(e);
    setTimeout(() => h1Ref.current?.focus(), 0);
  }, [location.search]);

  const pwdScore = useMemo(() => {
    let s = 0;
    if (password.length >= 8) s++;
    if (/[A-Z]/.test(password)) s++;
    if (/[a-z]/.test(password)) s++;
    if (/[0-9]/.test(password)) s++;
    if (/[^A-Za-z0-9]/.test(password)) s++;
    return s; // 0..5
  }, [password]);

  const descricaoForca = useMemo(() => {
    if (pwdScore <= 2) return 'força baixa';
    if (pwdScore === 3) return 'força média';
    return 'força alta';
  }, [pwdScore]);

  function announce(msg: string) {
    if (liveRef.current) {
      liveRef.current.textContent = msg;
      setTimeout(() => {
        if (liveRef.current) liveRef.current.textContent = '';
      }, 2000);
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

    if (!email.trim()) {
      setEEmail('Informe o e-mail.');
      ok = false;
    }
    if (!token.trim()) {
      setEToken('Informe o token.');
      ok = false;
    }
    if (!senhaValida(password)) {
      setEPwd('Mínimo de oito caracteres com letras e números.');
      ok = false;
    }
    if (password !== passwordConfirmation) {
      setEPwd2('As senhas não conferem.');
      ok = false;
    }
    return ok;
  }

  function focarPrimeiroErro() {
    if (eEmail) return emailRef.current?.focus();
    if (eToken) return tokenRef.current?.focus();
    if (ePwd) return pwdRef.current?.focus();
    if (ePwd2) return pwd2Ref.current?.focus();
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setSuccess('');

    const ok = validarCampos();
    if (!ok) {
      setTimeout(() => {
        alertRef.current?.focus();
        focarPrimeiroErro();
      }, 0);
      announce('Erros no formulário.');
      return;
    }

    setLoading(true);
    try {
      await api.post('/auth/reset-password', {
        email,
        token,
        password,
        password_confirmation: passwordConfirmation,
      });
      setSuccess('Senha alterada. Você pode entrar agora.');
      announce('Senha redefinida com sucesso.');
      navigate('/login');
    } catch (err: any) {
      const status = err?.response?.status;
      const msg =
        status === 400 || status === 422
          ? err?.response?.data?.message || 'Token inválido ou expirado.'
          : status === 429
          ? 'Muitas tentativas. Aguarde um minuto.'
          : 'Não foi possível redefinir a senha.';
      setError(msg);
      announce(msg);
      setTimeout(() => alertRef.current?.focus(), 0);
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className="max-w-md mx-auto p-4" aria-labelledby="titulo-reset" role="main">
      <h1
        id="titulo-reset"
        className="text-2xl font-extrabold mb-4"
        ref={h1Ref}
        tabIndex={-1}
      >
        Redefinir senha
      </h1>
      <p ref={liveRef} className="sr-only" aria-live="polite" />

      {(error || success) && (
        <div
          ref={alertRef}
          role={error ? 'alert' : 'status'}
          tabIndex={-1}
          className={`mb-3 rounded border p-3 ${
            error
              ? 'border-red-200 bg-red-50 text-red-800'
              : 'border-green-200 bg-green-50 text-green-800'
          }`}
        >
          {error || success}
        </div>
      )}

      <form onSubmit={handleSubmit} noValidate className="space-y-4" aria-describedby="ajuda">
        <p id="ajuda" className="text-sm text-zinc-600">
          Cole o token recebido por e-mail ou acesse pelo link do e-mail, que já preenche os campos.
        </p>

        <div>
          <label htmlFor="email" className="block text-sm font-medium">
            E-mail
          </label>
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
            className="mt-1 border p-2 w-full rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700"
          />
          {eEmail && (
            <p id="erro-email" className="mt-1 text-sm text-red-700">
              {eEmail}
            </p>
          )}
        </div>

        <div>
          <label htmlFor="token" className="block text-sm font-medium">
            Token
          </label>
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
            className="mt-1 border p-2 w-full rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700"
          />
          {eToken && (
            <p id="erro-token" className="mt-1 text-sm text-red-700">
              {eToken}
            </p>
          )}
        </div>

        <div>
          <label htmlFor="pwd" className="block text-sm font-medium">
            Nova senha
          </label>
          <div className="mt-1 relative">
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
              className="border p-2 w-full rounded pr-24 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700"
            />
            <button
              type="button"
              className="absolute inset-y-0 right-0 px-3 text-sm underline underline-offset-4"
              aria-pressed={showPwd}
              aria-controls="pwd"
              aria-label={showPwd ? 'Ocultar senha' : 'Mostrar senha'}
              onClick={() => setShowPwd((v) => !v)}
            >
              {showPwd ? 'Ocultar' : 'Mostrar'}
            </button>
          </div>

          {/* Medidor de força: inclui descrição textual para leitores de tela */}
          <div
            className="mt-1 h-1 rounded bg-zinc-200"
            role="progressbar"
            aria-valuemin={0}
            aria-valuemax={5}
            aria-valuenow={pwdScore}
            aria-label="Força da senha"
            aria-describedby="texto-forca"
          >
            <div
              className={`h-1 rounded ${
                pwdScore <= 2 ? 'bg-red-500 w-1/4' : pwdScore === 3 ? 'bg-amber-500 w-2/4' : 'bg-green-600 w-3/4'
              }`}
            />
          </div>
          <p id="texto-forca" className="sr-only">
            {descricaoForca}
          </p>

          {ePwd ? (
            <p id="erro-pwd" className="mt-1 text-sm text-red-700">
              {ePwd}
            </p>
          ) : (
            <p id="ajuda-pwd" className="text-xs text-zinc-600 mt-1">
              Mínimo oito caracteres. Use letras e números. Símbolos aumentam a força.
            </p>
          )}
        </div>

        <div>
          <label htmlFor="pwd2" className="block text-sm font-medium">
            Confirmar nova senha
          </label>
          <div className="mt-1 relative">
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
              className="border p-2 w-full rounded pr-24 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700"
            />
            <button
              type="button"
              className="absolute inset-y-0 right-0 px-3 text-sm underline underline-offset-4"
              aria-pressed={showPwd2}
              aria-controls="pwd2"
              aria-label={showPwd2 ? 'Ocultar confirmação' : 'Mostrar confirmação'}
              onClick={() => setShowPwd2((v) => !v)}
            >
              {showPwd2 ? 'Ocultar' : 'Mostrar'}
            </button>
          </div>
          {ePwd2 && (
            <p id="erro-pwd2" className="mt-1 text-sm text-red-700">
              {ePwd2}
            </p>
          )}
        </div>

        <button
          type="submit"
          disabled={loading}
          aria-busy={loading}
          className="bg-blue-700 text-white px-4 py-2 rounded w-full font-semibold disabled:opacity-60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700"
        >
          {loading ? 'Alterando…' : 'Alterar senha'}
        </button>

        <p className="text-sm text-center mt-2">
          Lembrou a senha?{' '}
          <a href="/login" className="underline underline-offset-4">
            Voltar ao login
          </a>
        </p>
      </form>
    </main>
  );
}
