import React from 'react';
import { View, ActivityIndicator, StyleSheet } from 'react-native';
import { colors } from '@/theme/tokens';

interface ListFooterLoaderProps {
  isLoading: boolean;
}

export function ListFooterLoader({ isLoading }: ListFooterLoaderProps) {
  if (!isLoading) return null;

  return (
    <View style={styles.container}>
      <ActivityIndicator size="small" color={colors.primaryPressed} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    paddingVertical: 16,
    alignItems: 'center',
  },
});
