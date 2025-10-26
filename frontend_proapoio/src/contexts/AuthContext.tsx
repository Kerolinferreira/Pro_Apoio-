import React, { createContext, useState, useContext, useEffect, useCallback } from 'react';
import api from '../services/api'; // Importa a instância configurada do Axios

// ===================================
// TIPOS E INTERFACES
// ===================================

/**
 * @interface AuthUser
 * @description Representa os dados essenciais do usuário após o login.
 */
export interface AuthUser {
    id: number;
    email: string;
    tipo_usuario: 'candidato' | 'instituicao';
    token: string;
}

/**
 * @interface AuthContextData
 * @description Define a estrutura do Contexto de Autenticação.
 */
interface AuthContextData {
    user: AuthUser | null;
    isAuthenticated: boolean;
    login: (email: string, password: string, options?: { remember: boolean }) => Promise<AuthUser>;
    logout: () => void;
    loading: boolean;
}

// Criação do Contexto
const AuthContext = createContext<AuthContextData>({} as AuthContextData);

// Nome da chave de armazenamento local
const STORAGE_KEY = '@ProApoio:user';

// ===================================
// PROVEDOR DE CONTEXTO
// ===================================

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [user, setUser] = useState<AuthUser | null>(null);
    const [loading, setLoading] = useState(true);

    /**
     * @description Carrega o usuário da sessão anterior (local storage) ao montar.
     */
    useEffect(() => {
        const storedUser = localStorage.getItem(STORAGE_KEY);
        if (storedUser) {
            try {
                const parsedUser: AuthUser = JSON.parse(storedUser);
                setUser(parsedUser);
                // Define o header Authorization para a instância do Axios
                api.defaults.headers.common['Authorization'] = `Bearer ${parsedUser.token}`;
            } catch (e) {
                console.error("Failed to parse stored user data:", e);
                localStorage.removeItem(STORAGE_KEY);
            }
        }
        setLoading(false);
    }, []);

    /**
     * @async
     * @function login
     * @description Realiza o login na API e armazena os dados do usuário.
     */
    const login = useCallback(async (email, password, options = { remember: true }) => {
        // POST /auth/login [cite: Documentação final.docx]
        const response = await api.post('/auth/login', { email, password });
        
        const token = response.data.access_token;
        const userData = response.data.user; 
        
        if (!token || !userData) {
            throw new Error("Resposta de login incompleta.");
        }

        // Monta o objeto de usuário completo
        const loggedUser: AuthUser = {
            id: userData.id,
            email: userData.email,
            tipo_usuario: userData.tipo_usuario as 'candidato' | 'instituicao',
            token: token,
        };

        setUser(loggedUser);
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

        if (options.remember) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(loggedUser));
        }

        return loggedUser;
    }, []);

    /**
     * @function logout
     * @description Limpa a sessão e remove o token.
     */
    const logout = useCallback(async () => {
        try {
            // POST /auth/logout [cite: Documentação final.docx]
            await api.post('/auth/logout'); 
        } catch (e) {
            console.warn('Logout failed on backend, but clearing local session.', e);
        }
        
        setUser(null);
        localStorage.removeItem(STORAGE_KEY);
        delete api.defaults.headers.common['Authorization'];
    }, []);

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
