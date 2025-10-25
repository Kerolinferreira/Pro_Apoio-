# 💻 ProApoio: Plataforma de Inclusão e Apoio

Este projeto consiste em uma plataforma de conexão entre Instituições de Ensino e Candidatos (Agentes de Apoio) para estudantes com deficiência, focada em segurança, acessibilidade e um fluxo de propostas claro.

A arquitetura do ProApoio é composta por duas aplicações distintas: um **Backend (API)** em Laravel e um **Frontend (UI)** em React/Vite.

---

## 🏗️ Arquitetura do Projeto

O projeto é dividido em dois diretórios principais:

1.  **`api_proapoio` (Backend):** Implementa a lógica de negócio, persistência de dados (MySQL) e expõe a API RESTful.
2.  **`frontend_proapoio` (Frontend):** Implementa a interface de usuário (Dashboards, Telas de Busca, Formulários) em React e consome a API do backend.

| Camada | Tecnologia | Linguagem | Autenticação |
| :--- | :--- | :--- | :--- |
| **Backend (API)** | Laravel 10 | PHP 8.2+ | JWT (Stateless) |
| **Frontend (UI)** | React.js / Vite | TypeScript/JSX | Context API + Axios |

---

## ✨ Funcionalidades Principais

O sistema implementa as seguintes funcionalidades conforme a documentação de arquitetura:

| Módulo | Funcionalidades |
| :--- | :--- |
| **Autenticação** | Cadastro segmentado (Candidato/Instituição), Login, Logout e Fluxo de Recuperação de Senha. |
| **Perfis** | Gestão de perfis (Candidato e Instituição), upload de foto/logo, cadastro de experiências profissionais/pessoais (Candidato). |
| **Vagas** | Criação e gestão de vagas (Instituição), busca pública de vagas (Candidato) e gestão de vagas salvas. |
| **Propostas** | Mecanismo central de comunicação. Envio e gestão de propostas (Enviadas/Recebidas), com status (Enviada, Aceita, Recusada). **Contatos são liberados apenas após a proposta ser Aceita**. |
| **Busca** | Busca avançada de candidatos por Instituições (com filtros de escolaridade e estimativa de deslocamento) e visualização de perfil público seguro. |
| **Notificações** | Sistema de notificações via banco de dados e e-mail para avisos de novas propostas e respostas. |
| **APIs Externas** | Integração com ViaCEP e ReceitaWS (em ambiente de desenvolvimento/homologação) para auto-preenchimento de dados. |

---

## 🛠️ Instalação e Configuração

Para rodar o projeto localmente, é necessário configurar e iniciar o Backend e o Frontend separadamente.

### Pré-requisitos

Certifique-se de ter instalado em seu ambiente:
* PHP >= 8.2 (com extensões PDO, Mbstring, OpenSSL)
* Composer
* Node.js e NPM
* MySQL (ou outra base de dados suportada pelo Laravel)

### 1. Configuração do Backend (`api_proapoio`)

A API foi desenvolvida em Laravel 10.

1.  **Navegue para o diretório da API:**
    ```bash
    cd kerolinferreira/pro_apoio-/Pro_Apoio--feature-api/api_proapoio
    ```

2.  **Instale as dependências PHP:**
    ```bash
    composer install
    ```

3.  **Configure o ambiente:**
    Copie o arquivo de exemplo e gere a chave da aplicação.
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    ⚠️ **Importante:** Edite o arquivo `.env` para configurar as credenciais do seu banco de dados (`DB_*`) e o `JWT_SECRET` (para tokens de autenticação).

4.  **Execute as migrações e seeders:**
    ```bash
    php artisan migrate --seed
    ```
    *Isso criará o esquema do banco de dados e usuários de exemplo (`candidato@example.com`, `instituicao@example.com` com a senha `password`).*

5.  **Inicie o servidor de desenvolvimento da API:**
    ```bash
    php artisan serve
    ```
    A API estará acessível em `http://127.0.0.1:8000/api`.

### 2. Configuração do Frontend (`frontend_proapoio`)

A interface de usuário foi desenvolvida em React com Vite.

1.  **Navegue para o diretório do Frontend:**
    ```bash
    cd ../frontend_proapoio
    ```
    *(Presumindo que você está em `api_proapoio`.)*

2.  **Instale as dependências Node.js:**
    ```bash
    npm install
    ```

3.  **Configure a URL da API:**
    Crie um arquivo `.env.local` e defina a URL do backend:
    ```
    VITE_API_URL=[http://127.0.0.1:8000/api](http://127.0.0.1:8000/api)
    ```

4.  **Inicie o servidor de desenvolvimento do Frontend:**
    ```bash
    npm run dev
    ```
    O Frontend estará acessível no endereço que o Vite exibir (geralmente `http://localhost:5173/`).

---

## 📋 Contrato da API (Endpoints Chave)

O Backend expõe uma API RESTful (JSON) seguindo o contrato definido na documentação. O prefixo da rota é `/api/`.

| Funcionalidade | Método | Endpoint | Restrição |
| :--- | :--- | :--- | :--- |
| **Login** | `POST` | `/auth/login` | Público |
| **Registro** | `POST` | `/auth/register/{tipo}` | Público |
| **Meu Perfil** | `GET` | `/candidatos/me` | JWT (Candidato) |
| **Atualizar Perfil** | `PUT` | `/instituicoes/me` | JWT (Instituição) |
| **Buscar Vagas** | `GET` | `/vagas` | Público |
| **Criar Vaga** | `POST` | `/vagas` | JWT (Instituição) |
| **Buscar Candidatos** | `GET` | `/candidatos` | JWT (Instituição) |
| **Ver Perfil Público** | `GET` | `/candidatos/{id}` | Público |
| **Enviar Proposta** | `POST` | `/propostas` | JWT (Ambos) |
| **Listar Propostas**| `GET` | `/propostas` | JWT (Ambos) |
| **Aceitar Proposta**| `PUT` | `/propostas/{id}/aceitar` | JWT (Receptor) |