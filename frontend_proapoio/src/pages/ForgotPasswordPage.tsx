import { useEffect, useRef, useState } from 'react'
import api from '../services/api'
import { Link } from 'react-router-dom';
import { Mail, Loader2, ArrowLeft } from 'lucide-react'; // Ícones para melhor UX

/**
 * @component ForgotPasswordPage
 * @description Página para recuperação de senha, seguindo o fluxo de envio de link por email.
 * Implementa contador de reenvio e tratamento de erro 429 (Muitas Tentativas).
 */
export default function ForgotPasswordPage() {
  const [email, setEmail] = useState('');
  const [submitted, setSubmitted] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string>('');
  const [secondsLeft, setSecondsLeft] = useState(0);
  // Live region para anunciar feedback de forma acessível
  const liveRef = useRef<HTMLParagraphElement>(null); 

  /**
   * @description Gerencia o contador de reenvio do email.
   */
  useEffect(() => {
    if (!submitted || secondsLeft <= 0) return;
    const t = setInterval(() => setSecondsLeft((s) => s - 1), 1000);
    return () => clearInterval(t);
  }, [submitted, secondsLeft]);

  /**
   * @async
   * @function handleSubmit
   * @description Envia a requisição de recuperação de senha para a API.
   * Trata a limitação de taxa (429) conforme a regra de negócio.
   */
  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (loading) return; // Evita cliques múltiplos

    setError('');
    setLoading(true);

    try {
      // POST /auth/forgot-password [cite: Documentação final.docx]
      await api.post('/auth/forgot-password', { email });
      setSubmitted(true);
      setSecondsLeft(60); // Inicia contador de 60 segundos
      announce('Solicitação enviada. Verifique seu email.');
    } catch (err: any) {
      const status = err?.response?.status;
      let message = 'Não foi possível processar a solicitação agora.';

      // Tratamento de erro específico para Limitação de Taxa (429)
      if (status === 429) {
        message = 'Muitas tentativas. Aguarde um minuto e tente novamente.';
      } else if (status === 404) {
        // Por segurança, a API não deve confirmar se o email existe.
        message = 'Processado. Verifique seu email para instruções.';
        setSubmitted(true);
        setSecondsLeft(60);
      } else if (status >= 400) {
        message = err?.response?.data?.message || 'Ocorreu um erro inesperado.';
      }

      setError(status === 404 ? '' : message); // Se for 404, ainda mostra o sucesso falso para não vazar emails
      announce(message);
    } finally {
      setLoading(false);
    }
  }

  /**
   * @function announce
   * @description Atualiza a região live para leitores de tela.
   */
  function announce(msg: string) {
    if (liveRef.current) {
      liveRef.current.textContent = msg;
    }
  }

  const canResend = submitted && secondsLeft === 0;
  const isButtonDisabled = loading || (submitted && secondsLeft > 0);

  return (
    // Usa 'auth-container' para centralização e card de autenticação
    <main className="auth-container" aria-labelledby="titulo-forgot"> 
      
      {/* Container de conteúdo/card principal */}
      <div className="card-auth">
        
        <h1 id="titulo-forgot" className="heading-secondary mb-lg text-center">Recuperar Acesso</h1>
        
        {/* Live region para feedback de acessibilidade */}
        <p ref={liveRef} className="sr-only" aria-live="polite" />

        {/* Exibição de Erro */}
        {error && (
          <div className="alert alert-error mb-md" role="alert">
            {error}
          </div>
        )}

        {!submitted ? (
          // FORMULÁRIO DE INFORMAÇÃO DE EMAIL
          <form onSubmit={handleSubmit} noValidate className="space-y-md" aria-describedby="dica">
            <p id="dica" className="text-sm text-muted">
              Informe seu e-mail cadastrado. Se houver conta, enviaremos um link seguro de redefinição de senha.
            </p>
            
            <div className="form-group">
              <label htmlFor="email" className="form-label">Email</label>
              <input
                id="email"
                name="email"
                type="email"
                inputMode="email"
                autoComplete="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="form-input" // Classe global
                disabled={loading}
              />
            </div>
            
            <button
              type="submit"
              disabled={isButtonDisabled || !email}
              aria-busy={loading}
              className="btn-primary w-full" // Classe global
            >
              {loading ? <Loader2 className="icon-spin mr-sm" size={20} /> : <Mail size={20} className="mr-sm" />}
              {loading ? 'Enviando…' : 'Enviar Link de Recuperação'}
            </button>
            
            {/* Link para Login */}
            <div className="text-center pt-xs">
                <Link to="/login" className="btn-link text-sm">
                    <ArrowLeft size={16} className="inline mr-xs" />
                    Voltar para o Login
                </Link>
            </div>
          </form>
        ) : (
          // MENSAGEM DE CONFIRMAÇÃO DE ENVIO
          <section aria-label="Confirmação de Envio" className="space-y-md text-center">
            <h2 className="title-lg" style={{ color: 'var(--color-success)' }}>
                Solicitação Recebida!
            </h2>
            <p className="text-base">
              Verifique sua caixa de entrada (e a pasta de spam) para o link de recuperação.
            </p>
            
            <p className="text-sm text-muted">
                Se o e‑mail não chegou, você pode solicitar novamente em {secondsLeft} segundos.
            </p>
            
            {/* Botão de Reenvio */}
            <button
              onClick={(e) => handleSubmit(e as any)}
              disabled={!canResend || loading}
              className="btn-link" // Classe global
              style={{ fontWeight: 600 }}
            >
              {canResend ? 'Reenviar Link Agora' : `Aguarde ${secondsLeft}s`}
            </button>
            
            {/* Link para Login */}
            <div className="pt-sm">
                <Link to="/login" className="btn-link text-sm">
                    <ArrowLeft size={16} className="inline mr-xs" />
                    Lembrou a senha? Voltar ao login
                </Link>
            </div>
          </section>
        )}
      </div>
    </main>
  );
}