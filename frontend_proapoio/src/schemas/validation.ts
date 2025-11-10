import { z } from 'zod';

/**
 * Schemas de Validação Frontend usando Zod
 *
 * Estes schemas fornecem validação client-side para melhorar UX
 * e reduzir requisições desnecessárias ao backend.
 *
 * A validação backend permanece como camada de segurança final.
 */

// ============================================
// Validadores Customizados
// ============================================

/**
 * Valida formato de CPF (apenas formato, não dígitos verificadores)
 * Aceita: 000.000.000-00 ou 00000000000
 */
const cpfRegex = /^(\d{3}\.?\d{3}\.?\d{3}-?\d{2})$/;

/**
 * Valida formato de CNPJ (apenas formato, não dígitos verificadores)
 * Aceita: 00.000.000/0000-00 ou 00000000000000
 */
const cnpjRegex = /^(\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2})$/;

/**
 * Valida formato de CEP
 * Aceita: 00000-000 ou 00000000
 */
const cepRegex = /^\d{5}-?\d{3}$/;

/**
 * Valida telefone com DDD
 * Aceita: (00) 0000-0000, (00) 00000-0000, ou sem formatação
 */
const telefoneRegex = /^\(?(\d{2})\)?\s?9?\d{4}-?\d{4}$/;

/**
 * Valida senha forte: mínimo 8 caracteres, pelo menos uma letra e um número
 */
const senhaForteRegex = /^(?=.*[A-Za-z])(?=.*\d).{8,}$/;

// ============================================
// Schema de Login
// ============================================

export const loginSchema = z.object({
  email: z
    .string()
    .min(1, 'E-mail é obrigatório')
    .email('E-mail inválido')
    .toLowerCase()
    .trim(),
  password: z
    .string()
    .min(1, 'Senha é obrigatória')
    .min(8, 'A senha deve ter no mínimo 8 caracteres'),
  remember: z.boolean().optional(),
});

export type LoginFormData = z.infer<typeof loginSchema>;

// ============================================
// Schema de Recuperação de Senha
// ============================================

export const forgotPasswordSchema = z.object({
  email: z
    .string()
    .min(1, 'E-mail é obrigatório')
    .email('E-mail inválido')
    .toLowerCase()
    .trim(),
});

export type ForgotPasswordFormData = z.infer<typeof forgotPasswordSchema>;

export const resetPasswordSchema = z.object({
  password: z
    .string()
    .min(8, 'A senha deve ter no mínimo 8 caracteres')
    .regex(senhaForteRegex, 'A senha deve conter pelo menos uma letra e um número'),
  password_confirmation: z
    .string()
    .min(1, 'Confirmação de senha é obrigatória'),
}).refine((data) => data.password === data.password_confirmation, {
  message: 'As senhas não coincidem',
  path: ['password_confirmation'],
});

export type ResetPasswordFormData = z.infer<typeof resetPasswordSchema>;

// ============================================
// Schema de Registro de Candidato
// ============================================

export const registerCandidatoSchema = z.object({
  // Dados pessoais
  nome: z
    .string()
    .min(1, 'Nome é obrigatório')
    .min(3, 'Nome deve ter no mínimo 3 caracteres')
    .max(255, 'Nome muito longo'),
  email: z
    .string()
    .min(1, 'E-mail é obrigatório')
    .email('E-mail inválido')
    .toLowerCase()
    .trim(),
  cpf: z
    .string()
    .min(1, 'CPF é obrigatório')
    .regex(cpfRegex, 'CPF inválido. Use o formato: 000.000.000-00'),
  telefone: z
    .string()
    .min(1, 'Telefone é obrigatório')
    .regex(telefoneRegex, 'Telefone inválido. Use o formato: (00) 00000-0000'),

  // Senha
  password: z
    .string()
    .min(8, 'A senha deve ter no mínimo 8 caracteres')
    .regex(senhaForteRegex, 'A senha deve conter pelo menos uma letra e um número'),
  password_confirmation: z
    .string()
    .min(1, 'Confirmação de senha é obrigatória'),

  // Endereço
  cep: z
    .string()
    .min(1, 'CEP é obrigatório')
    .regex(cepRegex, 'CEP inválido. Use o formato: 00000-000'),
  logradouro: z.string().optional(),
  bairro: z.string().optional(),
  cidade: z.string().optional(),
  estado: z.string().length(2, 'Estado deve ter 2 caracteres').optional(),
  numero: z.string().optional(),
  complemento: z.string().optional(),
  ponto_referencia: z.string().optional(),

  // Perfil acadêmico
  nivel_escolaridade: z
    .string()
    .min(1, 'Nível de escolaridade é obrigatório'),
  curso_superior: z.string().optional(),
  instituicao_ensino: z.string().optional(),

  // Experiência
  experiencia: z
    .string()
    .min(1, 'Experiência é obrigatória')
    .min(20, 'Descreva sua experiência com pelo menos 20 caracteres'),

  // Outros
  link_perfil: z
    .string()
    .url('URL inválida')
    .optional()
    .or(z.literal('')),
  termos_aceite: z
    .boolean()
    .refine((val) => val === true, 'Você deve aceitar os termos de uso'),
}).refine((data) => data.password === data.password_confirmation, {
  message: 'As senhas não coincidem',
  path: ['password_confirmation'],
});

