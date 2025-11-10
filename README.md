# 🎓 PRO APOIO - Plataforma de Conexão para Agentes de Apoio Escolar

**Pro Apoio** é uma plataforma que conecta profissionais qualificados (agentes de apoio) com instituições de ensino que necessitam de suporte especializado para alunos com deficiência.

[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![React](https://img.shields.io/badge/React-18-61DAFB?logo=react&logoColor=black)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Vite](https://img.shields.io/badge/Vite-5-646CFF?logo=vite&logoColor=white)](https://vitejs.dev)

---

## 📋 Índice

- [🎯 Sobre o Projeto](#-sobre-o-projeto)
- [✨ Funcionalidades](#-funcionalidades)
- [🛠️ Tecnologias](#️-tecnologias)
- [🚀 Instalação Rápida](#-instalação-rápida)
- [🔧 Configuração Manual](#-configuração-manual)
- [🧪 Testes](#-testes)
- [🚢 Deploy](#-deploy)
- [🤝 Contribuindo](#-contribuindo)
- [📄 Licença](#-licença)

---

## 🎯 Sobre o Projeto

O **Pro Apoio** é uma solução completa que facilita a contratação de agentes de apoio escolar, promovendo inclusão e acessibilidade na educação.

### Para Candidatos (Agentes de Apoio):
- ✅ Criar perfil profissional completo
- ✅ Buscar vagas por localização e especialidade
- ✅ Enviar propostas para instituições
- ✅ Gerenciar experiências profissionais e pessoais

### Para Instituições de Ensino:
- ✅ Publicar vagas com requisitos específicos
- ✅ Buscar candidatos qualificados
- ✅ Receber e avaliar propostas
- ✅ Gerenciar múltiplas vagas simultaneamente

---

## ✨ Funcionalidades

### 🔐 Autenticação
- Cadastro separado para Candidatos e Instituições
- Login seguro com JWT/Sanctum
- Recuperação de senha via email
- Validação robusta de dados (CPF, CNPJ, etc)

### 👤 Perfis
- Perfis completos e personalizáveis
- Upload de foto/logo
- Gerenciamento de experiências
- Endereços com busca automática por CEP

### 💼 Vagas
- Publicação de vagas com filtros avançados
- Busca por localização, tipo de deficiência, remuneração
- Status de vaga (Ativa, Pausada, Fechada)
- Vagas salvas (favoritos)

### 📝 Propostas
- Envio e recebimento de propostas
- Aceitação/recusa com histórico
- Notificações em tempo real
- Sistema de mensagens

### 🔔 Notificações
- Notificações de novas propostas
- Alertas de vagas fechadas
- Sistema de badges não lidas

---

## 🛠️ Tecnologias

### Backend
- **Laravel 10** - Framework PHP moderno
- **MySQL/PostgreSQL** - Banco de dados relacional
- **Sanctum** - Autenticação de API
- **Eloquent ORM** - Mapeamento objeto-relacional
- **Laravel Mail** - Envio de emails

### Frontend
- **React 18** - Biblioteca JavaScript
- **TypeScript** - Superset tipado de JavaScript
- **Vite** - Build tool ultrarrápido
- **React Router** - Roteamento SPA
- **Zod** - Validação de schemas
- **Axios** - Cliente HTTP

### Ferramentas
- **Composer** - Gerenciador de dependências PHP
- **npm** - Gerenciador de pacotes Node.js
- **Git** - Controle de versão

---

## 🚀 Instalação Rápida

### Opção 1: Script Automatizado (Recomendado)

#### 🐧 Linux / 🍎 macOS

```bash
# 1. Clone o repositório
git clone https://github.com/Kerolinferreira/Pro_Apoio-.git
cd Pro_Apoio-

# 2. Execute o script de instalação
chmod +x install.sh
./install.sh

# 3. Siga as instruções na tela
```

#### 🪟 Windows

```cmd
# 1. Clone o repositório
git clone https://github.com/Kerolinferreira/Pro_Apoio-.git
cd Pro_Apoio-

# 2. Execute o script de instalação
install.bat

# 3. Siga as instruções na tela
```

### Opção 2: Comando único

#### Linux/Mac
```bash
git clone https://github.com/Kerolinferreira/Pro_Apoio-.git && \
cd Pro_Apoio- && \
chmod +x install.sh && \
./install.sh
```

#### Windows (PowerShell)
```powershell
git clone https://github.com/Kerolinferreira/Pro_Apoio-.git; cd Pro_Apoio-; .\install.bat
```

### Depois da instalação:

#### Linux/Mac
**Terminal 1 - Backend:**
```bash
cd api_proapoio
php artisan serve
```

**Terminal 2 - Frontend:**
```bash
cd frontend_proapoio
npm run dev
```

#### Windows
**Terminal 1 - Backend:**
```cmd
cd api_proapoio
php artisan serve
```

**Terminal 2 - Frontend:**
```cmd
cd frontend_proapoio
npm run dev
```

**Acesse:** http://localhost:5174

---

### 📝 Scripts Disponíveis

| Script | Linux/Mac | Windows | Função |
|--------|-----------|---------|---------|
| **Instalação** | `./install.sh` | `install.bat` | Instalação automatizada completa |
| **Verificação** | `./verificar.sh` | `verificar.bat` | Verifica se está tudo configurado |

💡 **Dica Windows:** Se preferir usar os scripts `.sh`, você pode usar:
- **Git Bash** (vem com Git for Windows)
- **WSL** (Windows Subsystem for Linux)
- **PowerShell** com WSL instalado

---

## 🔧 Configuração Manual

Se preferir configurar manualmente:

### 1. Backend (Laravel)

```bash
cd api_proapoio

# Instalar dependências
composer install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Configurar banco de dados no .env
# DB_CONNECTION=mysql
# DB_DATABASE=proapoio
# DB_USERNAME=seu_usuario
# DB_PASSWORD=sua_senha

# Executar migrations
php artisan migrate

# (Opcional) Seeders
php artisan db:seed

# Iniciar servidor
php artisan serve
```

### 2. Frontend (React)

```bash
cd frontend_proapoio

# Instalar dependências
npm install

# Configurar ambiente
echo "VITE_API_URL=http://localhost:8000/api" > .env

# Iniciar servidor
npm run dev
```

---

## 📊 Estrutura do Projeto

```
Pro_Apoio-/
├── api_proapoio/           # Backend Laravel
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Middleware/
│   │   │   └── Requests/
│   │   ├── Models/
│   │   └── Enums/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   └── tests/
│
├── frontend_proapoio/      # Frontend React
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── contexts/
│   │   ├── services/
│   │   ├── utils/
│   │   └── types/
│   └── public/
│
├── install.sh              # Script de instalação
└── README.md               # Este arquivo
```

---

## 🔒 Segurança

O projeto implementa diversas medidas de segurança:

- ✅ Autenticação JWT/Sanctum
- ✅ Rate limiting em rotas sensíveis
- ✅ Sanitização de inputs
- ✅ Validação robusta de dados
- ✅ CSRF protection
- ✅ SQL injection prevention
- ✅ XSS protection
- ✅ CORS configurável

---

## 🧪 Testes

### Backend

```bash
cd api_proapoio
php artisan test
```

### Frontend

```bash
cd frontend_proapoio
npm run test
```

---

## 🚢 Deploy

### Backend (Laravel)

```bash
# Build de produção
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configure .env para produção
APP_ENV=production
APP_DEBUG=false
```

### Frontend (React)

```bash
# Build de produção
npm run build

# Arquivos estarão em dist/
# Sirva com Nginx, Apache, ou outro servidor
```

---

## 🤝 Contribuindo

Contribuições são bem-vindas! Para contribuir:

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/NovaFuncionalidade`)
3. Commit suas mudanças (`git commit -m 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/NovaFuncionalidade`)
5. Abra um Pull Request

### Diretrizes

- Siga os padrões de código existentes
- Escreva testes para novas funcionalidades
- Atualize a documentação quando necessário
- Use mensagens de commit descritivas

---

## 🐛 Reportando Bugs

Encontrou um bug? Por favor, [abra uma issue](https://github.com/Kerolinferreira/Pro_Apoio-/issues/new) com:

- Descrição clara do problema
- Passos para reproduzir
- Comportamento esperado vs atual
- Screenshots (se aplicável)
- Ambiente (OS, versões, etc)

---

---

## 📄 Licença

Este projeto está sob a licença [MIT](LICENSE).

---

## 👥 Autores

- **Kerolinferreira** - *Desenvolvimento* - [GitHub](https://github.com/Kerolinferreira)
- **Claude Code** - *Auditoria e Documentação*

---

## 📞 Contato

Para dúvidas ou sugestões, abra uma [issue](https://github.com/Kerolinferreira/Pro_Apoio-/issues) no GitHub.

---

## 🌟 Apoie o Projeto

Se este projeto foi útil para você, considere dar uma ⭐ no GitHub!

---

**Desenvolvido com ❤️ para promover inclusão e acessibilidade na educação**

---

## 📚 Links Úteis

- [Documentação Laravel](https://laravel.com/docs)
- [Documentação React](https://react.dev)
- [Documentação TypeScript](https://www.typescriptlang.org/docs)
- [Documentação Vite](https://vitejs.dev)
- [Guia de Acessibilidade](https://www.w3.org/WAI/)

---

<div align="center">

**Pro Apoio** - Conectando Talentos, Promovendo Inclusão

</div>
