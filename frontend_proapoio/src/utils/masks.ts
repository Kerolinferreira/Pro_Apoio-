/**
 * @file masks.ts
 * @description Utilitários para aplicar máscaras de formatação a strings de entrada
 * (usado principalmente nos formulários de Cadastro).
 */

/**
 * @function maskCEP
 * @description Aplica a máscara de CEP: XXXXX-XXX
 * @param value Valor não formatado.
 * @returns String formatada.
 */
export const maskCEP = (value: string): string => {
    return value
        .replace(/\D/g, '') // Remove tudo que não for dígito
        .slice(0, 8) // Limita a 8 dígitos
        .replace(/(\d{5})(\d{1,3})$/, '$1-$2'); // Aplica formato progressivo
};

/**
 * @function maskCPF
 * @description Aplica a máscara de CPF: XXX.XXX.XXX-XX
 * @param value Valor não formatado.
 * @returns String formatada.
 */
export const maskCPF = (value: string): string => {
    return value
        .replace(/\D/g, '')
        .slice(0, 11)
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
};

/**
 * @function maskCNPJ
 * @description Aplica a máscara de CNPJ: XX.XXX.XXX/XXXX-XX
 * @param value Valor não formatado.
 * @returns String formatada.
 */
export const maskCNPJ = (value: string): string => {
    return value
        .replace(/\D/g, '')
        .slice(0, 14)
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
};

/**
 * @function maskPhone
 * @description Aplica a máscara de Telefone (Fixo ou Celular)
 * (XX) XXXX-XXXX para fixo e (XX) XXXXX-XXXX para celular.
 * @param value Valor não formatado.
 * @returns String formatada.
 */
export const maskPhone = (value: string): string => {
    const digits = value.replace(/\D/g, '').slice(0, 11);

    if (digits.length <= 10) {
        // Fixo: (XX) XXXX-XXXX
        return digits.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1)$2-$3');
    }

    // Celular: (XX) XXXXX-XXXX
    return digits.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1)$2-$3');
};

/**
 * @function maskFixo
 * @description Aplica a máscara de Telefone Fixo: (XX) XXXX-XXXX
 * @param value Valor não formatado.
 * @returns String formatada.
 */
export const maskFixo = (value: string): string => {
    return value
        .replace(/\D/g, '')
        .slice(0, 10)
        .replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
};

/**
 * @function applyMask
 * @description Função auxiliar para uso genérico em onChange.
 * @param value Valor a ser mascarado.
 * @param name Nome do campo para determinar qual máscara aplicar.
 * @returns String formatada.
 */
export const applyMask = (value: string, name: string): string => {
    switch (name) {
        case 'cep':
            return maskCEP(value);
        case 'cpf':
            return maskCPF(value);
        case 'cnpj':
            return maskCNPJ(value);
        case 'telefone':
        case 'celular_corporativo':
            return maskPhone(value);
        case 'telefone_fixo':
            return maskFixo(value);
        default:
            return value;
    }
};
