import { Pressable, Text, PressableProps } from 'react-native';
import { styles } from './styles';
import { a11y } from '@/utils/accessibility';

interface SecondaryButtonProps extends PressableProps {
  label: string;
}

export default function SecondaryButton({ label, disabled, ...props }: SecondaryButtonProps) {
  return (
    <Pressable
      {...a11y.button(label, disabled ?? undefined)}
      disabled={disabled}
      {...props}
      style={({ pressed }) => [styles.buttonSecondary, pressed && styles.buttonHoverSecondary]}
    >
      {({ pressed }) => (
        <Text style={[styles.buttonTextSecondary, pressed && styles.buttonTextSecondaryHover]}>
          {label}
        </Text>
      )}
    </Pressable>
  );
}
