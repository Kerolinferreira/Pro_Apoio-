import { describe, it, expect } from 'vitest'
import { render, screen } from '../tests/utils/test-utils'
import VagaCard from './VagaCard'

describe('VagaCard', () => {
  const defaultProps = {
    id: 1,
    title: 'Auxiliar de Sala',
    institution: 'Escola Estadual ABC',
    city: 'São Paulo',
    regime: 'CLT' as const,
    linkTo: '/vagas/1'
  }

  it('renders vaga information correctly', () => {
    render(<VagaCard {...defaultProps} />)

    expect(screen.getByText('Auxiliar de Sala')).toBeInTheDocument()
    expect(screen.getByText('Escola Estadual ABC')).toBeInTheDocument()
    expect(screen.getByText(/São Paulo/)).toBeInTheDocument()
    expect(screen.getByText('CLT')).toBeInTheDocument()
  })

  it('renders as a link to vaga details', () => {
    render(<VagaCard {...defaultProps} />)

    const link = screen.getByRole('link')
    expect(link).toHaveAttribute('href', '/vagas/1')
  })

  it('has accessible aria-label', () => {
    render(<VagaCard {...defaultProps} />)

    const link = screen.getByRole('link')
    expect(link).toHaveAttribute(
      'aria-label',
      'Ver detalhes da vaga Auxiliar de Sala na instituição Escola Estadual ABC'
    )
  })

  it('applies correct badge class for CLT regime', () => {
    const { container } = render(<VagaCard {...defaultProps} regime="CLT" />)

    const badge = container.querySelector('.badge-green')
    expect(badge).toBeInTheDocument()
    expect(badge).toHaveTextContent('CLT')
  })

  it('applies correct badge class for ESTAGIO regime', () => {
    const { container } = render(<VagaCard {...defaultProps} regime="ESTAGIO" />)

    const badge = container.querySelector('.badge-green')
    expect(badge).toBeInTheDocument()
  })

  it('applies correct badge class for MEI regime', () => {
    const { container } = render(<VagaCard {...defaultProps} regime="MEI" />)

    const badge = container.querySelector('.badge-yellow')
    expect(badge).toBeInTheDocument()
  })

  it('applies correct badge class for TEMPORARIO regime', () => {
    const { container } = render(<VagaCard {...defaultProps} regime="TEMPORARIO" />)

    const badge = container.querySelector('.badge-yellow')
    expect(badge).toBeInTheDocument()
  })

  it('applies gray badge for unknown regime', () => {
    const { container } = render(<VagaCard {...defaultProps} regime="UNKNOWN" />)

    const badge = container.querySelector('.badge-gray')
    expect(badge).toBeInTheDocument()
  })

  it('renders with card-simple class', () => {
    const { container } = render(<VagaCard {...defaultProps} />)

    const article = container.querySelector('article')
    expect(article).toHaveClass('card-simple')
  })

  it('displays city in location field', () => {
    render(<VagaCard {...defaultProps} city="Rio de Janeiro" />)

    expect(screen.getByText(/Rio de Janeiro/)).toBeInTheDocument()
  })

  it('handles special characters in title', () => {
    render(<VagaCard {...defaultProps} title="Auxiliar & Apoio - Educação Especial" />)

    expect(screen.getByText('Auxiliar & Apoio - Educação Especial')).toBeInTheDocument()
  })
})
