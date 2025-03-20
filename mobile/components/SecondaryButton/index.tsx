import { Pressable, Text, PressableProps } from 'react-native';
import { styles } from './styles';

interface SecondaryButtonProps extends PressableProps {
  label: string;
}

export default function SecondaryButton({ label, ...props }: SecondaryButtonProps) {
  return (
    <Pressable
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
