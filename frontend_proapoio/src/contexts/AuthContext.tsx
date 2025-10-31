import React, {
  createContext,
  useState,
  useContext,
  useEffect,
  useCallback,
} from 'react';
import { useNavigate } from 'react-router-dom';
import api, { setLogoutCallback } from '../services/api';

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

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  // Carrega usuário salvo (prioriza "lembrar-me" = localStorage)
  useEffect(() => {
    const fromLocal = localStorage.getItem(STORAGE_KEY);
    const fromSession = sessionStorage.getItem(STORAGE_KEY);

    const storedUser = fromLocal || fromSession;

    if (storedUser) {
      try {
        const parsedUser: AuthUser = JSON.parse(storedUser);
        setUser(parsedUser);

        // garante token para interceptors
        if (parsedUser.token) {
          localStorage.setItem('authToken', parsedUser.token);
          sessionStorage.setItem('authToken', parsedUser.token);
        }
      } catch (e) {
        console.error('Failed to parse stored user data:', e);
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
        // sessão longa
        localStorage.setItem(STORAGE_KEY, JSON.stringify(loggedUser));
        localStorage.setItem('authToken', token);
        // mantém em sessão também, se quiser acessar rápido
        sessionStorage.setItem('authToken', token);
      } else {
        // sessão curta
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(loggedUser));
        sessionStorage.setItem('authToken', token);
        // limpa possíveis restos antigos no localStorage
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
    navigate('/login?session=expired');

    // tenta avisar o backend
    api.post('/auth/logout').catch((e) => {
      console.warn(
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
