import React from 'react';
import { Pressable, ScrollView, Text, View } from 'react-native';

import { Ionicons } from '@expo/vector-icons';
import { useRouter, type Href } from 'expo-router';

import { Badge } from '@/components/ui';
import { useAuthStore, type User } from '@/stores/authStore';
import { colors } from '@/theme/tokens';
import { a11y } from '@/utils/accessibility';

import { styles } from './styles';

export interface ProfileMenuItem {
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  href: Href;
  testID: string;
}

interface ProfileScreenProps {
  items: ProfileMenuItem[];
}

const ROLE_LABELS: Record<User['role'], string> = {
  doctor: 'Médico',
  receptor: 'Receptor',
};

function getInitials(name: string): string {
  const parts = name.trim().split(/\s+/);
  const first = parts[0]?.[0] ?? '';
  const last = parts.length > 1 ? (parts[parts.length - 1][0] ?? '') : '';
  return (first + last).toUpperCase();
}

export function ProfileScreen({ items }: ProfileScreenProps) {
  const router = useRouter();
  const user = useAuthStore((state) => state.user);

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <View style={styles.identity}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{user ? getInitials(user.name) : '?'}</Text>
        </View>
        <Text style={styles.name}>{user?.name}</Text>
        <Text style={styles.email}>{user?.email}</Text>
        {user && <Badge label={ROLE_LABELS[user.role]} variant="info" size="md" />}
      </View>

      <View style={styles.menu}>
        {items.map((item) => (
          <Pressable
            key={String(item.href)}
            testID={item.testID}
            {...a11y.button(item.label)}
            style={styles.menuItem}
            onPress={() => router.push(item.href)}
          >
            <Ionicons name={item.icon} size={20} color={colors.textSecondary} />
            <Text style={styles.menuItemLabel}>{item.label}</Text>
            <Ionicons name="chevron-forward" size={18} color={colors.textMuted} />
          </Pressable>
        ))}
      </View>
    </ScrollView>
  );
}
