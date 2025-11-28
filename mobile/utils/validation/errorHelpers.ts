interface ValidationError {
  [field: string]: string[];
}

interface ApiError {
  response?: {
    data?: {
      message?: string;
      errors?: ValidationError;
    };
  };
  message?: string;
}

export const extractErrorMessage = (error: ApiError): string => {
  const responseErrors = error.response?.data?.errors;

  if (responseErrors && Object.keys(responseErrors).length > 0) {
    return Object.entries(responseErrors)
      .map(([field, messages]) => {
        const fieldName = formatFieldName(field);
        return `${fieldName}: ${messages[0]}`;
      })
      .join('\n');
  }

  return error.response?.data?.message || error.message || 'Erro inesperado';
};

const formatFieldName = (field: string): string => {
  const fieldNames: Record<string, string> = {
    drug_id: 'Medicamento',
    lot_number: 'Número do lote',
    expires_at: 'Data de vencimento',
    quantity: 'Quantidade',
  };

  return fieldNames[field] || field;
};
