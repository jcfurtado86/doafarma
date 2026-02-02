import React from 'react';
import { View, ActivityIndicator, StyleSheet } from 'react-native';
import { Colors } from '@/constants/Colors';

interface ListFooterLoaderProps {
  isLoading: boolean;
}

export function ListFooterLoader({ isLoading }: ListFooterLoaderProps) {
  if (!isLoading) return null;

  return (
    <View style={styles.container}>
      <ActivityIndicator size="small" color={Colors.yellow_green_500} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    paddingVertical: 16,
    alignItems: 'center',
  },
});
