import React, { useState } from 'react';
import { X, Lock, Eye, EyeOff, Loader2, Shield } from 'lucide-react';
import { useToast } from './Toast';
import api from '../services/api';
import { parseApiError } from '../utils/errorHandler';
import { logger } from '../utils/logger';

interface ChangePasswordModalProps {
  isOpen: boolean;
  onClose: () => void;
  endpoint: string;
}

/**
 * @component ChangePasswordModal
 * @description Modal reutilizável para alteração de senha
 */
const ChangePasswordModal: React.FC<ChangePasswordModalProps> = ({
  isOpen,
  onClose,
  endpoint,
}) => {
  const toast = useToast();

  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  const [showCurrentPassword, setShowCurrentPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  const handleClose = () => {
    if (!isSubmitting) {
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      setShowCurrentPassword(false);
      setShowNewPassword(false);
      setShowConfirmPassword(false);
      setError(null);
      setFieldErrors({});
      onClose();
    }
  };

  const handleBackdropClick = (e: React.MouseEvent) => {
    if (e.target === e.currentTarget && !isSubmitting) {
      handleClose();
    }
  };

  const validatePassword = (password: string): string | null => {
    if (password.length < 8) {
      return 'A senha deve ter no mínimo 8 caracteres.';
    }
    if (!/[A-Za-z]/.test(password)) {
      return 'A senha deve conter ao menos uma letra.';
    }
    if (!/\d/.test(password)) {
      return 'A senha deve conter ao menos um número.';
    }
    return null;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setFieldErrors({});

    // Validações locais
    if (!currentPassword) {
      setFieldErrors({ current_password: 'Por favor, informe sua senha atual.' });
      return;
    }

    const passwordError = validatePassword(newPassword);
    if (passwordError) {
      setFieldErrors({ password: passwordError });
      return;
    }

    if (newPassword !== confirmPassword) {
      setFieldErrors({ password_confirmation: 'As senhas não conferem.' });
      return;
    }

    if (currentPassword === newPassword) {
      setFieldErrors({ password: 'A nova senha deve ser diferente da senha atual.' });
      return;
    }

    setIsSubmitting(true);

    try {
      await api.put(endpoint, {
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: confirmPassword,
      });

      toast.success('Senha alterada com sucesso!');
      handleClose();
    } catch (err) {
      logger.error('Erro ao alterar senha:', err);

      const { generalMessage } = parseApiError(err);
      const status = (err as any)?.response?.status;
      const errorData = (err as any)?.response?.data;

      if (status === 400) {
        // Senha atual incorreta
        if (errorData?.message?.includes('incorreta')) {
          setFieldErrors({ current_password: 'Senha atual incorreta.' });
        } else {
          setError(errorData?.message || generalMessage);
        }
      } else if (status === 422) {
        // Erros de validação
        const errors = errorData?.errors || {};
        const newFieldErrors: Record<string, string> = {};

        if (errors.current_password) {
          newFieldErrors.current_password = errors.current_password[0];
        }
        if (errors.password) {
          newFieldErrors.password = errors.password[0];
        }
        if (errors.password_confirmation) {
          newFieldErrors.password_confirmation = errors.password_confirmation[0];
        }

        setFieldErrors(newFieldErrors);

        if (Object.keys(newFieldErrors).length === 0) {
          setError(generalMessage);
        }
      } else {
        setError(generalMessage || 'Não foi possível alterar a senha. Tente novamente.');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div
      className="modal-overlay"
      onClick={handleBackdropClick}
      role="dialog"
      aria-modal="true"
      aria-labelledby="change-password-modal-title"
    >
      <div className="modal-content card" style={{ maxWidth: '500px' }}>
        {/* Header */}
        <div className="flex-group-md-row mb-md" style={{ justifyContent: 'space-between', alignItems: 'flex-start' }}>
          <div className="flex-group-item" style={{ gap: '0.5rem' }}>
            <Shield size={24} className="text-brand" />
            <h2 id="change-password-modal-title" className="title-lg">
              Alterar Senha
            </h2>
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

        {/* Mensagem de erro geral */}
        {error && (
          <div className="alert alert-error mb-md">
            <p className="text-sm">{error}</p>
          </div>
        )}

        {/* Form */}
        <form onSubmit={handleSubmit}>
          <div className="space-y-md mb-lg">
            {/* Senha Atual */}
            <div className="form-group">
              <label htmlFor="current_password" className="form-label">
                Senha Atual <span className="text-error">*</span>
              </label>
              <div className="form-input-icon-wrapper">
                <Lock size={20} className="form-icon" />
                <input
                  type={showCurrentPassword ? 'text' : 'password'}
                  id="current_password"
                  value={currentPassword}
                  onChange={(e) => {
                    setCurrentPassword(e.target.value);
                    if (fieldErrors.current_password) {
                      setFieldErrors(prev => {
                        const newErrors = { ...prev };
                        delete newErrors.current_password;
                        return newErrors;
                      });
                    }
                  }}
                  className={`form-input with-icon ${fieldErrors.current_password ? 'form-input-error' : ''}`}
                  placeholder="Digite sua senha atual"
                  disabled={isSubmitting}
                  required
                  autoComplete="current-password"
                />
                <button
                  type="button"
                  onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                  className="form-input-suffix"
                  aria-label={showCurrentPassword ? 'Ocultar senha' : 'Mostrar senha'}
                  disabled={isSubmitting}
                >
                  {showCurrentPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                </button>
              </div>
              {fieldErrors.current_password && (
                <p className="error-text">{fieldErrors.current_password}</p>
              )}
            </div>

            {/* Nova Senha */}
            <div className="form-group">
              <label htmlFor="new_password" className="form-label">
                Nova Senha <span className="text-error">*</span>
              </label>
              <div className="form-input-icon-wrapper">
                <Lock size={20} className="form-icon" />
                <input
                  type={showNewPassword ? 'text' : 'password'}
                  id="new_password"
                  value={newPassword}
                  onChange={(e) => {
                    setNewPassword(e.target.value);
                    if (fieldErrors.password) {
                      setFieldErrors(prev => {
                        const newErrors = { ...prev };
                        delete newErrors.password;
                        return newErrors;
                      });
                    }
                  }}
                  className={`form-input with-icon ${fieldErrors.password ? 'form-input-error' : ''}`}
                  placeholder="Digite sua nova senha"
                  disabled={isSubmitting}
                  required
                  autoComplete="new-password"
                />
                <button
                  type="button"
                  onClick={() => setShowNewPassword(!showNewPassword)}
                  className="form-input-suffix"
                  aria-label={showNewPassword ? 'Ocultar senha' : 'Mostrar senha'}
                  disabled={isSubmitting}
                >
                  {showNewPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                </button>
              </div>
              {fieldErrors.password && (
                <p className="error-text">{fieldErrors.password}</p>
              )}
              <p className="text-sm text-muted mt-xs">
                Mínimo 8 caracteres, incluindo letras e números
              </p>
            </div>

            {/* Confirmar Nova Senha */}
            <div className="form-group">
              <label htmlFor="confirm_password" className="form-label">
                Confirmar Nova Senha <span className="text-error">*</span>
              </label>
              <div className="form-input-icon-wrapper">
                <Lock size={20} className="form-icon" />
                <input
                  type={showConfirmPassword ? 'text' : 'password'}
                  id="confirm_password"
                  value={confirmPassword}
                  onChange={(e) => {
                    setConfirmPassword(e.target.value);
                    if (fieldErrors.password_confirmation) {
                      setFieldErrors(prev => {
                        const newErrors = { ...prev };
                        delete newErrors.password_confirmation;
                        return newErrors;
                      });
                    }
                  }}
                  className={`form-input with-icon ${fieldErrors.password_confirmation ? 'form-input-error' : ''}`}
                  placeholder="Digite a nova senha novamente"
                  disabled={isSubmitting}
                  required
                  autoComplete="new-password"
                />
                <button
                  type="button"
                  onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                  className="form-input-suffix"
                  aria-label={showConfirmPassword ? 'Ocultar senha' : 'Mostrar senha'}
                  disabled={isSubmitting}
                >
                  {showConfirmPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                </button>
              </div>
              {fieldErrors.password_confirmation && (
                <p className="error-text">{fieldErrors.password_confirmation}</p>
              )}
            </div>
          </div>

          {/* Actions */}
          <div className="flex-actions-end pt-md border-top-divider">
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
              disabled={isSubmitting}
            >
              {isSubmitting ? (
                <>
                  <Loader2 size={20} className="icon-spin" />
                  Alterando...
                </>
              ) : (
                <>
                  <Shield size={20} />
                  Alterar Senha
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ChangePasswordModal;
