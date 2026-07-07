import { Text, View } from 'react-native';

import { Ionicons } from '@expo/vector-icons';

import { colors } from '@/theme/tokens';

import { Button } from '../Button';
import { iconSize, styles } from './styles';

export type EmptyStateIcon = keyof typeof Ionicons.glyphMap;

export interface EmptyStateProps {
  icon: EmptyStateIcon;
  title: string;
  description: string;
  actionLabel?: string;
  onAction?: () => void;
  testID?: string;
}

export function EmptyState({
  icon,
  title,
  description,
  actionLabel,
  onAction,
  testID,
}: EmptyStateProps) {
  return (
    <View style={styles.container} testID={testID}>
      <Ionicons
        name={icon}
        size={iconSize}
        color={colors.textMuted}
        testID={testID ? `${testID}-icon` : undefined}
      />
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.description}>{description}</Text>
      {actionLabel && onAction && (
        <View style={styles.action}>
          <Button label={actionLabel} onPress={onAction} size="md" />
        </View>
      )}
    </View>
  );
}
