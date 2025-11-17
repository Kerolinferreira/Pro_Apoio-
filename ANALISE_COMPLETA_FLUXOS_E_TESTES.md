# ANÁLISE COMPLETA: FLUXOS DO SISTEMA E PLANO DE TESTES
## Projeto Pro Apoio - Sistema de Gestão de Vagas para Agentes de Apoio

**Data da Análise:** 2025-01-16
**Versão do Documento:** 1.0

---

## SUMÁRIO EXECUTIVO

Este documento apresenta a análise completa do sistema Pro Apoio, mapeando todos os fluxos de usuário, endpoints da API, componentes do frontend e um plano abrangente de testes automatizados.

### Escopo do Sistema
O Pro Apoio é uma plataforma que conecta **instituições de ensino** com **candidatos a agentes de apoio** para alunos com deficiência. O sistema possui:
- **2 tipos de usuários**: Candidatos e Instituições
- **Backend**: Laravel 11 com API RESTful + JWT
- **Frontend**: React + TypeScript com React Router
- **Banco de Dados**: MySQL com Eloquent ORM

---

## 1. MAPEAMENTO COMPLETO DE ROTAS E ENDPOINTS

### 1.1 Autenticação (`/api/auth/*`)

| Método | Endpoint | Autenticação | Descrição | Rate Limit |
|--------|----------|--------------|-----------|------------|
| POST | `/auth/register/candidato` | Não | Registro de candidato | 10/min |
| POST | `/auth/register/instituicao` | Não | Registro de instituição | 10/min |
| POST | `/auth/login` | Não | Login (retorna JWT) | Ilimitado |
| POST | `/auth/logout` | JWT | Logout (invalida token) | - |
| POST | `/auth/forgot-password` | Não | Solicita reset de senha | 5/min |
| POST | `/auth/reset-password` | Não | Redefine senha com token | 5/min |
| GET | `/auth/check-email` | Não | Verifica se email existe | 30/min |
| GET | `/auth/check-cpf` | Não | Verifica se CPF existe | 30/min |
| GET | `/auth/check-cnpj` | Não | Verifica se CNPJ existe | 30/min |

### 1.2 Candidatos (`/api/candidatos/*`)

| Método | Endpoint | Autenticação | Permissão | Descrição |
|--------|----------|--------------|-----------|-----------|
| GET | `/candidatos` | JWT | Instituição | Busca/filtra candidatos |
| GET | `/candidatos/{id}` | Não | Público | Visualiza perfil público de candidato |
| GET | `/candidatos/me/` | JWT | Candidato | Obtém perfil do candidato logado |
| PUT | `/candidatos/me/` | JWT | Candidato | Atualiza perfil do candidato |
| POST | `/candidatos/me/foto` | JWT | Candidato | Upload de foto de perfil |
| PUT | `/candidatos/me/senha` | JWT | Candidato | Altera senha |
| DELETE | `/candidatos/me/` | JWT | Candidato | Deleta conta |
| POST | `/candidatos/me/experiencias-profissionais` | JWT | Candidato | Adiciona experiência profissional |
| PUT | `/candidatos/me/experiencias-profissionais/{id}` | JWT | Candidato | Atualiza experiência profissional |
| DELETE | `/candidatos/me/experiencias-profissionais/{id}` | JWT | Candidato | Remove experiência profissional |
| POST | `/candidatos/me/experiencias-pessoais` | JWT | Candidato | Adiciona experiência pessoal |
| DELETE | `/candidatos/me/experiencias-pessoais/{id}` | JWT | Candidato | Remove experiência pessoal |
| GET | `/candidatos/me/vagas-salvas` | JWT | Candidato | Lista vagas salvas |

### 1.3 Instituições (`/api/instituicoes/*`)

| Método | Endpoint | Autenticação | Permissão | Descrição |
|--------|----------|--------------|-----------|-----------|
| GET | `/instituicoes/{id}` | Não | Público | Visualiza perfil público de instituição |
| GET | `/instituicao/profile` | JWT | Instituição | Obtém perfil da instituição logada |
| PUT | `/instituicao/profile` | JWT | Instituição | Atualiza perfil da instituição |
| POST | `/instituicao/profile/logo` | JWT | Instituição | Upload de logotipo |
| PUT | `/instituicao/profile/senha` | JWT | Instituição | Altera senha |
| DELETE | `/instituicao/profile` | JWT | Instituição | Deleta conta |

### 1.4 Vagas (`/api/vagas/*`)

| Método | Endpoint | Autenticação | Permissão | Descrição |
|--------|----------|--------------|-----------|-----------|
| GET | `/vagas` | Não | Público | Lista vagas ativas (com filtros) |
| GET | `/vagas/{id}` | Não | Público | Visualiza detalhes de vaga |
| GET | `/vagas/minhas` | JWT | Instituição | Lista vagas da instituição |
| GET | `/vagas/minhas/{id}` | JWT | Instituição | Detalhes de vaga própria |
| POST | `/vagas` | JWT | Instituição | Cria nova vaga |
| PUT | `/vagas/{id}` | JWT | Instituição | Atualiza vaga |
| DELETE | `/vagas/{id}` | JWT | Instituição | Remove vaga |
| PUT | `/vagas/{id}/pausar` | JWT | Instituição | Pausa vaga |
| PUT | `/vagas/{id}/fechar` | JWT | Instituição | Fecha vaga |
| PATCH | `/vagas/{id}/status` | JWT | Instituição | Altera status da vaga |
| POST | `/vagas/{id}/salvar` | JWT | Candidato | Salva vaga nos favoritos |
| DELETE | `/vagas/{id}/remover` | JWT | Candidato | Remove vaga dos favoritos |

### 1.5 Propostas (`/api/propostas/*`)

| Método | Endpoint | Autenticação | Permissão | Descrição | Rate Limit |
|--------|----------|--------------|-----------|-----------|------------|
| GET | `/propostas` | JWT | Ambos | Lista propostas do usuário | - |
| GET | `/propostas/{id}` | JWT | Ambos | Detalhes de proposta | - |
| POST | `/propostas` | JWT | Candidato | Envia nova proposta | 10/min |
| PUT | `/propostas/{id}/aceitar` | JWT | Instituição | Aceita proposta | - |
| PUT | `/propostas/{id}/recusar` | JWT | Instituição | Recusa proposta | - |
| DELETE | `/propostas/{id}` | JWT | Candidato | Cancela proposta | - |

### 1.6 Outros Endpoints

| Método | Endpoint | Autenticação | Descrição |
|--------|----------|--------------|-----------|
| GET | `/deficiencias` | Não | Lista todas as deficiências |
| GET | `/external/cep/{cep}` | Não | Consulta CEP via ViaCEP |
| GET | `/external/cnpj/{cnpj}` | Não | Consulta CNPJ via ReceitaWS |
| GET | `/dashboard/candidato` | JWT (Candidato) | Métricas do candidato |
| GET | `/dashboard/instituicao` | JWT (Instituição) | Métricas da instituição |
| GET | `/notifications` | JWT | Lista notificações do usuário |
| PUT | `/notifications/{id}/read` | JWT | Marca notificação como lida |

---

## 2. PÁGINAS DO FRONTEND (REACT)

### 2.1 Páginas Públicas (Não Autenticadas)

| Página | Rota | Componente | Descrição |
|--------|------|------------|-----------|
| Home | `/` | HomePage | Landing page principal |
| Como Funciona | `/como-funciona` | ComoFuncionaPage | Explica funcionamento |
| Para Candidatos | `/para-candidatos` | ParaCandidatosPage | Info para candidatos |
| Para Instituições | `/para-instituicoes` | ParaInstituicoesPage | Info para instituições |
| Login | `/login` | LoginPage | Tela de login |
| Registro (escolha) | `/register` | RegisterPage | Escolhe tipo de usuário |
| Registro Candidato | `/register/candidato` | RegisterCandidatoPage | Formulário candidato |
| Registro Instituição | `/register/instituicao` | RegisterInstituicaoPage | Formulário instituição |
| Esqueci Senha | `/forgot-password` | ForgotPasswordPage | Solicita reset |
| Redefinir Senha | `/reset-password` | ResetPasswordPage | Redefine senha |
| Buscar Vagas | `/vagas` | BuscarVagasPage | Lista vagas públicas |
| Detalhes Vaga | `/vagas/:id` | DetalhesVagaPage | Visualiza vaga |
| Perfil Público Candidato | `/candidatos/:id` | PerfilCandidatoPublicPage | Visualiza candidato |
| Perfil Público Instituição | `/instituicoes/:id` | InstituicaoPublicaPage | Visualiza instituição |

