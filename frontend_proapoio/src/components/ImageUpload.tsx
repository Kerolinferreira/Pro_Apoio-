import React, { useState, useRef } from 'react';
import { Upload, X, Loader2, Camera } from 'lucide-react';
import { useToast } from './Toast';
import api from '../services/api';
import { parseApiError } from '../utils/errorHandler';
import { logger } from '../utils/logger';
import { getAbsoluteImageUrl } from '../utils/imageUtils';

interface ImageUploadProps {
  currentImageUrl?: string | null;
  onUploadSuccess: (imageUrl: string) => void;
  uploadEndpoint: string;
  fieldName: string;
  label: string;
  helperText?: string;
  maxSizeMB?: number;
  disabled?: boolean;
}

/**
 * @component ImageUpload
 * @description Componente reutilizável para upload de imagens (foto de perfil, logos, etc)
 */
const ImageUpload: React.FC<ImageUploadProps> = ({
  currentImageUrl,
  onUploadSuccess,
  uploadEndpoint,
  fieldName,
  label,
  helperText = 'JPEG, PNG ou WEBP. Máximo 2MB. Mínimo 100x100px.',
  maxSizeMB = 2,
  disabled = false,
}) => {
  const toast = useToast();
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [preview, setPreview] = useState<string | null>(getAbsoluteImageUrl(currentImageUrl) || null);
  const [uploading, setUploading] = useState(false);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Validação de tipo
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
      toast.error('Formato inválido. Use JPEG, PNG ou WEBP.');
      return;
    }

    // Validação de tamanho
    const maxSizeBytes = maxSizeMB * 1024 * 1024;
    if (file.size > maxSizeBytes) {
      toast.error(`Arquivo muito grande. Máximo ${maxSizeMB}MB.`);
      return;
    }

    // Validação de dimensões mínimas
    const img = new Image();
    const objectUrl = URL.createObjectURL(file);

    img.onload = () => {
      URL.revokeObjectURL(objectUrl);

      // Validar dimensões mínimas (100x100px)
      if (img.width < 100 || img.height < 100) {
        toast.error('Imagem muito pequena. Mínimo 100x100 pixels.');
        return;
      }

      // Validar dimensões máximas (4000x4000px para evitar imagens excessivamente grandes)
      if (img.width > 4000 || img.height > 4000) {
        toast.error('Imagem muito grande. Máximo 4000x4000 pixels.');
        return;
      }

      // Criar preview após validação
      const reader = new FileReader();
      reader.onloadend = () => {
        setPreview(reader.result as string);
        setSelectedFile(file);
      };
      reader.readAsDataURL(file);
    };

    img.onerror = () => {
      URL.revokeObjectURL(objectUrl);
      toast.error('Não foi possível carregar a imagem.');
    };

    img.src = objectUrl;
  };

  const handleUpload = async () => {
    if (!selectedFile) {
      toast.error('Selecione uma imagem primeiro.');
      return;
    }

    setUploading(true);

    try {
      const formData = new FormData();
      formData.append(fieldName, selectedFile);

      const response = await api.post(uploadEndpoint, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      const imageUrl = response.data[`${fieldName}_url`] || response.data.url || response.data.path;

      onUploadSuccess(imageUrl);
      toast.success('Imagem enviada com sucesso!');
      setSelectedFile(null);
    } catch (err) {
      logger.error('Erro ao fazer upload:', err);

      const { generalMessage } = parseApiError(err);
      const status = (err as any)?.response?.status;
      const errorData = (err as any)?.response?.data;

      if (status === 400) {
        toast.error(errorData?.message || 'Arquivo inválido.');
      } else if (status === 413) {
        toast.error('Arquivo muito grande. Reduza o tamanho da imagem.');
      } else if (status === 422) {
        const validationErrors = errorData?.errors;
        if (validationErrors && validationErrors[fieldName]) {
          toast.error(validationErrors[fieldName][0]);
        } else {
          toast.error('Erro de validação. Verifique o arquivo.');
        }
      } else {
        toast.error(generalMessage || 'Não foi possível fazer upload da imagem.');
      }

      // Reverter preview em caso de erro
      setPreview(getAbsoluteImageUrl(currentImageUrl) || null);
      setSelectedFile(null);
    } finally {
      setUploading(false);
      // Limpar input
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  const handleCancel = () => {
    setPreview(getAbsoluteImageUrl(currentImageUrl) || null);
    setSelectedFile(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleClickUpload = () => {
    fileInputRef.current?.click();
  };

  return (
    <div className="space-y-sm">
      <label className="form-label">{label}</label>

      <div className="flex-group-md-row gap-md" style={{ alignItems: 'flex-start' }}>
        {/* Preview da Imagem */}
        <div
          className="image-upload-preview"
          style={{
            width: '150px',
            height: '150px',
            borderRadius: '8px',
            overflow: 'hidden',
            border: '2px dashed var(--color-border)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            backgroundColor: 'var(--color-bg-secondary)',
            position: 'relative',
          }}
        >
          {preview ? (
            <img
              src={preview}
              alt="Preview"
              style={{
                width: '100%',
                height: '100%',
                objectFit: 'cover',
              }}
            />
          ) : (
            <Camera size={48} className="text-muted" />
          )}
        </div>

        {/* Controles */}
        <div className="flex-1 space-y-sm">
          <p className="text-sm text-muted">{helperText}</p>

          <input
            ref={fileInputRef}
            type="file"
            accept="image/jpeg,image/jpg,image/png,image/webp"
            onChange={handleFileSelect}
            style={{ display: 'none' }}
            disabled={disabled || uploading}
          />

          <div className="flex-actions-start">
            <button
              type="button"
              onClick={handleClickUpload}
              className="btn-secondary btn-sm btn-icon"
              disabled={disabled || uploading}
            >
              <Upload size={16} />
              Escolher Imagem
            </button>

            {selectedFile && (
              <>
                <button
                  type="button"
                  onClick={handleUpload}
                  className="btn-primary btn-sm btn-icon"
                  disabled={uploading}
                >
                  {uploading ? (
                    <>
                      <Loader2 size={16} className="icon-spin" />
                      Enviando...
                    </>
                  ) : (
                    <>
                      <Upload size={16} />
                      Enviar
                    </>
                  )}
                </button>

                <button
                  type="button"
                  onClick={handleCancel}
                  className="btn-secondary btn-sm btn-icon"
                  disabled={uploading}
                >
                  <X size={16} />
                  Cancelar
                </button>
              </>
            )}
          </div>

          {selectedFile && (
            <p className="text-sm text-muted">
              Arquivo selecionado: {selectedFile.name} ({(selectedFile.size / 1024).toFixed(0)} KB)
            </p>
          )}
        </div>
      </div>
    </div>
  );
};

export default ImageUpload;
