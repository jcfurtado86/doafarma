import { useEffect, useRef } from 'react';
import { View, Text } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import Toast from 'react-native-toast-message';
import { useNetworkStore } from '@/stores/networkStore';
import { styles } from './styles';

export default function OfflineBanner() {
  const isConnected = useNetworkStore((state) => state.isConnected);
  const wasOfflineRef = useRef(false);

  useEffect(() => {
    if (!isConnected) {
      wasOfflineRef.current = true;
    } else if (wasOfflineRef.current) {
      // Only show reconnect toast if the device was previously offline
      Toast.show({
        type: 'success',
        text1: 'Você está online novamente',
        visibilityTime: 3000,
        autoHide: true,
      });
      wasOfflineRef.current = false;
    }
  }, [isConnected]);

  if (isConnected) return null;

  return (
    <View style={styles.banner}>
      <Ionicons name="wifi-outline" size={16} color="#fff" />
      <Text style={styles.text}>Sem conexão com a internet</Text>
    </View>
  );
}
