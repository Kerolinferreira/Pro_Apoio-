import React from "react";
import { Link } from "react-router-dom";

/**
 * @component Footer
 * @description Rodapé padrão para páginas da área logada.
 * Refatorado para usar classes semânticas do global.css.
 */
export default function Footer() {
  const year = new Date().getFullYear();

  return (
    <footer className="footer-primary" role="contentinfo">
      <div className="container footer-content-logged">
        
        <p className="text-sm text-muted">
            © {year} ProApoio. Todos os direitos reservados.
        </p>
        
        <nav aria-label="Rodapé" className="footer-nav">
          <Link to="#" className="nav-link text-sm">Termos de Uso</Link>
          <Link to="#" className="nav-link text-sm">Política de Privacidade</Link>
        </nav>
      </div>
    </footer>
  );
}