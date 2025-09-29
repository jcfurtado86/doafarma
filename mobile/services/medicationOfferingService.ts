import api from './api';
import {
  MedicationOffering,
  CreateMedicationOfferingData,
  UpdateMedicationOfferingData,
} from '@/types/medicationOffering';

export const medicationOfferingService = {
  // Listar todas as ofertas do médico logado
  list: async (includeDrug = true): Promise<MedicationOffering[]> => {
    try {
      const response = await api.get('/v1/medication-offerings', {
        params: {
          include: includeDrug ? 'drug' : undefined,
        },
      });
      return response.data.data;
    } catch (error) {
      console.error('Erro ao listar ofertas de medicamentos:', error);
      throw error;
    }
  },

  // Buscar uma oferta específica
  show: async (id: number, includeDrug = true): Promise<MedicationOffering> => {
    try {
      const response = await api.get(`/v1/medication-offerings/${id}`, {
        params: {
          include: includeDrug ? 'drug' : undefined,
        },
      });
      return response.data.data;
    } catch (error) {
      console.error('Erro ao buscar oferta de medicamento:', error);
      throw error;
    }
  },

  // Criar nova oferta
  create: async (data: CreateMedicationOfferingData): Promise<MedicationOffering> => {
    try {
      const response = await api.post('/v1/medication-offerings', data);
      return response.data.data;
    } catch (error) {
      console.error('Erro ao criar oferta de medicamento:', error);
      throw error;
    }
  },

  // Atualizar oferta existente
  update: async (id: number, data: UpdateMedicationOfferingData): Promise<MedicationOffering> => {
    try {
      const response = await api.put(`/v1/medication-offerings/${id}`, data);
      return response.data.data;
    } catch (error) {
      console.error('Erro ao atualizar oferta de medicamento:', error);
      throw error;
    }
  },

  // Deletar oferta
  delete: async (id: number): Promise<void> => {
    try {
      await api.delete(`/v1/medication-offerings/${id}`);
    } catch (error) {
      console.error('Erro ao deletar oferta de medicamento:', error);
      throw error;
    }
  },
};
