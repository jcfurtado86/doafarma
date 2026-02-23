import { Pressable, Text, PressableProps } from 'react-native';
import { styles } from './styles';
import { a11y } from '@/utils/accessibility';
import { useNetworkStore } from '@/stores/networkStore';

interface PrimaryButtonProps extends PressableProps {
  label: string;
  disableWhenOffline?: boolean;
}

export default function PrimaryButton({
  label,
  disabled,
  disableWhenOffline = false,
  ...props
}: PrimaryButtonProps) {
  const isConnected = useNetworkStore((state) => state.isConnected);
  const isDisabled = disabled || (disableWhenOffline && !isConnected);

  return (
    <Pressable
      {...a11y.button(label, isDisabled ?? undefined)}
      disabled={isDisabled}
      {...props}
      style={({ pressed }) => [
        styles.buttonPrimary,
        pressed && styles.buttonHover,
        isDisabled && styles.buttonDisabled,
      ]}
    >
      <Text style={[styles.buttonText, isDisabled && styles.buttonTextDisabled]}>{label}</Text>
    </Pressable>
  );
}
