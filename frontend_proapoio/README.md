# Frontend – ProApoio

Este projeto é o front‑end da aplicação ProApoio, construído com **React 18**, **Vite** e **TypeScript**. Utiliza **TailwindCSS** para estilização e `axios` para comunicação com a API do backend.

## Requisitos

- Node.js >= 18
- NPM

## Instalação

1. Acesse a pasta `frontend_proapoio`:

   ```bash
   cd frontend_proapoio
   ```

2. Instale as dependências JavaScript:

   ```bash
   npm install
   ```

3. Crie um arquivo `.env` (ou `.env.local`) com a URL base da API caso seja diferente de `http://localhost:8000/api`, por exemplo:

   ```env
   VITE_API_URL=http://localhost:8000/api
   ```

   O arquivo `src/services/api.ts` lê essa variável para configurar o Axios.

4. Inicie o servidor de desenvolvimento:

   ```bash
   npm run dev
   ```

5. A aplicação estará disponível em `http://localhost:5174` (porta padrão do Vite).

## Scripts Disponíveis

- `npm run dev` – inicia o servidor de desenvolvimento com hot reload.
- `npm run build` – gera a versão otimizada para produção.
- `npm run test` – executa os testes (caso configurados com Jest ou outra biblioteca).

## Funcionalidades Implementadas

- **Cadastro e Login**: formulários de registro para candidatos e instituições com campos específicos (CPF, CNPJ, CEP, telefone, escolaridade), integração com a API para criação de contas e login via token.
- **Recuperação de Senha**: telas para solicitar e redefinir senha, enviando requisições aos endpoints `/forgot-password` e `/reset-password` do backend.
- **Busca de Vagas**: página com filtros de palavra‑chave, cidade e regime de contratação; listagem paginada de vagas utilizando o componente `VagaCard`.
- **Busca de Candidatos**: página com filtros básicos de nome, cidade e escolaridade; exibição de resultados públicos sem dados sensíveis.
- **Minhas Propostas**: aba de propostas enviadas e recebidas com listagem simples e mudança dinâmica entre as guias.
- **Vagas Salvas**: listagem de vagas favoritas do candidato com opção de remoção.
- **Notificações**: componente `NotificationBell` que realiza polling na API a cada minuto e mostra o número de notificações não lidas.

## Acessibilidade

Os componentes utilizam elementos semânticos, rótulos (`label`) associados a campos de formulário, foco visível e atributos ARIA quando necessário. Recomenda‑se usar um leitor de telas (ex.: NVDA) para validar a navegação por teclado.

## Observações

- Esta aplicação espera que o backend Laravel esteja em execução e acessível no endereço configurado em `VITE_API_URL`.
- Para ambientes de produção, configure variáveis de ambiente adequadas, políticas de CORS e TLS (HTTPS).