import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../components/Toast';
import { Briefcase, Save, Loader2, X } from 'lucide-react';
import { ESTADOS_BRASILEIROS } from '../constants/options';
import { parseApiError, getFieldErrorMessage } from '../utils/errorHandler';
import { logger } from '../utils/logger';

interface Deficiencia {
  id_deficiencia: number;
  nome: string;
}

interface VagaFormData {
  titulo_vaga: string;
  descricao: string;
  necessidades_descricao: string;
  cidade: string;
  estado: string;
  tipo: string;
  modalidade: string;
  carga_horaria_semanal: string;
  regime_contratacao: string;
  valor_remuneracao: string;
  tipo_remuneracao: string;
  aluno_nascimento_mes: string;
  aluno_nascimento_ano: string;
  deficiencia_ids: number[];
}

const CreateVagaPage: React.FC = () => {
  const navigate = useNavigate();
  const { user } = useAuth();
  const toast = useToast();

  const [formData, setFormData] = useState<VagaFormData>({
    titulo_vaga: '',
    descricao: '',
    necessidades_descricao: '',
    cidade: '',
    estado: '',
    tipo: 'PRESENCIAL',
    modalidade: '',
    carga_horaria_semanal: '',
    regime_contratacao: 'CLT',
    valor_remuneracao: '',
    tipo_remuneracao: 'MENSAL',
    aluno_nascimento_mes: '',
    aluno_nascimento_ano: '',
    deficiencia_ids: []
  });

  const [deficiencias, setDeficiencias] = useState<Deficiencia[]>([]);
  const [loading, setLoading] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  // Mapeamento de nomes técnicos para nomes amigáveis
  const fieldLabels: Record<string, string> = {
    titulo_vaga: 'Título da Vaga',
    descricao: 'Descrição',
    necessidades_descricao: 'Necessidades Específicas',
    cidade: 'Cidade',
    estado: 'Estado',
    tipo: 'Tipo',
    modalidade: 'Modalidade',
    carga_horaria_semanal: 'Carga Horária Semanal',
    regime_contratacao: 'Regime de Contratação',
    valor_remuneracao: 'Remuneração',
    tipo_remuneracao: 'Tipo de Remuneração',
    aluno_nascimento_mes: 'Mês de Nascimento do Aluno',
    aluno_nascimento_ano: 'Ano de Nascimento do Aluno',
    deficiencia_ids: 'Deficiências Associadas'
  };

  // Redirecionar se não for instituição
  useEffect(() => {
    if (user && user.tipo_usuario !== 'instituicao') {
      toast.error('Apenas instituições podem criar vagas.');
      navigate('/');
    }
  }, [user, navigate, toast]);

  // Buscar deficiências
  useEffect(() => {
    const fetchDeficiencias = async () => {
      try {
        const response = await api.get('/deficiencias');
        setDeficiencias(response.data);
      } catch (err) {
        logger.error('Erro ao carregar deficiências:', err);
        toast.error('Erro ao carregar lista de deficiências.');
      }
    };
    fetchDeficiencias();
  }, [toast]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    // Limpar erro do campo ao editar
    if (fieldErrors[name]) {
      setFieldErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const handleDeficienciaToggle = (id: number) => {
    setFormData(prev => ({
      ...prev,
      deficiencia_ids: prev.deficiencia_ids.includes(id)
        ? prev.deficiencia_ids.filter(defId => defId !== id)
        : [...prev.deficiencia_ids, id]
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setFieldErrors({});

    // Validação: Verificar se ao menos uma deficiência foi selecionada
    if (formData.deficiencia_ids.length === 0) {
      toast.error('Selecione ao menos uma deficiência associada à vaga');
      setLoading(false);
      return;
    }

    try {
      // Preparar dados para envio (remover campos vazios)
      const dataToSend: any = {
        titulo_vaga: formData.titulo_vaga,
        descricao: formData.descricao || undefined,
        necessidades_descricao: formData.necessidades_descricao || undefined,
        cidade: formData.cidade || undefined,
        estado: formData.estado || undefined,
        tipo: formData.tipo || undefined,
        modalidade: formData.modalidade || undefined,
      };

      // Adicionar campos opcionais apenas se preenchidos
      if (formData.carga_horaria_semanal) {
        dataToSend.carga_horaria_semanal = parseInt(formData.carga_horaria_semanal);
      }
      if (formData.regime_contratacao) {
        dataToSend.regime_contratacao = formData.regime_contratacao;
      }
      if (formData.valor_remuneracao) {
        dataToSend.valor_remuneracao = parseFloat(formData.valor_remuneracao);
      }
      if (formData.tipo_remuneracao) {
        dataToSend.tipo_remuneracao = formData.tipo_remuneracao;
      }
      if (formData.aluno_nascimento_mes) {
        dataToSend.aluno_nascimento_mes = parseInt(formData.aluno_nascimento_mes);
      }
      if (formData.aluno_nascimento_ano) {
        dataToSend.aluno_nascimento_ano = parseInt(formData.aluno_nascimento_ano);
      }
      if (formData.deficiencia_ids.length > 0) {
        dataToSend.deficiencia_ids = formData.deficiencia_ids;
      }

      const response = await api.post('/vagas', dataToSend);
      toast.success('Vaga criada com sucesso!');
      navigate('/perfil/instituicao');
    } catch (err) {
      logger.error('Erro ao criar vaga:', err);
      const { generalMessage, fieldErrors: errors } = parseApiError(err);

      if (errors && Object.keys(errors).length > 0) {
        setFieldErrors(errors);

        // Criar lista de campos com erro usando nomes amigáveis
        const errorFieldNames = Object.keys(errors)
          .map(fieldKey => fieldLabels[fieldKey] || fieldKey)
          .join(', ');

        toast.error(`Por favor, corrija os seguintes campos: ${errorFieldNames}`);
      } else {
        toast.error(generalMessage || 'Erro ao criar vaga. Tente novamente.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="page-wrapper">
      <Header />
      <main className="container py-lg max-w-md-content">
        <div className="card">
          <header className="border-bottom-divider pb-md mb-lg">
            <h1 className="heading-secondary flex-group-item">
              <Briefcase size={32} className="mr-sm" />
              Publicar Nova Vaga
            </h1>
            <p className="text-muted">Preencha os dados da vaga para encontrar o agente de apoio ideal.</p>
          </header>

          <form onSubmit={handleSubmit}>
            {/* Informações Básicas */}
            <section className="mb-lg">
              <h2 className="title-lg mb-md">Informações Básicas</h2>

              <div className="form-field">
                <label htmlFor="titulo_vaga" className="label-field required">
                  Título da Vaga
                </label>
                <input
                  type="text"
                  id="titulo_vaga"
                  name="titulo_vaga"
                  value={formData.titulo_vaga}
                  onChange={handleChange}
                  className={`input-field ${fieldErrors.titulo_vaga ? 'input-error' : ''}`}
                  placeholder="Ex: Agente de Apoio para Aluno com Deficiência Visual"
                  maxLength={255}
                  required
                />
                {fieldErrors.titulo_vaga && (
                  <p className="error-message">{fieldErrors.titulo_vaga}</p>
                )}
              </div>

              <div className="form-field">
                <label htmlFor="modalidade" className="label-field">
                  Modalidade
                </label>
                <input
                  type="text"
                  id="modalidade"
                  name="modalidade"
                  value={formData.modalidade}
                  onChange={handleChange}
                  className={`input-field ${fieldErrors.modalidade ? 'input-error' : ''}`}
                  placeholder="Ex: Tempo Integral, Meio Período"
                  maxLength={50}
                />
              </div>

              <div className="grid-2-col">
                <div className="form-field">
                  <label htmlFor="cidade" className="label-field">
                    Cidade
                  </label>
                  <input
                    type="text"
                    id="cidade"
                    name="cidade"
                    value={formData.cidade}
                    onChange={handleChange}
                    className={`input-field ${fieldErrors.cidade ? 'input-error' : ''}`}
                    placeholder="Ex: São Paulo"
                    maxLength={120}
                  />
                </div>

                <div className="form-field">
                  <label htmlFor="estado" className="label-field">
                    Estado (UF)
                  </label>
                  <select
                    id="estado"
                    name="estado"
                    value={formData.estado}
                    onChange={handleChange}
                    className={`input-field ${fieldErrors.estado ? 'input-error' : ''}`}
                  >
                    <option value="">Selecione...</option>
                    {ESTADOS_BRASILEIROS.map(uf => (
                      <option key={uf} value={uf}>{uf}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="form-field">
                <label htmlFor="descricao" className="label-field">
                  Descrição da Vaga
                </label>
                <textarea
                  id="descricao"
                  name="descricao"
                  value={formData.descricao}
                  onChange={handleChange}
                  className={`input-field ${fieldErrors.descricao ? 'input-error' : ''}`}
                  placeholder="Descreva as principais informações sobre a vaga..."
                  rows={5}
                  maxLength={2000}
                />
                <p className="text-sm text-muted">{formData.descricao.length}/2000 caracteres</p>
              </div>

              <div className="form-field">
                <label htmlFor="necessidades_descricao" className="label-field">
                  Descrição das Necessidades do Aluno
                </label>
                <textarea
                  id="necessidades_descricao"
                  name="necessidades_descricao"
                  value={formData.necessidades_descricao}
                  onChange={handleChange}
                  className={`input-field ${fieldErrors.necessidades_descricao ? 'input-error' : ''}`}
                  placeholder="Descreva as necessidades específicas do aluno e as tarefas do agente de apoio..."
                  rows={5}
                  maxLength={2000}
                />
                <p className="text-sm text-muted">{formData.necessidades_descricao.length}/2000 caracteres</p>
              </div>
            </section>

            {/* Perfil do Aluno */}
            <section className="mb-lg section-divider pt-lg">
              <h2 className="title-lg mb-md">Perfil do Aluno</h2>

              <div className="grid-2-col">
                <div className="form-field">
                  <label htmlFor="aluno_nascimento_mes" className="label-field">
                    Mês de Nascimento
                  </label>
                  <select
                    id="aluno_nascimento_mes"
                    name="aluno_nascimento_mes"
                    value={formData.aluno_nascimento_mes}
                    onChange={handleChange}
                    className={`input-field ${fieldErrors.aluno_nascimento_mes ? 'input-error' : ''}`}
                  >
                    <option value="">Não especificado</option>
                    {Array.from({ length: 12 }, (_, i) => i + 1).map(mes => (
                      <option key={mes} value={mes}>{mes}</option>
                    ))}
                  </select>
                </div>

                <div className="form-field">
                  <label htmlFor="aluno_nascimento_ano" className="label-field">
                    Ano de Nascimento
                  </label>
                  <input
                    type="number"
                    id="aluno_nascimento_ano"
                    name="aluno_nascimento_ano"
                    value={formData.aluno_nascimento_ano}
                    onChange={handleChange}
                    className={`input-field ${fieldErrors.aluno_nascimento_ano ? 'input-error' : ''}`}
                    placeholder="Ex: 2010"
                    min={1924}
                    max={new Date().getFullYear()}
                  />
                </div>
              </div>

              <div className="form-field">
                <label className="label-field">Deficiências Associadas</label>
                <div className="flex-wrap gap-sm">
                  {deficiencias.map(def => (
                    <label
                      key={def.id_deficiencia}
                      className={`badge-checkbox ${formData.deficiencia_ids.includes(def.id_deficiencia) ? 'badge-checkbox-active' : ''}`}
                    >
                      <input
                        type="checkbox"
                        checked={formData.deficiencia_ids.includes(def.id_deficiencia)}
                        onChange={() => handleDeficienciaToggle(def.id_deficiencia)}
                        className="checkbox-hidden"
                        aria-label={`Selecionar ${def.nome}`}
                      />
                      {def.nome}
                    </label>
                  ))}
                </div>
              </div>
            </section>

            {/* Condições de Trabalho */}
            <section className="mb-lg section-divider pt-lg">
              <h2 className="title-lg mb-md">Condições de Trabalho</h2>

              <div className="grid-2-col">
                <div className="form-field">
                  <label htmlFor="regime_contratacao" className="label-field">
                    Regime de Contratação
                  </label>
                  <select
                    id="regime_contratacao"
                    name="regime_contratacao"
                    value={formData.regime_contratacao}
                    onChange={handleChange}
                    className={`input-field ${fieldErrors.regime_contratacao ? 'input-error' : ''}`}
                  >
                    <option value="CLT">CLT</option>
                    <option value="PJ">PJ</option>
                    <option value="ESTAGIO">Estágio</option>
                    <option value="VOLUNTARIO">Voluntário</option>
                    <option value="OUTRO">Outro</option>
                  </select>
                </div>

                <div className="form-field">
                  <label htmlFor="carga_horaria_semanal" className="label-field">
                    Carga Horária Semanal (horas)
                  </label>
                  <input
                    type="number"
                    id="carga_horaria_semanal"
                    name="carga_horaria_semanal"
                    value={formData.carga_horaria_semanal}
                    onChange={handleChange}
                    className={`input-field ${fieldErrors.carga_horaria_semanal ? 'input-error' : ''}`}
                    placeholder="Ex: 40"
                    min={1}
                    max={60}
                  />
                </div>
              </div>

              <div className="grid-2-col">
                <div className="form-field">
                  <label htmlFor="valor_remuneracao" className="label-field">
                    Valor da Remuneração (R$)
                  </label>
                  <input
                    type="number"
                    id="valor_remuneracao"
                    name="valor_remuneracao"
                    value={formData.valor_remuneracao}
                    onChange={handleChange}
                    className={`input-field ${fieldErrors.valor_remuneracao ? 'input-error' : ''}`}
                    placeholder="Ex: 2500.00"
                    min={0}
                    step="0.01"
                  />
                </div>

                <div className="form-field">
                  <label htmlFor="tipo_remuneracao" className="label-field">
                    Tipo de Remuneração
                  </label>
                  <select
                    id="tipo_remuneracao"
                    name="tipo_remuneracao"
                    value={formData.tipo_remuneracao}
                    onChange={handleChange}
                    className={`input-field ${fieldErrors.tipo_remuneracao ? 'input-error' : ''}`}
                  >
                    <option value="MENSAL">Mensal</option>
                    <option value="HORARIA">Por Hora</option>
                    <option value="DIARIA">Diária</option>
                    <option value="PROJETO">Por Projeto</option>
                  </select>
                </div>
              </div>
            </section>

            {/* Botões de Ação */}
            <div className="flex-actions-end mt-lg pt-lg border-top-divider">
              <button
                type="button"
                onClick={() => navigate('/perfil/instituicao')}
                disabled={loading}
                className="btn-secondary"
              >
                Cancelar
              </button>
              <button
                type="submit"
                disabled={loading}
                className="btn-primary btn-icon"
              >
                {loading ? (
                  <>
                    <Loader2 size={20} className="icon-spin" />
                    Publicando...
                  </>
                ) : (
                  <>
                    <Save size={20} />
                    Publicar Vaga
                  </>
                )}
              </button>
            </div>
          </form>
        </div>
      </main>
      <Footer />
    </div>
  );
};

export default CreateVagaPage;
