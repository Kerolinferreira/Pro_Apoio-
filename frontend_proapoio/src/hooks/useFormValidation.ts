import { useState, useCallback } from 'react';
import { z } from 'zod';

/**
 * Hook customizado para gerenciar validação de formulários com Zod
 *
 * @template T - Tipo dos dados do formulário
 * @param schema - Schema Zod para validação
 * @returns Objeto com estado de validação e funções auxiliares
 *
 * @example
 * const { errors, validate, validateField, clearErrors } = useFormValidation(loginSchema);
 *
 * // Validar campo único ao sair do input
 * <input onBlur={(e) => validateField('email', e.target.value)} />
 *
 * // Validar formulário completo antes de enviar
 * const handleSubmit = async (e) => {
 *   e.preventDefault();
 *   if (!validate(formData)) {
 *     return; // Erros serão exibidos automaticamente
 *   }
 *   // Prosseguir com envio
 * };
 */
export function useFormValidation<T>(schema: z.ZodType<T>) {
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [touched, setTouched] = useState<Record<string, boolean>>({});

  /**
   * Valida o formulário completo
   * @param data - Dados do formulário a serem validados
   * @returns true se válido, false se inválido
   */
  const validate = useCallback(
    (data: unknown): data is T => {
      const result = schema.safeParse(data);

      if (result.success) {
        setErrors({});
        return true;
      }

      const newErrors: Record<string, string> = {};
      result.error.errors.forEach((err) => {
        const path = err.path.join('.');
        if (!newErrors[path]) {
          newErrors[path] = err.message;
        }
      });

      setErrors(newErrors);
      return false;
    },
    [schema]
  );

  /**
   * Valida um campo individual
   * @param field - Nome do campo
   * @param value - Valor do campo
   * @returns Mensagem de erro ou null se válido
   */
  const validateField = useCallback(
    (field: string, value: unknown): string | null => {
      // Marca o campo como tocado
      setTouched((prev) => ({ ...prev, [field]: true }));

      try {
        // Tenta validar o campo específico usando o schema parcial
        const fieldSchema = (schema as any).shape?.[field];

        if (!fieldSchema) {
          // Se não conseguir acessar o schema do campo, valida o objeto completo
          // mas só exibe erro para o campo específico
          const result = schema.safeParse({ [field]: value });

          if (result.success) {
            setErrors((prev) => {
              const newErrors = { ...prev };
              delete newErrors[field];
              return newErrors;
            });
            return null;
          }

          const fieldError = result.error.errors.find(
            (err) => err.path.join('.') === field
          );

          if (fieldError) {
            const errorMessage = fieldError.message;
            setErrors((prev) => ({ ...prev, [field]: errorMessage }));
            return errorMessage;
          }

          // Limpa erro se não encontrou erro específico
          setErrors((prev) => {
            const newErrors = { ...prev };
            delete newErrors[field];
            return newErrors;
          });
          return null;
        }

        // Valida usando o schema do campo específico
        const result = fieldSchema.safeParse(value);

        if (result.success) {
          setErrors((prev) => {
            const newErrors = { ...prev };
            delete newErrors[field];
            return newErrors;
          });
          return null;
        }

        const errorMessage = result.error.errors[0]?.message || 'Valor inválido';
        setErrors((prev) => ({ ...prev, [field]: errorMessage }));
        return errorMessage;
      } catch (error) {
        // Em caso de erro ao validar, não exibe erro
        return null;
      }
    },
    [schema]
  );

  /**
   * Limpa todos os erros
   */
  const clearErrors = useCallback(() => {
    setErrors({});
    setTouched({});
  }, []);

  /**
   * Limpa o erro de um campo específico
   */
  const clearFieldError = useCallback((field: string) => {
    setErrors((prev) => {
      const newErrors = { ...prev };
      delete newErrors[field];
      return newErrors;
    });
  }, []);

  /**
   * Marca um campo como tocado
   */
  const touchField = useCallback((field: string) => {
    setTouched((prev) => ({ ...prev, [field]: true }));
  }, []);

  /**
   * Verifica se um campo foi tocado
   */
  const isFieldTouched = useCallback(
    (field: string) => {
      return touched[field] || false;
    },
    [touched]
  );

  /**
   * Obtém o erro de um campo (apenas se foi tocado)
   */
  const getFieldError = useCallback(
    (field: string) => {
      return touched[field] ? errors[field] : undefined;
    },
    [errors, touched]
  );

  /**
   * Verifica se o formulário tem erros
   */
  const hasErrors = Object.keys(errors).length > 0;

  return {
    errors,
    touched,
    hasErrors,
    validate,
    validateField,
    clearErrors,
    clearFieldError,
    touchField,
    isFieldTouched,
    getFieldError,
  };
}
