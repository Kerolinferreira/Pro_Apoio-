import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { Building, MapPin, Loader2, AlertCircle, ArrowLeft } from 'lucide-react';
import api from '../services/api';
import { useToast } from '../components/Toast';
import { logger } from '../utils/logger';

/**
 * Página pública de perfil de instituição.
 * Exibe informações básicas da instituição para visitantes.
 * Endpoint: GET /instituicoes/{id}
 */

interface InstituicaoPublica {
    id: number;
    razao_social: string;
    nome_fantasia: string;
    codigo_inep: string | null;
    cidade: string | null;
    estado: string | null;
}

const InstituicaoPublicaPage: React.FC = () => {
    const { id } = useParams<{ id: string }>();
    const toast = useToast();
    const [instituicao, setInstituicao] = useState<InstituicaoPublica | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchInstituicao = async () => {
            if (!id) {
                setError('ID da instituição não fornecido.');
                setLoading(false);
                return;
            }

            try {
                setLoading(true);
                setError(null);
                const response = await api.get(`/instituicoes/${id}`);
                setInstituicao(response.data);
            } catch (err: any) {
                logger.error('Erro ao carregar instituição:', err);
                const status = err.response?.status;

                if (status === 404) {
                    setError('Instituição não encontrada.');
                } else {
                    setError('Erro ao carregar informações da instituição. Tente novamente mais tarde.');
                }
                toast.error('Não foi possível carregar os dados da instituição.');
            } finally {
                setLoading(false);
            }
        };

        fetchInstituicao();
    }, [id, toast]);

    return (
        <div className="page-container">
            <Header />
            <main className="main-content">
                {/* Botão Voltar */}
                <div className="mb-lg">
                    <Link to="/vagas" className="btn-secondary btn-icon btn-sm">
                        <ArrowLeft size={18} />
                        Voltar para Vagas
                    </Link>
                </div>

                {loading && (
                    <div className="flex-center-column" style={{ minHeight: '400px' }}>
                        <Loader2 size={40} className="icon-spin text-brand-color" />
                        <p className="text-muted mt-md">Carregando informações...</p>
                    </div>
                )}

                {error && !loading && (
                    <div className="card border-error">
                        <div className="flex-center-column py-xl">
                            <AlertCircle size={48} className="text-error mb-md" />
                            <h2 className="heading-secondary mb-sm">{error}</h2>
                            <Link to="/vagas" className="btn-primary mt-md">
                                Ver Vagas Disponíveis
                            </Link>
                        </div>
                    </div>
                )}

                {instituicao && !loading && !error && (
                    <div className="card">
                        {/* Header da Instituição */}
                        <div className="card-header bg-brand-light">
                            <div className="flex-start-center gap-md">
                                <div className="avatar-lg bg-white">
                                    <Building size={40} className="text-brand-color" />
                                </div>
                                <div>
                                    <h1 className="heading-primary mb-xs">
                                        {instituicao.nome_fantasia || instituicao.razao_social}
                                    </h1>
                                    {instituicao.nome_fantasia && instituicao.razao_social &&
                                     instituicao.nome_fantasia !== instituicao.razao_social && (
                                        <p className="text-sm text-muted">
                                            Razão Social: {instituicao.razao_social}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Corpo do Card */}
                        <div className="card-body">
                            {/* Informações Básicas */}
                            <div className="grid-2-col-lg gap-lg">
                                {/* Localização */}
                                {(instituicao.cidade || instituicao.estado) && (
                                    <div className="flex-start-start gap-sm">
                                        <MapPin size={20} className="text-brand-color mt-xs" />
                                        <div>
                                            <h3 className="heading-quaternary mb-xs">Localização</h3>
                                            <p className="text-base">
                                                {instituicao.cidade && instituicao.estado
                                                    ? `${instituicao.cidade}, ${instituicao.estado}`
                                                    : instituicao.cidade || instituicao.estado || 'Não informado'}
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {/* Código INEP */}
                                {instituicao.codigo_inep && (
                                    <div className="flex-start-start gap-sm">
                                        <Building size={20} className="text-brand-color mt-xs" />
                                        <div>
                                            <h3 className="heading-quaternary mb-xs">Código INEP</h3>
                                            <p className="text-base">{instituicao.codigo_inep}</p>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Divider */}
                            <div className="border-top-divider my-lg"></div>

                            {/* Ações */}
                            <div className="flex-center-column gap-md">
                                <p className="text-muted text-center">
                                    Quer conhecer as vagas disponíveis nesta instituição?
                                </p>
                                <Link
                                    to={`/vagas?instituicao=${instituicao.id}`}
                                    className="btn-primary btn-lg"
                                >
                                    Ver Vagas Disponíveis
                                </Link>
                            </div>
                        </div>
                    </div>
                )}
            </main>
            <Footer />
        </div>
    );
};

export default InstituicaoPublicaPage;