### 2.2 Páginas Protegidas - Candidato

| Página | Rota | Componente | Descrição |
|--------|------|------------|-----------|
| Dashboard | `/dashboard` | DashboardPage | Dashboard do candidato |
| Meu Perfil | `/perfil/candidato` | PerfilCandidatoPage | Edição de perfil |
| Vagas Salvas | `/vagas-salvas` | VagasSalvasPage | Lista vagas favoritas |
| Minhas Propostas | `/minhas-propostas` | MinhasPropostasPage | Lista propostas enviadas |

### 2.3 Páginas Protegidas - Instituição

| Página | Rota | Componente | Descrição |
|--------|------|------------|-----------|
| Dashboard | `/dashboard` | DashboardPage | Dashboard da instituição |
| Meu Perfil | `/perfil/instituicao` | PerfilInstituicaoPage | Edição de perfil |
| Minhas Vagas | `/minhas-vagas` | MinhasVagasPage | Lista vagas criadas |
| Criar Vaga | `/vagas/criar` | CreateVagaPage | Formulário nova vaga |
| Editar Vaga | `/vagas/editar/:id` | EditVagaPage | Edita vaga existente |
| Buscar Candidatos | `/candidatos` | BuscarCandidatosPage | Busca/filtra candidatos |
| Minhas Propostas | `/minhas-propostas` | MinhasPropostasPage | Lista propostas recebidas |

---

## 3. FLUXOS COMPLETOS DO SISTEMA

### FLUXO 1: REGISTRO DE CANDIDATO

**Objetivo:** Permitir que um novo candidato crie uma conta no sistema

**Atores:** Visitante (não autenticado)

**Pré-condições:**
- Usuário possui CPF, email e telefone válidos
- CPF e email não estão cadastrados no sistema

**Fluxo Principal:**

1. Usuário acessa `/register`
2. Sistema exibe opções: "Sou Candidato" ou "Sou Instituição"
3. Usuário clica em "Sou Candidato"
4. Sistema redireciona para `/register/candidato`
5. Usuário preenche formulário:
   - Dados Pessoais: nome completo, email, telefone, CPF, data de nascimento, gênero
   - Endereço: CEP (com busca automática), logradouro, número, complemento, bairro, cidade, estado
   - Escolaridade: nível de escolaridade
   - Experiência: descrição opcional
   - Deficiências: seleciona deficiências que tem experiência (checkbox)
   - Senha: senha e confirmação
6. Sistema valida dados em tempo real (blur/change):
   - Email único (GET `/auth/check-email?email={email}`)
   - CPF único e válido (GET `/auth/check-cpf?cpf={cpf}`)
   - Senha forte (mín. 8 caracteres)
   - CEP válido (GET `/external/cep/{cep}`)
7. Usuário clica em "Criar Conta"
8. Sistema envia POST `/auth/register/candidato` com todos os dados
9. Backend valida dados:
   - Valida unicidade de email e CPF
   - Valida formato de telefone, CPF, data de nascimento
   - Valida existência das deficiências selecionadas
   - Hash da senha
10. Backend cria:
    - Registro na tabela `users` (tipo_usuario='CANDIDATO')
    - Registro na tabela `candidatos` (linked com user)
    - Registro na tabela `enderecos`
    - Registros em `candidatos_deficiencias` (many-to-many)
11. Backend retorna:
    - Status 201 Created
    - Token JWT
    - Dados do usuário criado
12. Frontend armazena token no localStorage
13. Frontend redireciona para `/perfil/candidato`
14. Sistema exibe mensagem de sucesso: "Cadastro realizado com sucesso!"

**Fluxos Alternativos:**

**FA1: Email já cadastrado**
- No passo 6, se email já existe:
  - Sistema exibe erro em tempo real: "Este email já está cadastrado"
  - Campo email fica destacado em vermelho
  - Botão "Criar Conta" fica desabilitado até correção

**FA2: CPF já cadastrado ou inválido**
- No passo 6, se CPF já existe ou é inválido:
  - Sistema exibe erro: "CPF inválido ou já cadastrado"
  - Campo CPF fica destacado em vermelho
  - Botão "Criar Conta" fica desabilitado

**FA3: CEP não encontrado**
- No passo 6, ao buscar CEP:
  - Se CEP inválido: Sistema exibe erro "CEP inválido"
  - Se CEP não encontrado: Permite preenchimento manual dos campos de endereço
  - Campos permanecem editáveis para correções

**FA4: Senha fraca**
- No passo 6, se senha não atende requisitos:
  - Sistema exibe feedback: "A senha deve ter no mínimo 8 caracteres"
  - Indicador visual de força da senha

**FA5: Senhas não coincidem**
- No passo 5/6, se confirmação difere da senha:
  - Sistema exibe erro: "As senhas não coincidem"
  - Campo confirmação fica destacado em vermelho

**FA6: Nenhuma deficiência selecionada**
- No passo 8, backend valida:
  - Permite cadastro mesmo sem deficiências
  - Campo é opcional

**FA7: Erro de validação no backend**
- No passo 9, se validação falha:
  - Backend retorna 422 Unprocessable Entity
  - Retorna objeto `errors` com campos e mensagens
  - Frontend exibe erros nos respectivos campos
  - Frontend exibe toast: "Por favor, corrija os campos destacados"

**FA8: Erro de servidor**
- No passo 8/9, se ocorrer erro 500:
  - Frontend exibe toast: "Erro ao criar conta. Tente novamente"
  - Não redireciona, mantém dados preenchidos
  - Log do erro é registrado no console

**FA9: Rate limit excedido**
- No passo 8, se exceder 10 tentativas/minuto:
  - Backend retorna 429 Too Many Requests
  - Frontend exibe: "Muitas tentativas. Aguarde um momento"

**Fluxos de Exceção:**

**FE1: Conexão perdida**
- Em qualquer passo de requisição de rede:
  - Frontend detecta timeout/erro de rede
  - Exibe toast: "Erro de conexão. Verifique sua internet"
  - Permite retentar

**FE2: Token JWT expirado (não aplicável ao registro)**

**Condições de Saída:**
- **Sucesso**: Usuário autenticado, redirecionado para `/perfil/candidato` com toast de sucesso
- **Falha**: Usuário permanece na tela de registro com erros exibidos

**Pontos de Teste:**
- ✅ Validação de email único
- ✅ Validação de CPF único e formato
- ✅ Validação de senha (força, confirmação)
- ✅ Busca automática de CEP
- ✅ Seleção de deficiências (opcional)
- ✅ Criação correta de todos os registros no BD
- ✅ Geração e retorno de token JWT válido
- ✅ Redirecionamento correto após sucesso
- ✅ Exibição de erros de validação
- ✅ Tratamento de rate limiting
- ✅ Tratamento de erros de rede

---

### FLUXO 2: REGISTRO DE INSTITUIÇÃO

**Objetivo:** Permitir que uma instituição crie uma conta no sistema

**Atores:** Visitante (não autenticado)

**Pré-condições:**
- Instituição possui CNPJ, email e telefone válidos
- CNPJ e email não estão cadastrados no sistema

**Fluxo Principal:**

1. Usuário acessa `/register`
2. Sistema exibe opções: "Sou Candidato" ou "Sou Instituição"
3. Usuário clica em "Sou Instituição"
4. Sistema redireciona para `/register/instituicao`
5. Usuário preenche formulário:
   - Dados da Instituição: nome fantasia, razão social, CNPJ, email, telefone
   - Endereço: CEP, logradouro, número, complemento, bairro, cidade, estado
   - Dados do Responsável: nome do responsável, cargo
   - Senha: senha e confirmação
