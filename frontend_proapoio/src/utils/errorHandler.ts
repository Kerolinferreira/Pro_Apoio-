/**
 * Utilitários para tratamento padronizado de erros de API
 */

export interface ApiErrorResponse {
  message?: string;
  errors?: Record<string, string[]>;
}

export interface ParsedError {
  generalMessage: string;
  fieldErrors: Record<string, string>;
}

/**
 * Extrai e formata erros de validação da API (Laravel format)
 *
 * @param error - Erro capturado do axios
 * @param fieldMap - Mapeamento de campos do backend para o frontend (opcional)
 * @returns Objeto com mensagem geral e erros por campo
 *
 * @example
 * try {
 *   await api.post('/endpoint', data);
 * } catch (error) {
 *   const { generalMessage, fieldErrors } = parseApiError(error, {
 *     'nome': 'nome_completo',
 *     'senha': 'password'
 *   });
 *   setError(generalMessage);
 *   setFieldErrors(fieldErrors);
 * }
 */
export function parseApiError(
  error: any,
  fieldMap?: Record<string, string>
): ParsedError {
  const result: ParsedError = {
    generalMessage: 'Erro ao processar sua solicitação. Tente novamente.',
    fieldErrors: {},
  };

  // Erro de rede ou sem resposta
  if (!error.response) {
    result.generalMessage = 'Falha na comunicação com o servidor. Verifique sua conexão e tente novamente.';
    return result;
  }

  const response = error.response;
  const data: ApiErrorResponse = response.data || {};

  // Erros de validação (422 Unprocessable Entity)
  if (response.status === 422 && data.errors) {
    const laravelErrors = data.errors;
    const mappedErrors: Record<string, string> = {};

    Object.entries(laravelErrors).forEach(([backendField, messages]) => {
      // Pega a primeira mensagem do array
      const message = (Array.isArray(messages) && messages.length > 0
        ? messages[0]
        : 'Erro de validação.') as string;

      // Aplica mapeamento de campos se fornecido
      const frontendField = fieldMap?.[backendField] || backendField;

      // Se o campo existe no mapeamento ou é um campo conhecido, adiciona aos erros de campo
      mappedErrors[frontendField] = message;
    });

    result.fieldErrors = mappedErrors;

    // Se há erros de campo, mensagem geral orienta a corrigi-los
    if (Object.keys(mappedErrors).length > 0) {
      result.generalMessage = 'Por favor, corrija os erros nos campos destacados.';
    }

    // Se há mensagem geral da API, usa ela como fallback
    if (data.message && Object.keys(mappedErrors).length === 0) {
      result.generalMessage = data.message;
    }

    return result;
  }

  // Erro de autenticação (401)
  if (response.status === 401) {
    result.generalMessage = data.message || 'Sessão expirada. Faça login novamente.';
    return result;
  }

  // Erro de permissão (403)
  if (response.status === 403) {
    result.generalMessage = data.message || 'Você não tem permissão para realizar esta ação.';
    return result;
  }

  // Recurso não encontrado (404)
  if (response.status === 404) {
    result.generalMessage = data.message || 'Recurso não encontrado.';
    return result;
  }

  // Conflito (409) - geralmente duplicação
  if (response.status === 409) {
    result.generalMessage = data.message || 'Este registro já existe no sistema.';
    return result;
  }

  // Erro de servidor (500+)
  if (response.status >= 500) {
    result.generalMessage = 'Erro no servidor. Por favor, tente novamente mais tarde.';
    return result;
  }

  // Qualquer outro erro com mensagem da API
  if (data.message) {
    result.generalMessage = data.message;
    return result;
  }

  // Fallback genérico
  return result;
}

/**
 * Formata os nomes dos campos para exibição amigável
 *
 * @param fieldErrors - Objeto com erros de campo
 * @param fieldLabels - Mapeamento de nomes técnicos para labels amigáveis
 * @returns String formatada com lista de campos com erro
 *
 * @example
 * const errorList = formatFieldErrorList(
 *   { email: 'Email inválido', password: 'Senha muito curta' },
 *   { email: 'E-mail', password: 'Senha' }
 * );
 * // Retorna: "E-mail, Senha"
 */
export function formatFieldErrorList(
  fieldErrors: Record<string, string>,
  fieldLabels?: Record<string, string>
): string {
  return Object.keys(fieldErrors)
    .map(field => {
      // Usa o label amigável se fornecido
      if (fieldLabels && fieldLabels[field]) {
        return fieldLabels[field];
      }

      // Converte snake_case para Title Case
      return field
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
    })
    .join(', ');
}

/**
 * Retorna uma mensagem de erro amigável com lista de campos afetados
 *
 * @param fieldErrors - Objeto com erros de campo
 * @param fieldLabels - Mapeamento de nomes técnicos para labels amigáveis
 * @returns Mensagem formatada
 *
 * @example
 * const message = getFieldErrorMessage(
 *   { email: 'Email inválido', password: 'Senha muito curta' },
 *   { email: 'E-mail', password: 'Senha' }
 * );
 * // Retorna: "Corrija os campos destacados: E-mail, Senha."
 */
export function getFieldErrorMessage(
  fieldErrors: Record<string, string>,
  fieldLabels?: Record<string, string>
): string {
  const fieldList = formatFieldErrorList(fieldErrors, fieldLabels);
  return `Corrija os campos destacados: ${fieldList}.`;
}
