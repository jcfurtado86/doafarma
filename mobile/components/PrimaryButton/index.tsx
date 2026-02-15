import { Pressable, Text, PressableProps } from 'react-native';
import { styles } from './styles';
import { a11y } from '@/utils/accessibility';

interface PrimaryButtonProps extends PressableProps {
  label: string;
}

export default function PrimaryButton({ label, disabled, ...props }: PrimaryButtonProps) {
  return (
    <Pressable
      {...a11y.button(label, disabled ?? undefined)}
      disabled={disabled}
      {...props}
      style={({ pressed }) => [styles.buttonPrimary, pressed && styles.buttonHover]}
    >
      <Text style={styles.buttonText}>{label}</Text>
    </Pressable>
  );
}
