// src/App.tsx
import { BrowserRouter, Routes, Route } from 'react-router-dom'

// Páginas já existentes no seu projeto
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

// Stubs temporários (troque depois por páginas reais em ./pages)
function ComoFuncionaPage() {
  return (
    <main className="p-4 max-w-5xl mx-auto">
      <h1 className="text-2xl font-bold">Como Funciona</h1>
      <p className="mt-2">Página temporária. Substitua por ./pages/ComoFuncionaPage.tsx</p>
    </main>
  )
}
function ParaCandidatosPage() {
  return (
    <main className="p-4 max-w-5xl mx-auto">
      <h1 className="text-2xl font-bold">Para Candidatos</h1>
      <p className="mt-2">Página temporária. Substitua por ./pages/ParaCandidatosPage.tsx</p>
    </main>
  )
}
function ParaInstituicoesPage() {
  return (
    <main className="p-4 max-w-5xl mx-auto">
      <h1 className="text-2xl font-bold">Para Instituições</h1>
      <p className="mt-2">Página temporária. Substitua por ./pages/ParaInstituicoesPage.tsx</p>
    </main>
  )
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        {/* Públicas */}
        <Route path="/" element={<HomePage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/forgot-password" element={<ForgotPasswordPage />} />
        <Route path="/reset-password" element={<ResetPasswordPage />} />

        {/* Navegação informativa novas */}
        <Route path="/como-funciona" element={<ComoFuncionaPage />} />
        <Route path="/para-candidatos" element={<ParaCandidatosPage />} />
        <Route path="/para-instituicoes" element={<ParaInstituicoesPage />} />

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

        {/* Sem catch-all redirecionando para "/" */}
      </Routes>
    </BrowserRouter>
  )
}
