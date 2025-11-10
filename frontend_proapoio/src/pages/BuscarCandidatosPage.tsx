import React, { useState, useEffect } from 'react';
import api from '../services/api';
import Header from '../components/Header';
import CandidatoCard from '../components/CandidatoCard';
import { Link } from 'react-router-dom';
import { ESCOLARIDADE_SELECT_OPTIONS } from '../constants/options';
import { logger } from '../utils/logger';

// Definição de tipos simulados
interface Candidato {
    id: number;
    nome_completo: string;
    escolaridade: string;
    deficiencias: { nome: string }[];
    cidade: string;
    estado: string;
    // ... outros campos relevantes
}

interface FilterState {
    termo: string;
    escolaridade: string[]; // Suporta múltiplos checkboxes
    tipo_deficiencia: string;
    localizacao: '' | 'cidade' | 'estado';
}

// Constantes agora importadas de src/constants/options.ts


const BuscarCandidatosPage: React.FC = () => {
    const [candidatos, setCandidatos] = useState<Candidato[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [deficienciaOptions, setDeficienciaOptions] = useState<{ label: string; value: string }[]>([]);
    const [filters, setFilters] = useState<FilterState>({
        termo: '',
        escolaridade: [],
        tipo_deficiencia: '',
        localizacao: '',
    });

    /**
     * @description Monta a query string com base nos filtros atuais.
     */
    const getQueryString = (f: FilterState) => {
        const params = new URLSearchParams();
        if (f.termo) params.append('termo', f.termo);
        // Junta os valores de escolaridade com vírgula para a API
        if (f.escolaridade.length > 0) params.append('escolaridade', f.escolaridade.join(','));
        if (f.tipo_deficiencia) params.append('tipo_deficiencia', f.tipo_deficiencia);
        
        // Filtro de deslocamento (distância_km)
        if (f.localizacao === 'cidade') {
            params.append('distancia_km', '50');
        } else if (f.localizacao === 'estado') {
            params.append('distancia_km', '200');
        }

        return params.toString();
    };

    /**
     * @description Busca os candidatos na API com base nos filtros.
     */
    const fetchCandidatos = async (currentFilters: FilterState) => {
        setLoading(true);
        setError(null);
        try {
            const queryString = getQueryString(currentFilters);
            // GET /candidatos (Endpoint correto para busca de candidatos)
            const response = await api.get(`/candidatos?${queryString}`);
            // A API retorna uma resposta paginada, os candidatos estão em response.data.data
            setCandidatos(response.data.data || []);
        } catch (err) {
            logger.error('Erro ao buscar candidatos:', err);
            setError('Não foi possível carregar a lista de agentes de apoio. Verifique sua conexão.');
        } finally {
            setLoading(false);
        }
    };

    /**
     * @description Busca lista de deficiências da API
     */
    useEffect(() => {
        const fetchDeficiencias = async () => {
            try {
                const response = await api.get('/deficiencias');
                // Backend retorna: [{ id_deficiencia: 1, nome: 'Visual' }, ...]
                const options = response.data.map((def: any) => ({
                    label: `Deficiência ${def.nome}`,
                    value: def.nome
                }));
                setDeficienciaOptions(options);
            } catch (err) {
                logger.error('Erro ao buscar deficiências:', err);
                // Fallback: usar opções padrão em caso de erro
                setDeficienciaOptions([
                    { label: 'Deficiência Visual', value: 'Visual' },
                    { label: 'Deficiência Auditiva', value: 'Auditiva' },
                    { label: 'Deficiência Física', value: 'Física' },
                    { label: 'Deficiência Intelectual', value: 'Intelectual' },
                ]);
            }
        };
        fetchDeficiencias();
    }, []);

    /**
     * @description Efeito com Debounce para buscar candidatos quando os filtros mudam,
     * evitando excesso de chamadas de API.
     */
    useEffect(() => {
        const handler = setTimeout(() => {
            fetchCandidatos(filters);
        }, 400); // 400ms de debounce

        // Cleanup function: cancela o timer se o filtro mudar antes do tempo
        return () => {
            clearTimeout(handler);
        };
    }, [filters]);

    // Handlers
    const handleTermChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setFilters(prev => ({ ...prev, termo: e.target.value }));
    };
    
    const handleLocalizacaoChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        setFilters(prev => ({ ...prev, localizacao: e.target.value as FilterState['localizacao'] }));
    };

    const handleEscolaridadeChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const { value, checked } = e.target;
        setFilters(prev => {
            const newEscolaridade = checked
                ? [...prev.escolaridade, value] // Adiciona
                : prev.escolaridade.filter(level => level !== value); // Remove
            return { ...prev, escolaridade: newEscolaridade };
        });
    };
    
    const handleDeficienciaChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        setFilters(prev => ({ ...prev, tipo_deficiencia: e.target.value }));
    };

    return (
        // Remove estilos de wrapper (bg-gray-50), confiando no body em global.css
        <div className="page-wrapper"> 
            <Header />
            {/* Aplica o container global para max-width e centralização */}
            <div className="container py-lg"> 
                {/* Título principal: heading-secondary e mb-lg para espaçamento */}
                <h1 className="heading-secondary mb-lg">Buscar Agentes de Apoio</h1>

                {/* Layout Grid (Desktop) / Coluna (Mobile) */}
                <div className="main-content-layout"> 
                    
                    {/* Painel de Filtros (Sidebar) */}
                    {/* Usa a classe card e a classe de span do grid para layout */}
                    <aside className="card lg-col-span-1"> 
                        <h2 className="title-md mb-lg">Filtros de Busca</h2>

                        {/* Palavra-chave/Habilidade */}
                        <div className="mb-md">
                            <label htmlFor="search-termo" className="text-sm mb-xs">
                                Palavra-chave ou Habilidade
                            </label>
                            <input
                                id="search-termo"
                                type="text"
                                value={filters.termo}
                                onChange={handleTermChange}
                                placeholder="Ex: Libras, Braile..."
                                className="form-input" // Estilo global
                            />
                        </div>
                        
                        {/* Filtro de Escolaridade (Checkboxes) */}
                        <div className="mb-md">
                            <h3 className="text-sm mb-sm">Nível de Escolaridade</h3>
                            <div className="space-y-sm">
                                {ESCOLARIDADE_SELECT_OPTIONS.map(option => (
                                    <div key={option.value} className="flex-group-item">
                                        <input
                                            id={`escolaridade-${option.value}`}
                                            name="escolaridade"
                                            type="checkbox"
                                            value={option.value}
                                            checked={filters.escolaridade.includes(option.value)}
                                            onChange={handleEscolaridadeChange}
                                            className="form-checkbox" // Estilo global
                                        />
                                        <label htmlFor={`escolaridade-${option.value}`} className="text-sm text-muted">
                                            {option.label}
                                        </label>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Filtro de Deslocamento (Select) */}
                        <div className="mb-md">
                            <label htmlFor="localizacao-filtro" className="text-sm mb-xs">
                                Disponibilidade de Deslocamento
                            </label>
                            <select
                                id="localizacao-filtro"
                                name="localizacao"
                                value={filters.localizacao}
                                onChange={handleLocalizacaoChange}
                                className="form-select"
                            >
                                <option value="">Qualquer distância</option>
                                <option value="cidade">Apenas na minha cidade (aprox. 50km)</option>
                                <option value="estado">Apenas no meu estado (aprox. 200km)</option>
                            </select>
                            <p className="text-xs text-muted mt-xs">
                                O filtro de distância é uma estimativa.
                            </p>
                        </div>
                        
                        {/* Filtro por Tipo de Deficiência (Select) */}
                        <div className="mb-md">
                            <label htmlFor="tipo_deficiencia" className="text-sm mb-xs">
                                Experiência com Deficiência
                            </label>
                            <select
                                id="tipo_deficiencia"
                                name="tipo_deficiencia"
                                value={filters.tipo_deficiencia}
                                onChange={handleDeficienciaChange}
                                className="form-select" // Estilo global
                            >
                                <option value="">Qualquer Experiência</option>
                                {deficienciaOptions.map(option => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </aside>

                    {/* Lista de Resultados */}
                    <section className="lg-col-span-3">
                        {loading ? (
                            <div className="text-center py-lg">
                                <p className="text-info">Carregando agentes de apoio...</p>
                                {/* Implementar um componente Spinner acessível */}
                            </div>
                        ) : error ? (
                            // Usa classes globais de Alerta
                            <div className="alert alert-error text-center">
                                <p>{error}</p>
                            </div>
                        ) : candidatos.length === 0 ? (
                            <div className="alert alert-warning text-center">
                                <p>Nenhum agente de apoio encontrado com os filtros aplicados.</p>
                            </div>
                        ) : (
                            <div className="space-y-md"> {/* Espaçamento entre cards */}
                                {candidatos.map(candidato => (
                                    // 'block' garante que o link ocupe toda a largura
                                    <Link key={candidato.id} to={`/candidatos/${candidato.id}`} className="block">
                                        <CandidatoCard candidato={candidato} />
                                    </Link>
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </div>
    );
};

export default BuscarCandidatosPage;