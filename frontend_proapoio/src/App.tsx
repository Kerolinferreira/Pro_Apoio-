import { Routes, Route, Navigate } from 'react-router-dom'
import { useAuth } from './contexts/AuthContext'
import ProtectedRoute from './components/ProtectedRoute'
import HomePage from './pages/HomePage'
import DashboardPage from './pages/DashboardPage'
import LoginPage from './pages/LoginPage'
import RegisterPage from './pages/RegisterPage'
import ForgotPasswordPage from './pages/ForgotPasswordPage'
import ResetPasswordPage from './pages/ResetPasswordPage'
import PerfilCandidatoPage from './pages/PerfilCandidatoPage'
import PerfilInstituicaoPage from './pages/PerfilInstituicaoPage'
import BuscarVagasPage from './pages/BuscarVagasPage'
import VagasSalvasPage from './pages/VagasSalvasPage'
import DetalhesVagaPage from './pages/DetalhesVagaPage'
import CreateVagaPage from './pages/CreateVagaPage'
import EditVagaPage from './pages/EditVagaPage'
import BuscarCandidatosPage from './pages/BuscarCandidatosPage'
import PerfilCandidatoPublicPage from './pages/PerfilCandidatoPublicPage'
import MinhasPropostasPage from './pages/MinhasPropostasPage'
import ComoFuncionaPage from './pages/ComoFuncionaPage'
import ParaCandidatosPage from './pages/ParaCandidatosPage'
import ParaInstituicoesPage from './pages/ParaInstituicoesPage'
import RegisterCandidatoPage from './pages/RegisterCandidatoPage'
import RegisterInstituicaoPage from './pages/RegisterInstituicaoPage'
import MinhasVagasPage from './pages/MinhasVagasPage'
import InstituicaoPublicaPage from './pages/InstituicaoPublicaPage'

/**
 * @component HomeRouter
 * @description Redireciona para Dashboard se autenticado, ou mostra HomePage se não autenticado
 */
function HomeRouter() {
  const { user } = useAuth();

  if (user) {
    return <Navigate to="/dashboard" replace />;
  }

  return <HomePage />;
}

export default function App() {
  return (
    <Routes>
      {/* Rota principal: Landing ou Dashboard baseado em autenticação */}
      <Route path="/" element={<HomeRouter />} />

      {/* Rotas Públicas de Autenticação */}
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />
      <Route path="/register/candidato" element={<RegisterCandidatoPage />} />
      <Route path="/register/instituicao" element={<RegisterInstituicaoPage />} />
      <Route path="/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/reset-password" element={<ResetPasswordPage />} />

      {/* Rotas Públicas Informativas */}
      <Route path="/como-funciona" element={<ComoFuncionaPage />} />
      <Route path="/para-candidatos" element={<ParaCandidatosPage />} />
      <Route path="/para-instituicoes" element={<ParaInstituicoesPage />} />

      {/* Rotas Públicas de Visualização */}
      <Route path="/candidatos/:id" element={<PerfilCandidatoPublicPage />} />
      <Route path="/instituicoes/:id" element={<InstituicaoPublicaPage />} />
      <Route path="/vagas" element={<BuscarVagasPage />} />
      <Route path="/buscar-vagas" element={<Navigate to="/vagas" replace />} />
      <Route path="/vagas/:id" element={<DetalhesVagaPage />} />

      {/* Rotas Protegidas - Dashboard */}
      <Route path="/dashboard" element={<ProtectedRoute><DashboardPage /></ProtectedRoute>} />

      {/* Rotas Protegidas - Perfil do Candidato */}
      <Route path="/perfil/candidato" element={<ProtectedRoute><PerfilCandidatoPage /></ProtectedRoute>} />
      <Route path="/vagas-salvas" element={<ProtectedRoute><VagasSalvasPage /></ProtectedRoute>} />
      <Route path="/minhas-propostas" element={<ProtectedRoute><MinhasPropostasPage /></ProtectedRoute>} />

      {/* Rotas Protegidas - Perfil da Instituição */}
      <Route path="/perfil/instituicao" element={<ProtectedRoute><PerfilInstituicaoPage /></ProtectedRoute>} />
      <Route path="/candidatos" element={<ProtectedRoute><BuscarCandidatosPage /></ProtectedRoute>} />
      <Route path="/vagas/minhas" element={<ProtectedRoute><MinhasVagasPage /></ProtectedRoute>} />
      <Route path="/vagas/criar" element={<ProtectedRoute><CreateVagaPage /></ProtectedRoute>} />
      <Route path="/vagas/:id/editar" element={<ProtectedRoute><EditVagaPage /></ProtectedRoute>} />
    </Routes>
  )
}
