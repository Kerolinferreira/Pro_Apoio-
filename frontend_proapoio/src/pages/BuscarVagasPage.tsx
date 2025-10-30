import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { ChevronDown, ChevronUp, Frown, Filter, Search, MapPin, Briefcase } from 'lucide-react';
import api from '../services/api';
import VagaCard from '../components/VagaCard';
import { useAuth } from '../contexts/AuthContext';
import Header from '../components/Header';
import Footer from '../components/Footer';

// Definições de Tipos
interface Vaga {
  id: number;
  titulo: string;
  descricao: string;
  tipo: string;
  modalidade: string;
  remuneracao: number | null;
  cidade: string;
  estado: string;
  instituicao: {
    nome: string;
    logo_url: string | null;
  };
  data_criacao: string;
}

interface FilterOptions {
  titulo?: string;
  tipo?: string[];
  modalidade?: string[];
  cidade?: string;
  estado?: string;
  remuneracaoMin?: number | '';
  remuneracaoMax?: number | '';
}

interface Meta {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
  from: number;
  to: number;
}

const BuscarVagasPage: React.FC = () => {
  const [vagas, setVagas] = useState<Vaga[]>([]);
  const [loading, setLoading] = useState(false);
  const [meta, setMeta] = useState<Meta | null>(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();

  // Estados dos Filtros (Sincronizados com a URL)
  const [filterQuery, setFilterQuery] = useState(searchParams.get('titulo') || '');
  const [tipoFiltro, setTipoFiltro] = useState<string[]>(searchParams.getAll('tipo') || []);
  const [modalidadeFiltro, setModalidadeFiltro] = useState<string[]>(searchParams.getAll('modalidade') || []);
  const [cidadeFiltro, setCidadeFiltro] = useState(searchParams.get('cidade') || '');
  const [estadoFiltro, setEstadoFiltro] = useState(searchParams.get('estado') || '');
  const [remuneracaoMinFiltro, setRemuneracaoMinFiltro] = useState<number | ''>(searchParams.get('remuneracaoMin') ? Number(searchParams.get('remuneracaoMin')) : '');
  const [remuneracaoMaxFiltro, setRemuneracaoMaxFiltro] = useState<number | ''>(searchParams.get('remuneracaoMax') ? Number(searchParams.get('remuneracaoMax')) : '');

  // Estado para visibilidade do filtro mobile
  const [isFilterOpen, setIsFilterOpen] = useState(false);

  // Mapeamentos para as opções de filtro
  const tiposVaga = ['Estágio', 'Voluntariado', 'Trabalho Fixo', 'Temporário'];
  const modalidadesVaga = ['Presencial', 'Remoto', 'Híbrido'];
  const estadosBrasileiros = [
    'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR',
    'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
  ];

  const fetchVagas = useCallback(async (page: number, filters: FilterOptions) => {
    setLoading(true);
    try {
      const params = new URLSearchParams();

      // Adiciona filtros para a requisição
      if (filters.titulo) params.append('titulo', filters.titulo);
      if (filters.cidade) params.append('cidade', filters.cidade);
      if (filters.estado) params.append('estado', filters.estado);
      if (filters.remuneracaoMin !== '' && filters.remuneracaoMin !== null) params.append('remuneracaoMin', String(filters.remuneracaoMin));
      if (filters.remuneracaoMax !== '' && filters.remuneracaoMax !== null) params.append('remuneracaoMax', String(filters.remuneracaoMax));

      // Adiciona filtros de array
      filters.tipo?.forEach(t => params.append('tipo[]', t));
      filters.modalidade?.forEach(m => params.append('modalidade[]', m));

      // Adiciona paginação
      params.append('page', String(page));

      const response = await api.get(`/vagas?${params.toString()}`);
      setVagas(response.data.data);
      setMeta(response.data.meta);
      setCurrentPage(page);
    } catch (error) {
      console.error('Erro ao buscar vagas:', error);
      setVagas([]);
      setMeta(null);
    } finally {
      setLoading(false);
    }
  }, []);

  // Efeito para carregar as vagas ao montar o componente ou quando a página/filtros mudam
  useEffect(() => {
    // Monta o objeto de filtros a partir dos estados
    const currentFilters: FilterOptions = {
      titulo: filterQuery,
      tipo: tipoFiltro.length > 0 ? tipoFiltro : undefined,
      modalidade: modalidadeFiltro.length > 0 ? modalidadeFiltro : undefined,
      cidade: cidadeFiltro,
      estado: estadoFiltro,
      remuneracaoMin: remuneracaoMinFiltro,
      remuneracaoMax: remuneracaoMaxFiltro,
    };

    // Atualiza a URL com os filtros (apenas para o estado inicial/aplicação de filtro)
    const newSearchParams = new URLSearchParams();
    if (currentFilters.titulo) newSearchParams.append('titulo', currentFilters.titulo);
    if (currentFilters.cidade) newSearchParams.append('cidade', currentFilters.cidade);
    if (currentFilters.estado) newSearchParams.append('estado', currentFilters.estado);
    if (currentFilters.remuneracaoMin !== '' && currentFilters.remuneracaoMin !== null) newSearchParams.append('remuneracaoMin', String(currentFilters.remuneracaoMin));
    if (currentFilters.remuneracaoMax !== '' && currentFilters.remuneracaoMax !== null) newSearchParams.append('remuneracaoMax', String(currentFilters.remuneracaoMax));
    currentFilters.tipo?.forEach(t => newSearchParams.append('tipo', t));
    currentFilters.modalidade?.forEach(m => newSearchParams.append('modalidade', m));
    newSearchParams.append('page', String(currentPage));

    // Remove a página dos searchParams se for 1, para manter a URL limpa
    if (currentPage === 1) {
      newSearchParams.delete('page');
    }

    setSearchParams(newSearchParams, { replace: true });

    // Busca as vagas com os filtros atuais
    fetchVagas(currentPage, currentFilters);
  }, [
    currentPage,
    fetchVagas,
    filterQuery,
    tipoFiltro,
    modalidadeFiltro,
    cidadeFiltro,
    estadoFiltro,
    remuneracaoMinFiltro,
    remuneracaoMaxFiltro,
  ]); // Dependências controlam quando a busca deve ser feita
  // CORREÇÃO: Adicionado 'setSearchParams' ao array de dependências para seguir as regras do React Hooks.

  // Handlers para o formulário de busca/filtro
  const handleFilterChange = (event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = event.target;
    if (name === 'titulo') setFilterQuery(value);
    if (name === 'cidade') setCidadeFiltro(value);
    if (name === 'estado') setEstadoFiltro(value);
    if (name === 'remuneracaoMin') setRemuneracaoMinFiltro(value === '' ? '' : Number(value));
    if (name === 'remuneracaoMax') setRemuneracaoMaxFiltro(value === '' ? '' : Number(value));
    setCurrentPage(1); // Volta para a primeira página ao mudar o filtro
  };

  const handleCheckboxChange = (group: 'tipo' | 'modalidade', value: string) => {
    const setter = group === 'tipo' ? setTipoFiltro : setModalidadeFiltro;
    const currentArray = group === 'tipo' ? tipoFiltro : modalidadeFiltro;

    setter(prev =>
      prev.includes(value)
        ? prev.filter(item => item !== value)
        : [...prev, value]
    );
    setCurrentPage(1);
  };

  const handleApplyFilters = (e: React.FormEvent) => {
    e.preventDefault();
    setCurrentPage(1); // Garante que a busca inicie na página 1 com os novos filtros
    // A chamada para fetchVagas ocorrerá no useEffect devido à mudança de currentPage para 1
    // ou se filterQuery/outros estados forem modificados antes, o que dispara um efeito secundário.
  };

  const handleClearFilters = () => {
    setFilterQuery('');
    setTipoFiltro([]);
    setModalidadeFiltro([]);
    setCidadeFiltro('');
    setEstadoFiltro('');
    setRemuneracaoMinFiltro('');
    setRemuneracaoMaxFiltro('');
    setCurrentPage(1); // Dispara a busca com filtros limpos
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
    window.scrollTo({ top: 0, behavior: 'smooth' }); // Rola para o topo da página
  };

  const renderPagination = () => {
    if (!meta || meta.total <= meta.per_page) return null;

    const totalPages = meta.last_page;
    const pages = [];
    const maxPagesToShow = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

    if (endPage - startPage + 1 < maxPagesToShow) {
      startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }

    // Botão Anterior
    pages.push(
      <button
        key="prev"
        onClick={() => handlePageChange(currentPage - 1)}
        disabled={currentPage === 1}
        className="px-4 py-2 mx-1 border rounded-lg bg-white disabled:opacity-50 hover:bg-gray-100 transition-colors"
      >
        Anterior
      </button>
    );

    // Renderiza páginas
    for (let i = startPage; i <= endPage; i++) {
      pages.push(
        <button
          key={i}
          onClick={() => handlePageChange(i)}
          className={`px-4 py-2 mx-1 border rounded-lg transition-colors ${i === currentPage ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-100'}`}
        >
          {i}
        </button>
      );
    }

    // Botão Próximo
    pages.push(
      <button
        key="next"
        onClick={() => handlePageChange(currentPage + 1)}
        disabled={currentPage === totalPages}
        className="px-4 py-2 mx-1 border rounded-lg bg-white disabled:opacity-50 hover:bg-gray-100 transition-colors"
      >
        Próximo
      </button>
    );

    return <div className="flex justify-center mt-8">{pages}</div>;
  };

  const FilterPanel: React.FC = () => (
    <div className="space-y-6">
      {/* Filtro de Tipo de Vaga */}
      <div className="bg-white p-4 rounded-lg shadow-md border border-gray-100">
        <h3 className="font-semibold text-lg text-indigo-700 mb-3 flex items-center">
          <Briefcase className="w-5 h-5 mr-2" /> Tipo de Vaga
        </h3>
        {tiposVaga.map(tipo => (
          <div key={tipo} className="flex items-center mb-2">
            <input
              type="checkbox"
              id={`tipo-${tipo}`}
              checked={tipoFiltro.includes(tipo)}
              onChange={() => handleCheckboxChange('tipo', tipo)}
              className="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500"
            />
            <label htmlFor={`tipo-${tipo}`} className="ml-2 text-sm font-medium text-gray-700 cursor-pointer">
              {tipo}
            </label>
          </div>
        ))}
      </div>

      {/* Filtro de Modalidade */}
      <div className="bg-white p-4 rounded-lg shadow-md border border-gray-100">
        <h3 className="font-semibold text-lg text-indigo-700 mb-3 flex items-center">
          <Briefcase className="w-5 h-5 mr-2" /> Modalidade
        </h3>
        {modalidadesVaga.map(modalidade => (
          <div key={modalidade} className="flex items-center mb-2">
            <input
              type="checkbox"
              id={`modalidade-${modalidade}`}
              checked={modalidadeFiltro.includes(modalidade)}
              onChange={() => handleCheckboxChange('modalidade', modalidade)}
              className="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500"
            />
            <label htmlFor={`modalidade-${modalidade}`} className="ml-2 text-sm font-medium text-gray-700 cursor-pointer">
              {modalidade}
            </label>
          </div>
        ))}
      </div>

      {/* Filtro de Localização */}
      <div className="bg-white p-4 rounded-lg shadow-md border border-gray-100">
        <h3 className="font-semibold text-lg text-indigo-700 mb-3 flex items-center">
          <MapPin className="w-5 h-5 mr-2" /> Localização
        </h3>
        <input
          type="text"
          name="cidade"
          placeholder="Cidade (ex: São Paulo)"
          value={cidadeFiltro}
          onChange={handleFilterChange}
          className="w-full p-2 border border-gray-300 rounded-lg mb-3 focus:ring-indigo-500 focus:border-indigo-500"
        />
        <select
          name="estado"
          value={estadoFiltro}
          onChange={handleFilterChange}
          className="w-full p-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option value="">Selecione o Estado</option>
          {estadosBrasileiros.map(estado => (
            <option key={estado} value={estado}>
              {estado}
            </option>
          ))}
        </select>
      </div>

      {/* Filtro de Remuneração */}
      <div className="bg-white p-4 rounded-lg shadow-md border border-gray-100">
        <h3 className="font-semibold text-lg text-indigo-700 mb-3">Remuneração (R$)</h3>
        <div className="flex space-x-2">
          <input
            type="number"
            name="remuneracaoMin"
            placeholder="Mínimo"
            value={remuneracaoMinFiltro === null ? '' : remuneracaoMinFiltro}
            onChange={handleFilterChange}
            min="0"
            className="w-1/2 p-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
          />
          <input
            type="number"
            name="remuneracaoMax"
            placeholder="Máximo"
            value={remuneracaoMaxFiltro === null ? '' : remuneracaoMaxFiltro}
            onChange={handleFilterChange}
            min="0"
            className="w-1/2 p-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>
      </div>

      {/* Botões de Ação para Mobile/Form */}
      <div className="flex flex-col space-y-2 lg:hidden">
        <button
          onClick={handleApplyFilters}
          className="w-full bg-indigo-600 text-white font-semibold py-2 rounded-lg hover:bg-indigo-700 transition-colors"
        >
          Aplicar Filtros
        </button>
        <button
          onClick={handleClearFilters}
          className="w-full bg-red-500 text-white font-semibold py-2 rounded-lg hover:bg-red-600 transition-colors"
        >
          Limpar Filtros
        </button>
      </div>
    </div>
  );

  return (
    <div className="min-h-screen flex flex-col bg-gray-50">
      <Header />
      <main className="flex-grow container mx-auto px-4 py-8 max-w-7xl">
        <h1 className="text-4xl font-extrabold text-gray-900 mb-8 text-center sm:text-left">
          Encontre Sua Próxima Oportunidade
        </h1>

        {/* Barra de Pesquisa Principal */}
        <form onSubmit={handleApplyFilters} className="mb-8 bg-white p-4 rounded-xl shadow-lg border border-indigo-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="flex-grow flex items-center border border-gray-300 rounded-lg overflow-hidden bg-gray-50 focus-within:ring-2 focus-within:ring-indigo-500 transition-shadow">
              <Search className="w-5 h-5 text-gray-400 ml-3" />
              <input
                type="text"
                name="titulo"
                placeholder="Buscar por título, área ou palavras-chave"
                value={filterQuery}
                onChange={handleFilterChange}
                className="w-full p-3 bg-transparent text-gray-800 placeholder-gray-500 focus:outline-none"
              />
            </div>
            <button
              type="submit"
              className="flex items-center justify-center sm:w-auto px-6 py-3 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition-colors shadow-md"
            >
              <Search className="w-5 h-5 mr-2 sm:mr-0 sm:hidden" />
              <span className="hidden sm:inline">Buscar</span>
            </button>
          </div>
        </form>

        {/* Layout Principal - Filtros e Resultados */}
        <div className="flex flex-col lg:flex-row gap-8">
          {/* Coluna de Filtros (Desktop) */}
          <aside className="hidden lg:block lg:w-1/4">
            <div className="sticky top-4">
              <h2 className="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                <Filter className="w-6 h-6 mr-2 text-indigo-600" /> Filtros
              </h2>
              <FilterPanel />
              <div className="mt-4 flex flex-col space-y-2">
                <button
                  onClick={handleClearFilters}
                  className="w-full bg-red-500 text-white font-semibold py-2 rounded-lg hover:bg-red-600 transition-colors"
                >
                  Limpar Filtros
                </button>
              </div>
            </div>
          </aside>

          {/* Coluna de Filtros (Mobile Toggle) */}
          <div className="lg:hidden mb-4">
            <button
              onClick={() => setIsFilterOpen(!isFilterOpen)}
              className="w-full flex justify-center items-center py-2 px-4 bg-indigo-100 text-indigo-700 font-semibold rounded-lg hover:bg-indigo-200 transition-colors"
            >
              <Filter className="w-5 h-5 mr-2" />
              Filtros
              {isFilterOpen ? <ChevronUp className="w-5 h-5 ml-2" /> : <ChevronDown className="w-5 h-5 ml-2" />}
            </button>
            {isFilterOpen && (
              <div className="mt-4 border border-gray-200 p-4 rounded-lg bg-white shadow-inner">
                <FilterPanel />
              </div>
            )}
          </div>

          {/* Coluna de Resultados */}
          <section className="lg:w-3/4">
            <div className="mb-6">
              {loading && <p className="text-center text-indigo-600 font-semibold">Buscando vagas...</p>}
              {!loading && meta && (
                <p className="text-lg text-gray-700 font-medium">
                  Encontradas <span className="font-bold text-indigo-600">{meta.total}</span> vagas
                  {filterQuery && ` para "${filterQuery}"`}
                </p>
              )}
            </div>

            {/* Lista de Vagas */}
            <div className="space-y-6">
              {!loading && vagas.length > 0 ? (
                vagas.map(vaga => (
                  <VagaCard
                    key={vaga.id}
                    id={vaga.id}
                    title={vaga.titulo}
                    institution={vaga.instituicao.nome}
                    city={vaga.cidade}
                    regime={vaga.tipo}
                    linkTo={`/vagas/${vaga.id}`}
                  />
                ))
              ) : !loading && vagas.length === 0 ? (
                <div className="flex flex-col items-center justify-center p-12 bg-white rounded-xl shadow-lg border border-gray-200">
                  <Frown className="w-16 h-16 text-indigo-400 mb-4" />
                  <p className="text-xl font-semibold text-gray-700">Nenhuma vaga encontrada.</p>
                  <p className="text-gray-500 text-center mt-2">Tente ajustar os termos de busca ou limpar os filtros.</p>
                  <button
                    onClick={handleClearFilters}
                    className="mt-4 bg-indigo-500 text-white font-semibold py-2 px-4 rounded-lg hover:bg-indigo-600 transition-colors"
                  >
                    Limpar Todos os Filtros
                  </button>
                </div>
              ) : null}
            </div>

            {/* Paginação */}
            {!loading && vagas.length > 0 && renderPagination()}
          </section>
        </div>
      </main>
      <Footer />
    </div>
  );
};

export default BuscarVagasPage;
