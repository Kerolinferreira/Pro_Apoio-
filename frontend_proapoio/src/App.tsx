import HomePage from './pages/HomePage'
import { AuthProvider } from './contexts/AuthContext'

/**
 * Componente raiz do aplicativo.
 * Envolve a aplicação no AuthProvider para persistência de sessão.
 * O roteamento principal é configurado em `src/main.tsx`.
 */
export default function App() {
  return (
    <AuthProvider>
      <main role="main" className="min-h-screen bg-[var(--bg)] text-[var(--fg)]">
        <HomePage />
      </main>
    </AuthProvider>
  )
}
