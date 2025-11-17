import { useEffect, useRef } from 'react';

/**
 * Hook customizado para gerenciar foco e trap de foco em modais
 * CORREÇÃO P12: Melhoria de acessibilidade para leitores de tela
 *
 * @param isOpen - Estado de abertura do modal
 * @param isLoading - Se o modal está processando (desabilita ESC)
 * @param onClose - Callback para fechar o modal
 * @returns Refs para o modal e primeiro elemento focável
 */
export function useModalFocus(
  isOpen: boolean,
  isLoading: boolean = false,
  onClose?: () => void
) {
  const modalRef = useRef<HTMLDivElement>(null);
  const firstFocusableRef = useRef<HTMLButtonElement>(null);

  // Foco automático ao abrir o modal
  useEffect(() => {
    if (isOpen && firstFocusableRef.current) {
      // Pequeno delay para garantir que o modal esteja renderizado
      const timer = setTimeout(() => {
        firstFocusableRef.current?.focus();
      }, 100);
      return () => clearTimeout(timer);
    }
    return undefined;
  }, [isOpen]);

  // Trap de foco dentro do modal (Tab cycling) + ESC para fechar
  useEffect(() => {
    if (!isOpen) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      // ESC para fechar (se não estiver loading)
      if (e.key === 'Escape' && !isLoading && onClose) {
        onClose();
        return;
      }

      // Tab cycling
      if (e.key === 'Tab') {
        const modal = modalRef.current;
        if (!modal) return;

        const focusableElements = modal.querySelectorAll<HTMLElement>(
          'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );

        if (focusableElements.length === 0) return;

        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (e.shiftKey) {
          // Shift+Tab: voltando
          if (document.activeElement === firstElement) {
            lastElement?.focus();
            e.preventDefault();
          }
        } else {
          // Tab: avançando
          if (document.activeElement === lastElement) {
            firstElement?.focus();
            e.preventDefault();
          }
        }
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, isLoading, onClose]);

  return { modalRef, firstFocusableRef };
}
