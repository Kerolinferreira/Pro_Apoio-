import React, { createContext, useContext, useState, useCallback } from 'react';
import { X, CheckCircle, AlertCircle, AlertTriangle, Info } from 'lucide-react';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

interface Toast {
    id: string;
    type: ToastType;
    title?: string;
    message: string;
}

interface ToastContextType {
    showToast: (type: ToastType, message: string, title?: string) => void;
    success: (message: string, title?: string) => void;
    error: (message: string, title?: string) => void;
    warning: (message: string, title?: string) => void;
    info: (message: string, title?: string) => void;
}

const ToastContext = createContext<ToastContextType | undefined>(undefined);

/**
 * Hook para usar o sistema de toasts
 */
export const useToast = () => {
    const context = useContext(ToastContext);
    if (!context) {
        throw new Error('useToast deve ser usado dentro de ToastProvider');
    }
    return context;
};

/**
 * Provider do sistema de toasts
 */
export const ToastProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [toasts, setToasts] = useState<Toast[]>([]);

    const showToast = useCallback((type: ToastType, message: string, title?: string) => {
        const id = Math.random().toString(36).substring(2, 9);
        const newToast: Toast = { id, type, message, title };

        setToasts(prev => [...prev, newToast]);

        // Auto remove após 5 segundos
        setTimeout(() => {
            setToasts(prev => prev.filter(t => t.id !== id));
        }, 5000);
    }, []);

    const removeToast = useCallback((id: string) => {
        setToasts(prev => prev.filter(t => t.id !== id));
    }, []);

    const success = useCallback((message: string, title?: string) => {
        showToast('success', message, title || 'Sucesso');
    }, [showToast]);

    const error = useCallback((message: string, title?: string) => {
        showToast('error', message, title || 'Erro');
    }, [showToast]);

    const warning = useCallback((message: string, title?: string) => {
        showToast('warning', message, title || 'Atenção');
    }, [showToast]);

    const info = useCallback((message: string, title?: string) => {
        showToast('info', message, title || 'Informação');
    }, [showToast]);

    const getIcon = (type: ToastType) => {
        switch (type) {
            case 'success':
                return <CheckCircle size={20} className="text-green-600" />;
            case 'error':
                return <AlertCircle size={20} className="text-red-600" />;
            case 'warning':
                return <AlertTriangle size={20} className="text-yellow-600" />;
            case 'info':
                return <Info size={20} className="text-blue-600" />;
        }
    };

    return (
        <ToastContext.Provider value={{ showToast, success, error, warning, info }}>
            {children}

            {/* Toast Container */}
            {toasts.length > 0 && (
                <div className="toast-container">
                    {toasts.map(toast => (
                        <div key={toast.id} className={`toast toast-${toast.type}`} role="alert">
                            {getIcon(toast.type)}
                            <div className="toast-content">
                                {toast.title && <div className="toast-title">{toast.title}</div>}
                                <div className="toast-message">{toast.message}</div>
                            </div>
                            <button
                                onClick={() => removeToast(toast.id)}
                                className="toast-close"
                                aria-label="Fechar notificação"
                            >
                                <X size={16} />
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </ToastContext.Provider>
    );
};
