import { Pressable, PressableProps, StyleProp, ViewStyle } from 'react-native';
import { styles } from './styles';
import { Ionicons } from '@expo/vector-icons';
import { a11y } from '@/utils/accessibility';

type ArrowBackButtonProps = Omit<PressableProps, 'style'> & {
  style?: StyleProp<ViewStyle>;
};

export default function ArrowBackButton({ style, ...props }: ArrowBackButtonProps) {
  return (
    <Pressable {...a11y.backButton()} {...props} style={[styles.arrowBackButton, style]}>
      {({ pressed }) => (
        <Ionicons
          name="arrow-back"
          size={28}
          style={[styles.arrowBackIcon, pressed && styles.arrowBackButtonPressed]}
        />
      )}
    </Pressable>
  );
}
