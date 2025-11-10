/**
 * Constantes centralizadas do projeto
 * Evita duplicação de arrays e opções em múltiplos arquivos
 */

// Estados Brasileiros (siglas)
export const ESTADOS_BRASILEIROS = [
    'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
    'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
    'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
] as const;

// Níveis de Escolaridade
export const ESCOLARIDADE_OPTIONS = [
    'Fundamental Completo',
    'Médio Completo',
    'Superior Incompleto',
    'Superior Completo',
    'Pós-graduação'
] as const;

// Opções de Escolaridade com label e value (para selects)
export const ESCOLARIDADE_SELECT_OPTIONS = ESCOLARIDADE_OPTIONS.map(nivel => ({
    label: nivel,
    value: nivel
}));

// Opções de Gênero
export const GENERO_OPTIONS = [
    'Masculino',
    'Feminino',
    'Outro',
    'Não declarar'
] as const;

// Tipos de Vaga
export const TIPO_VAGA_OPTIONS = [
    'Estágio',
    'Voluntariado',
    'Trabalho Fixo',
    'Temporário'
] as const;

// Modalidades de Vaga
export const MODALIDADE_VAGA_OPTIONS = [
    'Presencial',
    'Remoto',
    'Híbrido'
] as const;

// Status de Vaga
export const STATUS_VAGA = {
    ABERTA: 'aberta',
    PAUSADA: 'pausada',
    FECHADA: 'fechada'
} as const;

// Status de Proposta
export const STATUS_PROPOSTA = {
    PENDENTE: 'pendente',
    ACEITA: 'aceita',
    RECUSADA: 'recusada',
    CANCELADA: 'cancelada'
} as const;

// Tipo de Experiência
export const TIPO_EXPERIENCIA = {
    PESSOAL: 'pessoal',
    PROFISSIONAL: 'profissional'
} as const;

// Tempo de Experiência Profissional
export const TEMPO_EXPERIENCIA_OPTIONS = [
    'Menos de 1 ano',
    '1-2 anos',
    '2-3 anos',
    '3-5 anos',
    'Mais de 5 anos'
] as const;

// Tipo de Usuário
export const TIPO_USUARIO = {
    CANDIDATO: 'candidato',
    INSTITUICAO: 'instituicao'
} as const;

// Deficiências (fallback - idealmente vem da API)
export const DEFICIENCIAS_FALLBACK = [
    { id: 1, nome: 'Visual' },
    { id: 2, nome: 'Auditiva' },
    { id: 3, nome: 'Física' },
    { id: 4, nome: 'Intelectual' }
] as const;
