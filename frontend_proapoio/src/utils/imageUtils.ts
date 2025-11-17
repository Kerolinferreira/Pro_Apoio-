/**
 * Utility functions for handling image URLs
 */

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
const BACKEND_BASE_URL = API_BASE_URL.replace('/api', '');

/**
 * Converte URLs relativas de imagens para URLs absolutas do backend
 * @param relativeUrl URL relativa (ex: /storage/fotos-candidatos/...)
 * @returns URL absoluta do backend (ex: http://localhost:8000/storage/...)
 */
export function getAbsoluteImageUrl(relativeUrl: string | null | undefined): string | null {
  if (!relativeUrl) return null;

  // Se já é uma URL absoluta, retorna como está
  if (relativeUrl.startsWith('http://') || relativeUrl.startsWith('https://')) {
    return relativeUrl;
  }

  // Se é uma URL relativa, adiciona o base URL do backend
  if (relativeUrl.startsWith('/')) {
    return `${BACKEND_BASE_URL}${relativeUrl}`;
  }

  return relativeUrl;
}