6. Sistema valida dados em tempo real:
   - Email único (GET `/auth/check-email`)
   - CNPJ único e válido (GET `/auth/check-cnpj`)
   - CNPJ real via ReceitaWS (GET `/external/cnpj/{cnpj}`) - opcional, preenche razão social
   - Senha forte
   - CEP válido
7. Usuário clica em "Criar Conta"
8. Sistema envia POST `/auth/register/instituicao`
9. Backend valida e cria registros:
   - `users` (tipo_usuario='INSTITUICAO')
   - `instituicoes`
   - `enderecos`
10. Backend retorna token JWT e dados
11. Frontend armazena token
12. Frontend redireciona para `/perfil/instituicao`
13. Sucesso: "Cadastro realizado com sucesso!"

**Fluxos Alternativos:**

**FA1: CNPJ já cadastrado**
- Sistema exibe: "Este CNPJ já está cadastrado"
- Campo CNPJ destacado em vermelho

**FA2: CNPJ inválido**
- Sistema exibe: "CNPJ inválido"
- Valida formato 00.000.000/0000-00

**FA3: Busca CNPJ na ReceitaWS com sucesso**
- Sistema preenche automaticamente:
  - Razão Social
  - Opcionalmente endereço (se disponível)
- Campos permanecem editáveis

**FA4: Busca CNPJ falha (ReceitaWS offline)**
- Sistema permite preenchimento manual
- Exibe aviso: "Não foi possível buscar dados do CNPJ. Preencha manualmente"

**FA5-FA8:** Similares ao Fluxo 1 (senhas, validações, erros)

**Pontos de Teste:**
- ✅ Validação de CNPJ único e formato
- ✅ Integração com ReceitaWS (mock em testes)
- ✅ Preenchimento automático de dados
- ✅ Validação de email único
- ✅ Validação de senha
- ✅ Criação correta de registros
- ✅ Geração de token JWT
- ✅ Tratamento de erros de API externa

---

### FLUXO 3: LOGIN

**Objetivo:** Autenticar usuário existente no sistema

**Atores:** Visitante com conta cadastrada

**Pré-condições:**
- Usuário possui conta ativa (candidato ou instituição)
- Email e senha cadastrados

**Fluxo Principal:**

1. Usuário acessa `/login`
2. Sistema exibe formulário de login:
   - Campo "Email"
   - Campo "Senha" (tipo password)
   - Checkbox "Lembrar-me" (opcional)
   - Link "Esqueci minha senha"
   - Botão "Entrar"
3. Usuário preenche email e senha
4. Usuário clica em "Entrar"
5. Sistema envia POST `/auth/login` com `{email, password}`
6. Backend valida credenciais:
   - Busca usuário por email
   - Verifica hash da senha
7. Backend gera token JWT com:
   - `user_id`
   - `tipo_usuario` (CANDIDATO ou INSTITUICAO)
   - `exp` (expiração em 24h)
8. Backend retorna:
   ```json
   {
     "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
     "user": {
       "id": 1,
       "email": "user@example.com",
       "tipo_usuario": "CANDIDATO",
       "nome": "João Silva"
     }
   }
   ```
9. Frontend armazena:
   - Token no `localStorage` (ou `sessionStorage` se não marcou "Lembrar-me")
   - Dados do usuário no Context/State
10. Frontend redireciona baseado em `tipo_usuario`:
    - CANDIDATO → `/dashboard` ou `/perfil/candidato`
    - INSTITUICAO → `/dashboard` ou `/perfil/instituicao`
11. Sistema exibe toast: "Bem-vindo de volta, {nome}!"

**Fluxos Alternativos:**

**FA1: Credenciais inválidas**
- No passo 6, se email não existe ou senha incorreta:
  - Backend retorna 401 Unauthorized
  - Mensagem: "Email ou senha inválidos"
  - Frontend exibe toast de erro
  - Campos mantêm-se preenchidos (exceto senha, que é limpa)
  - Não indica se é email ou senha incorreta (segurança)

**FA2: Conta desativada/deletada**
- No passo 6, se conta foi deletada (soft delete):
  - Backend retorna 403 Forbidden
  - Mensagem: "Esta conta foi desativada. Entre em contato com o suporte"

**FA3: Múltiplas tentativas de login falhadas**
- Após 5 tentativas incorretas em 5 minutos (se implementado):
  - Backend ativa temporariamente rate limiting mais agressivo
  - Retorna 429 Too Many Requests
  - Mensagem: "Muitas tentativas. Aguarde 5 minutos"
  - (Nota: não implementado no código atual, mas sugerido)

**FA4: Usuário clica "Esqueci minha senha"**
- Sistema redireciona para `/forgot-password` (Ver Fluxo 4)

**FA5: Token já existente no localStorage (usuário já logado)**
- Antes do passo 1, ao acessar `/login`:
  - Sistema verifica token no localStorage
  - Se token válido, redireciona direto para dashboard
  - Evita login duplicado

**Fluxos de Exceção:**

**FE1: Erro de rede**
- Frontend exibe: "Erro de conexão. Verifique sua internet"
- Permite retentar

**FE2: Erro de servidor (500)**
- Frontend exibe: "Erro no servidor. Tente novamente mais tarde"

**Condições de Saída:**
- **Sucesso**: Usuário autenticado, redirecionado para dashboard/perfil
- **Falha**: Usuário permanece na tela de login com mensagem de erro

**Pontos de Teste:**
- ✅ Login com credenciais válidas (Candidato)
- ✅ Login com credenciais válidas (Instituição)
- ✅ Login com email inexistente
- ✅ Login com senha incorreta
- ✅ Geração correta de token JWT
- ✅ Armazenamento correto de token
- ✅ Redirecionamento baseado em tipo de usuário
- ✅ Verificação de token existente ao acessar /login
- ✅ Logout correto (limpeza de token)
- ✅ Expiração de token após 24h

---

### FLUXO 4: RECUPERAÇÃO DE SENHA

**Objetivo:** Permitir que usuário redefina senha esquecida

**Atores:** Visitante que esqueceu a senha

**Pré-condições:**
- Usuário possui conta com email válido

**Parte 1: Solicitação de Reset**

**Fluxo Principal:**

1. Usuário acessa `/forgot-password`
2. Sistema exibe formulário:
   - Campo "Email"
   - Botão "Enviar Link de Recuperação"
3. Usuário preenche email
4. Usuário clica no botão
5. Sistema envia POST `/auth/forgot-password` com `{email}`
6. Backend verifica se email existe:
   - Se existir: Gera token único de reset (válido por 1h)
   - Salva token em `password_reset_tokens` com hash
   - Envia email com link: `http://site.com/reset-password?token={token}&email={email}`
   - Retorna 200 OK (sempre, mesmo se email não existir - segurança)
7. Frontend exibe mensagem: "Se o email estiver cadastrado, você receberá um link de recuperação"
8. Usuário verifica email
9. Usuário clica no link recebido
10. Sistema redireciona para `/reset-password?token=abc&email=user@example.com`

**Parte 2: Redefinição de Senha**

11. Sistema pré-preenche campo email (readonly)
12. Sistema exibe campos:
    - Email (readonly)
    - Nova Senha
    - Confirmar Nova Senha
    - Botão "Redefinir Senha"
13. Usuário preenche nova senha
14. Sistema valida senha (mín. 8 caracteres, confirmação igual)
15. Usuário clica "Redefinir Senha"
16. Sistema envia POST `/auth/reset-password` com `{email, token, password, password_confirmation}`
17. Backend valida:
    - Token existe e não expirou (< 1h)
    - Token corresponde ao email
    - Senha atende requisitos
18. Backend:
    - Atualiza senha do usuário (hash)
    - Invalida token de reset
    - Invalida todos os tokens JWT anteriores (opcional)
19. Backend retorna 200 OK
20. Frontend exibe mensagem: "Senha redefinida com sucesso!"
21. Frontend redireciona para `/login` após 2s

**Fluxos Alternativos:**

