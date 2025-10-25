import React from "react";

export const LoadingSpinner = () => (
  <div className="text-center py-10">Carregando...</div>
);

export const ErrorAlert = ({ message }: { message: string }) => (
  <div className="text-center py-10 text-red-600">{message}</div>
);

export const Button = (
  props: React.ButtonHTMLAttributes<HTMLButtonElement> & { children?: React.ReactNode }
) => <button className="px-3 py-2 border rounded" {...props} />;
