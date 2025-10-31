import { Routes, Route } from 'react-router-dom'
import HomePage from './pages/HomePage'
import LoginPage from './pages/LoginPage'
import RegisterPage from './pages/RegisterPage'
import ForgotPasswordPage from './pages/ForgotPasswordPage'
import ResetPasswordPage from './pages/ResetPasswordPage'
import PerfilCandidatoPage from './pages/PerfilCandidatoPage'
import PerfilInstituicaoPage from './pages/PerfilInstituicaoPage'
import BuscarVagasPage from './pages/BuscarVagasPage'
import VagasSalvasPage from './pages/VagasSalvasPage'
import DetalhesVagaPage from './pages/DetalhesVagaPage'
import BuscarCandidatosPage from './pages/BuscarCandidatosPage'
import PerfilCandidatoPublicPage from './pages/PerfilCandidatoPublicPage'
import MinhasPropostasPage from './pages/MinhasPropostasPage'
import ComoFuncionaPage from './pages/ComoFuncionaPage'
import ParaCandidatosPage from './pages/ParaCandidatosPage'
import ParaInstituicoesPage from './pages/ParaInstituicoesPage'
import RegisterCandidatoPage from './pages/RegisterCandidatoPage'
import RegisterInstituicaoPage from './pages/RegisterInstituicaoPage'

export default function App() {
  return (
    <Routes>
      {/* Públicas */}
      <Route path="/" element={<HomePage />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />
      
      {/* ROTAS DE CADASTRO ESPECÍFICAS */}
      <Route path="/register/candidato" element={<RegisterCandidatoPage />} />
      <Route path="/register/instituicao" element={<RegisterInstituicaoPage />} />

      <Route path="/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/reset-password" element={<ResetPasswordPage />} />

      {/* Navegação informativa  */}
      <Route path="/como-funciona" element={<ComoFuncionaPage />} />
      <Route path="/para-candidatos" element={<ParaCandidatosPage />} />
      <Route path="/para-instituicoes" element={<ParaInstituicoesPage />} />
      
      {/* Rotas de Usuário (Exigem Auth) */}
      <Route path="/perfil/candidato" element={<PerfilCandidatoPage />} />
      <Route path="/vagas-salvas" element={<VagasSalvasPage />} />
      <Route path="/perfil/instituicao" element={<PerfilInstituicaoPage />} />
      <Route path="/candidatos" element={<BuscarCandidatosPage />} />
      <Route path="/candidatos/:id" element={<PerfilCandidatoPublicPage />} />
      <Route path="/vagas" element={<BuscarVagasPage />} />
      <Route path="/vagas/:id" element={<DetalhesVagaPage />} />
      <Route path="/propostas" element={<MinhasPropostasPage />} />
    </Routes>
  )
}