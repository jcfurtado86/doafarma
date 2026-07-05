import { Text, View } from 'react-native';
import { a11y } from '@/utils/accessibility';
import { BadgeSize, BadgeVariant, getBadgeStyles } from './styles';

interface BadgeProps {
  label: string;
  variant?: BadgeVariant;
  size?: BadgeSize;
}

export function Badge({ label, variant = 'neutral', size = 'md' }: BadgeProps) {
  const styles = getBadgeStyles(variant, size);
  return (
    <View style={styles.container} {...a11y.badge(label)}>
      <Text style={styles.text} numberOfLines={1} ellipsizeMode="tail">
        {label}
      </Text>
    </View>
  );
}

export type { BadgeProps, BadgeVariant, BadgeSize };