**FA1: Email não cadastrado**
- No passo 6, mesmo que email não exista:
  - Backend retorna 200 OK (para não revelar emails cadastrados)
  - Frontend exibe mesma mensagem genérica

**FA2: Email não enviado (erro SMTP)**
- No passo 6, se falha ao enviar email:
  - Backend loga erro
  - Retorna 200 OK para usuário (não revela o problema)
  - Admin recebe notificação do erro

**FA3: Token expirado**
- No passo 17, se token tem > 1h:
  - Backend retorna 422 Unprocessable Entity
  - Mensagem: "Link expirado. Solicite um novo link de recuperação"
  - Frontend redireciona para `/forgot-password`

**FA4: Token inválido/não existe**
- No passo 17:
  - Backend retorna 422
  - Mensagem: "Link inválido. Solicite um novo link"

**FA5: Senhas não coincidem**
- No passo 14/16:
  - Frontend/Backend valida
  - Exibe erro: "As senhas não coincidem"

**FA6: Senha muito fraca**
- No passo 14/16:
  - Exibe erro: "A senha deve ter no mínimo 8 caracteres"

**FA7: Rate limit excedido**
- No passo 5, se > 5 solicitações/minuto:
  - Backend retorna 429
  - Mensagem: "Muitas tentativas. Aguarde um momento"

**Condições de Saída:**
- **Sucesso**: Senha redefinida, usuário redirecionado para login
- **Falha**: Usuário recebe mensagem de erro apropriada

**Pontos de Teste:**
- ✅ Solicitação de reset com email válido
- ✅ Solicitação com email inexistente (retorna sucesso)
- ✅ Geração de token único
- ✅ Expiração de token após 1h
- ✅ Redefinição com token válido
- ✅ Redefinição com token expirado
- ✅ Redefinição com token inválido
- ✅ Validação de senha (força, confirmação)
- ✅ Invalidação de token após uso
- ✅ Rate limiting
- ✅ Envio de email (mock em testes)

---

### FLUXO 5: CRIAR VAGA (INSTITUIÇÃO)

**Objetivo:** Instituição publica nova vaga de agente de apoio

**Atores:** Instituição autenticada

**Pré-condições:**
- Usuário logado como instituição
- Token JWT válido
- Middleware `instituicao` autoriza acesso

**Fluxo Principal:**

1. Instituição acessa `/vagas/criar` (ou clica "Criar Nova Vaga" no dashboard/perfil)
2. Sistema verifica autenticação:
   - Valida token JWT no header `Authorization: Bearer {token}`
   - Verifica `tipo_usuario === 'INSTITUICAO'`
   - Se inválido, redireciona para `/login`
3. Sistema exibe formulário com seções:

   **Seção 1: Informações Básicas**
   - Título da Vaga* (required)
   - Modalidade (ex: Tempo Integral, Meio Período)
   - Cidade
   - Estado (dropdown com UFs)
   - Descrição da Vaga (textarea, max 2000 chars)
   - Descrição das Necessidades do Aluno (textarea, max 2000 chars)

   **Seção 2: Perfil do Aluno**
   - Mês de Nascimento (dropdown 1-12)
   - Ano de Nascimento (input, 1924 - ano atual)
   - Deficiências Associadas* (checkboxes, required ao menos 1)

   **Seção 3: Condições de Trabalho**
   - Regime de Contratação (dropdown: CLT, PJ, Estágio, Voluntário, Outro)
   - Carga Horária Semanal (input numérico, 1-60h)
   - Valor da Remuneração (input numérico, R$)
   - Tipo de Remuneração (dropdown: Mensal, Por Hora, Diária, Por Projeto)

4. Sistema busca deficiências disponíveis (GET `/deficiencias`)
5. Sistema exibe checkboxes de deficiências dinâmicas
6. Usuário preenche formulário
7. Sistema valida em tempo real (opcional):
   - Título: mín. 3 caracteres
   - Ano de nascimento: entre 1924 e ano atual
   - Carga horária: 1-60
8. Usuário clica "Publicar Vaga"
9. Sistema valida:
   - Pelo menos 1 deficiência selecionada
   - Campos obrigatórios preenchidos
10. Sistema envia POST `/vagas` com dados:
    ```json
    {
      "titulo_vaga": "Agente de Apoio para Deficiência Visual",
      "descricao": "Descrição...",
      "necessidades_descricao": "Necessidades...",
      "cidade": "São Paulo",
      "estado": "SP",
      "tipo": "PRESENCIAL",
      "modalidade": "Tempo Integral",
      "carga_horaria_semanal": 40,
      "regime_contratacao": "CLT",
      "valor_remuneracao": 2500.00,
      "tipo_remuneracao": "MENSAL",
      "aluno_nascimento_mes": 6,
      "aluno_nascimento_ano": 2010,
      "deficiencia_ids": [1, 2]
    }
    ```
11. Backend (VagaController::store):
    - Valida usuário é instituição
    - Valida campos obrigatórios e formatos
    - Valida deficiência_ids existem na tabela `deficiencias`
    - Obtém `id_instituicao` do usuário autenticado
    - Inicia transação DB
    - Cria registro em `vagas`:
      - id_instituicao
      - status = 'ATIVA' (padrão)
      - tipo = 'PRESENCIAL' (padrão, removido seleção)
      - demais campos
    - Relaciona deficiências (tabela pivot `vagas_deficiencias`)
    - Commit da transação
12. Backend retorna 201 Created com dados da vaga criada:
    ```json
    {
      "id_vaga": 123,
      "titulo_vaga": "...",
      "status": "ATIVA",
      "deficiencias": [
        {"id_deficiencia": 1, "nome": "Visual"},
        {"id_deficiencia": 2, "nome": "Auditiva"}
      ],
      "created_at": "2025-01-16T10:00:00Z"
    }
    ```
13. Frontend exibe toast: "Vaga criada com sucesso!"
14. Frontend redireciona para `/perfil/instituicao` (ou `/minhas-vagas`)
15. Vaga aparece na lista de vagas da instituição
16. Vaga está visível na busca pública (`/vagas`)

**Fluxos Alternativos:**

**FA1: Nenhuma deficiência selecionada**
- No passo 9:
  - Validação frontend impede envio
  - Toast: "Selecione ao menos uma deficiência associada à vaga"
  - Botão "Publicar Vaga" pode ficar desabilitado até seleção

**FA2: Título vazio**
- No passo 9/11:
  - Backend retorna 422
  - Erro no campo `titulo_vaga`: "O campo título da vaga é obrigatório"
  - Frontend destaca campo

**FA3: Deficiência inválida**
- No passo 11, se `deficiencia_ids` contém ID inexistente:
  - Backend retorna 422
  - Erro: "Uma ou mais deficiências selecionadas são inválidas"

**FA4: Campos opcionais vazios**
- No passo 11:
  - Backend aceita campos opcionais como null/undefined
  - Campos não obrigatórios podem ser omitidos do JSON

**FA5: Valor de remuneração negativo**
- No passo 11:
  - Backend valida: `valor_remuneracao >= 0`
  - Se negativo, retorna 422

**FA6: Carga horária inválida**
- No passo 11:
  - Backend valida: `1 <= carga_horaria_semanal <= 60`
  - Se fora do range, retorna 422

**FA7: Erro de transação no banco**
- No passo 11, se falha ao criar vaga ou relacionar deficiências:
  - DB::transaction faz rollback automático
  - Backend retorna 500
  - Mensagem: "Erro ao criar vaga. Tente novamente"
  - Nenhum registro é salvo (integridade garantida)

**FA8: Usuário não é instituição**
- No passo 2/11:
  - Middleware bloqueia acesso
  - Retorna 403 Forbidden
  - Frontend redireciona ou exibe erro

**FA9: Token expirado**
- No passo 2:
  - Middleware jwt detecta expiração
  - Retorna 401 Unauthorized
  - Frontend limpa token e redireciona para `/login`

**Fluxos de Exceção:**

**FE1: Falha ao buscar deficiências**
- No passo 4, se GET `/deficiencias` falha:
  - Frontend usa lista fallback (const DEFICIENCIAS_FALLBACK)
  - Toast: "Erro ao carregar deficiências. Usando lista padrão"

