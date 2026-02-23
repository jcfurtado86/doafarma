import { Pressable, Text, PressableProps } from 'react-native';
import { styles } from './styles';
import { a11y } from '@/utils/accessibility';
import { useNetworkStore } from '@/stores/networkStore';

interface SecondaryButtonProps extends PressableProps {
  label: string;
  disableWhenOffline?: boolean;
}

export default function SecondaryButton({
  label,
  disabled,
  disableWhenOffline = false,
  ...props
}: SecondaryButtonProps) {
  const isConnected = useNetworkStore((state) => state.isConnected);
  const isDisabled = disabled || (disableWhenOffline && !isConnected);

  return (
    <Pressable
      {...a11y.button(label, isDisabled ?? undefined)}
      disabled={isDisabled}
      {...props}
      style={({ pressed }) => [
        styles.buttonSecondary,
        pressed && styles.buttonHoverSecondary,
        isDisabled && styles.buttonDisabled,
      ]}
    >
      {({ pressed }) => (
        <Text
          style={[
            styles.buttonTextSecondary,
            pressed && styles.buttonTextSecondaryHover,
            isDisabled && styles.buttonTextDisabled,
          ]}
        >
          {label}
        </Text>
      )}
    </Pressable>
  );
}
