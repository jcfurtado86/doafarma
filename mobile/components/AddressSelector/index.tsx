import React from 'react';
import { View, Pressable, Text } from 'react-native';
import { Address } from '@/types/medicationAppointment';
import { styles } from './styles';

interface AddressSelectorProps {
  addresses: Address[];
  selectedId: number | undefined;
  onSelect: (addressId: number) => void;
}

export function AddressSelector({ addresses, selectedId, onSelect }: AddressSelectorProps) {
  if (addresses.length === 0) {
    return null;
  }

  return (
    <View style={styles.container}>
      {addresses.map((address) => {
        const isSelected = address.id === selectedId;
        return (
          <Pressable
            key={address.id}
            style={[styles.option, isSelected && styles.optionSelected]}
            onPress={() => onSelect(address.id)}
          >
            <View style={[styles.radioButton, isSelected && styles.radioButtonSelected]}>
              {isSelected && <View style={styles.radioButtonInner} />}
            </View>
            <View style={styles.addressInfo}>
              <Text style={styles.addressName}>{address.location_name}</Text>
              <Text style={styles.addressText}>{address.full_address}</Text>
            </View>
          </Pressable>
        );
      })}
    </View>
  );
}
