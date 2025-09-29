export interface Drug {
  id: number;
  product_name: string;
  substance: string;
  laboratory: string;
}

export interface MedicationOffering {
  id: number;
  lot_number: string;
  expires_at: string;
  quantity: number;
  drug?: Drug;
  user?: {
    id: number;
    name: string;
    email: string;
  };
}

export interface CreateMedicationOfferingData {
  drug_id: number;
  lot_number: string;
  expires_at: string;
  quantity: number;
}

export interface UpdateMedicationOfferingData {
  drug_id?: number;
  lot_number?: string;
  expires_at?: string;
  quantity?: number;
}
