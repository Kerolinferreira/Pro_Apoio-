# Backend – ProApoio

Este projeto é o backend da aplicação ProApoio, desenvolvido em **Laravel 10** e **PHP 8.2+**. Ele expõe uma API RESTful que atende às funcionalidades de cadastro de candidatos e instituições, vagas, propostas, notificações e outros módulos descritos no blueprint do projeto.

## Requisitos

- PHP >= 8.2 com extensões **PDO**, **Mbstring** e **OpenSSL** habilitadas
- Composer
- MySQL 8.0+
- Node.js e NPM (apenas para executar testes de integração ou ferramentas auxiliares)

## Instalação

1. Clone o repositório e acesse a pasta `api_proapoio`:

   ```bash
   cd api_proapoio
   ```

2. Instale as dependências do PHP:

   ```bash
   composer install
   ```

3. Copie o arquivo `.env.example` para `.env` e configure as variáveis de ambiente:

   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` para o seu banco MySQL
   - `APP_URL` como `http://localhost:8000`
   - Configurações de e‑mail (SMTP) para envio de notificações

4. Gere a chave da aplicação:

   ```bash
   php artisan key:generate
   ```

5. Execute as migrações e seeders para criar o esquema do banco de dados e dados de teste:

   ```bash
   php artisan migrate --seed
   ```

6. Inicie o servidor de desenvolvimento:

   ```bash
   php artisan serve
   ```

7. A API estará disponível em `http://localhost:8000/api`.

## Testes

Para rodar os testes de feature e unitários (PHPUnit ou Pest), execute:

```bash
php artisan test
```

## Funcionalidades Implementadas

- **Autenticação e Registro**: suporta registro de candidatos e instituições com validações específicas para CPF, CNPJ, CEP e telefones (via pacote `laravellegends/pt-br-validator`), login, logout, recuperação e redefinição de senha.
- **Perfis**: consulta e atualização de perfil de candidatos e instituições, incluindo experiências profissionais e pessoais.
- **Vagas**: criação, listagem, edição e alteração de status de vagas por instituições; busca pública com filtros de palavra‑chave, cidade e regime de contratação; marcação de vagas favoritas por candidatos.
- **Candidatos**: busca pública de candidatos com filtros básicos e visualização de perfis sem dados sensíveis.
- **Propostas**: envio, listagem (enviadas ou recebidas), aceitação, recusa e cancelamento de propostas entre candidatos e instituições.
- **Notificações**: sistema de notificações via banco de dados utilizando o mecanismo nativo do Laravel. Usuários recebem avisos de novas propostas e propostas aceitas e podem marcar notificações como lidas.
- **APIs Externas**: proxy para ViaCEP e ReceitaWS para consulta de CEP e CNPJ.
- **Segurança**: uso de Laravel Sanctum para autenticação via tokens API, validação rigorosa de entradas, verificação de senha atual antes de alteração de senha ou exclusão de conta.

## Observações

- Este README resume o funcionamento do backend para fins de desenvolvimento local. Em produção, é recomendável configurar cache, filas e serviços de email/SMS, além de definir uma política de CORS adequada e camadas de rate limiting.