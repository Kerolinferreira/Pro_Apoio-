import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { render, screen } from '../tests/utils/test-utils'
import Footer from './Footer'

describe('Footer', () => {
  beforeEach(() => {
    // Set a fixed date for consistent year testing
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2024-01-01'))
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('renders footer element', () => {
    render(<Footer />)

    const footer = screen.getByRole('contentinfo')
    expect(footer).toBeInTheDocument()
  })

  it('displays copyright text with current year', () => {
    render(<Footer />)

    expect(screen.getByText(/© 2024 ProApoio/)).toBeInTheDocument()
    expect(screen.getByText(/Todos os direitos reservados/)).toBeInTheDocument()
  })

  it('renders navigation links', () => {
    render(<Footer />)

    expect(screen.getByText('Termos de Uso')).toBeInTheDocument()
    expect(screen.getByText('Política de Privacidade')).toBeInTheDocument()
  })

  it('navigation has accessible label', () => {
    render(<Footer />)

    expect(screen.getByRole('navigation', { name: /rodapé/i })).toBeInTheDocument()
  })

  it('links are rendered as Link components', () => {
    render(<Footer />)

    const termosLink = screen.getByText('Termos de Uso')
    const privacidadeLink = screen.getByText('Política de Privacidade')

    expect(termosLink.closest('a')).toBeInTheDocument()
    expect(privacidadeLink.closest('a')).toBeInTheDocument()
  })

  it('has footer-primary class', () => {
    const { container } = render(<Footer />)

    const footer = container.querySelector('footer')
    expect(footer).toHaveClass('footer-primary')
  })

  it('uses semantic footer-content-logged class', () => {
    const { container } = render(<Footer />)

    const contentDiv = container.querySelector('.footer-content-logged')
    expect(contentDiv).toBeInTheDocument()
  })

  it('copyright text has correct styling classes', () => {
    render(<Footer />)

    const copyrightText = screen.getByText(/© 2024 ProApoio/)
    expect(copyrightText).toHaveClass('text-sm', 'text-muted')
  })

  it('updates year dynamically', () => {
    // Test with different year
    vi.setSystemTime(new Date('2025-06-15'))

    const { rerender } = render(<Footer />)
    expect(screen.getByText(/© 2025 ProApoio/)).toBeInTheDocument()

    // Change year again
    vi.setSystemTime(new Date('2026-12-31'))
    rerender(<Footer />)
    expect(screen.getByText(/© 2026 ProApoio/)).toBeInTheDocument()
  })
})
