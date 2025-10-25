import React, { useState, useEffect } from 'react';
import api from '../services/api'; // Assumindo serviço de API
import Header from '../components/Header';
import CandidatoCard from '../components/CandidatoCard'; // Assumindo componente de card
import { Link } from 'react-router-dom';

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
    escolaridade: string[]; // Alterado para array para suportar múltiplos checkboxes
    tipo_deficiencia: string;
    distancia_km: number; // Novo filtro para deslocamento
}

const escolaridadeOptions = [
    { label: 'Fundamental Completo', value: 'Fundamental Completo' },
    { label: 'Médio Completo', value: 'Médio Completo' },
    { label: 'Superior Incompleto', value: 'Superior Incompleto' },
    { label: 'Superior Completo', value: 'Superior Completo' },
];

const BuscarCandidatosPage: React.FC = () => {
    const [candidatos, setCandidatos] = useState<Candidato[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [filters, setFilters] = useState<FilterState>({
        termo: '',
        escolaridade: [],
        tipo_deficiencia: '',
        distancia_km: 10, // Valor inicial para o slider (10km)
    });

    // Função que formata os filtros para a API
    const getQueryString = (f: FilterState) => {
        const params = new URLSearchParams();
        if (f.termo) params.append('termo', f.termo);
        if (f.escolaridade.length > 0) params.append('escolaridade', f.escolaridade.join(','));
        if (f.tipo_deficiencia) params.append('tipo_deficiencia', f.tipo_deficiencia);
        
        // Adiciona o novo filtro de deslocamento
        if (f.distancia_km > 0) params.append('distancia_km', f.distancia_km.toString());

        return params.toString();
    };

    const fetchCandidatos = async (currentFilters: FilterState) => {
        setLoading(true);
        setError(null);
        try {
            const queryString = getQueryString(currentFilters);
            const response = await api.get(`/candidatos/buscar?${queryString}`);
            setCandidatos(response.data);
        } catch (err) {
            console.error('Erro ao buscar candidatos:', err);
            setError('Não foi possível carregar a lista de candidatos.');
        } finally {
            setLoading(false);
        }
    };

    // Efeito para buscar candidatos quando os filtros mudam
    useEffect(() => {
        fetchCandidatos(filters);
    }, [filters]);

    const handleTermChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setFilters(prev => ({ ...prev, termo: e.target.value }));
    };
    
    // Função para lidar com o novo slider de deslocamento
    const handleDistanciaChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setFilters(prev => ({ ...prev, distancia_km: Number(e.target.value) }));
    };

    // Função para lidar com os checkboxes de escolaridade
    const handleEscolaridadeChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const { value, checked } = e.target;
        setFilters(prev => {
            const newEscolaridade = checked
                ? [...prev.escolaridade, value] // Adiciona
                : prev.escolaridade.filter(level => level !== value); // Remove
            return { ...prev, escolaridade: newEscolaridade };
        });
    };

    // Simulação de lista de deficiências para filtros (DEVE VIR DE UMA API REAL)
    const deficienciaOptions = [
        { label: 'Deficiência Visual', value: 'Visual' },
        { label: 'Deficiência Auditiva', value: 'Auditiva' },
        { label: 'Deficiência Física', value: 'Física' },
        // ...
    ];
    
    const handleDeficienciaChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        setFilters(prev => ({ ...prev, tipo_deficiencia: e.target.value }));
    };

    return (
        <div className="min-h-screen bg-gray-50">
            <Header />
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
                <h1 className="text-3xl font-bold text-gray-900 mb-8">Buscar Agentes de Apoio</h1>

                <div className="lg:grid lg:grid-cols-4 lg:gap-8">
                    {/* Painel de Filtros (Sidebar) */}
                    <div className="lg:col-span-1 bg-white p-6 rounded-lg shadow-md mb-8 lg:mb-0">
                        <h2 className="text-xl font-semibold text-gray-800 mb-4">Filtros</h2>

                        {/* Campo de Pesquisa por Termo */}
                        <div className="mb-6">
                            <label htmlFor="search-termo" className="block text-sm font-medium text-gray-700 mb-1">
                                Palavra-chave ou Habilidade
                            </label>
                            <input
                                id="search-termo"
                                type="text"
                                value={filters.termo}
                                onChange={handleTermChange}
                                placeholder="Ex: Libras, Braile..."
                                className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        
                        {/* INÍCIO DA CORREÇÃO: Filtro de Escolaridade como Checkboxes */}
                        <div className="mb-6">
                            <h3 className="text-sm font-medium text-gray-700 mb-2">Nível de Escolaridade</h3>
                            <div className="space-y-2">
                                {escolaridadeOptions.map(option => (
                                    <div key={option.value} className="flex items-center">
                                        <input
                                            id={`escolaridade-${option.value}`}
                                            name="escolaridade"
                                            type="checkbox"
                                            value={option.value}
                                            checked={filters.escolaridade.includes(option.value)}
                                            onChange={handleEscolaridadeChange}
                                            className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        />
                                        <label htmlFor={`escolaridade-${option.value}`} className="ml-3 text-sm text-gray-600">
                                            {option.label}
                                        </label>
                                    </div>
                                ))}
                            </div>
                        </div>
                        {/* FIM DA CORREÇÃO: Checkboxes de Escolaridade */}

                        {/* INÍCIO DA CORREÇÃO: Filtro de Deslocamento (Slider/Range) */}
                        <div className="mb-6">
                            <h3 className="text-sm font-medium text-gray-700 mb-2">Disponibilidade de Deslocamento</h3>
                            <label htmlFor="distancia-km" className="block text-xl font-bold text-blue-600 mb-2">
                                {filters.distancia_km} km
                            </label>
                            <input
                                id="distancia-km"
                                type="range"
                                min="0"
                                max="100" // Ajuste o max conforme a necessidade do negócio
                                step="5"
                                value={filters.distancia_km}
                                onChange={handleDistanciaChange}
                                className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-lg"
                            />
                            <div className="flex justify-between text-xs text-gray-500 mt-1">
                                <span>0 km</span>
                                <span>100 km+</span>
                            </div>
                        </div>
                        {/* FIM DA CORREÇÃO: Filtro de Deslocamento */}
                        
                        {/* Filtro por Tipo de Deficiência (mantido como Select/Input por enquanto) */}
                        <div className="mb-6">
                            <label htmlFor="tipo_deficiencia" className="block text-sm font-medium text-gray-700 mb-1">
                                Experiência com Deficiência (Excluirá este filtro se não implementado no BE)
                            </label>
                            <select
                                id="tipo_deficiencia"
                                name="tipo_deficiencia"
                                value={filters.tipo_deficiencia}
                                onChange={handleDeficienciaChange}
                                className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Qualquer Experiência</option>
                                {deficienciaOptions.map(option => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    {/* Fim do Painel de Filtros */}

                    {/* Lista de Resultados */}
                    <div className="lg:col-span-3">
                        {loading ? (
                            <div className="text-center py-10">
                                <p>Carregando candidatos...</p>
                                {/* Adicionar um spinner real aqui */}
                            </div>
                        ) : error ? (
                            <div className="text-center py-10 text-red-600">
                                <p>{error}</p>
                            </div>
                        ) : candidatos.length === 0 ? (
                            <div className="text-center py-10 text-gray-500">
                                <p>Nenhum candidato encontrado com os filtros aplicados.</p>
                            </div>
                        ) : (
                            <div className="space-y-6">
                                {candidatos.map(candidato => (
                                    // Assumindo CandidatoCard existe e usa Link para ir ao Perfil Público
                                    <Link key={candidato.id} to={`/candidatos/${candidato.id}`}>
                                        <CandidatoCard candidato={candidato} />
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default BuscarCandidatosPage;
