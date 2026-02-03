import Toast from 'react-native-toast-message';
import { toast } from '@/utils/toast';

jest.mock('react-native-toast-message', () => ({
  show: jest.fn(),
}));

describe('toast utility', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('success', () => {
    it('should call Toast.show with type success', () => {
      toast.success('Message');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'success',
        })
      );
    });

    it('should use default title "Sucesso" when not provided', () => {
      toast.success('Message');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          text1: 'Sucesso',
        })
      );
    });

    it('should use custom title when provided', () => {
      toast.success('Message', 'Custom Title');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          text1: 'Custom Title',
        })
      );
    });

    it('should pass message as text2', () => {
      toast.success('Test message');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          text2: 'Test message',
        })
      );
    });

    it('should use visibilityTime of 3000ms', () => {
      toast.success('Message');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          visibilityTime: 3000,
        })
      );
    });
  });

  describe('error', () => {
    it('should call Toast.show with type error', () => {
      toast.error('Error message');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'error',
        })
      );
    });

    it('should use default title "Erro" when not provided', () => {
      toast.error('Error message');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          text1: 'Erro',
        })
      );
    });

    it('should use custom title when provided', () => {
      toast.error('Error message', 'Custom Error');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          text1: 'Custom Error',
        })
      );
    });

    it('should pass message as text2', () => {
      toast.error('This is an error');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          text2: 'This is an error',
        })
      );
    });

    it('should use visibilityTime of 3000ms', () => {
      toast.error('Error');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          visibilityTime: 3000,
        })
      );
    });
  });

  describe('info', () => {
    it('should call Toast.show with type info', () => {
      toast.info('Info message');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'info',
        })
      );
    });

    it('should use default title "Informação" when not provided', () => {
      toast.info('Info message');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          text1: 'Informação',
        })
      );
    });

    it('should use custom title when provided', () => {
      toast.info('Info message', 'Custom Info');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          text1: 'Custom Info',
        })
      );
    });

    it('should pass message as text2', () => {
      toast.info('This is info');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          text2: 'This is info',
        })
      );
    });

    it('should use visibilityTime of 3000ms', () => {
      toast.info('Info');

      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          visibilityTime: 3000,
        })
      );
    });
  });
});
