import React from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { colors } from '@/theme/tokens';

type FilterBarProps = {
  children: React.ReactNode;
};

/**
 * Faixa de filtros acima de uma lista. Os chips têm a largura do próprio
 * texto e rolam na horizontal quando não cabem na tela.
 */
export function FilterBar({ children }: FilterBarProps) {
  return (
    <View style={styles.bar}>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.content}
      >
        {children}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  bar: {
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  content: {
    flexDirection: 'row',
    gap: 8,
    paddingHorizontal: 12,
    paddingVertical: 12,
  },
});
