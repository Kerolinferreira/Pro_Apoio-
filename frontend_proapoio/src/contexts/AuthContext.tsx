import React, { createContext, useState, useContext, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import api, { setLogoutCallback } from '../services/api'; // Importa a instância e o callback setter

// ===================================
// TIPOS E INTERFACES
// ===================================

export interface AuthUser {
    id: number;
    email: string;
    tipo_usuario: 'candidato' | 'instituicao';
    token: string;
}

interface AuthContextData {
    user: AuthUser | null;
    isAuthenticated: boolean;
    login: (email: string, password: string, options?: { remember: boolean }) => Promise<AuthUser>;
    logout: () => void;
    loading: boolean;
}

const AuthContext = createContext<AuthContextData>({} as AuthContextData);
const STORAGE_KEY = '@ProApoio:user';

// ===================================
// PROVEDOR DE CONTEXTO
// ===================================

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [user, setUser] = useState<AuthUser | null>(null);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    /**
     * @description Carrega o usuário da sessão anterior (local storage) ao montar.
     */
    useEffect(() => {
        const storedUser = localStorage.getItem(STORAGE_KEY);
        if (storedUser) {
            try {
                const parsedUser: AuthUser = JSON.parse(storedUser);
                setUser(parsedUser);
                // Garante que o token também esteja disponível separadamente para o interceptor
                if (parsedUser.token && !localStorage.getItem('authToken')) {
                    localStorage.setItem('authToken', parsedUser.token);
                }
            } catch (e) {
                console.error("Failed to parse stored user data:", e);
                localStorage.removeItem(STORAGE_KEY);
                localStorage.removeItem('authToken');
            }
        }
        setLoading(false);
    }, []);

    /**
     * @async
     * @function login
     * @description Realiza o login na API e armazena os dados do usuário.
     */
    const login = useCallback(async (email: string, password: string, options = { remember: true }) => {
        const response = await api.post('/auth/login', { email, password });

        const token = response.data.token;
        const userData = response.data.user;

        if (!token || !userData) {
            throw new Error("Resposta de login incompleta.");
        }

        const loggedUser: AuthUser = {
            id: userData.id,
            email: userData.email,
            tipo_usuario: userData.tipo_usuario as 'candidato' | 'instituicao',
            token: token,
        };

        setUser(loggedUser);

        // Armazena o token separadamente para o interceptor de api.ts
        if (options.remember) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(loggedUser));
            localStorage.setItem('authToken', token);
        }

        return loggedUser;
    }, []);

    /**
     * @function logout
     * @description Limpa a sessão local e redireciona. Chamado pelo usuário ou pelo interceptor 401.
     */
    const logout = useCallback(() => {
        // Limpa o estado local primeiro para uma resposta de UI imediata.
        setUser(null);
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem('authToken');

        // Redireciona para a página de login com um parâmetro que pode ser útil.
        navigate('/login?session=expired');

        // Tenta fazer o logout no backend (fire-and-forget).
        // Não bloqueia a UI e não gera erro se falhar, pois a sessão local já foi encerrada.
        api.post('/auth/logout').catch(e => {
            console.warn('Backend logout call failed, but local session is cleared.', e);
        });
    }, [navigate]);

    // Registra a função de logout no interceptor da API assim que o provider for montado.
    useEffect(() => {
        setLogoutCallback(logout);
    }, [logout]);

    if (loading) return null; 

    return (
        <AuthContext.Provider
            value={{
                user,
                isAuthenticated: !!user,
                login,
                logout,
                loading,
            }}
        >
            {children}
        </AuthContext.Provider>
    );
};

// ===================================
// HOOK CUSTOMIZADO
// ===================================

/**
 * @function useAuth
 * @description Hook para acessar os dados e funções do AuthContext.
 */
export const useAuth = () => {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};

export default AuthProvider;
