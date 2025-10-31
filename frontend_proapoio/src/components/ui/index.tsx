import React from 'react';

// Componentes que já existiam
export const LoadingSpinner = () => (
  <div className="text-center py-10">Carregando...</div>
);

export const ErrorAlert = ({ message }: { message: string }) => (
  <div className="text-center py-10 text-red-600">{message}</div>
);

export const Button = (
  props: React.ButtonHTMLAttributes<HTMLButtonElement> & { children?: React.ReactNode }
) => <button className="px-3 py-2 border rounded" {...props} />;

// =============================================================
// 👇 COMPONENTES CORRIGIDOS/ADICIONADOS (Causa da tela branca) 👇
// =============================================================

// 1. Componente Input (Base)
export const Input = (
  props: React.InputHTMLAttributes<HTMLInputElement> & { label?: string }
) => (
  <div className="mb-4">
    {props.label && <label className="block text-gray-700 text-sm font-bold mb-2">{props.label}</label>}
    <input 
      className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
      type="text" 
      {...props} 
    />
  </div>
);

// 2. Componente Select (Base)
export const Select = (
  props: React.SelectHTMLAttributes<HTMLSelectElement> & { label?: string, options: { value: string, label: string }[] }
) => (
  <div className="mb-4">
    {props.label && <label className="block text-gray-700 text-sm font-bold mb-2">{props.label}</label>}
    <select 
      className="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
      {...props}
    >
      {props.options.map(option => (
        <option key={option.value} value={option.value}>{option.label}</option>
      ))}
    </select>
  </div>
);

// 3. Componente CheckboxGroup (Base)
interface CheckboxGroupProps {
  label: string;
  options: { value: string; label: string }[];
  selectedValues: string[];
  onChange: (newValues: string[]) => void;
}

export const CheckboxGroup: React.FC<CheckboxGroupProps> = ({ label, options, selectedValues, onChange }) => {
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    const isChecked = e.target.checked;
    
    const newValues = isChecked
      ? [...selectedValues, value]
      : selectedValues.filter(v => v !== value);
      
    onChange(newValues);
  };

  return (
    <div className="mb-4">
      <label className="block text-gray-700 text-sm font-bold mb-2">{label}</label>
      <div className="flex flex-wrap gap-4">
        {options.map(option => (
          <label key={option.value} className="inline-flex items-center">
            <input
              type="checkbox"
              className="form-checkbox h-5 w-5 text-indigo-600"
              value={option.value}
              checked={selectedValues.includes(option.value)}
              onChange={handleChange}
            />
            <span className="ml-2 text-gray-700">{option.label}</span>
          </label>
        ))}
      </div>
    </div>
  );
};