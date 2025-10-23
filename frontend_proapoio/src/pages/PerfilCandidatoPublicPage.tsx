import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import api from '../services/api'; // Assuming standard service location
import Header from '../components/Header'; // Assuming common components
import Footer from '../components/Footer'; // Assuming common components
import {
    Container,
    Title,
    Section,
    InfoBlock,
    ErrorAlert,
    LoadingSpinner,
    // Add other relevant components like Avatar, Button if necessary
} from '../components/ui'; // Placeholder for assumed UI components

// Define types based on common API responses
interface CandidatoPublico {
    id: number;
    nome_completo: string;
    escolaridade: string;
    bio: string;
    deficiencias: { nome: string }[];
    experiencias_pessoais: { descricao: string }[];
    // Add other public fields like cidade, estado, foto_url
    cidade?: string;
    estado?: string;
    foto_url?: string;
}

const PerfilCandidatoPublicPage: React.FC = () => {
    const { id } = useParams<{ id: string }>();
    const [candidato, setCandidato] = useState<CandidatoPublico | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchCandidato = async () => {
            if (!id) {
                setError("ID do candidato não fornecido.");
                setLoading(false);
                return;
            }
            try {
                // CORREÇÃO APLICADA: Rota alterada de /candidatos/public/${id} para /candidatos/${id}
                // Conforme a documentação de API, o endpoint público é /candidatos/{id}.
                const response = await api.get(`/candidatos/${id}`); 
                setCandidato(response.data);
                setLoading(false);
            } catch (err) {
                console.error("Erro ao buscar perfil público:", err);
                setError("Não foi possível carregar o perfil do candidato.");
                setLoading(false);
            }
        };
        fetchCandidato();
    }, [id]);

    if (loading) {
        return (
            <div className="flex justify-center items-center h-screen">
                <LoadingSpinner />
            </div>
        );
    }

    if (error) {
        return <ErrorAlert message={error} />;
    }

    if (!candidato) {
        return <ErrorAlert message="Perfil não encontrado." />;
    }

    return (
        <div className="min-h-screen bg-gray-50">
            <Header />
            <Container className="py-10">
                <div className="bg-white shadow-xl rounded-lg p-8">
                    <div className="flex items-center space-x-6">
                        {/* Assuming an Avatar component for the profile picture */}
                        <div className="w-24 h-24 bg-gray-200 rounded-full overflow-hidden flex-shrink-0">
                             {candidato.foto_url ? (
                                <img src={candidato.foto_url} alt={`Foto de ${candidato.nome_completo}`} className="w-full h-full object-cover" />
                            ) : (
                                <svg className="w-full h-full text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 20.993V24H0v-3.007a12 12 0 0112-12c4.418 0 8.582 1.993 12 5.007zM12 9a6 6 0 100-12 6 6 0 000 12z" />
                                </svg>
                            )}
                        </div>
                        <div>
                            <Title level={1} className="text-3xl font-bold text-gray-800">
                                {candidato.nome_completo}
                            </Title>
                            <p className="text-gray-600">
                                {candidato.cidade}, {candidato.estado}
                            </p>
                        </div>
                    </div>

                    <Section title="Sobre Mim" className="mt-8">
                        <p className="text-gray-700 whitespace-pre-wrap">
                            {candidato.bio || "Nenhuma biografia fornecida."}
                        </p>
                    </Section>

                    <Section title="Deficiências e Condições" className="mt-8">
                        <InfoBlock label="Escolaridade">
                            {candidato.escolaridade || "Não informado"}
                        </InfoBlock>
                        <InfoBlock label="Deficiências Associadas" className="mt-4">
                            {candidato.deficiencias && candidato.deficiencias.length > 0 ? (
                                <div className="flex flex-wrap gap-2">
                                    {candidato.deficiencias.map((d, index) => (
                                        <span key={index} className="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                                            {d.nome}
                                        </span>
                                    ))}
                                </div>
                            ) : (
                                <p>Nenhuma deficiência listada.</p>
                            )}
                        </InfoBlock>
                    </Section>

                    <Section title="Experiências Pessoais Relevantes" className="mt-8">
                        {candidato.experiencias_pessoais && candidato.experiencias_pessoais.length > 0 ? (
                            <ul className="list-disc list-inside space-y-2 text-gray-700">
                                {candidato.experiencias_pessoais.map((exp, index) => (
                                    <li key={index}>{exp.descricao}</li>
                                ))}
                            </ul>
                        ) : (
                            <p>Nenhuma experiência pessoal relevante listada.</p>
                        )}
                    </Section>

                    {/* Button to contact or apply - assuming this page is accessed by Institutions */}
                    {/* Placeholder for the Contact Button, usually handled by a separate modal/logic */}
                    <div className="mt-10 pt-6 border-t border-gray-100">
                        <p className="text-sm text-gray-500">Para iniciar uma proposta, por favor, utilize a área logada da Instituição.</p>
                        {/* Example Button component placeholder */}
                        {/* <Button variant="primary" onClick={() => alert('Implementar lógica de contato/proposta')}>
                            Iniciar Proposta
                        </Button> */}
                    </div>
                </div>
            </Container>
            <Footer />
        </div>
    );
};

export default PerfilCandidatoPublicPage;