export type RegisterCandidatoFormData = z.infer<typeof registerCandidatoSchema>;

// ============================================
// Schema de Registro de Instituição
// ============================================

export const registerInstituicaoSchema = z.object({
  // Dados da instituição
  nome: z
    .string()
    .min(1, 'Nome é obrigatório')
    .min(3, 'Nome deve ter no mínimo 3 caracteres')
    .max(255, 'Nome muito longo'),
  email: z
    .string()
    .min(1, 'E-mail é obrigatório')
    .email('E-mail inválido')
    .toLowerCase()
    .trim(),
  cnpj: z
    .string()
    .min(1, 'CNPJ é obrigatório')
    .regex(cnpjRegex, 'CNPJ inválido. Use o formato: 00.000.000/0000-00'),
  razao_social: z
    .string()
    .min(1, 'Razão social é obrigatória')
    .max(255, 'Razão social muito longa'),
  nome_fantasia: z
    .string()
    .min(1, 'Nome fantasia é obrigatório')
    .max(255, 'Nome fantasia muito longo'),
  codigo_inep: z
    .string()
    .regex(/^\d{8}$/, 'Código INEP deve ter 8 dígitos')
    .optional()
    .or(z.literal('')),

  // Senha
  password: z
    .string()
    .min(8, 'A senha deve ter no mínimo 8 caracteres')
    .regex(senhaForteRegex, 'A senha deve conter pelo menos uma letra e um número'),
  password_confirmation: z
    .string()
    .min(1, 'Confirmação de senha é obrigatória'),

  // Endereço
  cep: z
    .string()
    .min(1, 'CEP é obrigatório')
    .regex(cepRegex, 'CEP inválido. Use o formato: 00000-000'),
  logradouro: z
    .string()
    .min(1, 'Logradouro é obrigatório')
    .max(255, 'Logradouro muito longo'),
  bairro: z
    .string()
    .min(1, 'Bairro é obrigatório')
    .max(255, 'Bairro muito longo'),
  cidade: z
    .string()
    .min(1, 'Cidade é obrigatória')
    .max(255, 'Cidade muito longa'),
  estado: z
    .string()
    .length(2, 'Estado deve ter 2 caracteres'),
  numero: z
    .string()
    .min(1, 'Número é obrigatório')
    .max(10, 'Número muito longo'),
  complemento: z.string().max(255, 'Complemento muito longo').optional(),
  ponto_referencia: z.string().max(255, 'Ponto de referência muito longo').optional(),

  // Contatos
  telefone_fixo: z
    .string()
    .regex(telefoneRegex, 'Telefone inválido')
    .optional()
    .or(z.literal('')),
  celular_corporativo: z
    .string()
    .regex(telefoneRegex, 'Celular inválido')
    .optional()
    .or(z.literal('')),
  email_corporativo: z
    .string()
    .email('E-mail corporativo inválido')
    .optional()
    .or(z.literal('')),

  // Metadados
  tipo_instituicao: z
    .string()
    .min(1, 'Tipo de instituição é obrigatório')
    .max(50, 'Tipo muito longo'),
  niveis_oferecidos: z
    .string()
    .min(1, 'Níveis oferecidos é obrigatório'),
  nome_responsavel: z
    .string()
    .min(1, 'Nome do responsável é obrigatório')
    .max(255, 'Nome muito longo'),
  funcao_responsavel: z
    .string()
    .min(1, 'Função do responsável é obrigatória')
    .max(255, 'Função muito longa'),

  // Termos
  termos_aceite: z
    .boolean()
    .refine((val) => val === true, 'Você deve aceitar os termos de uso'),
}).refine((data) => data.password === data.password_confirmation, {
  message: 'As senhas não coincidem',
  path: ['password_confirmation'],
});

export type RegisterInstituicaoFormData = z.infer<typeof registerInstituicaoSchema>;

// ============================================
// Utilitários de Validação
// ============================================

/**
 * Valida um campo individual e retorna mensagem de erro
 */
export function validateField<T extends z.ZodType>(
  schema: T,
  value: unknown
): string | null {
  const result = schema.safeParse(value);
  if (result.success) {
    return null;
  }
  return result.error.errors[0]?.message || 'Valor inválido';
}

/**
 * Valida formulário completo e retorna objeto com erros por campo
 */
export function validateForm<T>(
  schema: z.ZodType<T>,
  data: unknown
): { success: true; data: T } | { success: false; errors: Record<string, string> } {
  const result = schema.safeParse(data);

  if (result.success) {
    return { success: true, data: result.data };
  }

  const errors: Record<string, string> = {};
  result.error.errors.forEach((err) => {
    const path = err.path.join('.');
    if (!errors[path]) {
      errors[path] = err.message;
    }
  });

  return { success: false, errors };
}
