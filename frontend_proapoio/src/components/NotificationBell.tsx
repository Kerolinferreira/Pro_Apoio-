import React, { useState, useEffect } from 'react';
import { Bell, AlertCircle } from 'lucide-react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import { logger } from '../utils/logger';

interface NotificationData {
    mensagem: string;
    id_proposta?: number;
    id_vaga?: number;
    id_candidato?: number;
    [key: string]: any;
}

interface Notification {
    id: string; // UUID da notificação
    type: string; // Nome da classe de notificação
    data: NotificationData; // Dados da notificação
    read_at: string | null; // Data/hora de leitura
    created_at: string; // Data/hora de criação
}

/**
 * @component NotificationBell
 * @description Botão de notificação que exibe um sino e um contador de notificações não lidas.
 * Ao clicar, exibe um painel com as notificações obtidas da API.
 *
 * Integrado com a API real de notificações do Laravel:
 * - GET /notificacoes?status=unread|read|all&per_page=10
 * - POST /notificacoes/marcar-como-lidas { ids: string[] } ou { all: true }
 */
export default function NotificationBell() {
    const [isOpen, setIsOpen] = useState(false);
    const [notifications, setNotifications] = useState<Notification[]>([]);
    const [loading, setLoading] = useState(false);

    const unreadCount = notifications.filter(n => n.read_at === null).length;

    // Busca notificações ao montar o componente e quando abre o painel
    useEffect(() => {
        if (isOpen && notifications.length === 0) {
            fetchNotifications();
        }
    }, [isOpen]);

    // Atualiza a contagem periodicamente (a cada 60 segundos)
    useEffect(() => {
        fetchNotifications();
        const interval = setInterval(fetchNotifications, 60000);
        return () => clearInterval(interval);
    }, []);

    const fetchNotifications = async () => {
        try {
            setLoading(true);
            // A API retorna paginação: { data: [...], current_page, last_page, total, etc }
            const response = await api.get('/notificacoes', {
                params: {
                    status: 'all', // Busca todas (lidas e não lidas)
                    per_page: 20, // Últimas 20 notificações
                }
            });
            const data = response.data?.data || [];
            setNotifications(data);
        } catch (error) {
            logger.error('Erro ao carregar notificações:', error);
            setNotifications([]);
        } finally {
            setLoading(false);
        }
    };

    const togglePanel = () => {
        setIsOpen(prev => !prev);
        if (!isOpen) {
            fetchNotifications();
        }
    };

    /**
     * Gera o link apropriado baseado no tipo de notificação
     * NOTA: Backend retorna tipos em snake_case desde correção #22
     */
    const getNotificationLink = (notification: Notification): string => {
        const { type, data } = notification;

        // Nova proposta recebida (para instituição)
        // Redireciona para página de propostas onde usuário pode filtrar/encontrar
        if (type === 'nova_proposta_notification' && data.id_proposta) {
            return `/minhas-propostas`;
        }

        // Proposta aceita (para candidato)
        if (type === 'proposta_aceita_notification' && data.id_proposta) {
            return `/minhas-propostas`;
        }

        // Proposta recusada
        if (type === 'proposta_recusada_notification' && data.id_proposta) {
            return `/minhas-propostas`;
        }

        // Vaga excluída
        if (type === 'vaga_excluida_notification') {
            return `/vagas`;
        }

        // Link padrão
        return '/';
    };

    // Marca notificação como lida e atualiza a API
    const handleRead = async (id: string) => {
        try {
            // Marca localmente primeiro para UX imediata
            setNotifications(prev => prev.map(n =>
                n.id === id ? { ...n, read_at: new Date().toISOString() } : n
            ));

            // Envia para a API
            await api.post('/notificacoes/marcar-como-lidas', { ids: [id] });
        } catch (error) {
            logger.error('Erro ao marcar notificação como lida:', error);
            // Reverte se falhar
            fetchNotifications();
        }
    };

    const markAllAsRead = async () => {
        try {
            const unreadIds = notifications.filter(n => n.read_at === null).map(n => n.id);
            if (unreadIds.length === 0) return;

            // Marca localmente primeiro
            const now = new Date().toISOString();
            setNotifications(prev => prev.map(n => ({ ...n, read_at: now })));

            // Envia para a API - pode usar { all: true } ou { ids: [...] }
            await api.post('/notificacoes/marcar-como-lidas', { all: true });
        } catch (error) {
            logger.error('Erro ao marcar todas como lidas:', error);
            fetchNotifications();
        }
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

            {/* Painel Dropdown de Notificações */}
            {isOpen && (
                <div className="notification-panel card">
                    <div className="flex-group-space-between mb-xs border-bottom-divider pb-xs">
                        <h3 className="title-md">Notificações</h3>
                        {unreadCount > 0 && (
                            <button
                                onClick={markAllAsRead}
                                className="btn-link text-xs"
                                aria-label="Marcar todas como lidas"
                            >
                                Marcar todas como lidas
                            </button>
                        )}
                    </div>

                    {loading && notifications.length === 0 ? (
                        <p className="text-sm text-muted">Carregando...</p>
                    ) : notifications.length === 0 ? (
                        <p className="text-sm text-muted">Nenhuma notificação.</p>
                    ) : (
                        <ul className="space-y-xs">
                            {notifications.map(n => (
                                <li
                                    key={n.id}
                                    className={`notification-item ${n.read_at === null ? 'notification-unread' : ''}`}
                                    onClick={() => handleRead(n.id)}
                                >
                                    <Link to={getNotificationLink(n)} className="notification-link">
                                        {n.data.mensagem}
                                        {n.read_at === null && <span className="notification-dot" />}
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
