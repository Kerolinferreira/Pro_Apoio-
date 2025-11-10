import axios from 'axios';

// Define a URL base da API lendo a variável de ambiente
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

/**
 * Instância do Axios para comunicação com o backend.
 */
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

/**
 * Interceptor de Requisição:
 * Em cada requisição, verifica se existe um token de autenticação.
 * Prioriza localStorage (sessão persistente), mas também verifica sessionStorage.
 * Se existir, o adiciona ao cabeçalho 'Authorization' como um Bearer Token.
 */
api.interceptors.request.use(config => {
  // Tenta pegar o token do localStorage primeiro (sessão persistente - "lembrar-me")
  // Se não encontrar, tenta o sessionStorage (sessão temporária)
  const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// --- LÓGICA DE LOGOUT GLOBAL ---
// Para desacoplar a camada de API do AuthContext, criamos um callback.
// O AuthContext será responsável por registrar sua função de logout aqui.
let logoutCallback: () => void;

export const setLogoutCallback = (callback: () => void) => {
  logoutCallback = callback;
};
// --------------------------------

/**
 * Interceptor de Resposta:
 * Trata erros de API de forma global.
 */
api.interceptors.response.use(
  (response) => response, // Para respostas de sucesso, não faz nada.
  (error) => {
    // Verifica se o erro é uma resposta da API com status 401 (Não Autorizado)
    if (error.response && error.response.status === 401) {
      // Se o erro for 401, chama a função de logout global (se registrada).
      // Isso acontece se o token for inválido, expirado ou ausente.
      if (logoutCallback) {
        logoutCallback();
      }
    }
    // Rejeita a promise para que o erro possa ser tratado localmente (ex: em um .catch) se necessário.
    return Promise.reject(error);
  }
);

export default api;
