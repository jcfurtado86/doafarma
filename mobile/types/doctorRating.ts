export interface DoctorRating {
  id: number;
  rating: number;
  comment: string | null;
  created_at: string;
  updated_at: string;
  doctor?: {
    id: number;
    name: string;
  };
  receptor?: {
    id: number;
    name: string;
  };
}

export interface CreateDoctorRatingData {
  rating: number;
  comment?: string;
}

export const RATING_LABELS: Record<number, string> = {
  1: 'Muito ruim',
  2: 'Ruim',
  3: 'Regular',
  4: 'Bom',
  5: 'Excelente',
};
