export const ValidationMessages = {
  date: {
    invalid: 'Data inválida. Use o formato DD/MM/AAAA',
    inPast: 'A data não pode ser no passado',
    mustBeTodayOrFuture: 'A data deve ser hoje ou no futuro',
  },
  time: {
    invalid: 'Horário inválido. Use o formato HH:MM',
  },
} as const;
