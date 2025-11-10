import React, { useState } from 'react';
import { X, Send, Loader2 } from 'lucide-react';
import { logger } from '../utils/logger';

interface PropostaModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (mensagem: string) => Promise<void>;
  vagaTitulo: string;
}

/**
 * @component PropostaModal
 * @description Modal para envio de proposta de candidatura a uma vaga
 */
const PropostaModal: React.FC<PropostaModalProps> = ({
  isOpen,
  onClose,
  onSubmit,
  vagaTitulo,
}) => {
  const [mensagem, setMensagem] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const maxLength = 2000;
  const remainingChars = maxLength - mensagem.length;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    // Validação
    if (!mensagem.trim()) {
      setError('Por favor, escreva uma mensagem para sua candidatura.');
      return;
    }

    if (mensagem.length > maxLength) {
      setError(`A mensagem não pode ter mais de ${maxLength} caracteres.`);
      return;
    }

    setIsSubmitting(true);
    try {
      await onSubmit(mensagem.trim());
      // Limpa o formulário após sucesso
      setMensagem('');
      setError(null);
      onClose();
    } catch (err) {
      // O erro será tratado no componente pai
      logger.error('Erro ao enviar proposta:', err);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleClose = () => {
    if (!isSubmitting) {
      setMensagem('');
      setError(null);
      onClose();
    }
  };

  const handleBackdropClick = (e: React.MouseEvent) => {
    if (e.target === e.currentTarget && !isSubmitting) {
      handleClose();
    }
  };

  if (!isOpen) return null;

  return (
    <div
      className="modal-overlay"
      onClick={handleBackdropClick}
      role="dialog"
      aria-modal="true"
      aria-labelledby="proposta-modal-title"
    >
      <div className="modal-content card" style={{ maxWidth: '600px' }}>
        {/* Header */}
        <div className="flex-group-md-row mb-md" style={{ justifyContent: 'space-between', alignItems: 'flex-start' }}>
          <div>
            <h2 id="proposta-modal-title" className="title-lg mb-xs">
              Candidatar-se à Vaga
            </h2>
            <p className="text-sm text-muted">{vagaTitulo}</p>
          </div>
          <button
            onClick={handleClose}
            className="btn-icon btn-sm"
            aria-label="Fechar modal"
            disabled={isSubmitting}
          >
            <X size={20} />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit}>
          <div className="mb-lg">
            <label htmlFor="mensagem" className="form-label">
              Mensagem de Apresentação <span className="text-error">*</span>
            </label>
            <p className="text-sm text-muted mb-sm">
              Conte um pouco sobre você, suas experiências e por que você é o candidato ideal para esta vaga.
            </p>
            <textarea
              id="mensagem"
              value={mensagem}
              onChange={(e) => setMensagem(e.target.value)}
              className={`form-textarea ${error ? 'form-input' : ''}`}
              placeholder="Olá! Tenho muito interesse nesta vaga porque..."
              rows={8}
              maxLength={maxLength}
              disabled={isSubmitting}
              required
            />
            <div className="flex-group-md-row mt-xs" style={{ justifyContent: 'space-between' }}>
              <span className={`text-sm ${remainingChars < 100 ? 'text-warning' : 'text-muted'}`}>
                {remainingChars} caracteres restantes
              </span>
              {error && <span className="text-sm text-error">{error}</span>}
            </div>
          </div>

          {/* Info Box */}
          <div className="alert alert-info mb-lg">
            <p className="text-sm">
              <strong>Dica:</strong> Uma boa mensagem de candidatura inclui suas motivações,
              experiências relevantes e como você pode contribuir para a instituição.
            </p>
          </div>

          {/* Actions */}
          <div className="flex-actions-end">
            <button
              type="button"
              onClick={handleClose}
              className="btn-secondary"
              disabled={isSubmitting}
            >
              Cancelar
            </button>
            <button
              type="submit"
              className="btn-primary btn-icon"
              disabled={isSubmitting || !mensagem.trim()}
            >
              {isSubmitting ? (
                <>
                  <Loader2 size={20} className="icon-spin" />
                  Enviando...
                </>
              ) : (
                <>
                  <Send size={20} />
                  Enviar Candidatura
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default PropostaModal;
