import React, { useState } from 'react';
import { X, Send, Loader2 } from 'lucide-react';
import { logger } from '../utils/logger';
import { useModalFocus } from '../hooks/useModalFocus';

interface PropostaModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (mensagem: string) => Promise<void>;
  vagaTitulo: string;
  mode?: 'candidate' | 'institution';
}

/**
 * @component PropostaModal
 * @description Modal para envio de proposta de candidatura a uma vaga
 * CORREÇÃO P12: Foco automático e trap de foco para leitores de tela
 */
const PropostaModal: React.FC<PropostaModalProps> = ({
  isOpen,
  onClose,
  onSubmit,
  vagaTitulo,
  mode = 'candidate', // Default to candidate mode
}) => {
  const [mensagem, setMensagem] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Hook de acessibilidade para foco
  const { modalRef, firstFocusableRef } = useModalFocus(isOpen, isSubmitting, onClose);

  const maxLength = 2000;
  const remainingChars = maxLength - mensagem.length;

  // Text configuration based on mode
  const textConfig = {
    candidate: {
      title: 'Candidatar-se à Vaga',
      label: 'Mensagem de Apresentação',
      description: 'Conte um pouco sobre você, suas experiências e por que você é o candidato ideal para esta vaga.',
      placeholder: 'Olá! Tenho muito interesse nesta vaga porque...',
      submitButton: 'Enviar Candidatura',
      submitButtonLoading: 'Enviando...',
      errorEmpty: 'Por favor, escreva uma mensagem para sua candidatura.',
      tip: 'Uma boa mensagem de candidatura inclui suas motivações, experiências relevantes e como você pode contribuir para a instituição.',
    },
    institution: {
      title: 'Enviar Proposta ao Candidato',
      label: 'Mensagem da Proposta',
      description: 'Descreva a oportunidade e por que este candidato é ideal para esta vaga.',
      placeholder: 'Olá! Gostaríamos de convidá-lo(a) para esta oportunidade porque...',
      submitButton: 'Enviar Proposta',
      submitButtonLoading: 'Enviando...',
      errorEmpty: 'Por favor, escreva uma mensagem para sua proposta.',
      tip: 'Uma boa proposta inclui detalhes sobre a vaga, benefícios e expectativas.',
    },
  };

  const texts = textConfig[mode];

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    // Validação
    if (!mensagem.trim()) {
      setError(texts.errorEmpty);
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
      <div ref={modalRef} className="modal-content card" style={{ maxWidth: '600px' }}>
        {/* Header */}
        <div className="flex-group-md-row mb-md" style={{ justifyContent: 'space-between', alignItems: 'flex-start' }}>
          <div>
            <h2 id="proposta-modal-title" className="title-lg mb-xs">
              {texts.title}
            </h2>
            <p className="text-sm text-muted">{vagaTitulo}</p>
          </div>
          <button
            ref={firstFocusableRef}
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
              {texts.label} <span className="text-error">*</span>
            </label>
            <p className="text-sm text-muted mb-sm">
              {texts.description}
            </p>
            <textarea
              id="mensagem"
              value={mensagem}
              onChange={(e) => setMensagem(e.target.value)}
              className={`form-textarea ${error ? 'form-input' : ''}`}
              placeholder={texts.placeholder}
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
              <strong>Dica:</strong> {texts.tip}
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
                  {texts.submitButtonLoading}
                </>
              ) : (
                <>
                  <Send size={20} />
                  {texts.submitButton}
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
