import { Text, TextProps } from 'react-native';
import { styles } from './styles';

interface TitleProps extends TextProps {
  children: React.ReactNode;
}

export function Title({ children, ...props }: TitleProps) {
  return (
    <Text {...props} style={styles.title}>
      {children}
    </Text>
  );
}
