import axios from 'axios';

// Define a URL base da API lendo a variável de ambiente
// Assume que existe uma variável VITE_API_BASE_URL (ex: http://localhost:8000/api)
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

/**
 * Instância do Axios para comunicação com o backend.
 * Todos os interceptors de AuthContext operam nesta instância.
 */
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

/**
 * Exporta a instância para ser utilizada em todo o projeto.
 * Usamos Default Export (export default api) para evitar erros de Named Export.
 */
export default api;
