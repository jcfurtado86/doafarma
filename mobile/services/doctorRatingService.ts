import api from './api';
import { DoctorRating, CreateDoctorRatingData } from '@/types/doctorRating';
import { extractErrorMessage } from '@/utils/validation/errorHelpers';

export const doctorRatingService = {
  /**
   * Create a rating for a doctor after completed appointment
   */
  create: async (appointmentId: number, data: CreateDoctorRatingData): Promise<DoctorRating> => {
    try {
      const response = await api.post(`/v1/doctor-ratings/${appointmentId}`, data);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  /**
   * Update an existing rating
   */
  update: async (ratingId: number, data: CreateDoctorRatingData): Promise<DoctorRating> => {
    try {
      const response = await api.patch(`/v1/doctor-ratings/${ratingId}`, data);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  /**
   * Get rating for a specific appointment (or null if not rated)
   */
  getByAppointment: async (appointmentId: number): Promise<DoctorRating | null> => {
    try {
      const response = await api.get(`/v1/doctor-ratings/appointment/${appointmentId}`);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  /**
   * List all ratings for a specific doctor
   */
  listByDoctor: async (doctorId: number): Promise<DoctorRating[]> => {
    try {
      const response = await api.get(`/v1/doctor-ratings/doctor/${doctorId}`);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },
};
