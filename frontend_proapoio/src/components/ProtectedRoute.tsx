import { Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

interface ProtectedRouteProps {
  children: React.ReactElement;
}

/**
 * Componente que protege rotas exigindo autenticação.
 *
 * Se o usuário não estiver autenticado, redireciona para /login.
 * Se estiver autenticado, renderiza o componente filho.
 *
 * @example
 * <Route path="/perfil" element={<ProtectedRoute><PerfilPage /></ProtectedRoute>} />
 */
export default function ProtectedRoute({ children }: ProtectedRouteProps) {
  const { user, loading } = useAuth();

  // Enquanto verifica autenticação, não redireciona
  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  // Se não autenticado, redireciona para login
  if (!user) {
    return <Navigate to="/login" replace />;
  }

  // Se autenticado, renderiza o componente
  return children;
}
