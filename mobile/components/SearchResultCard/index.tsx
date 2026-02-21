import React from 'react';
import { View, Text, Pressable } from 'react-native';
import { MedicationOfferingSearchResult } from '@/types/medicationOffering';
import { formatShortDate } from '@/utils/dateFormatters';
import { styles } from './styles';

interface SearchResultCardProps {
  offering: MedicationOfferingSearchResult;
  onPress: () => void;
}

export function SearchResultCard({ offering, onPress }: SearchResultCardProps) {
  const isExpired = new Date(offering.expires_at) < new Date();

  return (
    <Pressable
      style={({ pressed }) => [
        styles.card,
        isExpired && styles.expiredCard,
        pressed && { opacity: 0.8 },
      ]}
      onPress={onPress}
      accessibilityLabel={`${offering.drug.product_name}, ${offering.quantity} unidades, oferecido por ${offering.doctor.name}`}
      accessibilityRole="button"
    >
      <View style={styles.pressableContent}>
        <View style={styles.content}>
          <View style={styles.header}>
            <Text style={styles.drugName} numberOfLines={1}>
              {offering.drug.product_name}
            </Text>
            {isExpired && <Text style={styles.expiredLabel}>VENCIDO</Text>}
          </View>

          <Text style={styles.substance} numberOfLines={1}>
            {offering.drug.substance}
          </Text>

          <View style={styles.details}>
            <Text style={styles.detailText}>
              <Text style={styles.label}>Quantidade:</Text> {offering.quantity} unidades
            </Text>
            <Text style={[styles.detailText, isExpired && styles.expiredText]}>
              <Text style={styles.label}>Validade:</Text> {formatShortDate(offering.expires_at)}
            </Text>
          </View>

          <Text style={styles.doctorText}>Oferecido por: {offering.doctor.name}</Text>
        </View>

        <View style={styles.chevron}>
          <Text style={styles.chevronText}>›</Text>
        </View>
      </View>
    </Pressable>
  );
}
