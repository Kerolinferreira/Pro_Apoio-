# ProApoio Frontend – Scaffold

Estrutura mínima adicionada para rodar o frontend (Vite + React + TypeScript).

## Passos
1. Copie estes arquivos para a raiz do seu `frontend_proapoio/` (onde ficam `src/` e `README.md`).
2. Renomeie `.env.example` para `.env` e ajuste `VITE_API_URL`.
3. Instale dependências:
   ```bash
   npm i
   npm run dev
   ```
4. Garanta que `src/main.tsx` importe os estilos globais:
   ```ts
   import './styles/global.css'
   ```
