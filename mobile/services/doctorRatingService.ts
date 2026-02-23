import { apiClient } from './api';
import { DoctorRating, CreateDoctorRatingData, MyRatingsResponse } from '@/types/doctorRating';
import { extractErrorMessage } from '@/utils/validation/errorHelpers';

export const doctorRatingService = {
  create: async (appointmentId: number, data: CreateDoctorRatingData): Promise<DoctorRating> => {
    try {
      const response = await apiClient.post(`/v1/doctor-ratings/${appointmentId}`, data);
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  update: async (ratingId: number, data: CreateDoctorRatingData): Promise<DoctorRating> => {
    try {
      const response = await apiClient.patch(`/v1/doctor-ratings/${ratingId}`, data);
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  getByAppointment: async (appointmentId: number): Promise<DoctorRating | null> => {
    try {
      const response = await apiClient.get(`/v1/doctor-ratings/appointment/${appointmentId}`);
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  listByDoctor: async (doctorId: number): Promise<DoctorRating[]> => {
    try {
      const response = await apiClient.get(`/v1/doctor-ratings/doctor/${doctorId}`);
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  getMyRatings: async (): Promise<MyRatingsResponse> => {
    try {
      const response = await apiClient.get('/v1/doctor-ratings/my-ratings');
      return response.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },
};
