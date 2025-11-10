# PRO APOIO: Plataforma de Conexão para Agentes de Apoio Escolar

## Promovendo Inclusão e Acessibilidade na Educação

[![Licença](https://img.shields.io/badge/Licen%C3%A7a-MIT-green)](LICENSE)

[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![React](https://img.shields.io/badge/React-18-61DAFB?logo=react&logoColor=black)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Vite](https://img.shields.io/badge/Vite-5-646CFF?logo=vite&logoColor=white)](https://vitejs.dev)

---

## Sumário Executivo

- [1. Introdução e Justificativa](#1-introdução-e-justificativa)
- [2. Propósito e Escopo](#2-propósito-e-escopo)
- [3. Arquitetura e Funcionalidades](#3-arquitetura-e-funcionalidades)
- [4. Stack Tecnológico](#4-stack-tecnológico)
- [5. Guia de Instalação e Execução](#5-guia-de-instalação-e-execução)
- [6. Testes e Segurança](#6-testes-e-segurança)
- [7. Autoria e Contato](#7-autoria-e-contato)
- [8. Licença](#8-licença)

---

## 1. Introdução e Justificativa

O **Pro Apoio** é um projeto de software desenvolvido com o objetivo de mitigar a lacuna existente entre a demanda por **Agentes de Apoio Escolar** qualificados e a oferta de profissionais para instituições de ensino. O projeto visa centralizar e otimizar o processo de contratação, essencial para garantir a **inclusão plena** de estudantes com deficiência (PcD) conforme as diretrizes da Lei Brasileira de Inclusão (Lei nº 13.146/2015).

Este sistema atua como uma **Plataforma de Conexão (Marketplace)**, estruturada para atender às necessidades específicas do ambiente educacional.

### Contexto Acadêmico

* **Instituição:** *ATEC Itapetininga*
* **Disciplina/Módulo:** *Trabalho de Graduação*
* **Semestre/Ano:** * 2025/2*

---

## 2. Propósito e Escopo

O escopo do projeto abrange a criação de dois perfis primários de usuário, cada um com funcionalidades dedicadas:

| Usuário | Objetivo Principal | Funcionalidades Chave |
| :--- | :--- | :--- |
| **Candidato (Agente de Apoio)** | Apresentar qualificações e buscar oportunidades de trabalho. | Criação de perfil profissional detalhado, busca por vagas com filtros de especialidade e localização, e gerenciamento de propostas. |
| **Instituição de Ensino** | Publicar e gerenciar vagas, e localizar profissionais especializados. | Publicação de vagas com requisitos específicos, recebimento/avaliação de propostas e gerenciamento centralizado de candidatos. |

---

## 3. Arquitetura e Funcionalidades

O projeto adota uma arquitetura *API-First*, separando o *backend* (API) do *frontend* (SPA), garantindo escalabilidade e flexibilidade para o desenvolvimento.

### 3.1. Arquitetura de Software
O sistema é dividido em duas componentes principais:

* **Backend (API):** Desenvolvido em **Laravel**, responsável pela lógica de negócios, manipulação do banco de dados e autenticação via API.
* **Frontend (SPA):** Desenvolvido em **React/TypeScript**, responsável pela interface do usuário e interação assíncrona com o Backend.

### 3.2. Funcionalidades Detalhadas

#### Módulo de Autenticação e Segurança
* Cadastro e Login Separados (Candidato/Instituição).
* Autenticação via **JWT/Sanctum** para comunicação segura.
* Recuperação de credenciais via e-mail.

#### Módulo de Perfis (User Management)
* **Customização:** Campos específicos para credenciais profissionais (Agente) e dados corporativos (Instituição - CNPJ, etc.).
* **Localização:** Uso de serviços de geolocalização para busca automática de endereços (via CEP).

#### Módulo de Vagas e Matchmaking
* Publicação de vagas com taxonomias detalhadas (Tipo de Deficiência, Remuneração, Horário).
* Mecanismo de busca e filtros avançados.

#### Módulo de Comunicação
* **Propostas:** Fluxo de envio, recebimento, aceite e recusa de propostas de trabalho.
* **Notificações:** Sistema em tempo real (badges) para alertar sobre novas propostas e *updates* de vagas.

---

## 4. Stack Tecnológico

A escolha das tecnologias baseou-se na robustez, performance e no ecossistema de suporte de cada ferramenta.

| Categoria | Tecnologia | Versão | Propósito Principal |
| :--- | :--- | :--- | :--- |
| **Backend (API)** | **Laravel** | 10 | Framework PHP principal, Lógica de Negócios e Rotas API. |
| | **MySQL/PostgreSQL** | *latest* | Banco de dados relacional (ORM Eloquent). |
| | **Sanctum** | *latest* | Geração e gerenciamento de tokens de API. |
| **Frontend (Interface)** | **React** | 18 | Construção da interface de usuário (Single Page Application - SPA). |
| | **TypeScript** | 5 | Tipagem estática para maior segurança e manutenção do código. |
| | **Vite** | 5 | Ferramenta de *build* e servidor de desenvolvimento otimizado. |
| **Utilitários** | **Composer** | *latest* | Gerenciamento de dependências PHP. |
| | **npm/Yarn** | *latest* | Gerenciamento de pacotes Node.js. |

---

## 5. Guia de Instalação e Execução

Para replicar e avaliar o projeto, siga os passos abaixo.

### 5.1. Pré-requisitos
Certifique-se de ter instalado:
* **Git**
* **PHP** (versão 8.1 ou superior)
* **Composer**
* **Node.js** (versão 18 ou superior) e **npm**
* Um servidor de banco de dados (MySQL ou PostgreSQL).

### 5.2. Instalação (Script Automatizado - Recomendado para Windows)

#### Windows
```cmd
# 1. Clone o repositório
git clone [https://github.com/Kerolinferreira/Pro_Apoio-.git](https://github.com/Kerolinferreira/Pro_Apoio-.git)
cd Pro_Apoio-

# 2. Execute o script de instalação
install.bat
### 5.3. Execução do Projeto

Após a instalação, abra dois terminais separados para iniciar o *backend* e o *frontend*.

| Componente | Terminal | Comandos (Windows/Gerais) |
| :--- | :--- | :--- |
| **Backend** | Terminal 1 | `cd api_proapoio` <br> `php artisan serve` |
| **Frontend** | Terminal 2 | `cd frontend_proapoio` <br> `npm run dev` |

**Acessar a Aplicação:** O projeto estará disponível em `http://localhost:5174`.


---

## 6. Testes e Segurança

### 6.1. Testes Unitários e de Integração
Os testes foram implementados para garantir a robustez e a integridade da lógica de negócios.

* **Backend:** Execute os testes do Laravel.
    ```bash
    cd api_proapoio
    php artisan test
    ```
* **Frontend:** Execute os testes do React.
    ```bash
    cd frontend_proapoio
    npm run test
    ```

### 6.2. Protocolos de Segurança
O projeto foi desenvolvido com foco em segurança, aplicando as seguintes práticas:
* **Validação:** Implementação de Zod (Frontend) e Validação Laravel (Backend) em todos os *inputs*.
* **Proteção de API:** Uso de *Rate Limiting* e Autenticação *Stateless* (Sanctum/JWT).
* **Prevenção:** Medidas contra XSS (*Cross-Site Scripting*), CSRF (*Cross-Site Request Forgery*) e *SQL Injection*.

---

## 7. Autoria e Contato

Este projeto foi concebido e desenvolvido como parte de um requisito acadêmico por:

| Nome Completo | Papel no Projeto | Contato (GitHub/LinkedIn) |
| :--- | :--- | :--- |
| **Kerolin Ferreira de Oliveira** | Desenvolvimento Full-Stack e Gestão do Projeto | [GitHub/Kerolinferreira](https://github.com/Kerolinferreira) |
| **Diogo Lima Gomes de Oliveira** | Desenvolvimento Backend e Arquitetura de Dados | [GitHub/diogolimaoliveira](https://github.com/diogolimaoliveira)* |


**Contribuições:**
* Para relatar bugs ou sugerir melhorias, utilize a seção **Issues** do repositório.

---



---

<div align="center">
  <h3>Pro Apoio - Desenvolvido com o objetivo de promover a inclusão social e educacional.</h3>
</div>