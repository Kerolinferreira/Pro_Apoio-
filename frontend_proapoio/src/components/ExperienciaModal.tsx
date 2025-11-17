import React, { useState, useEffect } from 'react';
import { X, Briefcase, Heart, Loader2 } from 'lucide-react';
import { logger } from '../utils/logger';
import { useModalFocus } from '../hooks/useModalFocus';

interface Deficiencia {
    id: number;
    nome: string;
}

interface ExperienciaFormData {
    // Para experiências profissionais
    idade_aluno?: number | string;
    tempo_experiencia?: string;
    interesse_mesma_deficiencia?: boolean;
    deficiencia_ids?: number[];
    // Para experiências pessoais
    interesse_atuar?: boolean;
    // Comum
    descricao: string;
}

interface ExperienciaModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSubmit: (data: ExperienciaFormData) => Promise<void>;
    tipo: 'pessoal' | 'profissional';
    deficienciaOptions?: Deficiencia[];
    initialData?: ExperienciaFormData;
    isEditing?: boolean;
}

/**
 * @component ExperienciaModal
 * @description Modal para adicionar/editar experiências profissionais e pessoais
 */
const ExperienciaModal: React.FC<ExperienciaModalProps> = ({
    isOpen,
    onClose,
    onSubmit,
    tipo,
    deficienciaOptions = [],
    initialData,
    isEditing = false
}) => {
    const [formData, setFormData] = useState<ExperienciaFormData>({
        descricao: '',
        interesse_mesma_deficiencia: false,
        interesse_atuar: false,
        deficiencia_ids: []
    });

    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    // Hook de acessibilidade para foco
    const { modalRef, firstFocusableRef } = useModalFocus(isOpen, loading, onClose);

    // Atualiza o formulário quando initialData muda
    useEffect(() => {
        if (initialData) {
            setFormData({
                ...initialData,
                deficiencia_ids: initialData.deficiencia_ids || []
            });
        } else {
            // Reset para valores padrão
            setFormData({
                descricao: '',
                interesse_mesma_deficiencia: false,
                interesse_atuar: false,
                deficiencia_ids: []
            });
        }
        setErrors({});
    }, [initialData, isOpen]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value, type } = e.target;

        if (type === 'checkbox') {
            const checked = (e.target as HTMLInputElement).checked;
            setFormData(prev => ({ ...prev, [name]: checked }));
        } else {
            setFormData(prev => ({ ...prev, [name]: value }));
        }

        // Limpa erro do campo quando o usuário começa a digitar
        if (errors[name]) {
            setErrors(prev => ({ ...prev, [name]: '' }));
        }
    };

    const handleDeficienciaChange = (defId: number, isChecked: boolean) => {
        setFormData(prev => {
            const currentIds = prev.deficiencia_ids || [];
            const newIds = isChecked
                ? [...currentIds, defId]
                : currentIds.filter(id => id !== defId);
            return { ...prev, deficiencia_ids: newIds };
        });
    };

    const validate = (): boolean => {
        const newErrors: Record<string, string> = {};

        // Validação comum
        if (!formData.descricao || formData.descricao.trim().length === 0) {
            newErrors.descricao = 'A descrição é obrigatória';
        } else if (formData.descricao.length > 1000) {
            newErrors.descricao = 'A descrição não pode ter mais de 1000 caracteres';
        }

        // Validação específica para experiência profissional
        if (tipo === 'profissional') {
            if (formData.idade_aluno && (Number(formData.idade_aluno) < 0 || Number(formData.idade_aluno) > 120)) {
                newErrors.idade_aluno = 'Idade inválida';
            }

            if (!formData.tempo_experiencia || formData.tempo_experiencia.trim().length === 0) {
                newErrors.tempo_experiencia = 'O tempo de experiência é obrigatório';
            }

            if (!formData.deficiencia_ids || formData.deficiencia_ids.length === 0) {
                newErrors.deficiencia_ids = 'Selecione ao menos uma deficiência';
            }
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!validate()) {
            return;
        }

        setLoading(true);
        try {
            await onSubmit(formData);
            onClose();
        } catch (error) {
            logger.error('Erro ao salvar experiência:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleBackdropClick = (e: React.MouseEvent) => {
        if (e.target === e.currentTarget && !loading) {
            onClose();
        }
    };

    if (!isOpen) return null;

    const isProfissional = tipo === 'profissional';

    return (
        <div
            className="modal-overlay"
            onClick={handleBackdropClick}
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-title"
        >
            <div ref={modalRef} className="modal-content card" style={{ maxWidth: '600px', maxHeight: '90vh', overflowY: 'auto' }}>
                {/* Header */}
                <div className="flex-group-md-row mb-md" style={{ justifyContent: 'space-between', alignItems: 'flex-start' }}>
                    <div className="flex-group" style={{ gap: '1rem', alignItems: 'center' }}>
                        {isProfissional ? <Briefcase size={28} className="text-brand-color" /> : <Heart size={28} className="text-brand-color" />}
                        <h2 id="modal-title" className="title-lg">
                            {isEditing ? 'Editar' : 'Adicionar'} Experiência {isProfissional ? 'Profissional' : 'Pessoal'}
                        </h2>
                    </div>
                    <button
                        ref={firstFocusableRef}
                        onClick={onClose}
                        className="btn-icon btn-sm"
                        aria-label="Fechar modal"
                        disabled={loading}
                    >
                        <X size={20} />
                    </button>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-md">

                    {/* Campos específicos para Experiência Profissional */}
                    {isProfissional && (
                        <>
                            <div className="form-group">
                                <label htmlFor="idade_aluno" className="form-label">
                                    Idade do Aluno <span className="text-muted">(opcional)</span>
                                </label>
                                <input
                                    id="idade_aluno"
                                    name="idade_aluno"
                                    type="number"
                                    min="0"
                                    max="120"
                                    value={formData.idade_aluno || ''}
                                    onChange={handleChange}
                                    className="form-input"
                                    placeholder="Ex: 25"
                                    disabled={loading}
                                />
                                {errors.idade_aluno && <p className="text-error text-sm mt-xs">{errors.idade_aluno}</p>}
                            </div>

                            <div className="form-group">
                                <label htmlFor="tempo_experiencia" className="form-label">
                                    Tempo de Experiência <span className="text-error">*</span>
                                </label>
                                <input
                                    id="tempo_experiencia"
                                    name="tempo_experiencia"
                                    type="text"
                                    value={formData.tempo_experiencia || ''}
                                    onChange={handleChange}
                                    className="form-input"
                                    placeholder="Ex: 2 anos, 6 meses"
                                    disabled={loading}
                                    required
                                />
                                {errors.tempo_experiencia && <p className="text-error text-sm mt-xs">{errors.tempo_experiencia}</p>}
                            </div>

                            <div className="form-group">
                                <label className="form-label mb-sm">
                                    Deficiências com Experiência <span className="text-error">*</span>
                                </label>
                                <div className="grid-2-col-lg space-y-xs" style={{ maxHeight: '150px', overflowY: 'auto', padding: '0.5rem', border: '1px solid var(--color-border)', borderRadius: 'var(--border-radius)' }}>
                                    {deficienciaOptions.map(def => (
                                        <label key={def.id} className="checkbox-label text-base">
                                            <input
                                                type="checkbox"
                                                className="form-checkbox"
                                                checked={(formData.deficiencia_ids || []).includes(def.id)}
                                                onChange={(e) => handleDeficienciaChange(def.id, e.target.checked)}
                                                disabled={loading}
                                            />
                                            {def.nome}
                                        </label>
                                    ))}
                                </div>
                                {errors.deficiencia_ids && <p className="text-error text-sm mt-xs">{errors.deficiencia_ids}</p>}
                            </div>

                            <div className="form-group">
                                <label className="checkbox-label text-base">
                                    <input
                                        type="checkbox"
                                        name="interesse_mesma_deficiencia"
                                        className="form-checkbox"
                                        checked={formData.interesse_mesma_deficiencia || false}
                                        onChange={handleChange}
                                        disabled={loading}
                                    />
                                    Tenho interesse em trabalhar com a mesma deficiência
                                </label>
                            </div>
                        </>
                    )}

                    {/* Campos para Experiência Pessoal */}
                    {!isProfissional && (
                        <div className="form-group">
                            <label className="checkbox-label text-base">
                                <input
                                    type="checkbox"
                                    name="interesse_atuar"
                                    className="form-checkbox"
                                    checked={formData.interesse_atuar || false}
                                    onChange={handleChange}
                                    disabled={loading}
                                />
                                Tenho interesse em atuar profissionalmente na área
                            </label>
                        </div>
                    )}

                    {/* Descrição (comum para ambos) */}
                    <div className="form-group">
                        <label htmlFor="descricao" className="form-label">
                            Descrição da Experiência <span className="text-error">*</span>
                        </label>
                        <textarea
                            id="descricao"
                            name="descricao"
                            value={formData.descricao}
                            onChange={handleChange}
                            className="form-input"
                            rows={5}
                            maxLength={1000}
                            placeholder={isProfissional
                                ? "Descreva sua experiência profissional, atividades realizadas, resultados alcançados..."
                                : "Descreva sua experiência pessoal, como você se envolveu com apoio a pessoas com deficiência..."
                            }
                            disabled={loading}
                            required
                        />
                        <p className="text-sm text-muted mt-xs">
                            {formData.descricao.length}/1000 caracteres
                        </p>
                        {errors.descricao && <p className="text-error text-sm mt-xs">{errors.descricao}</p>}
                    </div>

                    {/* Actions */}
                    <div className="flex-actions-end pt-md border-top-divider" style={{ gap: '0.75rem' }}>
                        <button
                            type="button"
                            onClick={onClose}
                            className="btn-secondary"
                            disabled={loading}
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            className="btn-primary btn-icon"
                            disabled={loading}
                        >
                            {loading && <Loader2 size={20} className="icon-spin mr-sm" />}
                            {loading ? 'Salvando...' : (isEditing ? 'Salvar Alterações' : 'Adicionar')}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default ExperienciaModal;
