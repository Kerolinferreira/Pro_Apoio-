import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, fireEvent } from '../tests/utils/test-utils'
import Header from './Header'
import * as AuthContext from '../contexts/AuthContext'

// Mock the NotificationBell component
vi.mock('./NotificationBell', () => ({
  default: () => <div data-testid="notification-bell">Notification Bell</div>
}))

// Mock lucide-react icons
vi.mock('lucide-react', () => ({
  Briefcase: () => <span>Briefcase Icon</span>,
  User: () => <span>User Icon</span>,
  LogOut: () => <span>LogOut Icon</span>,
  Search: () => <span>Search Icon</span>,
  Heart: () => <span>Heart Icon</span>,
  MessageSquare: () => <span>MessageSquare Icon</span>
}))

describe('Header', () => {
  const mockLogout = vi.fn()

  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('when user is not logged in', () => {
    beforeEach(() => {
      vi.spyOn(AuthContext, 'useAuth').mockReturnValue({
        user: null,
        logout: mockLogout,
        login: vi.fn(),
        loading: false
      })
    })

    it('renders simplified header with login button', () => {
      render(<Header />)

      expect(screen.getByText('ProApoio')).toBeInTheDocument()
      expect(screen.getByText('Entrar')).toBeInTheDocument()
    })

    it('login button links to /login', () => {
      render(<Header />)

      const loginLink = screen.getByText('Entrar')
      expect(loginLink.closest('a')).toHaveAttribute('href', '/login')
    })

    it('logo links to home', () => {
      render(<Header />)

      const logo = screen.getByText('ProApoio').closest('a')
      expect(logo).toHaveAttribute('href', '/')
    })
  })

  describe('when candidato user is logged in', () => {
    beforeEach(() => {
      vi.spyOn(AuthContext, 'useAuth').mockReturnValue({
        user: {
          id: 1,
          nome: 'João Candidato',
          email: 'joao@example.com',
          tipo_usuario: 'candidato'
        },
        logout: mockLogout,
        login: vi.fn(),
        loading: false
      })
    })

    it('renders navigation links for candidato', () => {
      render(<Header />)

      expect(screen.getByText('Buscar Vagas')).toBeInTheDocument()
      expect(screen.getByText('Salvos')).toBeInTheDocument()
      expect(screen.getByText('Propostas')).toBeInTheDocument()
      expect(screen.getByText('Meu Perfil')).toBeInTheDocument()
    })

    it('navigation links have correct hrefs for candidato', () => {
      render(<Header />)

      expect(screen.getByText('Buscar Vagas').closest('a')).toHaveAttribute('href', '/buscar-vagas')
      expect(screen.getByText('Salvos').closest('a')).toHaveAttribute('href', '/vagas-salvas')
      expect(screen.getByText('Propostas').closest('a')).toHaveAttribute('href', '/minhas-propostas')
      expect(screen.getByText('Meu Perfil').closest('a')).toHaveAttribute('href', '/perfil/candidato')
    })

    it('renders notification bell', () => {
      render(<Header />)

      expect(screen.getByTestId('notification-bell')).toBeInTheDocument()
    })

    it('renders logout button', () => {
      render(<Header />)

      expect(screen.getByRole('button', { name: /sair/i })).toBeInTheDocument()
    })

    it('calls logout when logout button is clicked', () => {
      render(<Header />)

      const logoutButton = screen.getByRole('button', { name: /sair/i })
      fireEvent.click(logoutButton)

      expect(mockLogout).toHaveBeenCalledTimes(1)
    })
  })

  describe('when instituicao user is logged in', () => {
    beforeEach(() => {
      vi.spyOn(AuthContext, 'useAuth').mockReturnValue({
        user: {
          id: 2,
          nome: 'Escola ABC',
          email: 'escola@example.com',
          tipo_usuario: 'instituicao'
        },
        logout: mockLogout,
        login: vi.fn(),
        loading: false
      })
    })

    it('renders navigation links for instituicao', () => {
      render(<Header />)

      expect(screen.getByText('Buscar Agentes')).toBeInTheDocument()
      expect(screen.getByText('Propostas')).toBeInTheDocument()
      expect(screen.getByText('Meu Perfil')).toBeInTheDocument()
    })

    it('navigation links have correct hrefs for instituicao', () => {
      render(<Header />)

      expect(screen.getByText('Buscar Agentes').closest('a')).toHaveAttribute('href', '/buscar-candidatos')
      expect(screen.getByText('Propostas').closest('a')).toHaveAttribute('href', '/minhas-propostas')
      expect(screen.getByText('Meu Perfil').closest('a')).toHaveAttribute('href', '/perfil/instituicao')
    })

    it('does not render "Salvos" link for instituicao', () => {
      render(<Header />)

      expect(screen.queryByText('Salvos')).not.toBeInTheDocument()
    })
  })

  describe('accessibility', () => {
    beforeEach(() => {
      vi.spyOn(AuthContext, 'useAuth').mockReturnValue({
        user: {
          id: 1,
          nome: 'Test User',
          email: 'test@example.com',
          tipo_usuario: 'candidato'
        },
        logout: mockLogout,
        login: vi.fn(),
        loading: false
      })
    })

    it('has banner role', () => {
      render(<Header />)

      expect(screen.getByRole('banner')).toBeInTheDocument()
    })

    it('navigation has accessible label', () => {
      render(<Header />)

      expect(screen.getByRole('navigation', { name: /navegação do usuário/i })).toBeInTheDocument()
    })

    it('logout button has accessible label', () => {
      render(<Header />)

      const logoutButton = screen.getByRole('button', { name: /sair da conta/i })
      expect(logoutButton).toBeInTheDocument()
    })
  })

  describe('styling', () => {
    it('applies header-logged class', () => {
      vi.spyOn(AuthContext, 'useAuth').mockReturnValue({
        user: null,
        logout: mockLogout,
        login: vi.fn(),
        loading: false
      })

      const { container } = render(<Header />)

      const header = container.querySelector('header')
      expect(header).toHaveClass('header-logged')
    })
  })
})
