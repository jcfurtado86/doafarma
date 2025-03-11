import React from 'react';
import { View } from 'react-native';
import { styles } from './styles';

type InputRowProps = {
  children: React.ReactNode;
  spacing?: number;
};

export const InputRow = ({ children, spacing = 8 }: InputRowProps) => {
  return <View style={[styles.row, { gap: spacing }]}>{children}</View>;
};