**FE2: Perda de conexão durante envio**
- No passo 10:
  - Frontend detecta timeout
  - Toast: "Erro de conexão. Tente novamente"
  - Dados do formulário permanecem preenchidos

**Condições de Saída:**
- **Sucesso**: Vaga criada, visível publicamente, instituição redirecionada
- **Falha Parcial**: Vaga não criada, erros de validação exibidos
- **Falha Total**: Erro de servidor, toast genérico

**Pontos de Teste:**
- ✅ Criação com todos os campos preenchidos
- ✅ Criação apenas com campos obrigatórios
- ✅ Validação de título vazio
- ✅ Validação de deficiências (nenhuma, inválidas)
- ✅ Validação de carga horária (min, max, inválido)
- ✅ Validação de remuneração negativa
- ✅ Validação de ano de nascimento (passado, futuro, inválido)
- ✅ Middleware autorização (candidato tenta criar)
- ✅ Token expirado
- ✅ Transação DB (rollback em caso de erro)
- ✅ Relacionamento correto com deficiências
- ✅ Status padrão "ATIVA"
- ✅ Tipo padrão "PRESENCIAL"
- ✅ Vaga aparece em busca pública após criação

---

### FLUXO 6: BUSCAR VAGAS (PÚBLICO E CANDIDATO)

**Objetivo:** Permitir busca e visualização de vagas ativas

**Atores:** Visitante (público) ou Candidato autenticado

**Pré-condições:** Nenhuma (rota pública)

**Fluxo Principal:**

1. Usuário acessa `/vagas` (página pública de busca)
2. Sistema envia GET `/vagas` sem filtros
3. Backend retorna vagas ativas (status='ATIVA'), paginadas:
   ```json
   {
     "data": [
       {
         "id_vaga": 123,
         "titulo_vaga": "Agente de Apoio Visual",
         "cidade": "São Paulo",
         "estado": "SP",
         "tipo": "PRESENCIAL",
         "modalidade": "Tempo Integral",
         "valor_remuneracao": 2500.00,
         "instituicao": {
           "id": 10,
           "nome_fantasia": "Escola ABC"
         },
         "deficiencias": [...]
       }
     ],
     "meta": {
       "current_page": 1,
       "last_page": 5,
       "per_page": 10,
       "total": 45
     },
     "links": { ... }
   }
   ```
4. Sistema exibe:
   - Campo de busca textual (título/descrição)
   - Filtros:
     - Cidade (input)
     - Estado (dropdown)
     - Tipo (checkboxes: CLT, PJ, Estágio, etc.)
     - Regime de Contratação
   - Lista de cards de vagas
   - Paginação
5. **[Fluxo Opcional] Usuário aplica filtros:**
   - Digita cidade: "São Paulo"
   - Seleciona estado: "SP"
   - Seleciona tipos: ["Estágio", "CLT"]
6. Sistema envia GET `/vagas?cidade=São Paulo&estado=SP&tipo[]=Estágio&tipo[]=CLT`
7. Backend filtra vagas:
   - WHERE cidade LIKE '%São Paulo%'
   - AND estado = 'SP'
   - AND tipo IN ('Estágio', 'CLT')
   - AND status = 'ATIVA'
8. Sistema exibe resultados filtrados
9. **[Fluxo Opcional] Usuário busca por termo:**
   - Digita "deficiência visual"
10. Sistema envia GET `/vagas?q=deficiência visual`
11. Backend busca:
    - WHERE (titulo_vaga LIKE '%deficiência visual%' OR necessidades_descricao LIKE '%deficiência visual%')
    - AND status = 'ATIVA'
12. Sistema exibe resultados da busca
13. Usuário clica em um card de vaga
14. Sistema redireciona para `/vagas/{id}` (Ver Fluxo 7)

**Fluxo Alternativo (Candidato Autenticado):**

**FA1: Candidato autenticado vê status de candidatura**
- No passo 3, se usuário está autenticado como candidato:
  - Backend adiciona campo `ja_candidatou: boolean` em cada vaga
  - Sistema busca em `propostas` se candidato já enviou proposta para cada vaga
  - Frontend exibe badge "Você já se candidatou" em vagas aplicadas

**FA2: Candidato pode salvar vaga**
- Em cada card de vaga, se autenticado:
  - Botão "Salvar Vaga" (ícone coração)
  - Ao clicar: POST `/vagas/{id}/salvar`
  - Backend cria registro em `vagas_salvas`
  - Frontend atualiza ícone para "Remover dos Salvos"
  - Toast: "Vaga salva com sucesso!"

**FA3: Nenhuma vaga encontrada**
- No passo 8/12, se backend retorna array vazio:
  - Sistema exibe: "Nenhuma vaga encontrada com esses critérios"
  - Botão "Limpar Filtros"

**FA4: Erro ao carregar vagas**
- No passo 2/6/10, se erro 500:
  - Sistema exibe: "Erro ao carregar vagas. Tente novamente"
  - Botão "Recarregar"

**FA5: Paginação**
- Usuário clica "Próxima Página" ou número da página:
  - Sistema envia GET `/vagas?page=2` (mantém filtros)
  - Backend retorna próxima página
  - Sistema exibe novos resultados

**FA6: Termo de busca muito longo**
- No passo 10, se termo > 100 caracteres:
  - Backend retorna 400 Bad Request: "Termo muito longo"
  - Frontend limita input a 100 caracteres

**FA7: Estado inválido**
- No passo 6, se estado não é UF brasileira válida:
  - Backend ignora filtro inválido silenciosamente
  - Retorna todas as vagas (sem filtro de estado)

**Condições de Saída:**
- Usuário visualiza lista de vagas (vazia ou com resultados)
- Usuário pode clicar em vaga para ver detalhes

**Pontos de Teste:**
- ✅ Listar todas as vagas ativas (sem filtros)
- ✅ Filtrar por cidade
- ✅ Filtrar por estado (UF válida)
- ✅ Filtrar por múltiplos tipos
- ✅ Buscar por termo (título e descrição)
- ✅ Buscar com termo vazio (retorna todas)
- ✅ Buscar com termo muito longo (limite 100)
- ✅ Paginação (navegação entre páginas)
- ✅ Combinação de filtros
- ✅ Campo `ja_candidatou` para candidato autenticado
- ✅ Salvar/remover vaga (candidato)
- ✅ Não exibir vagas inativas/fechadas
- ✅ Ordenação por mais recentes (id_vaga DESC)
- ✅ Estrutura de paginação correta
- ✅ Escape de caracteres especiais SQL (LIKE injection)

---

### FLUXO 7: VISUALIZAR DETALHES DE VAGA

**Objetivo:** Exibir informações completas de uma vaga específica

**Atores:** Qualquer usuário (público, candidato, instituição)

**Pré-condições:** Vaga existe e está ativa

**Fluxo Principal:**

1. Usuário acessa `/vagas/{id}` (clicou em card na busca ou link direto)
2. Sistema envia GET `/vagas/{id}`
3. Backend (VagaController::showPublic):
   - Busca vaga por ID
   - Carrega relacionamentos (instituicao, deficiencias)
   - Verifica se status = 'ATIVA' (ou retorna 404)
   - Se usuário autenticado como candidato:
     - Verifica se já enviou proposta para esta vaga
     - Adiciona campo `ja_candidatou: boolean`
4. Backend retorna 200 OK:
   ```json
   {
     "id_vaga": 123,
     "titulo_vaga": "Agente de Apoio para Deficiência Visual",
     "descricao": "Descrição completa...",
     "necessidades_descricao": "Necessidades específicas...",
     "tipo": "PRESENCIAL",
     "modalidade": "Tempo Integral",
     "cidade": "São Paulo",
     "estado": "SP",
     "carga_horaria_semanal": 40,
     "regime_contratacao": "CLT",
     "valor_remuneracao": 2500.00,
     "tipo_remuneracao": "MENSAL",
     "aluno_nascimento_mes": 6,
     "aluno_nascimento_ano": 2010,
     "status": "ATIVA",
     "created_at": "2025-01-10T08:00:00Z",
     "instituicao": {
       "id": 10,
       "nome_fantasia": "Escola ABC",
       "razao_social": "Escola ABC Ltda"
     },
     "deficiencias": [
       {"id_deficiencia": 1, "nome": "Visual"},
       {"id_deficiencia": 2, "nome": "Auditiva"}
     ],
     "ja_candidatou": false
   }
   ```
