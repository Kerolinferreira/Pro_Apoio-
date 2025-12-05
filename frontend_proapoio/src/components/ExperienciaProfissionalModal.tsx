import React, { useState, useEffect } from 'react';
import { X } from 'lucide-react';
import api from '../services/api';
import { useToast } from './Toast';
import { parseApiError, getFieldErrorMessage } from '../utils/errorHandler';
import { TEMPO_EXPERIENCIA_OPTIONS } from '../constants/options';
import { useModalFocus } from '../hooks/useModalFocus';

/**
 * Modal para adicionar ou editar experiência profissional do candidato.
 * Permite especificar idade do aluno, tempo de experiência, deficiências trabalhadas, etc.
 * CORREÇÃO P12: Foco automático e trap de foco para leitores de tela
 */

interface Deficiencia {
    id: number;
    nome: string;
}

interface ExperienciaProfissionalFormData {
    idade_aluno?: number | '';
    tempo_experiencia?: string;
    candidatar_mesma_deficiencia: boolean;
    comentario: string;
    deficiencia_ids: number[];
}

interface ExperienciaProfissional {
    id_experiencia_profissional: number;
    idade_aluno?: number | null;
    tempo_experiencia?: string | null;
    interesse_mesma_deficiencia: boolean;
    descricao: string;
    deficiencias: Deficiencia[];
}

interface ExperienciaProfissionalModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: () => void; // Callback após sucesso (para recarregar lista)
    deficienciaOptions: Deficiencia[]; // Lista de deficiências disponíveis
    experienciaToEdit?: ExperienciaProfissional | null; // Experiência a ser editada (opcional)
}

