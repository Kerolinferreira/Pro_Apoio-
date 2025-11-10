import React, {
  createContext,
  useState,
  useContext,
  useEffect,
  useCallback,
} from 'react';
import { useNavigate } from 'react-router-dom';
import api, { setLogoutCallback } from '../services/api';
import { logger } from '../utils/logger';

export interface AuthUser {
  id: number;
  email: string;
  tipo_usuario: 'candidato' | 'instituicao';
  token: string;
}

interface AuthContextData {
  user: AuthUser | null;
  isAuthenticated: boolean;
  login: (
    email: string,
    password: string,
    options?: { remember: boolean }
  ) => Promise<AuthUser>;
  logout: () => void;
  loading: boolean;
}

const AuthContext = createContext<AuthContextData>({} as AuthContextData);
const STORAGE_KEY = '@ProApoio:user';

/**
 * SECURITY NOTE - JWT Storage Risk (H3):
 * ========================================
 *
 * CURRENT IMPLEMENTATION:
 * - JWT tokens are stored in localStorage/sessionStorage
 * - This approach is vulnerable to XSS (Cross-Site Scripting) attacks
 * - If malicious JavaScript is injected, tokens can be stolen
 *
 * MITIGATIONS IN PLACE:
 * - React's automatic XSS protection (JSX escaping)
 * - Content Security Policy (should be configured in production)
 * - Token expiration (JWT has limited lifetime)
 *
 * RECOMMENDED FUTURE IMPROVEMENTS:
 * 1. Migrate to httpOnly cookies for token storage
 *    - Backend: Set tokens as httpOnly, secure, sameSite cookies
 *    - Frontend: Remove localStorage/sessionStorage token handling
 *    - Benefit: JavaScript cannot access tokens, immune to XSS
 *
 * 2. Implement refresh token rotation
 *    - Short-lived access tokens (15-30 min)
 *    - Long-lived refresh tokens stored in httpOnly cookies
 *    - Automatic token refresh before expiration
 *
 * 3. Add Content Security Policy headers
 *    - Block inline scripts
 *    - Whitelist trusted script sources
 *    - Prevent XSS injection vectors
 *
 * TRADE-OFFS:
 * - Current approach: Simple, works with CORS, mobile-friendly
 * - httpOnly cookies: More secure, but requires backend changes and
 *   same-origin or careful CORS configuration
 *
 * Until migration to httpOnly cookies, ensure:
 * - All user inputs are properly sanitized
 * - dangerouslySetInnerHTML is never used
 * - External scripts are from trusted sources only
 */

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  // Carrega usuário salvo (prioriza "lembrar-me" = localStorage)
  useEffect(() => {
    const fromLocal = localStorage.getItem(STORAGE_KEY);
    const fromSession = sessionStorage.getItem(STORAGE_KEY);

    const storedUser = fromLocal || fromSession;
    const isFromLocal = !!fromLocal; // Determina de onde veio o user

    if (storedUser) {
      try {
        const parsedUser: AuthUser = JSON.parse(storedUser);
        setUser(parsedUser);

        // Garante token para interceptors no storage correto
        if (parsedUser.token) {
          if (isFromLocal) {
            // Se veio do localStorage, mantém apenas lá (sessão persistente)
            localStorage.setItem('authToken', parsedUser.token);
          } else {
            // Se veio do sessionStorage, mantém apenas lá (sessão temporária)
            sessionStorage.setItem('authToken', parsedUser.token);
          }
        }
      } catch (e) {
        logger.error('Failed to parse stored user data:', e);
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem('authToken');
        sessionStorage.removeItem(STORAGE_KEY);
        sessionStorage.removeItem('authToken');
      }
    }

    setLoading(false);
  }, []);

  const login = useCallback(
    async (email: string, password: string, options = { remember: true }) => {
      const response = await api.post('/auth/login', { email, password });

      const token = response.data.token;
      const userData = response.data.user;

      if (!token || !userData) {
        throw new Error('Resposta de login incompleta.');
      }

      const loggedUser: AuthUser = {
        id: userData.id,
        email: userData.email,
        tipo_usuario: userData.tipo_usuario as 'candidato' | 'instituicao',
        token: token,
      };

      setUser(loggedUser);

      if (options.remember) {
        // sessão longa (persistente) - salva APENAS no localStorage
        localStorage.setItem(STORAGE_KEY, JSON.stringify(loggedUser));
        localStorage.setItem('authToken', token);
        // limpa possíveis restos do sessionStorage
        sessionStorage.removeItem(STORAGE_KEY);
        sessionStorage.removeItem('authToken');
      } else {
        // sessão curta (temporária) - salva APENAS no sessionStorage
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(loggedUser));
        sessionStorage.setItem('authToken', token);
        // limpa possíveis restos do localStorage
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem('authToken');
      }

      return loggedUser;
    },
    []
  );

  const logout = useCallback(() => {
    // limpa estado
    setUser(null);

    // limpa tudo dos dois lugares
    localStorage.removeItem(STORAGE_KEY);
    localStorage.removeItem('authToken');
    sessionStorage.removeItem(STORAGE_KEY);
    sessionStorage.removeItem('authToken');

    // redireciona
    navigate('/login');

    // tenta avisar o backend
    api.post('/auth/logout').catch((e) => {
      logger.warn(
        'Backend logout call failed, but local session is cleared.',
        e
      );
    });
  }, [navigate]);

  // registra logout global para o interceptor
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

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export default AuthProvider;