5. Sistema exibe página de detalhes:
   - **Cabeçalho:**
     - Título da vaga
     - Nome da instituição (link para `/instituicoes/{id}`)
   - **Informações Básicas** (grid 2 colunas):
     - ~~Tipo de Apoio: PRESENCIAL~~ (removido, sempre presencial)
     - Localização: São Paulo, SP
     - Remuneração: R$ 2.500,00
     - Publicado em: 10/01/2025
   - **Requisitos do Aluno:**
     - Deficiências: Badges com "Visual", "Auditiva"
     - Descrição das Necessidades: Textarea com texto completo
   - **Descrição da Vaga:**
     - Texto completo em parágrafo
   - **Condições de Trabalho:**
     - Regime: CLT
     - Carga Horária: 40h semanais
     - Tipo de Remuneração: Mensal
   - **Ações** (se candidato autenticado):
     - Botão "Candidatar-se" (se `ja_candidatou === false`)
     - Badge "Você já se candidatou" (se `ja_candidatou === true`)
     - Botão "Salvar Vaga" (coração)
6. **[Candidato] Clica em "Candidatar-se":**
   - Sistema abre modal de proposta (Ver Fluxo 8)
7. **[Candidato] Clica em "Salvar Vaga":**
   - Sistema envia POST `/vagas/{id}/salvar`
   - Backend cria registro em `vagas_salvas`
   - Frontend atualiza botão para "Remover dos Salvos"
   - Toast: "Vaga salva com sucesso!"
8. **[Qualquer usuário] Clica no nome da instituição:**
   - Sistema redireciona para `/instituicoes/{id}` (perfil público)

**Fluxos Alternativos:**

**FA1: Vaga não encontrada (ID inválido)**
- No passo 3, se vaga não existe:
  - Backend retorna 404 Not Found
  - Frontend exibe: "Vaga não encontrada. Ela pode ter sido removida ou fechada"
  - Botão "Voltar para Busca de Vagas"

**FA2: Vaga pausada ou fechada**
- No passo 3, se status !== 'ATIVA':
  - Backend retorna 404 (ou 410 Gone)
  - Mensagem: "Esta vaga não está mais disponível"

**FA3: Usuário não autenticado**
- No passo 5, se não há token:
  - Não exibe botões de ação
  - Exibe banner: "Faça login como candidato para se candidatar"
  - Link para `/login`

**FA4: Usuário logado como instituição**
- No passo 5, se tipo_usuario = 'INSTITUICAO':
  - Não exibe botões "Candidatar-se" ou "Salvar"
  - Se for a instituição dona da vaga:
    - Exibe botão "Editar Vaga" (link para `/vagas/editar/{id}`)
    - Exibe botão "Gerenciar Propostas"

**FA5: Vaga já salva**
- No passo 7, se vaga já está em `vagas_salvas`:
  - Botão exibe "Remover dos Salvos"
  - Ao clicar: DELETE `/vagas/{id}/remover`
  - Backend remove registro
  - Botão volta para "Salvar Vaga"
  - Toast: "Vaga removida dos salvos"

**FA6: Candidato já se candidatou**
- No passo 6, se `ja_candidatou === true`:
  - Botão "Candidatar-se" não é exibido
  - Badge "Você já enviou uma proposta para esta vaga"
  - Link para "Ver Minhas Propostas"

**FA7: Erro ao carregar detalhes**
- No passo 2/3, se erro 500:
  - Frontend exibe: "Erro ao carregar detalhes da vaga"
  - Botão "Tentar Novamente"

**Fluxos de Exceção:**

**FE1: Instituição deletada**
- No passo 4, se relacionamento instituicao é null (soft delete):
  - Backend retorna nome genérico: "Instituição Não Informada"
  - Link para instituição não é clicável

**FE2: Deficiências vazias**
- No passo 5, se array deficiencias está vazio:
  - Exibe: "Nenhuma deficiência específica listada"

**FE3: Campos opcionais nulos**
- No passo 5, para cada campo opcional:
  - Se null/undefined: Exibe "Não informado" ou "-"
  - Ex: Remuneração: "A combinar"

**Condições de Saída:**
- Usuário visualiza detalhes completos da vaga
- Candidato pode candidatar-se ou salvar vaga
- Instituição dona pode editar vaga

**Pontos de Teste:**
- ✅ Visualização com ID válido
- ✅ Vaga não encontrada (404)
- ✅ Vaga inativa não exibida
- ✅ Campo `ja_candidatou` correto para candidato
- ✅ Botão "Candidatar-se" habilitado/desabilitado
- ✅ Salvar/remover vaga (candidato)
- ✅ Visualização como visitante (sem ações)
- ✅ Visualização como instituição dona (botões de gerenciamento)
- ✅ Link para instituição funcional
- ✅ Exibição de todos os campos
- ✅ Tratamento de campos opcionais nulos
- ✅ Formatação de valores monetários
- ✅ Formatação de datas
- ✅ Badges de deficiências dinâmicas

---

### FLUXO 8: ENVIAR PROPOSTA (CANDIDATO)

**Objetivo:** Candidato se candidata a uma vaga específica

**Atores:** Candidato autenticado

**Pré-condições:**
- Candidato logado com token válido
- Vaga ativa e não candidatada ainda
- Candidato possui perfil completo

**Fluxo Principal:**

1. Candidato está em `/vagas/{id}` (detalhes da vaga)
2. Sistema verifica:
   - Usuário é candidato (tipo_usuario = 'CANDIDATO')
   - Não enviou proposta ainda (`ja_candidatou === false`)
3. Sistema exibe botão "Candidatar-se" habilitado
4. Candidato clica em "Candidatar-se"
5. Sistema abre modal (PropostaModal):
   - Título: "Candidatar-se à Vaga"
   - Subtítulo: Nome da vaga
   - Campo textarea: "Mensagem para a Instituição" (opcional)
   - Botões: "Cancelar" e "Enviar Proposta"
6. Candidato escreve mensagem (opcional):
   - Ex: "Tenho 3 anos de experiência com deficiência visual..."
7. Candidato clica "Enviar Proposta"
8. Sistema valida:
   - Token JWT válido
   - Usuário é candidato
9. Sistema busca `id_candidato` do usuário:
   - GET `/candidatos/me/` (ou busca em estado/context)
10. Sistema envia POST `/propostas`:
    ```json
    {
      "id_vaga": 123,
      "id_candidato": 45,
      "mensagem": "Tenho 3 anos de experiência..."
    }
    ```
11. Backend (PropostaController::store):
    - Valida autenticação (middleware jwt)
    - Valida que usuário é candidato
    - Valida que vaga existe e está ativa
    - Verifica se candidato já enviou proposta para esta vaga:
      ```sql
      SELECT * FROM propostas
      WHERE id_vaga = 123
      AND id_candidato = 45
      AND status != 'CANCELADA'
      ```
    - Se já existe: Retorna 400 Bad Request
    - Se não existe: Cria proposta
12. Backend cria registro em `propostas`:
    ```sql
    INSERT INTO propostas (id_vaga, id_candidato, mensagem, status, data_envio)
    VALUES (123, 45, 'mensagem...', 'PENDENTE', NOW())
    ```
13. Backend (opcional) envia notificação para instituição:
    - Cria registro em `notificacoes` para instituição
    - Envia email para instituição (assíncrono, via queue)
14. Backend retorna 201 Created:
    ```json
    {
      "id_proposta": 789,
      "id_vaga": 123,
      "id_candidato": 45,
      "mensagem": "...",
      "status": "PENDENTE",
      "data_envio": "2025-01-16T14:30:00Z"
    }
    ```
