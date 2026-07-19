import React, { useCallback, memo } from 'react';
import { View, Pressable, Text } from 'react-native';
import { Address } from '@/types/medicationAppointment';
import { styles } from './styles';
import { a11y } from '@/utils/accessibility';

interface AddressItemProps {
  address: Address;
  isSelected: boolean;
  onSelect: (addressId: number) => void;
}

const AddressItem = memo(function AddressItem({ address, isSelected, onSelect }: AddressItemProps) {
  const handlePress = useCallback(() => {
    onSelect(address.id);
  }, [address.id, onSelect]);

  return (
    <Pressable
      style={[styles.option, isSelected && styles.optionSelected]}
      onPress={handlePress}
      {...a11y.radioButton(address.label, isSelected)}
    >
      <View style={[styles.radioButton, isSelected && styles.radioButtonSelected]}>
        {isSelected && <View style={styles.radioButtonInner} />}
      </View>
      <View style={styles.addressInfo}>
        <Text style={styles.addressName}>{address.label}</Text>
        <Text style={styles.addressText}>{address.formatted_address}</Text>
      </View>
    </Pressable>
  );
});

interface AddressSelectorProps {
  addresses: Address[];
  selectedId: number | undefined;
  onSelect: (addressId: number) => void;
}

export const AddressSelector = memo(function AddressSelector({
  addresses,
  selectedId,
  onSelect,
}: AddressSelectorProps) {
  if (addresses.length === 0) {
    return null;
  }

  return (
    <View style={styles.container}>
      {addresses.map((address) => (
        <AddressItem
          key={address.id}
          address={address}
          isSelected={address.id === selectedId}
          onSelect={onSelect}
        />
      ))}
    </View>
  );
});
