import React from "react";
import { Link } from "react-router-dom";
import { useAuth } from "../contexts/AuthContext"; // Deve existir e fornecer user, logout
import NotificationBell from "./NotificationBell"; // Componente de notificação
import { Briefcase, User, LogOut, Search, Heart, MessageSquare } from "lucide-react";

/**
 * @component Header
 * @description Header principal da aplicação para usuários logados (Candidato ou Instituição).
 * Refatorado para usar classes semânticas do global.css.
 */
export default function Header() {
  const { user, logout } = useAuth();
  const isLoggedIn = !!user;

  // Define os links de navegação baseado no perfil
  const navLinks = React.useMemo(() => {
    if (!user) return [];

    if (user.tipo_usuario === 'instituicao') {
      return [
        { to: "/candidatos", label: "Buscar Agentes", icon: <Search size={18} /> },
        { to: "/minhas-propostas", label: "Propostas", icon: <MessageSquare size={18} /> },
        { to: "/perfil/instituicao", label: "Meu Perfil", icon: <Briefcase size={18} /> },
      ];
    } else { // Candidato
      return [
        { to: "/vagas", label: "Buscar Vagas", icon: <Search size={18} /> },
        { to: "/vagas-salvas", label: "Salvos", icon: <Heart size={18} /> },
        { to: "/minhas-propostas", label: "Propostas", icon: <MessageSquare size={18} /> },
        { to: "/perfil/candidato", label: "Meu Perfil", icon: <User size={18} /> },
      ];
    }
  }, [user]);

  // Se não estiver logado, exibe um header simplificado para Home/Login
  if (!isLoggedIn) {
      return (
        <header className="header-logged">
             <div className="container header-container justify-between">
                 <Link to="/" className="logo-link">
                    <span aria-hidden className="logo-icon" />
                    <span className="logo-text">ProApoio</span>
                </Link>
                <Link to="/login" className="btn-primary btn-sm">Entrar</Link>
             </div>
        </header>
      );
  }

  return (
    <header className="header-logged" role="banner">
      <div className="container header-container">

        {/* Logo */}
        <Link to="/dashboard" className="logo-link">
            <span aria-hidden className="logo-icon" />
            <span className="logo-text">ProApoio</span>
        </Link>

        {/* Navegação Principal */}
        <nav aria-label="Navegação do usuário" className="main-nav-logged">
            <ul className="flex-group-item gap-md" role="list">
                {navLinks.map(link => (
                    <li key={link.to}>
                        <Link to={link.to} className="nav-link-logged btn-icon">
                            {link.icon}
                            {link.label}
                        </Link>
                    </li>
                ))}
            </ul>
        </nav>

        {/* Ações (Notificação e Logout) */}
        <div className="flex-group-item gap-md">
            {/* Notificação Bell (Componente de notificação) */}
            <NotificationBell /> 
            
            {/* Botão Logout */}
            <button
                onClick={logout}
                className="btn-secondary btn-icon btn-sm"
                aria-label="Sair da conta"
            >
                <LogOut size={18} />
                <span className="hidden-sm">Sair</span>
            </button>
        </div>

      </div>
    </header>
  );
}
