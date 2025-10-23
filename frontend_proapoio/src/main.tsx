import React from 'react'
import ReactDOM from 'react-dom/client'
import { BrowserRouter, Routes, Route } from 'react-router-dom'
import App from './App'
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
import { AuthProvider } from './contexts/AuthContext'
import './global.css'

ReactDOM.createRoot(document.getElementById('root') as HTMLElement).render(
  <React.StrictMode>
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/" element={<App />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route path="/forgot-password" element={<ForgotPasswordPage />} />
          <Route path="/reset-password" element={<ResetPasswordPage />} />

          {/* Candidato */}
          <Route path="/perfil/candidato" element={<PerfilCandidatoPage />} />
          <Route path="/vagas-salvas" element={<VagasSalvasPage />} />

          {/* Instituição */}
          <Route path="/perfil/instituicao" element={<PerfilInstituicaoPage />} />
          <Route path="/candidatos" element={<BuscarCandidatosPage />} />
          <Route path="/candidatos/:id" element={<PerfilCandidatoPublicPage />} />

          {/* Vagas e propostas */}
          <Route path="/vagas" element={<BuscarVagasPage />} />
          <Route path="/vagas/:id" element={<DetalhesVagaPage />} />
          <Route path="/propostas" element={<MinhasPropostasPage />} />

          {/* Fallback */}
          <Route path="*" element={<h2 className="p-4">Página não encontrada</h2>} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  </React.StrictMode>
)
