import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api'; // Usando o axios configurado do projeto
// Importação dos componentes de UI conforme o padrão
// NOTE: Este arquivo não usa diretamente os componentes nomeados 'Input', 'Select', 'Button'
// mas os adota no JSX para manter a coerência visual e funcional do projeto.

// =========================================================================================
// DEFINIÇÕES E OPÇÕES
// =========================================================================================

// Tipagem corrigida para incluir todos os campos necessários pelo BE Candidato Request
// Campos adicionados: telefone, cpf, data_nascimento
type FormData = {
    nome_completo: string; // Nome completo para exibição
    email: string;
    telefone: string;
    cpf: string;
    data_nascimento: string;
    senha: string;
    confirmarSenha: string;
    cep: string;
    cidade: string;
    estado: string;
    escolaridade: string;
    curso: string;
    instituicao_ensino: string; // Adicionado: Requerido se for nível superior
    experiencia: string;
};

type FieldErrors = Partial<Record<keyof FormData, string>>;

const ESCOLARIDADE_OPCOES = [
    'Fundamental Completo',
    'Médio Completo',
    'Superior Incompleto',
    'Superior Completo',
    'Pós-Graduação',
    'Mestrado',
    'Doutorado',
];

// =========================================================================================
// LÓGICA DO COMPONENTE
// =========================================================================================

