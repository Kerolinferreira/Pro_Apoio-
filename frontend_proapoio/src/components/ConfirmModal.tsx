import React from 'react';
import { X, AlertCircle, CheckCircle, AlertTriangle } from 'lucide-react';
import { useModalFocus } from '../hooks/useModalFocus';

interface ConfirmModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    type?: 'success' | 'warning' | 'danger' | 'info';
    isLoading?: boolean;
}

/**
 * @component ConfirmModal
 * @description Modal de confirmação customizado para substituir window.confirm
 * Acessível, responsivo e com visual moderno
 * CORREÇÃO P12: Foco automático e trap de foco para leitores de tela
 */
const ConfirmModal: React.FC<ConfirmModalProps> = ({
    isOpen,
    onClose,
    onConfirm,
    title,
    message,
    confirmText = 'Confirmar',
    cancelText = 'Cancelar',
    type = 'info',
    isLoading = false
}) => {
    const { modalRef, firstFocusableRef } = useModalFocus(isOpen, isLoading, onClose);

    if (!isOpen) return null;

    const getIcon = () => {
        switch (type) {
            case 'success':
                return <CheckCircle size={32} className="text-green-600" />;
            case 'warning':
                return <AlertTriangle size={32} className="text-yellow-600" />;
            case 'danger':
                return <AlertCircle size={32} className="text-red-600" />;
            default:
                return <AlertCircle size={32} className="text-brand-color" />;
        }
    };

    const getConfirmButtonClass = () => {
        switch (type) {
            case 'danger':
                return 'btn-error';
            case 'success':
                return 'btn-primary';
            case 'warning':
                return 'btn-secondary';
            default:
                return 'btn-primary';
        }
    };

    const handleBackdropClick = (e: React.MouseEvent) => {
        if (e.target === e.currentTarget && !isLoading) {
            onClose();
        }
    };

    return (
        <div
            className="modal-overlay"
            onClick={handleBackdropClick}
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-title"
            aria-describedby="modal-description"
        >
            <div className="modal-content card" ref={modalRef}>
                {/* Header */}
                <div className="flex-group-md-row mb-md" style={{ justifyContent: 'space-between', alignItems: 'flex-start' }}>
                    <div className="flex-group" style={{ gap: '1rem', alignItems: 'center' }}>
                        {getIcon()}
                        <h2 id="modal-title" className="title-lg">{title}</h2>
                    </div>
                    <button
                        onClick={onClose}
                        className="btn-icon btn-sm"
                        aria-label="Fechar modal"
                        disabled={isLoading}
                    >
                        <X size={20} />
                    </button>
                </div>

                {/* Message */}
                <div className="mb-lg">
                    <p id="modal-description" className="text-base text-base-color">{message}</p>
                </div>

                {/* Actions */}
                <div className="flex-actions-end" style={{ gap: '0.75rem' }}>
                    <button
                        ref={firstFocusableRef}
                        onClick={onClose}
                        className="btn-secondary"
                        disabled={isLoading}
                    >
                        {cancelText}
                    </button>
                    <button
                        onClick={onConfirm}
                        className={getConfirmButtonClass()}
                        disabled={isLoading}
                    >
                        {isLoading ? 'Processando...' : confirmText}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ConfirmModal;
