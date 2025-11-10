import React, { useEffect, useRef } from 'react';
import { Link, useSearchParams, useNavigate } from 'react-router-dom';
import { User, Building, ArrowLeft, CheckCircle } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import Header from '../components/Header';
import Footer from '../components/Footer';

/**
 * @component RegisterPage
 * @description Página de seleção do tipo de perfil (Candidato ou Instituição) antes do cadastro.
 * Recebe o parâmetro 'success' da URL para exibir feedback após o cadastro bem-sucedido.
 */
export default function RegisterPage() {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const { user } = useAuth();
    const successType = searchParams.get('success'); // 'candidato' ou 'instituicao'
    const successRef = useRef<HTMLDivElement>(null);

    // Redireciona para dashboard se já estiver logado
    useEffect(() => {
        if (user) {
            navigate('/dashboard', { replace: true });
        }
    }, [user, navigate]);

    // Foca no alerta de sucesso após o cadastro
    useEffect(() => {
        if (successType) {
            successRef.current?.focus();
        }
    }, [successType]);

    // Componente de Card de Escolha
    const ChoiceCard: React.FC<{
        title: string;
        description: string;
        linkTo: string;
        icon: React.ReactNode;
    }> = ({ title, description, linkTo, icon }) => (
        <div className="card-choice">
            <Link to={linkTo} className="card-choice-link" aria-label={`Continuar como ${title}`}>
                <div className="card-choice-icon">{icon}</div>
                <h2 className="title-lg mb-xs">{title}</h2>
                <p className="text-sm text-muted">{description}</p>
                
                <span className="btn-primary btn-sm mt-md">
                    Continuar
                </span>
            </Link>
        </div>
    );

    return (
        <div className="page-wrapper">
            <Header />
            <main className="auth-container" aria-labelledby="titulo-escolha-cadastro">
                
                <div className="card-auth-medium">
                    
                    {/* Alerta de Sucesso Pós-Cadastro */}
                    {successType && (
                        <div
                            ref={successRef}
                            tabIndex={-1}
                            role="status"
                            className="alert alert-success mb-lg"
                        >
                            <CheckCircle size={20} className="inline mr-sm" />
                            <span className="font-semibold">
                                Sucesso!
                            </span> Sua conta de {successType === 'candidato' ? 'Agente de Apoio' : 'Instituição'} foi criada. Faça login para acessar o painel.
                        </div>
                    )}
                    
                    <h1
                        id="titulo-escolha-cadastro"
                        className="heading-secondary mb-md text-center"
                    >
                        Quero me cadastrar como:
                    </h1>
                    
                    <p className="text-sm text-muted text-center mb-lg">
                        Escolha o perfil que melhor se encaixa no seu objetivo para iniciar o cadastro.
                    </p>

                    {/* Cartões de Escolha */}
                    <div className="form-grid-2">
                        <ChoiceCard
                            title="Agente de Apoio (Candidato)"
                            description="Busque vagas e ofereça seus serviços a instituições de ensino."
                            linkTo="/register/candidato"
                            icon={<User size={32} />}
                        />
                        <ChoiceCard
                            title="Instituição de Ensino"
                            description="Publique vagas e encontre agentes de apoio qualificados para seus alunos."
                            linkTo="/register/instituicao"
                            icon={<Building size={32} />}
                        />
                    </div>
                    
                    {/* Link para Login */}
                    <div className='mt-lg text-center'>
                        <Link to="/login" className="btn-link text-sm btn-icon" type="button">
                            <ArrowLeft size={16} className="mr-xs" /> Já tenho conta
                        </Link>
                    </div>
                    
                </div>
            </main>
            <Footer />
        </div>
    );
}