import { describe, it, expect } from 'vitest'
import { render, screen } from '../tests/utils/test-utils'
import CandidatoCard, { CandidatoPublico } from './CandidatoCard'

describe('CandidatoCard', () => {
  const mockCandidato: CandidatoPublico = {
    id: 1,
    nome_completo: 'João da Silva',
    escolaridade: 'Ensino Superior Completo',
    cidade: 'São Paulo',
    estado: 'SP',
    deficiencias: [
      { nome: 'Deficiência Visual' },
      { nome: 'Deficiência Auditiva' }
    ]
  }

  it('renders candidato information correctly', () => {
    render(<CandidatoCard candidato={mockCandidato} />)

    expect(screen.getByText('João da Silva')).toBeInTheDocument()
    expect(screen.getByText('Ensino Superior Completo')).toBeInTheDocument()
    expect(screen.getByText('São Paulo - SP')).toBeInTheDocument()
    expect(screen.getByText(/Deficiência Visual, Deficiência Auditiva/)).toBeInTheDocument()
  })

  it('renders with default values when fields are missing', () => {
    const minimalCandidato: CandidatoPublico = {
      nome_completo: 'Maria Santos'
    }

    render(<CandidatoCard candidato={minimalCandidato} />)

    expect(screen.getByText('Maria Santos')).toBeInTheDocument()
    expect(screen.getByText('Não informado')).toBeInTheDocument() // escolaridade
    expect(screen.getByText('Cidade não informada - UF')).toBeInTheDocument()
    expect(screen.getByText(/Deficiências: Não informado/)).toBeInTheDocument()
  })

  it('handles empty deficiencias array', () => {
    const candidatoSemDef: CandidatoPublico = {
      nome_completo: 'Pedro Oliveira',
      escolaridade: 'Ensino Médio',
      cidade: 'Rio de Janeiro',
      estado: 'RJ',
      deficiencias: []
    }

    render(<CandidatoCard candidato={candidatoSemDef} />)

    expect(screen.getByText(/Deficiências: Não informado/)).toBeInTheDocument()
  })

  it('filters out null/undefined deficiencias names', () => {
    const candidatoComDefInvalidas: CandidatoPublico = {
      nome_completo: 'Ana Costa',
      deficiencias: [
        { nome: 'Deficiência Física' },
        { nome: null as any },
        { nome: undefined as any },
        { nome: 'Deficiência Intelectual' }
      ]
    }

    render(<CandidatoCard candidato={candidatoComDefInvalidas} />)

    expect(screen.getByText(/Deficiência Física, Deficiência Intelectual/)).toBeInTheDocument()
  })

  it('has accessible aria-label', () => {
    render(<CandidatoCard candidato={mockCandidato} />)

    const card = screen.getByTestId('candidato-card')
    expect(card).toHaveAttribute('aria-label', 'Candidato João da Silva')
  })

  it('uses card-simple class', () => {
    render(<CandidatoCard candidato={mockCandidato} />)

    const card = screen.getByTestId('candidato-card')
    expect(card).toHaveClass('card-simple')
  })

  it('renders as article element', () => {
    const { container } = render(<CandidatoCard candidato={mockCandidato} />)

    const article = container.querySelector('article')
    expect(article).toBeInTheDocument()
  })

  it('displays name with title-md class', () => {
    const { container } = render(<CandidatoCard candidato={mockCandidato} />)

    const nameElement = screen.getByText('João da Silva')
    expect(nameElement.tagName).toBe('H3')
    expect(nameElement).toHaveClass('title-md')
  })

  it('memoizes component correctly', () => {
    const { rerender } = render(<CandidatoCard candidato={mockCandidato} />)

    // Re-render with same props should use memoized version
    rerender(<CandidatoCard candidato={mockCandidato} />)

    expect(screen.getByText('João da Silva')).toBeInTheDocument()
  })
})
