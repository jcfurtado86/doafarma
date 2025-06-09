import { Pressable, Text, PressableProps } from 'react-native';
import { styles } from './styles';

interface PrimaryButtonProps extends PressableProps {
  label: string;
}

export default function PrimaryButton({ label, ...props }: PrimaryButtonProps) {
  return (
    <Pressable
      {...props}
      style={({ pressed }) => [styles.buttonPrimary, pressed && styles.buttonHover]}
    >
      <Text style={styles.buttonText}>{label}</Text>
    </Pressable>
  );
}