15. Frontend:
    - Fecha modal
    - Atualiza estado local: `ja_candidatou = true`
    - Oculta botão "Candidatar-se"
    - Exibe badge "Você já enviou uma proposta"
    - Toast: "Proposta enviada com sucesso! A instituição receberá sua candidatura"
16. Proposta aparece em "Minhas Propostas" do candidato
17. Proposta aparece no dashboard da instituição

**Fluxos Alternativos:**

**FA1: Candidato já se candidatou**
- No passo 11, se já existe proposta:
  - Backend retorna 400 Bad Request
  - Mensagem: "Você já possui uma proposta para esta vaga"
  - Frontend exibe toast de erro
  - Frontend atualiza estado: `ja_candidatou = true`
  - (Previne múltiplas propostas para mesma vaga)

**FA2: Vaga não existe ou foi deletada**
- No passo 11:
  - Backend retorna 404 Not Found
  - Mensagem: "Vaga não encontrada"

**FA3: Vaga foi pausada/fechada**
- No passo 11, se status !== 'ATIVA':
  - Backend retorna 400 ou 422
  - Mensagem: "Esta vaga não está mais disponível para candidaturas"

**FA4: Mensagem vazia**
- No passo 10:
  - Campo mensagem é opcional
  - Backend aceita string vazia ou null
  - Proposta é criada normalmente

**FA5: Mensagem muito longa**
- No passo 7/10:
  - Frontend limita textarea a 2000 caracteres
  - Backend valida max 2000
  - Se exceder: Retorna 422 com erro de validação

**FA6: Candidato sem perfil completo** (regra de negócio opcional)
- No passo 11:
  - Se candidato não tem foto ou experiências (opcional):
    - Backend permite envio (não é bloqueante)
    - Ou: Retorna 400 "Complete seu perfil antes de se candidatar"
  - (Não implementado no código atual, mas sugerido)

**FA7: Rate limit excedido**
- No passo 10, se > 10 propostas/minuto:
  - Backend retorna 429 Too Many Requests
  - Mensagem: "Muitas propostas enviadas. Aguarde um momento"

**FA8: Candidato cancela modal**
- No passo 5-7, usuário clica "Cancelar":
  - Modal fecha sem enviar nada
  - Retorna à página de detalhes da vaga

**FA9: Token expirado durante envio**
- No passo 10:
  - Backend retorna 401 Unauthorized
  - Frontend limpa token
  - Redireciona para `/login`
  - Toast: "Sessão expirada. Faça login novamente"

**FA10: Usuário não é candidato (é instituição)**
- No passo 11:
  - Middleware `candidato` bloqueia (se aplicado)
  - Backend retorna 403 Forbidden
  - Mensagem: "Apenas candidatos podem enviar propostas"

**Fluxos de Exceção:**

**FE1: Erro ao buscar id_candidato**
- No passo 9, se GET `/candidatos/me/` falha:
  - Frontend usa id do context/estado
  - Ou exibe erro: "Erro ao identificar candidato"

**FE2: Erro de rede**
- No passo 10:
  - Frontend detecta timeout
  - Toast: "Erro de conexão. Tente novamente"
  - Modal permanece aberto com dados preenchidos

**FE3: Erro ao enviar notificação (não crítico)**
- No passo 13, se email falha:
  - Backend loga erro
  - Proposta é criada normalmente
  - Instituição verá notificação no sistema (sem email)

**Condições de Saída:**
- **Sucesso**: Proposta criada, candidato informado, instituição notificada
- **Falha**: Proposta não criada, candidato recebe feedback de erro

**Pontos de Teste:**
- ✅ Envio de proposta com mensagem
- ✅ Envio de proposta sem mensagem
- ✅ Envio com mensagem muito longa (max 2000)
- ✅ Candidato já candidatado (duplicata)
- ✅ Vaga não existe (404)
- ✅ Vaga inativa/fechada
- ✅ Usuário não candidato tenta enviar
- ✅ Token expirado
- ✅ Rate limiting (10/min)
- ✅ Criação correta em `propostas`
- ✅ Status padrão "PENDENTE"
- ✅ Notificação para instituição criada
- ✅ Atualização de `ja_candidatou` no frontend
- ✅ Proposta aparece em "Minhas Propostas"
- ✅ Proposta aparece no dashboard da instituição
- ✅ Validação de mensagem opcional

---

### FLUXO 9: GERENCIAR PROPOSTAS (INSTITUIÇÃO)

**Objetivo:** Instituição visualiza e responde propostas recebidas

**Atores:** Instituição autenticada

**Pré-condições:**
- Instituição logada
- Possui vagas criadas
- Recebeu propostas

**Fluxo Principal:**

1. Instituição acessa `/minhas-propostas` ou clica "Ver Propostas" no dashboard
2. Sistema envia GET `/propostas`
3. Backend (PropostaController::index):
   - Valida autenticação
   - Identifica tipo de usuário
   - Se INSTITUICAO:
     ```sql
     SELECT p.*, c.nome_completo, v.titulo_vaga
     FROM propostas p
     INNER JOIN candidatos c ON p.id_candidato = c.id
     INNER JOIN vagas v ON p.id_vaga = v.id_vaga
     WHERE v.id_instituicao = {id_instituicao_logada}
     ORDER BY p.data_envio DESC
     ```
4. Backend retorna lista paginada:
   ```json
   {
     "data": [
       {
         "id_proposta": 789,
         "id_vaga": 123,
         "id_candidato": 45,
         "mensagem": "Tenho experiência...",
         "status": "PENDENTE",
         "data_envio": "2025-01-16T14:30:00Z",
         "data_resposta": null,
         "candidato": {
           "id": 45,
           "nome_completo": "João Silva",
           "foto_url": "...",
           "escolaridade": "Superior Completo"
         },
         "vaga": {
           "id_vaga": 123,
           "titulo_vaga": "Agente de Apoio Visual"
         }
       }
     ],
     "meta": { ... }
   }
   ```
5. Sistema exibe lista de propostas com filtros:
   - **Filtros:**
     - Status: Todas | Pendentes | Aceitas | Recusadas
     - Vaga específica (dropdown)
   - **Cards de Proposta** (cada um com):
     - Foto e nome do candidato
     - Título da vaga
     - Data de envio
     - Status (badge colorido)
     - Mensagem (truncada)
     - Botões: "Ver Perfil" | "Ver Detalhes"
6. **[Filtrar por Status]**
   - Instituição seleciona "Pendentes"
   - Sistema filtra frontend ou envia GET `/propostas?status=PENDENTE`
7. Instituição clica "Ver Detalhes" em uma proposta
8. Sistema abre modal ou redireciona para `/propostas/{id}`
9. Sistema envia GET `/propostas/{id}`
10. Backend valida:
    - Proposta pertence à instituição (via vaga)
    - Retorna 403 se não for dona
11. Backend retorna detalhes completos:
    ```json
    {
      "id_proposta": 789,
      "status": "PENDENTE",
      "mensagem": "Mensagem completa do candidato...",
      "data_envio": "...",
      "candidato": {
        "id": 45,
        "nome_completo": "João Silva",
        "email": "joao@example.com",
        "telefone": "(11) 98765-4321",
        "foto_url": "...",
        "escolaridade": "Superior Completo",
        "experiencia": "Resumo...",
        "deficiencias_atuadas": [
          {"id": 1, "nome": "Visual"}
        ],
        "experienciasProfissionais": [...]
      },
      "vaga": {
        "id_vaga": 123,
        "titulo_vaga": "...",
        "deficiencias": [...]
      }
    }
    ```
12. Sistema exibe modal detalhado:
    - Foto e dados do candidato
    - Mensagem completa
    - Link "Ver Perfil Completo do Candidato"
    - Experiências profissionais resumidas
    - Deficiências atuadas
    - **Se status = PENDENTE:**
      - Botão "Aceitar Proposta" (verde)
      - Botão "Recusar Proposta" (vermelho)
    - **Se status = ACEITA/RECUSADA:**
      - Badge de status
      - Data da resposta
