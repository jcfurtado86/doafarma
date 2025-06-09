import { Text, TextProps } from 'react-native';
import { styles } from './styles';

interface CaptionProps extends TextProps {
  children: React.ReactNode;
}

export function Caption({ children, ...props }: CaptionProps) {
  return (
    <Text {...props} style={styles.caption}>
      {children}
    </Text>
  );
}
