import React, { useState, useEffect } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
// api, useAuth, e componentes de UI não são necessários nesta página, pois ela

const Button = ({ children, onClick, variant = 'primary', type = 'button' }) => (
    <button
        type={type}
        onClick={onClick}
        className={`w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white ${
            variant === 'primary' ? 'bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500' : 'bg-gray-600 hover:bg-gray-700'
        }`}
    >
        {children}
    </button>
);

/**
 * Componente RegisterPage: Agora focado apenas na seleção inicial do tipo de usuário.
 * O redirecionamento é feito para as rotas específicas criadas em App.tsx.
 */
const RegisterPage: React.FC = () => {
    const navigate = useNavigate();
    const location = useLocation();

    // Lógica para capturar o parâmetro 'tipo' da URL (ex: /register?tipo=candidato)
    useEffect(() => {
        const query = new URLSearchParams(location.search);
        const userTypeFromQuery = query.get('tipo');

        if (userTypeFromQuery === 'candidato') {
            navigate('/register/candidato');
        } else if (userTypeFromQuery === 'instituicao') {
            navigate('/register/instituicao');
        }
        // Se não houver 'tipo' ou for inválido, continua na tela de escolha.
    }, [location.search, navigate]);

    // Funções para seleção e redirecionamento
    const handleCandidatoSelect = () => {
        // Redireciona para a rota específica
        navigate('/register/candidato');
    };

    const handleInstituicaoSelect = () => {
        // Redireciona para a rota específica
        navigate('/register/instituicao');
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-100">
            <div className="bg-white p-8 rounded-lg shadow-xl w-full max-w-md text-center">
                <h1 className="text-2xl font-bold mb-6 text-gray-800">Escolha o seu Perfil</h1>
                <p className="text-gray-600 mb-8">Você está se cadastrando como:</p>
                <div className="space-y-4">
                    <Button onClick={handleCandidatoSelect}>
                        Agente de Apoio (Candidato)
                    </Button>
                    <Button onClick={handleInstituicaoSelect} variant="secondary">
                        Instituição de Ensino
                    </Button>
                </div>
                <Link to="/login" className="mt-6 inline-block text-sm text-blue-600 hover:text-blue-800">
                    Já tem conta? Entrar
                </Link>
            </div>
        </div>
    );
};

export default RegisterPage;
