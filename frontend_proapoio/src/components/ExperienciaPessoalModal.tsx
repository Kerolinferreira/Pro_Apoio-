import React, { useState, useEffect } from 'react';
import { X, Accessibility } from 'lucide-react';
import api from '../services/api';
import { useToast } from './Toast';
import { parseApiError, getFieldErrorMessage } from '../utils/errorHandler';
import { useModalFocus } from '../hooks/useModalFocus';

/**
 * Modal para adicionar ou editar experiência pessoal do candidato.
 * Captura descrição da experiência, interesse em atuar em vagas similares e deficiências relacionadas.
 * CORREÇÃO P12: Foco automático e trap de foco para leitores de tela
 */

interface Deficiencia {
    id: number;
    nome: string;
}

interface ExperienciaPessoalFormData {
    interesse_atuar: boolean;
    descricao: string;
    deficiencia_ids: number[];
}

interface ExperienciaPessoal {
    id_experiencia_pessoal: number;
    interesse_atuar: boolean;
    descricao: string;
    deficiencias: Deficiencia[];
}

interface ExperienciaPessoalModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: () => void; // Callback após sucesso (para recarregar lista)
    deficienciaOptions: Deficiencia[]; // Lista de deficiências disponíveis
    experienciaToEdit?: ExperienciaPessoal | null; // Experiência a ser editada (opcional)
}

const ExperienciaPessoalModal: React.FC<ExperienciaPessoalModalProps> = ({
    isOpen,
    onClose,
    onSuccess,
    deficienciaOptions,
    experienciaToEdit,
}) => {
    const toast = useToast();

    // Estado do formulário
    const [formData, setFormData] = useState<ExperienciaPessoalFormData>({
        interesse_atuar: false,
        descricao: '',
        deficiencia_ids: [],
    });

    const [isLoading, setIsLoading] = useState(false);
    const [errors, setErrors] = useState<{ [key: string]: string }>({});

    // Hook de acessibilidade para foco
    const { modalRef, firstFocusableRef } = useModalFocus(isOpen, isLoading, onClose);

    // Preencher formulário ao abrir (para edição) ou limpar (para criação)
    useEffect(() => {
        if (isOpen && experienciaToEdit) {
            // Modo edição: preenche com os dados existentes
            setFormData({
                interesse_atuar: experienciaToEdit.interesse_atuar,
                descricao: experienciaToEdit.descricao,
                deficiencia_ids: experienciaToEdit.deficiencias.map(d => d.id),
            });
        } else if (isOpen) {
            // Modo criação: limpa o formulário
            setFormData({
                interesse_atuar: false,
                descricao: '',
                deficiencia_ids: [],
            });
        }
        setErrors({});
    }, [isOpen, experienciaToEdit]);

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

        // Validação das deficiências (ao menos uma deve ser selecionada)
        if (formData.deficiencia_ids.length === 0) {
            newErrors.deficiencia_ids = 'Selecione ao menos uma deficiência relacionada à sua experiência.';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    /**
     * Submete a requisição de criação ou edição de experiência pessoal
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
                deficiencia_ids: formData.deficiencia_ids,
            };

            if (experienciaToEdit) {
                // Modo edição: PUT
                await api.put(`/candidatos/me/experiencias-pessoais/${experienciaToEdit.id_experiencia_pessoal}`, payload);
                toast.success('Experiência pessoal atualizada com sucesso!');
            } else {
                // Modo criação: POST
                await api.post('/candidatos/me/experiencias-pessoais', payload);
                toast.success('Experiência pessoal adicionada com sucesso!');
            }

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

    /**
     * Toggle de deficiência (adiciona/remove da lista)
     */
    const toggleDeficiencia = (deficienciaId: number) => {
        setFormData((prev) => {
            const isSelected = prev.deficiencia_ids.includes(deficienciaId);
            const newIds = isSelected
                ? prev.deficiencia_ids.filter((id) => id !== deficienciaId)
                : [...prev.deficiencia_ids, deficienciaId];
            return { ...prev, deficiencia_ids: newIds };
        });
        // Limpa o erro de deficiências ao selecionar
        if (errors.deficiencia_ids) {
            setErrors((prev) => ({ ...prev, deficiencia_ids: '' }));
        }
    };

    if (!isOpen) return null;

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div
                ref={modalRef}
                className="modal-content modal-md"
                onClick={(e) => e.stopPropagation()}
                role="dialog"
                aria-labelledby="experiencia-pessoal-modal-title"
                aria-modal="true"
            >
                {/* Header */}
                <div className="modal-header">
                    <h2 id="experiencia-pessoal-modal-title" className="modal-title">
                        {experienciaToEdit ? 'Editar Experiência Pessoal' : 'Adicionar Experiência Pessoal'}
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

                        {/* Deficiências Relacionadas */}
                        <div className="form-group">
                            <label className="form-label required">
                                Deficiências relacionadas à experiência
                            </label>
                            <div className="space-y-xs">
                                {deficienciaOptions.map((def) => (
                                    <label key={def.id} className="checkbox-label">
                                        <input
                                            type="checkbox"
                                            className="form-checkbox"
                                            checked={formData.deficiencia_ids.includes(def.id)}
                                            onChange={() => toggleDeficiencia(def.id)}
                                            disabled={isLoading}
                                        />
                                        <Accessibility size={20} className="text-brand-color mr-xs" />
                                        {def.nome}
                                    </label>
                                ))}
                            </div>
                            {errors.deficiencia_ids && (
                                <span className="form-error" role="alert">
                                    {errors.deficiencia_ids}
                                </span>
                            )}
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
                        ref={firstFocusableRef}
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
                            experienciaToEdit ? 'Salvar Alterações' : 'Adicionar Experiência'
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ExperienciaPessoalModal;
