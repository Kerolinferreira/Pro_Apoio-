/**
 * Logger utility que só funciona em desenvolvimento.
 * Em produção, os logs são silenciados para não expor informações sensíveis.
 */

const isDevelopment = import.meta.env.MODE !== 'production';

export const logger = {
  error: (...args: unknown[]) => {
    if (isDevelopment) {
      console.error(...args);
    }
  },

  warn: (...args: unknown[]) => {
    if (isDevelopment) {
      console.warn(...args);
    }
  },

  info: (...args: unknown[]) => {
    if (isDevelopment) {
      console.info(...args);
    }
  },

  log: (...args: unknown[]) => {
    if (isDevelopment) {
      console.log(...args);
    }
  },
};