13. **[ACEITAR] Instituição clica "Aceitar Proposta":**
14. Sistema exibe confirmação: "Deseja aceitar esta proposta?"
15. Instituição confirma
16. Sistema envia PUT `/propostas/{id}/aceitar`
17. Backend (PropostaController::accept):
    - Valida propriedade da vaga
    - Valida status atual é PENDENTE
    - Atualiza proposta:
      ```sql
      UPDATE propostas
      SET status = 'ACEITA',
          data_resposta = NOW()
      WHERE id_proposta = 789
      ```
18. Backend cria notificação para candidato:
    - "Sua proposta para {vaga} foi aceita!"
19. Backend envia email para candidato (opcional, assíncrono)
20. Backend retorna 200 OK com proposta atualizada
21. Frontend:
    - Fecha modal
    - Atualiza status na lista para "ACEITA"
    - Toast: "Proposta aceita com sucesso!"
22. **[RECUSAR] Instituição clica "Recusar Proposta":**
23. Sistema solicita motivo (opcional):
    - Modal com textarea: "Motivo da recusa (opcional)"
24. Instituição confirma
25. Sistema envia PUT `/propostas/{id}/recusar` com `{motivo}`
26. Backend atualiza:
    ```sql
    UPDATE propostas
    SET status = 'RECUSADA',
        data_resposta = NOW(),
        motivo_recusa = 'motivo...'
    WHERE id_proposta = 789
    ```
27. Backend notifica candidato
28. Backend envia email com motivo (se fornecido)
29. Frontend atualiza lista e exibe toast

**Fluxos Alternativos:**

**FA1: Nenhuma proposta recebida**
- No passo 4, se array vazio:
  - Sistema exibe: "Você ainda não recebeu propostas"
  - Imagem ilustrativa
  - Link "Criar Nova Vaga"

**FA2: Filtrar por vaga**
- No passo 6:
  - Sistema envia GET `/propostas?vaga_id=123`
  - Exibe apenas propostas daquela vaga

**FA3: Proposta já respondida**
- No passo 17/26, se status !== 'PENDENTE':
  - Backend retorna 400 Bad Request
  - Mensagem: "Esta proposta já foi respondida"
  - Frontend atualiza estado local

**FA4: Instituição não é dona da vaga**
- No passo 10/17/26:
  - Backend retorna 403 Forbidden
  - Mensagem: "Você não tem permissão para responder esta proposta"

**FA5: Proposta deletada/inexistente**
- No passo 9:
  - Backend retorna 404 Not Found

**FA6: Clicar "Ver Perfil Completo"**
- No passo 12:
  - Sistema abre nova aba com `/candidatos/{id}` (perfil público)

**FA7: Múltiplas respostas rápidas**
- Instituição aceita/recusa várias propostas seguidas:
  - Sistema processa cada uma individualmente
  - Atualiza lista após cada resposta

**FA8: Ordenação de propostas**
- Sistema permite ordenar por:
  - Mais recentes (padrão)
  - Mais antigas
  - Status

**Fluxos de Exceção:**

**FE1: Erro ao aceitar/recusar**
- No passo 17/26, se erro 500:
  - Frontend exibe: "Erro ao processar resposta. Tente novamente"
  - Não atualiza estado

**FE2: Falha ao enviar notificação**
- No passo 18/27:
  - Backend loga erro
  - Proposta é atualizada normalmente
  - Candidato não recebe notificação (problema não crítico)

**Condições de Saída:**
- **Sucesso**: Proposta respondida, candidato notificado
- **Falha**: Erro exibido, proposta mantém estado anterior

**Pontos de Teste:**
- ✅ Listar propostas da instituição
- ✅ Filtrar por status (PENDENTE, ACEITA, RECUSADA)
- ✅ Filtrar por vaga
- ✅ Visualizar detalhes de proposta
- ✅ Aceitar proposta pendente
- ✅ Recusar proposta com motivo
- ✅ Recusar proposta sem motivo
- ✅ Tentar aceitar proposta já respondida
- ✅ Tentar aceitar proposta de outra instituição (403)
- ✅ Visualizar perfil público do candidato
- ✅ Notificação criada para candidato
- ✅ Email enviado (mock)
- ✅ Atualização de status e data_resposta
- ✅ Ordenação de propostas
- ✅ Paginação

---

## 4. MATRIZ DE COBERTURA DE TESTES

### 4.1 Testes Existentes

| Módulo | Tipo de Teste | Arquivo | Casos | Status |
|--------|---------------|---------|-------|--------|
| Autenticação | Feature | AuthControllerTest.php | 15+ | ✅ Completo |
| Autenticação | Acceptance | AuthCest.php | 10+ | ✅ Completo |
| Vagas | Feature | VagaControllerTest.php | 20+ | ✅ Completo |
| Vagas | Acceptance | VagaCest.php | 15+ | ✅ Completo |
| Propostas | Feature | PropostaControllerTest.php | 25+ | ✅ Completo |
| Propostas | Acceptance | PropostaCest.php | 30+ | ✅ Completo |
| Candidatos | Feature | CandidatoProfileControllerTest.php | 20+ | ✅ Completo |
| Candidatos | Feature | CandidatoFinderControllerTest.php | 10+ | ✅ Completo |
| Candidatos | Acceptance | CandidatoCest.php | 15+ | ✅ Completo |
| Instituições | Feature | InstituicaoProfileControllerTest.php | 15+ | ✅ Completo |
| Instituições | Acceptance | InstituicaoCest.php | 10+ | ✅ Completo |
| Vagas Salvas | Feature | VagaSalvaControllerTest.php | 8+ | ✅ Completo |
| Notificações | Feature | NotificationControllerTest.php | 10+ | ✅ Completo |
| Notificações | Acceptance | NotificationCest.php | 8+ | ✅ Completo |
| Dashboard | Acceptance | DashboardCest.php | 8+ | ✅ Completo |
| APIs Externas | Feature | ExternalApiControllerTest.php | 12+ | ✅ Completo |
| APIs Externas | Integration | ExternalApiIntegrationTest.php | 6+ | ⚠️ Desabilitado |
| Deficiências | Feature | DeficienciaControllerTest.php | 4+ | ✅ Completo |
| Modelos | Unit | Models/*Test.php | 40+ | ✅ Completo |
| JWT Helper | Unit | JwtHelperTest.php | 15+ | ✅ Completo |

**Total**: ~413+ casos de teste identificados

### 4.2 Gaps e Lacunas de Cobertura

| Área | Lacuna Identificada | Criticidade | Sugestão |
|------|---------------------|-------------|----------|
| Upload de Arquivos | Testes E2E para upload de foto/logo | Média | Adicionar teste Codeception com upload real |
| Rate Limiting | Validação de rate limits em testes | Baixa | Adicionar testes específicos para throttle |
| Emails | Mock de envio de emails em Acceptance | Baixa | Usar MailHog ou similar |
| Concorrência | Testes de concorrência (2 usuários simultâneos) | Alta | Testes paralelos com transações |
| Performance | Testes de carga (muitas vagas/propostas) | Média | Adicionar testes de stress |
| SEO | Validação de meta tags e conteúdo público | Baixa | Adicionar verificações de HTML |
| Acessibilidade | Testes de navegação via teclado, ARIA | Média | Ferramentas de a11y |
| Mobile | Testes em viewports mobile | Média | Codeception com resize |
| Timezone | Testes de datas em diferentes fusos | Baixa | Testes com timezone configurado |
| Paginação | Navegação entre páginas (edge cases) | Média | Testes com pág. vazia, última pág. |
| CSRF | Validação de proteção CSRF | Alta | Testes com token inválido |
| XSS | Testes de injeção de scripts | Alta | Testes com input malicioso |
| SQL Injection | Testes de escape de SQL | Alta | Testes com caracteres especiais |

---

## 5. PLANO DE TESTES AUTOMATIZADOS

### 5.1 Novos Testes Sugeridos (Acceptance com Codeception)

Abaixo estão os testes adicionais sugeridos para cobrir fluxos completos end-to-end que não estão completamente cobertos ou precisam de maior profundidade.

---

### TESTE 1: Fluxo Completo de Registro e Primeiro Acesso (Candidato)

**Arquivo:** `tests/Acceptance/RegistroCompletoCandidat