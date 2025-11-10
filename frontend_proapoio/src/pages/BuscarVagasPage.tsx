import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { ChevronDown, ChevronUp, Frown, Filter, Search, MapPin, Briefcase } from 'lucide-react';
import api from '../services/api';
import VagaCard from '../components/VagaCard';
import { useAuth } from '../contexts/AuthContext';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { TIPO_VAGA_OPTIONS, MODALIDADE_VAGA_OPTIONS, ESTADOS_BRASILEIROS } from '../constants/options';
import { logger } from '../utils/logger';

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
    nome_fantasia: string; // Backend retorna nome_fantasia
    razao_social?: string; // Fallback
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

  // Constantes agora importadas de src/constants/options.ts
  const tiposVaga = TIPO_VAGA_OPTIONS;
  const modalidadesVaga = MODALIDADE_VAGA_OPTIONS;
  const estadosBrasileiros = ESTADOS_BRASILEIROS;

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
      logger.error('Erro ao buscar vagas:', error);
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
  ]);

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
    setCurrentPage(1);
  };

  const handleClearFilters = () => {
    setFilterQuery('');
    setTipoFiltro([]);
    setModalidadeFiltro([]);
    setCidadeFiltro('');
    setEstadoFiltro('');
    setRemuneracaoMinFiltro('');
    setRemuneracaoMaxFiltro('');
    setCurrentPage(1);
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
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
        className="btn-secondary btn-sm mx-md"
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
          className={i === currentPage ? 'btn-primary btn-sm mx-md' : 'btn-secondary btn-sm mx-md'}
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
        className="btn-secondary btn-sm mx-md"
      >
        Próximo
      </button>
    );

    return <div className="flex-actions-center mt-xl">{pages}</div>;
  };

  const FilterPanel: React.FC = () => (
    <div className="space-y-lg">
      {/* Filtro de Tipo de Vaga */}
      <div className="card-simple">
        <h3 className="title-md mb-sm flex-group-item">
          <Briefcase size={20} /> Tipo de Vaga
        </h3>
        <div className="space-y-xs">
          {tiposVaga.map(tipo => (
            <label key={tipo} className="checkbox-label">
              <input
                type="checkbox"
                id={`tipo-${tipo}`}
                checked={tipoFiltro.includes(tipo)}
                onChange={() => handleCheckboxChange('tipo', tipo)}
                className="form-checkbox"
              />
              {tipo}
            </label>
          ))}
        </div>
      </div>

      {/* Filtro de Modalidade */}
      <div className="card-simple">
        <h3 className="title-md mb-sm flex-group-item">
          <Briefcase size={20} /> Modalidade
        </h3>
        <div className="space-y-xs">
          {modalidadesVaga.map(modalidade => (
            <label key={modalidade} className="checkbox-label">
              <input
                type="checkbox"
                id={`modalidade-${modalidade}`}
                checked={modalidadeFiltro.includes(modalidade)}
                onChange={() => handleCheckboxChange('modalidade', modalidade)}
                className="form-checkbox"
              />
              {modalidade}
            </label>
          ))}
        </div>
      </div>

      {/* Filtro de Localização */}
      <div className="card-simple">
        <h3 className="title-md mb-sm flex-group-item">
          <MapPin size={20} /> Localização
        </h3>
        <div className="space-y-xs">
          <input
            type="text"
            name="cidade"
            placeholder="Cidade (ex: São Paulo)"
            value={cidadeFiltro}
            onChange={handleFilterChange}
            className="form-input mb-sm"
          />
          <select
            name="estado"
            value={estadoFiltro}
            onChange={handleFilterChange}
            className="form-select"
          >
            <option value="">Selecione o Estado</option>
            {estadosBrasileiros.map(estado => (
              <option key={estado} value={estado}>
                {estado}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Filtro de Remuneração */}
      <div className="card-simple">
        <h3 className="title-md mb-sm">Remuneração (R$)</h3>
        <div className="form-grid-2">
          <input
            type="number"
            name="remuneracaoMin"
            placeholder="Mínimo"
            value={remuneracaoMinFiltro === null ? '' : remuneracaoMinFiltro}
            onChange={handleFilterChange}
            min="0"
            className="form-input"
          />
          <input
            type="number"
            name="remuneracaoMax"
            placeholder="Máximo"
            value={remuneracaoMaxFiltro === null ? '' : remuneracaoMaxFiltro}
            onChange={handleFilterChange}
            min="0"
            className="form-input"
          />
        </div>
      </div>

      {/* Botões de Ação para Mobile/Form */}
      <div className="flex-group hidden-lg">
        <button
          onClick={handleApplyFilters}
          className="btn-primary w-full"
        >
          Aplicar Filtros
        </button>
        <button
          onClick={handleClearFilters}
          className="btn-error w-full"
        >
          Limpar Filtros
        </button>
      </div>
    </div>
  );

  return (
    <div className="page-wrapper">
      <Header />
      <main className="container py-xl">
        <h1 className="heading-primary mb-lg text-center">
          Encontre Sua Próxima Oportunidade
        </h1>

        {/* Barra de Pesquisa Principal */}
        <form onSubmit={handleApplyFilters} className="card mb-lg">
          <div className="flex-group-md-row">
            <div className="form-input-icon-wrapper flex-1">
              <Search size={20} className="form-icon" />
              <input
                type="text"
                name="titulo"
                placeholder="Buscar por título, área ou palavras-chave"
                value={filterQuery}
                onChange={handleFilterChange}
                className="form-input with-icon"
              />
            </div>
            <button
              type="submit"
              className="btn-primary btn-icon"
            >
              <Search size={20} />
              <span className="hidden-sm">Buscar</span>
            </button>
          </div>
        </form>

        {/* Layout Principal - Filtros e Resultados */}
        <div className="grid-2-col-lg">
          {/* Coluna de Filtros (Desktop) */}
          <aside className="hidden-lg">
            <div className="sticky-top">
              <h2 className="heading-secondary mb-md flex-group-item">
                <Filter size={24} /> Filtros
              </h2>
              <FilterPanel />
              <div className="mt-md">
                <button
                  onClick={handleClearFilters}
                  className="btn-error w-full"
                >
                  Limpar Filtros
                </button>
              </div>
            </div>
          </aside>

          {/* Coluna de Filtros (Mobile Toggle) */}
          <div className="show-lg mb-md">
            <button
              onClick={() => setIsFilterOpen(!isFilterOpen)}
              className="btn-secondary w-full btn-icon justify-center"
            >
              <Filter size={20} />
              Filtros
              {isFilterOpen ? <ChevronUp size={20} /> : <ChevronDown size={20} />}
            </button>
            {isFilterOpen && (
              <div className="card-simple mt-md">
                <FilterPanel />
              </div>
            )}
          </div>

          {/* Coluna de Resultados */}
          <section>
            <div className="mb-lg">
              {loading && <p className="text-center text-brand title-md">Buscando vagas...</p>}
              {!loading && meta && (
                <p className="text-lg">
                  Encontradas <span className="title-md text-brand">{meta.total}</span> vagas
                  {filterQuery && ` para "${filterQuery}"`}
                </p>
              )}
            </div>

            {/* Lista de Vagas */}
            <div className="space-y-lg">
              {!loading && vagas.length > 0 ? (
                vagas.map(vaga => (
                  <VagaCard
                    key={vaga.id}
                    id={vaga.id}
                    title={vaga.titulo}
                    institution={vaga.instituicao.nome_fantasia}
                    city={vaga.cidade}
                    regime={vaga.tipo}
                    linkTo={`/vagas/${vaga.id}`}
                  />
                ))
              ) : !loading && vagas.length === 0 ? (
                <div className="card text-center py-xl">
                  <Frown size={64} className="mx-md mb-md text-muted" />
                  <p className="title-lg mb-sm">Nenhuma vaga encontrada.</p>
                  <p className="text-muted mb-md">Tente ajustar os termos de busca ou limpar os filtros.</p>
                  <button
                    onClick={handleClearFilters}
                    className="btn-primary"
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