const ExperienciaProfissionalModal: React.FC<ExperienciaProfissionalModalProps> = ({
    isOpen,
    onClose,
    onSuccess,
    deficienciaOptions,
    experienciaToEdit,
}) => {
    const toast = useToast();

    // Estado do formulário
    const [formData, setFormData] = useState<ExperienciaProfissionalFormData>({
        idade_aluno: '',
        tempo_experiencia: '',
        candidatar_mesma_deficiencia: false,
        comentario: '',
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
                idade_aluno: experienciaToEdit.idade_aluno ?? '',
                tempo_experiencia: experienciaToEdit.tempo_experiencia ?? '',
                candidatar_mesma_deficiencia: experienciaToEdit.interesse_mesma_deficiencia,
                comentario: experienciaToEdit.descricao,
                deficiencia_ids: experienciaToEdit.deficiencias.map(d => d.id),
            });
        } else if (isOpen) {
            // Modo criação: limpa o formulário
            setFormData({
                idade_aluno: '',
                tempo_experiencia: '',
                candidatar_mesma_deficiencia: false,
                comentario: '',
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

        // Validação da idade do aluno (opcional, mas se fornecido deve ser válido)
        if (formData.idade_aluno && (formData.idade_aluno < 0 || formData.idade_aluno > 100)) {
            newErrors.idade_aluno = 'A idade deve estar entre 0 e 100 anos.';
        }

        // Validação da descrição (obrigatória)
        if (!formData.comentario.trim()) {
            newErrors.comentario = 'Por favor, descreva sua experiência profissional.';
        } else if (formData.comentario.length > 1000) {
            newErrors.comentario = 'A descrição não pode ter mais de 1000 caracteres.';
        }

        // Validação das deficiências (ao menos uma deve ser selecionada)
        if (formData.deficiencia_ids.length === 0) {
            newErrors.deficiencia_ids = 'Selecione ao menos uma deficiência com a qual você trabalhou.';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    /**
     * Submete a requisição de criação ou edição de experiência profissional
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
                idade_aluno: formData.idade_aluno === '' ? null : Number(formData.idade_aluno),
                tempo_experiencia: formData.tempo_experiencia || null,
                candidatar_mesma_deficiencia: formData.candidatar_mesma_deficiencia,
                comentario: formData.comentario.trim(),
                deficiencia_ids: formData.deficiencia_ids,
            };

            if (experienciaToEdit) {
                // Modo edição: PUT
                await api.put(`/candidatos/me/experiencias-profissionais/${experienciaToEdit.id_experiencia_profissional}`, payload);
                toast.success('Experiência profissional atualizada com sucesso!');
            } else {
                // Modo criação: POST
                await api.post('/candidatos/me/experiencias-profissionais', payload);
                toast.success('Experiência profissional adicionada com sucesso!');
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
    const handleChange = (field: keyof ExperienciaProfissionalFormData, value: any) => {
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
                className="modal-content modal-lg"
                onClick={(e) => e.stopPropagation()}
                role="dialog"
                aria-labelledby="experiencia-profissional-modal-title"
                aria-modal="true"
            >
                {/* Header */}
                <div className="modal-header">
                    <h2 id="experiencia-profissional-modal-title" className="modal-title">
                        {experienciaToEdit ? 'Editar Experiência Profissional' : 'Adicionar Experiência Profissional'}
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
                    <form onSubmit={handleSubmit} id="experiencia-profissional-form">
                        {/* Idade do Aluno */}
                        <div className="form-group">
                            <label htmlFor="idade_aluno" className="form-label">
                                Idade do(a) aluno(a) atendido(a)
                            </label>
                            <input
                                type="number"
                                id="idade_aluno"
                                name="idade_aluno"
                                className={`form-input ${errors.idade_aluno ? 'form-input-error' : ''}`}
                                value={formData.idade_aluno}
                                onChange={(e) => handleChange('idade_aluno', e.target.value === '' ? '' : parseInt(e.target.value))}
                                placeholder="Ex: 10"
                                min="0"
                                max="100"
                                disabled={isLoading}
                                aria-invalid={errors.idade_aluno ? 'true' : 'false'}
                                aria-describedby={errors.idade_aluno ? 'idade-error' : undefined}
                            />
                            {errors.idade_aluno && (
                                <span className="form-error" id="idade-error" role="alert">
                                    {errors.idade_aluno}
                                </span>
                            )}
                            <small className="form-text">
                                Opcional. Idade aproximada do(a) aluno(a) com quem trabalhou.
                            </small>
                        </div>

                        {/* Tempo de Experiência */}
                        <div className="form-group">
                            <label htmlFor="tempo_experiencia" className="form-label">
                                Tempo de experiência
                            </label>
                            <select
                                id="tempo_experiencia"
                                name="tempo_experiencia"
                                className={`form-input ${errors.tempo_experiencia ? 'form-input-error' : ''}`}
                                value={formData.tempo_experiencia}
                                onChange={(e) => handleChange('tempo_experiencia', e.target.value)}
                                disabled={isLoading}
                                aria-invalid={errors.tempo_experiencia ? 'true' : 'false'}
                                aria-describedby={errors.tempo_experiencia ? 'tempo-error' : undefined}
                            >
                                <option value="">Selecione...</option>
                                {TEMPO_EXPERIENCIA_OPTIONS.map((option) => (
                                    <option key={option} value={option}>
                                        {option}
                                    </option>
                                ))}
                            </select>
                            {errors.tempo_experiencia && (
                                <span className="form-error" id="tempo-error" role="alert">
                                    {errors.tempo_experiencia}
                                </span>
                            )}
                        </div>

                        {/* Deficiências Trabalhadas */}
                        <div className="form-group">
                            <label className="form-label required">
                                Deficiências com as quais trabalhou
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

                        {/* Interesse em trabalhar com a mesma deficiência */}
                        <div className="form-group">
                            <label className="checkbox-label">
                                <input
                                    type="checkbox"
                                    className="form-checkbox"
                                    checked={formData.candidatar_mesma_deficiencia}
                                    onChange={(e) => handleChange('candidatar_mesma_deficiencia', e.target.checked)}
                                    disabled={isLoading}
                                />
                                Tenho interesse em trabalhar com as mesmas deficiências novamente
                            </label>
                        </div>

                        {/* Descrição/Comentário */}
                        <div className="form-group">
                            <label htmlFor="comentario" className="form-label required">
                                Descrição da experiência
                            </label>
                            <textarea
                                id="comentario"
                                name="comentario"
                                className={`form-input ${errors.comentario ? 'form-input-error' : ''}`}
                                value={formData.comentario}
                                onChange={(e) => handleChange('comentario', e.target.value)}
                                placeholder="Descreva sua experiência profissional com apoio a pessoas com deficiência..."
                                rows={5}
                                maxLength={1000}
                                disabled={isLoading}
                                aria-invalid={errors.comentario ? 'true' : 'false'}
                                aria-describedby={errors.comentario ? 'comentario-error comentario-help' : 'comentario-help'}
                            />
                            {errors.comentario && (
                                <span className="form-error" id="comentario-error" role="alert">
                                    {errors.comentario}
                                </span>
                            )}
                            <small className="form-text" id="comentario-help">
                                {formData.comentario.length}/1000 caracteres
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
                        form="experiencia-profissional-form"
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

export default ExperienciaProfissionalModal;
