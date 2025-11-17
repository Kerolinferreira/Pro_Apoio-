import React, { useState, useEffect } from 'react';
import { X, Eye, EyeOff, AlertTriangle } from 'lucide-react';
import api from '../services/api';
import { useToast } from './Toast';
import { parseApiError, getFieldErrorMessage } from '../utils/errorHandler';
import { useModalFocus } from '../hooks/useModalFocus';

/**
 * Modal para exclusão de conta do usuário.
 * Requer confirmação por senha e exibe aviso sobre a permanência da ação.
 * CORREÇÃO P12: Foco automático e trap de foco para leitores de tela
 */

interface DeleteAccountModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: () => void; // Callback após exclusão bem-sucedida (para fazer logout)
    endpoint: string; // Endpoint da API (ex: /candidatos/me/profile)
}

const DeleteAccountModal: React.FC<DeleteAccountModalProps> = ({
    isOpen,
    onClose,
    onSuccess,
    endpoint,
}) => {
    const toast = useToast();

    // Estados do formulário
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [errors, setErrors] = useState<{ [key: string]: string }>({});

    // Hook de acessibilidade para foco
    const { modalRef, firstFocusableRef } = useModalFocus(isOpen, isLoading, onClose);

    // Limpar formulário ao fechar
    useEffect(() => {
        if (!isOpen) {
            setPassword('');
            setShowPassword(false);
            setErrors({});
        }
    }, [isOpen]);

    /**
     * Valida o formulário antes do envio
     */
    const validateForm = (): boolean => {
        const newErrors: { [key: string]: string } = {};

        if (!password.trim()) {
            newErrors.password = 'Por favor, informe sua senha para confirmar a exclusão.';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    /**
     * Submete a requisição de exclusão de conta
     */
    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        setIsLoading(true);
        setErrors({});

        try {
            await api.delete(endpoint, {
                data: { password },
            });

            toast.success('Conta excluída com sucesso.');
            onClose();
            onSuccess(); // Chama callback para fazer logout
        } catch (error: any) {
            const { generalMessage, fieldErrors } = parseApiError(error);
            const status = error.response?.status;
            const errorData = error.response?.data;

            if (status === 400) {
                // Senha inválida
                const message = errorData?.message || 'Senha inválida.';
                setErrors({ password: message });
                toast.error(message);
            } else if (status === 422) {
                // Erros de validação
                setErrors(fieldErrors);
                toast.error(generalMessage);
            } else {
                // Erro genérico
                toast.error(generalMessage);
            }
        } finally {
            setIsLoading(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div
                ref={modalRef}
                className="modal-content modal-md"
                onClick={(e) => e.stopPropagation()}
                role="dialog"
                aria-labelledby="delete-account-modal-title"
                aria-modal="true"
            >
                {/* Header */}
                <div className="modal-header">
                    <h2 id="delete-account-modal-title" className="modal-title">
                        Excluir Conta
                    </h2>
                    <button
                        onClick={onClose}
                        className="modal-close"
                        aria-label="Fechar modal"
                        disabled={isLoading}
                    >
                        <X size={24} />
                    </button>
                </div>

                {/* Body */}
                <div className="modal-body">
                    {/* Aviso de Perigo */}
                    <div className="alert alert-error mb-lg">
                        <AlertTriangle size={20} className="mr-sm" />
                        <div>
                            <strong>Atenção: Esta ação é permanente e irreversível!</strong>
                            <p className="mt-sm mb-0">
                                Ao excluir sua conta, todos os seus dados serão removidos definitivamente
                                e não poderão ser recuperados.
                            </p>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} id="delete-account-form">
                        {/* Campo de Senha */}
                        <div className="form-group">
                            <label htmlFor="password" className="form-label required">
                                Confirme sua senha
                            </label>
                            <div className="form-input-icon-wrapper">
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    id="password"
                                    name="password"
                                    className={`form-input ${errors.password ? 'form-input-error' : ''}`}
                                    value={password}
                                    onChange={(e) => {
                                        setPassword(e.target.value);
                                        if (errors.password) {
                                            setErrors({ ...errors, password: '' });
                                        }
                                    }}
                                    placeholder="Digite sua senha atual"
                                    disabled={isLoading}
                                    autoComplete="current-password"
                                    aria-invalid={errors.password ? 'true' : 'false'}
                                    aria-describedby={errors.password ? 'password-error' : undefined}
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="form-input-suffix"
                                    aria-label={showPassword ? 'Ocultar senha' : 'Mostrar senha'}
                                    disabled={isLoading}
                                    tabIndex={-1}
                                >
                                    {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
                                </button>
                            </div>
                            {errors.password && (
                                <span className="form-error" id="password-error" role="alert">
                                    {errors.password}
                                </span>
                            )}
                            <small className="form-text">
                                Para sua segurança, precisamos confirmar sua identidade.
                            </small>
                        </div>
                    </form>
                </div>

                {/* Footer */}
                <div className="modal-footer">
                    <button
                        ref={firstFocusableRef}
                        type="button"
                        onClick={onClose}
                        className="btn-secondary"
                        disabled={isLoading}
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        form="delete-account-form"
                        className="btn-danger"
                        disabled={isLoading}
                    >
                        {isLoading ? (
                            <>
                                <span className="spinner-border spinner-sm mr-sm" role="status" aria-hidden="true" />
                                Excluindo...
                            </>
                        ) : (
                            'Excluir Conta Permanentemente'
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default DeleteAccountModal;
