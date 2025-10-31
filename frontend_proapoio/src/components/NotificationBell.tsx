import React, { useState } from 'react';
import { Bell, AlertCircle } from 'lucide-react';
import { Link } from 'react-router-dom';
import api from '../services/api'; // Usado para buscar notificações

// Simulação de tipo de notificação
interface Notification {
    id: number;
    mensagem: string;
    read: boolean;
    link: string;
}

/**
 * @component NotificationBell
 * @description Botão de notificação que exibe um sino e um contador de notificações não lidas.
 * Ao clicar, exibe um painel (simulado) com as notificações.
 */
export default function NotificationBell() {
    const [isOpen, setIsOpen] = useState(false);
    // Simulação de dados de notificação
    const [notifications, setNotifications] = useState<Notification[]>([
        { id: 1, mensagem: "Nova proposta recebida!", read: false, link: "/minhas-propostas" },
        { id: 2, mensagem: "Sua vaga 'Auxiliar de Sala' foi pausada.", read: true, link: "/perfil/instituicao" },
    ]);

    const unreadCount = notifications.filter(n => !n.read).length;

    // TODO: Implementar fetch real de notificações GET /notifications

    const togglePanel = () => setIsOpen(prev => !prev);
    
    // Marca a notificação como lida (simulação)
    const handleRead = (id: number) => {
        setNotifications(prev => prev.map(n => n.id === id ? { ...n, read: true } : n));
    };

    return (
        <div className="notification-wrapper">
            <button
                onClick={togglePanel}
                className="btn-icon btn-sm btn-secondary notification-button"
                aria-label={`Você tem ${unreadCount} notificações não lidas`}
                aria-expanded={isOpen}
            >
                <Bell size={20} />
                {unreadCount > 0 && (
                    <span className="notification-badge" aria-hidden="true">
                        {unreadCount}
                    </span>
                )}
            </button>

            {/* Painel Dropdown de Notificações (Simulação) */}
            {isOpen && (
                <div className="notification-panel card">
                    <h3 className="title-md border-bottom-divider pb-xs mb-xs">Notificações</h3>
                    
                    {notifications.length === 0 ? (
                        <p className="text-sm text-muted">Nenhuma nova notificação.</p>
                    ) : (
                        <ul className="space-y-xs">
                            {notifications.map(n => (
                                <li key={n.id} className={`notification-item ${!n.read ? 'notification-unread' : ''}`} onClick={() => handleRead(n.id)}>
                                    <Link to={n.link} className="notification-link">
                                        {n.mensagem}
                                        {!n.read && <span className="notification-dot" />}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}
        </div>
    );
}
