import Toast from 'react-native-toast-message';

const DEFAULT_VISIBILITY_TIME = 3000;

export const toast = {
  success(message: string, title = 'Sucesso') {
    Toast.show({
      type: 'success',
      text1: title,
      text2: message,
      visibilityTime: DEFAULT_VISIBILITY_TIME,
    });
  },

  error(message: string, title = 'Erro') {
    Toast.show({
      type: 'error',
      text1: title,
      text2: message,
      visibilityTime: DEFAULT_VISIBILITY_TIME,
    });
  },

  info(message: string, title = 'Informação') {
    Toast.show({
      type: 'info',
      text1: title,
      text2: message,
      visibilityTime: DEFAULT_VISIBILITY_TIME,
    });
  },
};
