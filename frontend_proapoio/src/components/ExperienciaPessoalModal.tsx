import React, { useState, useEffect } from 'react';
import { X } from 'lucide-react';
import api from '../services/api';
import { useToast } from './Toast';
import { parseApiError, getFieldErrorMessage } from '../utils/errorHandler';

/**
 * Modal para adicionar ou editar experiência pessoal do candidato.
 * Captura descrição da experiência e interesse em atuar em vagas similares.
 */

interface ExperienciaPessoalFormData {
    interesse_atuar: boolean;
    descricao: string;
}

interface ExperienciaPessoalModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: () => void; // Callback após sucesso (para recarregar lista)
}

const ExperienciaPessoalModal: React.FC<ExperienciaPessoalModalProps> = ({
    isOpen,
    onClose,
    onSuccess,
}) => {
    const toast = useToast();

    // Estado do formulário
    const [formData, setFormData] = useState<ExperienciaPessoalFormData>({
        interesse_atuar: false,
        descricao: '',
    });

    const [isLoading, setIsLoading] = useState(false);
    const [errors, setErrors] = useState<{ [key: string]: string }>({});

    // Limpar formulário ao fechar
    useEffect(() => {
        if (!isOpen) {
            setFormData({
                interesse_atuar: false,
                descricao: '',
            });
            setErrors({});
        }
    }, [isOpen]);

    /**
     * Valida o formulário antes do envio
     */
    const validateForm = (): boolean => {
        const newErrors: { [key: string]: string } = {};

        // Validação da descrição (obrigatória)
        if (!formData.descricao.trim()) {
            newErrors.descricao = 'Por favor, descreva sua experiência pessoal.';
        } else if (formData.descricao.length > 1000) {
            newErrors.descricao = 'A descrição não pode ter mais de 1000 caracteres.';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    /**
     * Submete a requisição de criação de experiência pessoal
     */
    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        setIsLoading(true);
        setErrors({});

        try {
            const payload = {
                interesse_atuar: formData.interesse_atuar,
                descricao: formData.descricao.trim(),
            };

            await api.post('/candidatos/me/experiencias-pessoais', payload);

            toast.success('Experiência pessoal adicionada com sucesso!');
            onClose();
            onSuccess(); // Recarregar lista
        } catch (error: any) {
            const { generalMessage, fieldErrors } = parseApiError(error);

            if (error.response?.status === 422) {
                // Erros de validação
                setErrors(fieldErrors);
                toast.error(generalMessage);
            } else {
                // Erro genérico
                toast.error(generalMessage);
            }
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * Atualiza um campo do formulário
     */
    const handleChange = (field: keyof ExperienciaPessoalFormData, value: any) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
        // Limpa o erro do campo ao editar
        if (errors[field]) {
            setErrors((prev) => ({ ...prev, [field]: '' }));
        }
    };

    if (!isOpen) return null;

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div
                className="modal-content modal-md"
                onClick={(e) => e.stopPropagation()}
                role="dialog"
                aria-labelledby="experiencia-pessoal-modal-title"
                aria-modal="true"
            >
                {/* Header */}
                <div className="modal-header">
                    <h2 id="experiencia-pessoal-modal-title" className="modal-title">
                        Adicionar Experiência Pessoal
                    </h2>
                    <button
                        onClick={onClose}
                        className="modal-close"
                        aria-label="Fechar modal"
                        disabled={isLoading}
                    >
                        <X size={24} />
                    </button>
                </div>

                {/* Body */}
                <div className="modal-body">
                    <form onSubmit={handleSubmit} id="experiencia-pessoal-form">
                        {/* Descrição */}
                        <div className="form-group">
                            <label htmlFor="descricao" className="form-label required">
                                Descrição da experiência pessoal
                            </label>
                            <textarea
                                id="descricao"
                                name="descricao"
                                className={`form-input ${errors.descricao ? 'form-input-error' : ''}`}
                                value={formData.descricao}
                                onChange={(e) => handleChange('descricao', e.target.value)}
                                placeholder="Descreva sua experiência pessoal com apoio a pessoas com deficiência. Por exemplo: cuidar de familiar, trabalho voluntário, vivências pessoais..."
                                rows={6}
                                maxLength={1000}
                                disabled={isLoading}
                                aria-invalid={errors.descricao ? 'true' : 'false'}
                                aria-describedby={errors.descricao ? 'descricao-error descricao-help' : 'descricao-help'}
                            />
                            {errors.descricao && (
                                <span className="form-error" id="descricao-error" role="alert">
                                    {errors.descricao}
                                </span>
                            )}
                            <small className="form-text" id="descricao-help">
                                {formData.descricao.length}/1000 caracteres
                            </small>
                        </div>

                        {/* Interesse em atuar */}
                        <div className="form-group">
                            <label className="checkbox-label">
                                <input
                                    type="checkbox"
                                    className="form-checkbox"
                                    checked={formData.interesse_atuar}
                                    onChange={(e) => handleChange('interesse_atuar', e.target.checked)}
                                    disabled={isLoading}
                                />
                                Tenho interesse em atuar profissionalmente em vagas relacionadas a essa experiência
                            </label>
                            <small className="form-text">
                                Marque se você gostaria de transformar sua experiência pessoal em oportunidade profissional.
                            </small>
                        </div>
                    </form>
                </div>

                {/* Footer */}
                <div className="modal-footer">
                    <button
                        type="button"
                        onClick={onClose}
                        className="btn-secondary"
                        disabled={isLoading}
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        form="experiencia-pessoal-form"
                        className="btn-primary"
                        disabled={isLoading}
                    >
                        {isLoading ? (
                            <>
                                <span className="spinner-border spinner-sm mr-sm" role="status" aria-hidden="true" />
                                Salvando...
                            </>
                        ) : (
                            'Adicionar Experiência'
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ExperienciaPessoalModal;