export default function RegisterCandidatoPage() {
    const navigate = useNavigate();
    const [formData, setFormData] = useState<FormData>({
        nome_completo: '', // Corrigido para nome_completo
        email: '',
        telefone: '',
        cpf: '',
        data_nascimento: '',
        senha: '',
        confirmarSenha: '',
        cep: '',
        cidade: '',
        estado: '',
        escolaridade: '',
        curso: '',
        instituicao_ensino: '', // Adicionado ao estado
        experiencia: '',
    });

    const [mensagem, setMensagem] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [errors, setErrors] = useState<FieldErrors>({});
    const liveRef = useRef<HTMLDivElement>(null);
    const alertRef = useRef<HTMLDivElement>(null);
    const h1Ref = useRef<HTMLHeadingElement>(null);

    useEffect(() => {
        h1Ref.current?.focus();
    }, []);

    function announce(text: string) {
        if (!liveRef.current) return;
        liveRef.current.textContent = text;
        window.setTimeout(() => {
            if (liveRef.current && liveRef.current.textContent === text) {
                liveRef.current.textContent = '';
            }
        }, 4000);
    }

    function handleChange(
        e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>
    ) {
        const { name, value } = e.target;
        setFormData((prev) => {
            const next = { ...prev, [name]: value };
            // Limpeza de erros por campo ao digitar
            if (errors[name as keyof FormData]) {
                const copy = { ...errors };
                delete copy[name as keyof FormData];
                setErrors(copy);
            }
            return next;
        });
    }

    function senhaValida(s: string) {
        // mínimo 8, ao menos uma letra e um número
        return /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d!@#$%^&*()_+\-={}[\]|:;"'<>,.?/~`]{8,}$/.test(s);
    }

    function validar(): FieldErrors {
        const e: FieldErrors = {};
        if (!formData.nome_completo.trim()) e.nome_completo = 'Informe o nome completo.';
        if (!formData.email.trim()) e.email = 'Informe o e-mail.';
        if (!formData.telefone.trim()) e.telefone = 'Informe o telefone.';
        if (!formData.cpf.trim()) e.cpf = 'Informe o CPF.';
        if (!formData.data_nascimento.trim()) e.data_nascimento = 'Informe a data de nascimento.';
        
        if (!senhaValida(formData.senha))
            e.senha = 'Senha deve ter no mínimo oito caracteres com letras e números.';
        if (formData.senha !== formData.confirmarSenha) e.confirmarSenha = 'As senhas não coincidem.';
        
        const cepDigits = formData.cep.replace(/\D/g, '');
        if (cepDigits.length !== 8) e.cep = 'CEP inválido. Use oito dígitos.';
        if (!formData.cidade.trim()) e.cidade = 'Informe a cidade.';
        if (!formData.estado.trim()) e.estado = 'Informe o estado.';
        if (!formData.escolaridade) e.escolaridade = 'Selecione a escolaridade.';
        
        const exigeCurso = formData.escolaridade.startsWith('Superior') || ['Pós-Graduação', 'Mestrado', 'Doutorado'].includes(formData.escolaridade);
        if (exigeCurso && !formData.curso.trim()) e.curso = 'Informe o curso.';
        if (exigeCurso && !formData.instituicao_ensino.trim()) e.instituicao_ensino = 'Informe a instituição de ensino.';
        
        if (!formData.experiencia.trim()) e.experiencia = 'Descreva ao menos uma experiência.';
        return e;
    }

    async function handleCepBlur() {
        const digits = formData.cep.replace(/\D/g, '');
        if (digits.length !== 8) {
            setErrors((prev) => ({ ...prev, cep: 'CEP inválido. Use oito dígitos.' }));
            announce('CEP inválido. Use oito dígitos.');
            return;
        }
        try {
            // Usando a API de CEP pública
            const res = await fetch(`https://viacep.com.br/ws/${digits}/json/`);
            const data = await res.json();
            if (data?.erro) {
                setErrors((prev) => ({ ...prev, cep: 'CEP não encontrado.' }));
                announce('CEP não encontrado.');
                return;
            }
            setFormData((prev) => ({
                ...prev,
                cidade: data.localidade || prev.cidade,
                estado: data.uf || prev.estado,
            }));
            announce('Endereço preenchido pelo CEP.');
        } catch {
            announce('Falha ao consultar CEP.');
        }
    }

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        setMensagem(null);

        const eMap = validar();
        setErrors(eMap);
        if (Object.keys(eMap).length > 0) {
            setMensagem('Corrija os campos destacados.');
            setTimeout(() => alertRef.current?.focus(), 0);
            return;
        }

        setSubmitting(true);
        try {
            // Montando o payload para o Laravel API
            const payload = {
                // Mapeamento: 'nome_completo' do front -> 'nome' na tabela User do BE
                nome: formData.nome_completo,
                email: formData.email,
                telefone: formData.telefone,
                cpf: formData.cpf,
                data_nascimento: formData.data_nascimento,
                senha: formData.senha,
                // Campo 'confirmar_senha' é gerenciado pelo Laravel Request (confirmed)
                senha_confirmation: formData.confirmarSenha, 
                // Mapeamento: Endereço
                cep: formData.cep,
                cidade: formData.cidade,
                estado: formData.estado,
                // Mapeamento: Candidato Info
                nivel_escolaridade: formData.escolaridade, // O BE espera 'nivel_escolaridade'
                curso_superior: formData.curso, // O BE espera 'curso_superior'
                instituicao_ensino: formData.instituicao_ensino, // O BE espera 'instituicao_ensino'
                experiencia: formData.experiencia,
            };

            // Usando a API configurada (axios)
            const resposta = await api.post('/auth/register/candidato', payload);

            if (resposta.status === 201 || resposta.status === 200) {
                setMensagem('Cadastro realizado com sucesso.');
                // Limpar o formulário e redirecionar
                setFormData(prev => ({ 
                    ...prev, 
                    nome_completo: '', email: '', telefone: '', cpf: '', data_nascimento: '',
                    senha: '', confirmarSenha: '', cep: '', cidade: '', estado: '',
                    escolaridade: '', curso: '', instituicao_ensino: '', experiencia: ''
                }));
                setErrors({});
                announce('Cadastro realizado com sucesso.');
                navigate('/login?success=candidato'); // Redireciona após sucesso
            } else {
                 // Tratamento de erro básico para status que não são 2xx
                 setMensagem('Erro inesperado no servidor. Tente novamente.');
            }
        } catch (error: any) {
            console.error('Erro de requisição:', error);
            let erroMsg = 'Falha na comunicação com o servidor.';

            if (error.response?.data?.errors) {
                // Erros de Validação do Laravel (422 Unprocessable Entity)
                const laravelErrors = error.response.data.errors;
                const fieldErrors: FieldErrors = {};
                for (const key in laravelErrors) {
                    // Mapeia erros de volta para os campos do Front-end
                    const frontKey = key === 'nome' ? 'nome_completo' : key;
                    const msg = laravelErrors[key][0];
                    if (frontKey in formData) {
                         // Adapta o nome da chave do back-end para o estado do front-end
                        (fieldErrors as any)[frontKey] = msg;
                    } else {
                        // Mensagem de erro geral (ex: email já existe)
                        erroMsg = msg;
                    }
                }
                setErrors(fieldErrors);
                setMensagem(Object.keys(fieldErrors).length > 0 ? 'Corrija os campos destacados.' : erroMsg);
            } else if (error.message) {
                 erroMsg = error.message;
            }
            
            setMensagem(erroMsg);
            announce(erroMsg);
            setTimeout(() => alertRef.current?.focus(), 0);
        } finally {
            setSubmitting(false);
        }
    }

    const exigeCurso = formData.escolaridade.startsWith('Superior') || ['Pós-Graduação', 'Mestrado', 'Doutorado'].includes(formData.escolaridade);

    return (
        <main
            id="conteudo"
            className="mx-auto max-w-2xl px-4 py-10"
            aria-labelledby="titulo-cadastro-candidato"
            role="main"
        >
            {/* Regiões vivas e alerta focável para leitores de tela */}
            <div ref={liveRef} className="sr-only" aria-live="polite" aria-atomic="true" />
            
            {mensagem && (
                <div
                    ref={alertRef}
                    role="alert"
                    tabIndex={-1}
                    className={`mb-4 p-3 rounded outline-none ${
                        mensagem.includes('sucesso')
                            ? 'bg-green-100 text-green-800'
                            : 'bg-red-100 text-red-800'
                    }`}
                >
                    {mensagem}
                </div>
            )}

            <header className="text-center mb-6">
                <h1
                    id="titulo-cadastro-candidato"
                    className="text-3xl font-bold"
                    tabIndex={-1}
                    ref={h1Ref}
                >
                    Cadastro de Candidato
                </h1>
                <p className="mt-2 text-zinc-700">Preencha suas informações para criar seu perfil.</p>
            </header>

            <form
                onSubmit={handleSubmit}
                className="space-y-4"
                aria-label="Formulário de cadastro de candidato"
                noValidate
            >
                {/* DADOS PESSOAIS */}
                <div>
                    <label htmlFor="nome_completo" className="block font-semibold">
                        Nome completo
                    </label>
                    <input
                        id="nome_completo"
                        name="nome_completo"
                        type="text"
                        required
                        aria-invalid={!!errors.nome_completo}
                        aria-describedby={errors.nome_completo ? 'erro-nome_completo' : undefined}
                        value={formData.nome_completo}
                        onChange={handleChange}
                        className="mt-1 w-full rounded border px-3 py-2"
                    />
                    {errors.nome_completo && (
                        <p id="erro-nome_completo" className="mt-1 text-sm text-red-700">
                            {errors.nome_completo}
                        </p>
                    )}
                </div>
                
                <div className="grid md:grid-cols-2 gap-4">
                    <div>
                        <label htmlFor="email" className="block font-semibold">
                            E-mail
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            aria-invalid={!!errors.email}
                            aria-describedby={errors.email ? 'erro-email' : 'ajuda-email'}
                            value={formData.email}
                            onChange={handleChange}
                            className="mt-1 w-full rounded border px-3 py-2"
                            autoComplete="email"
                        />
                        {errors.email && (
                            <p id="erro-email" className="mt-1 text-sm text-red-700">
                                {errors.email}
                            </p>
                        )}
                    </div>
                    <div>
                        <label htmlFor="telefone" className="block font-semibold">
                            Telefone
                        </label>
                        <input
                            id="telefone"
                            name="telefone"
                            type="tel"
                            required
                            aria-invalid={!!errors.telefone}
                            aria-describedby={errors.telefone ? 'erro-telefone' : undefined}
                            value={formData.telefone}
                            onChange={handleChange}
                            className="mt-1 w-full rounded border px-3 py-2"
                        />
                        {errors.telefone && (
                            <p id="erro-telefone" className="mt-1 text-sm text-red-700">
                                {errors.telefone}
                            </p>
                        )}
                    </div>
                </div>

                <div className="grid md:grid-cols-2 gap-4">
                    <div>
                        <label htmlFor="cpf" className="block font-semibold">
                            CPF
                        </label>
                        <input
                            id="cpf"
                            name="cpf"
                            type="text"
                            required
                            aria-invalid={!!errors.cpf}
                            aria-describedby={errors.cpf ? 'erro-cpf' : undefined}
                            value={formData.cpf}
                            onChange={handleChange}
                            className="mt-1 w-full rounded border px-3 py-2"
                        />
                        {errors.cpf && (
                            <p id="erro-cpf" className="mt-1 text-sm text-red-700">
                                {errors.cpf}
                            </p>
                        )}
                    </div>
                    <div>
                        <label htmlFor="data_nascimento" className="block font-semibold">
                            Data de Nascimento
                        </label>
                        <input
                            id="data_nascimento"
                            name="data_nascimento"
                            type="date"
                            required
                            aria-invalid={!!errors.data_nascimento}
                            aria-describedby={errors.data_nascimento ? 'erro-data_nascimento' : undefined}
                            value={formData.data_nascimento}
                            onChange={handleChange}
                            className="mt-1 w-full rounded border px-3 py-2"
                        />
                        {errors.data_nascimento && (
                            <p id="erro-data_nascimento" className="mt-1 text-sm text-red-700">
                                {errors.data_nascimento}
                            </p>
                        )}
                    </div>
                </div>


                {/* SENHA */}
                <fieldset className="grid md:grid-cols-2 gap-4" aria-describedby="ajuda-senha">
                    <legend className="sr-only">Definição de senha</legend>
                    <div>
                        <label htmlFor="senha" className="block font-semibold">
                            Senha
                        </label>
                        <input
                            id="senha"
                            name="senha"
                            type="password"
                            required
                            aria-invalid={!!errors.senha}
                            aria-describedby={errors.senha ? 'erro-senha' : 'ajuda-senha'}
                            value={formData.senha}
                            onChange={handleChange}
                            className="mt-1 w-full rounded border px-3 py-2"
                            autoComplete="new-password"
                            inputMode="text"
                        />
                        {errors.senha ? (
                            <p id="erro-senha" className="mt-1 text-sm text-red-700">
                                {errors.senha}
                            </p>
                        ) : (
                            <p id="ajuda-senha" className="mt-1 text-xs text-zinc-600">
                                Mínimo de oito caracteres com letras e números.
                            </p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="confirmarSenha" className="block font-semibold">
                            Confirmar senha
                        </label>
                        <input
                            id="confirmarSenha"
                            name="confirmarSenha"
                            type="password"
                            required
                            aria-invalid={!!errors.confirmarSenha}
                            aria-describedby={errors.confirmarSenha ? 'erro-confirmar' : undefined}
                            value={formData.confirmarSenha}
                            onChange={handleChange}
                            className="mt-1 w-full rounded border px-3 py-2"
                            autoComplete="new-password"
                        />
                        {errors.confirmarSenha && (
                            <p id="erro-confirmar" className="mt-1 text-sm text-red-700">
                                {errors.confirmarSenha}
                            </p>
                        )}
                    </div>
                </fieldset>

                {/* ENDEREÇO */}
                <div>
                    <label htmlFor="cep" className="block font-semibold">
                        CEP
                    </label>
                    <input
                        id="cep"
                        name="cep"
                        type="text"
                        inputMode="numeric"
                        maxLength={9}
                        placeholder="00000-000"
                        required
                        aria-invalid={!!errors.cep}
                        aria-describedby={errors.cep ? 'erro-cep' : 'ajuda-cep'}
                        value={formData.cep}
                        onChange={(e) => {
                            const raw = e.target.value.replace(/\D/g, '').slice(0, 8);
                            const masked = raw.length > 5 ? `${raw.slice(0, 5)}-${raw.slice(5)}` : raw;
                            setFormData((prev) => ({ ...prev, cep: masked }));
                        }}
                        onBlur={handleCepBlur}
                        className="mt-1 w-full rounded border px-3 py-2"
                    />
                    {errors.cep ? (
                        <p id="erro-cep" className="mt-1 text-sm text-red-700">
                            {errors.cep}
                        </p>
                    ) : (
                        <p id="ajuda-cep" className="text-xs text-zinc-600 mt-1">
                            Ao sair do campo, cidade e estado serão preenchidos automaticamente.
                        </p>
                    )}
                </div>

                <div className="grid md:grid-cols-2 gap-4">
                    <div>
                        <label htmlFor="cidade" className="block font-semibold">
                            Cidade
                        </label>
                        <input
                            id="cidade"
                            name="cidade"
                            type="text"
                            required
                            aria-invalid={!!errors.cidade}
                            aria-describedby={errors.cidade ? 'erro-cidade' : undefined}
                            value={formData.cidade}
                            onChange={handleChange}
                            className="mt-1 w-full rounded border px-3 py-2"
                        />
                        {errors.cidade && (
                            <p id="erro-cidade" className="mt-1 text-sm text-red-700">
                                {errors.cidade}
                            </p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="estado" className="block font-semibold">
                            Estado
                        </label>
                        <input
                            id="estado"
                            name="estado"
                            type="text"
                            required
                            aria-invalid={!!errors.estado}
                            aria-describedby={errors.estado ? 'erro-estado' : undefined}
                            value={formData.estado}
                            onChange={handleChange}
                            className="mt-1 w-full rounded border px-3 py-2"
                        />
                        {errors.estado && (
                            <p id="erro-estado" className="mt-1 text-sm text-red-700">
                                {errors.estado}
                            </p>
                        )}
                    </div>
                </div>

                {/* ESCOLARIDADE E CURSO */}
                <div>
                    <label htmlFor="escolaridade" className="block font-semibold">
                        Escolaridade
                    </label>
                    <select
                        id="escolaridade"
                        name="escolaridade"
                        required
                        aria-invalid={!!errors.escolaridade}
                        aria-describedby={errors.escolaridade ? 'erro-escolaridade' : 'ajuda-escolaridade'}
                        value={formData.escolaridade}
                        onChange={handleChange}
                        className="mt-1 w-full rounded border px-3 py-2"
                    >
                        <option value="">Selecione</option>
                        {ESCOLARIDADE_OPCOES.map((op) => (
                            <option key={op} value={op}>
                                {op}
                            </option>
                        ))}
                    </select>
                    {errors.escolaridade ? (
                        <p id="erro-escolaridade" className="mt-1 text-sm text-red-700">
                            {errors.escolaridade}
                        </p>
                    ) : (
                        <p id="ajuda-escolaridade" className="mt-1 text-xs text-zinc-600">
                            Se escolher nível superior, informe também o curso.
                        </p>
                    )}
                </div>

                {/* Campos curso e instituição condicional */}
                {exigeCurso && (
                    <div className='grid md:grid-cols-2 gap-4'>
                        <div>
                            <label htmlFor="curso" className="block font-semibold">
                                Nome do Curso
                            </label>
                            <input
                                id="curso"
                                name="curso"
                                type="text"
                                required
                                aria-invalid={!!errors.curso}
                                aria-describedby={errors.curso ? 'erro-curso' : undefined}
                                value={formData.curso}
                                onChange={handleChange}
                                className="mt-1 w-full rounded border px-3 py-2"
                            />
                            {errors.curso && (
                                <p id="erro-curso" className="mt-1 text-sm text-red-700">
                                    {errors.curso}
                                </p>
                            )}
                        </div>
                        <div>
                            <label htmlFor="instituicao_ensino" className="block font-semibold">
                                Instituição de Ensino
                            </label>
                            <input
                                id="instituicao_ensino"
                                name="instituicao_ensino"
                                type="text"
                                required
                                aria-invalid={!!errors.instituicao_ensino}
                                aria-describedby={errors.instituicao_ensino ? 'erro-instituicao_ensino' : undefined}
                                value={formData.instituicao_ensino}
                                onChange={handleChange}
                                className="mt-1 w-full rounded border px-3 py-2"
                            />
                            {errors.instituicao_ensino && (
                                <p id="erro-instituicao_ensino" className="mt-1 text-sm text-red-700">
                                    {errors.instituicao_ensino}
                                </p>
                            )}
                        </div>
                    </div>
                )}

                {/* EXPERIÊNCIA */}
                <div>
                    <label htmlFor="experiencia" className="block font-semibold">
                        Descreva sua experiência
                    </label>
                    <textarea
                        id="experiencia"
                        name="experiencia"
                        rows={3}
                        required
                        aria-invalid={!!errors.experiencia}
                        aria-describedby={errors.experiencia ? 'erro-experiencia' : 'ajuda-experiencia'}
                        value={formData.experiencia}
                        onChange={handleChange}
                        className="mt-1 w-full rounded border px-3 py-2"
                    />
                    {errors.experiencia ? (
                        <p id="erro-experiencia" className="mt-1 text-sm text-red-700">
                            {errors.experiencia}
                        </p>
                    ) : (
                        <p id="ajuda-experiencia" className="mt-1 text-xs text-zinc-600">
                            Informe pelo menos uma experiência com alunos com deficiência.
                        </p>
                    )}
                </div>

                <button
                    type="submit"
                    disabled={submitting}
                    className="w-full rounded bg-blue-700 text-white px-4 py-3 font-semibold shadow focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 disabled:opacity-60"
                    aria-busy={submitting}
                    aria-disabled={submitting}
                >
                    {submitting ? 'Enviando…' : 'Criar conta'}
                </button>
            </form>
            
            {/* Link para voltar ao seletor de perfil */}
            <div className='mt-4 text-center'>
                 <button 
                    onClick={() => navigate('/register')}
                    className="text-sm text-blue-600 hover:text-blue-800"
                    type="button"
                >
                    &larr; Voltar para a escolha de perfil
                </button>
            </div>
        </main>
    );
}
