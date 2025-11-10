import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import Header from '../components/Header';
import Footer from '../components/Footer';
import VagaCard from '../components/VagaCard';
import { Loader2, AlertTriangle, Heart } from 'lucide-react';
import { logger } from '../utils/logger';

// Tipos baseados no que o VagaCard espera e no que a API deve retornar
interface VagaSalva {
    id: number;
    titulo: string;
    instituicao: {
        nome_fantasia: string;
    };
    cidade: string;
    regime_contratacao: string; // ou o campo correto que vem da API
}

const LoadingSpinner: React.FC = () => (
    <div className="text-center py-xl">
        <Loader2 className="icon-spin text-brand-color mb-sm mx-auto" size={32} />
        <p className="text-info">Carregando vagas salvas...</p>
    </div>
);

const ErrorAlert: React.FC<{ message: string }> = ({ message }) => (
    <div className="alert alert-error text-center my-xl">
        <p className="title-md">{message}</p>
    </div>
);

const EmptyState: React.FC = () => (
    <div className="alert alert-warning text-center my-xl">
        <Heart size={32} className="mb-sm mx-auto" />
        <h2 className="title-lg">Nenhuma vaga salva ainda</h2>
        <p className="text-base text-muted mt-xs">
            Que tal <Link to="/vagas" className="btn-link">explorar algumas vagas</Link> e salvar as que você mais gostar?
        </p>
    </div>
);

const VagasSalvasPage: React.FC = () => {
    const [vagas, setVagas] = useState<VagaSalva[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchVagasSalvas = async () => {
            setLoading(true);
            setError(null);
            try {
                // GET /candidatos/me/vagas-salvas
                const response = await api.get('/candidatos/me/vagas-salvas');
                setVagas(response.data.data || response.data); // Ajuste conforme a estrutura da sua API
            } catch (err) {
                logger.error("Erro ao buscar vagas salvas:", err);
                setError("Não foi possível carregar suas vagas salvas. Tente novamente mais tarde.");
            } finally {
                setLoading(false);
            }
        };

        fetchVagasSalvas();
    }, []);

    return (
        <div className="page-wrapper">
            <Header />
            <main className="container py-lg">
                <header className="mb-lg">
                    <h1 className="heading-secondary">Minhas Vagas Salvas</h1>
                    <p className="text-base text-muted">Aqui estão as oportunidades que você guardou para ver mais tarde.</p>
                </header>

                {loading && <LoadingSpinner />}
                {error && <ErrorAlert message={error} />}

                {!loading && !error && (
                    vagas.length > 0 ? (
                        <div className="grid-1-col-sm grid-2-col-lg gap-md">
                            {vagas.map((vaga) => (
                                <VagaCard
                                    key={vaga.id}
                                    id={vaga.id}
                                    title={vaga.titulo}
                                    institution={vaga.instituicao?.nome_fantasia || 'Não informado'}
                                    city={vaga.cidade}
                                    regime={vaga.regime_contratacao}
                                    linkTo={`/vagas/${vaga.id}`}
                                />
                            ))}
                        </div>
                    ) : (
                        <EmptyState />
                    )
                )}
            </main>
            <Footer />
        </div>
    );
};

export default VagasSalvasPage;
