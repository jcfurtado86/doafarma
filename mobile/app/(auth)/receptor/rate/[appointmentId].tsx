import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  TextInput,
  ScrollView,
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { toast } from '@/utils/toast';
import { useLocalSearchParams, router } from 'expo-router';
import { colors } from '@/theme/tokens';
import { useDoctorRatingStore } from '@/stores/doctorRatingStore';
import { RATING_LABELS } from '@/types/doctorRating';

export default function RateDoctorScreen() {
  const { appointmentId, doctorName, medicationName } = useLocalSearchParams<{
    appointmentId: string;
    doctorName?: string;
    medicationName?: string;
  }>();

  const [rating, setRating] = useState<number>(0);
  const [comment, setComment] = useState('');
  const [isEditMode, setIsEditMode] = useState(false);
  const scrollViewRef = useRef<ScrollView>(null);

  const {
    createRating,
    updateRating,
    fetchRatingByAppointment,
    currentRating,
    isLoading,
    error,
    clearError,
    clearCurrentRating,
  } = useDoctorRatingStore();

  // Load existing rating on mount
  useEffect(() => {
    const loadExistingRating = async () => {
      try {
        const existingRating = await fetchRatingByAppointment(parseInt(appointmentId, 10));
        if (existingRating) {
          setRating(existingRating.rating);
          setComment(existingRating.comment || '');
          setIsEditMode(true);
        }
      } catch {
        // Error handled by store
      }
    };

    loadExistingRating();

    return () => {
      clearCurrentRating();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [appointmentId]);

  const showConfirmation = () => {
    if (rating === 0) {
      toast.error('Por favor, selecione uma avaliação de 1 a 5 estrelas.', 'Avaliação obrigatória');
      return;
    }

    const stars = '★'.repeat(rating) + '☆'.repeat(5 - rating);
    const commentText = comment.trim() ? `\n\nComentário: "${comment.trim()}"` : '';

    Alert.alert(
      isEditMode ? 'Confirmar alteração' : 'Confirmar avaliação',
      `Sua avaliação:\n\n${stars} (${RATING_LABELS[rating]})${commentText}`,
      [
        { text: 'Cancelar', style: 'cancel' },
        { text: isEditMode ? 'Salvar' : 'Enviar', onPress: handleSubmit },
      ]
    );
  };

  const handleSubmit = async () => {
    try {
      if (isEditMode && currentRating) {
        await updateRating(currentRating.id, {
          rating,
          comment: comment.trim() || undefined,
        });
        toast.success('Sua avaliação foi alterada com sucesso.', 'Avaliação atualizada!');
        router.back();
      } else {
        await createRating(parseInt(appointmentId, 10), {
          rating,
          comment: comment.trim() || undefined,
        });
        toast.success('Obrigado por avaliar sua experiência.', 'Avaliação enviada!');
        router.back();
      }
    } catch {
      // Error is handled by the store
    }
  };

  const renderStar = (starNumber: number) => {
    const isSelected = starNumber <= rating;
    return (
      <TouchableOpacity
        key={starNumber}
        onPress={() => setRating(starNumber)}
        style={styles.starButton}
        activeOpacity={0.7}
      >
        <Text style={[styles.star, isSelected && styles.starSelected]}>
          {isSelected ? '★' : '☆'}
        </Text>
      </TouchableOpacity>
    );
  };

  // Show loading while fetching existing rating
  if (isLoading && !rating) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={colors.primaryPressed} />
        <Text style={styles.loadingText}>Carregando...</Text>
      </View>
    );
  }

  return (
    <KeyboardAvoidingView
      style={styles.keyboardAvoid}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      keyboardVerticalOffset={Platform.OS === 'ios' ? 100 : 0}
    >
      <ScrollView
        ref={scrollViewRef}
        style={styles.container}
        contentContainerStyle={styles.contentContainer}
        keyboardShouldPersistTaps="handled"
      >
        {/* Header */}
        <View style={styles.header}>
          <Text style={styles.headerIcon}>{isEditMode ? '✏️' : '⭐'}</Text>
          <Text style={styles.headerTitle}>
            {isEditMode ? 'Editar Avaliação' : 'Avaliar Doador'}
          </Text>
          <Text style={styles.headerSubtitle}>
            {isEditMode
              ? 'Você pode alterar sua avaliação a qualquer momento.'
              : 'Sua opinião ajuda outros pacientes e incentiva boas práticas.'}
          </Text>
        </View>

        {/* Medication Info */}
        {(doctorName || medicationName) && (
          <View style={styles.infoCard}>
            {medicationName && (
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Medicamento:</Text>
                <Text style={styles.infoValue}>{medicationName}</Text>
              </View>
            )}
            {doctorName && (
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Doador:</Text>
                <Text style={styles.infoValue}>{doctorName}</Text>
              </View>
            )}
          </View>
        )}

        {/* Rating Stars */}
        <View style={styles.ratingSection}>
          <Text style={styles.sectionTitle}>Como foi sua experiência?</Text>
          <View style={styles.starsContainer}>{[1, 2, 3, 4, 5].map(renderStar)}</View>
          {rating > 0 && <Text style={styles.ratingLabel}>{RATING_LABELS[rating]}</Text>}
        </View>

        {/* Comment Input */}
        <View style={styles.commentSection}>
          <Text style={styles.sectionTitle}>Comentário (opcional)</Text>
          <TextInput
            style={styles.commentInput}
            placeholder="Conte como foi sua experiência com este doador..."
            placeholderTextColor={colors.textMuted}
            value={comment}
            onChangeText={setComment}
            multiline
            numberOfLines={4}
            maxLength={1000}
            textAlignVertical="top"
            autoCorrect={true}
            autoCapitalize="sentences"
            onFocus={() => {
              setTimeout(() => {
                scrollViewRef.current?.scrollToEnd({ animated: true });
              }, 300);
            }}
          />
          <Text style={styles.charCount}>{comment.length}/1000</Text>
        </View>

        {/* Error Message */}
        {error && (
          <View style={styles.errorContainer}>
            <Text style={styles.errorText}>{error}</Text>
            <TouchableOpacity onPress={clearError}>
              <Text style={styles.errorDismiss}>Fechar</Text>
            </TouchableOpacity>
          </View>
        )}

        {/* Submit Button */}
        <TouchableOpacity
          style={[styles.submitButton, (isLoading || rating === 0) && styles.submitButtonDisabled]}
          onPress={showConfirmation}
          disabled={isLoading || rating === 0}
          activeOpacity={0.8}
        >
          {isLoading ? (
            <ActivityIndicator color={colors.textInverted} />
          ) : (
            <Text style={styles.submitButtonText}>
              {isEditMode ? 'Salvar Alterações' : 'Enviar Avaliação'}
            </Text>
          )}
        </TouchableOpacity>

        {/* Skip Link */}
        <TouchableOpacity
          style={styles.skipButton}
          onPress={() => router.back()}
          disabled={isLoading}
        >
          <Text style={styles.skipButtonText}>{isEditMode ? 'Cancelar' : 'Avaliar depois'}</Text>
        </TouchableOpacity>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  keyboardAvoid: {
    flex: 1,
  },
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  contentContainer: {
    padding: 16,
    paddingBottom: 32,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: colors.background,
    gap: 16,
  },
  loadingText: {
    fontSize: 16,
    color: colors.textSecondary,
  },
  header: {
    alignItems: 'center',
    marginBottom: 24,
    paddingTop: 16,
  },
  headerIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: colors.textPrimary,
    marginBottom: 8,
  },
  headerSubtitle: {
    fontSize: 16,
    color: colors.textSecondary,
    textAlign: 'center',
    paddingHorizontal: 16,
  },
  infoCard: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    padding: 16,
    marginBottom: 24,
    shadowColor: colors.black,
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  infoRow: {
    flexDirection: 'row',
    marginBottom: 8,
  },
  infoLabel: {
    fontSize: 14,
    color: colors.textSecondary,
    width: 100,
  },
  infoValue: {
    fontSize: 14,
    color: colors.textPrimary,
    fontWeight: '500',
    flex: 1,
  },
  ratingSection: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    padding: 24,
    marginBottom: 16,
    alignItems: 'center',
    shadowColor: colors.black,
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.textPrimary,
    marginBottom: 16,
  },
  starsContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 8,
  },
  starButton: {
    padding: 8,
  },
  star: {
    fontSize: 40,
    color: colors.border,
  },
  starSelected: {
    color: colors.warning,
  },
  ratingLabel: {
    marginTop: 12,
    fontSize: 16,
    fontWeight: '500',
    color: colors.primaryPressed,
  },
  commentSection: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    padding: 16,
    marginBottom: 24,
    shadowColor: colors.black,
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  commentInput: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    color: colors.textPrimary,
    minHeight: 100,
    backgroundColor: colors.background,
  },
  charCount: {
    textAlign: 'right',
    fontSize: 12,
    color: colors.textMuted,
    marginTop: 8,
  },
  errorContainer: {
    backgroundColor: colors.errorSurface,
    borderRadius: 8,
    padding: 12,
    marginBottom: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  errorText: {
    color: colors.error,
    fontSize: 14,
    flex: 1,
  },
  errorDismiss: {
    color: colors.error,
    fontWeight: '600',
    marginLeft: 8,
  },
  submitButton: {
    backgroundColor: colors.primaryPressed,
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    marginBottom: 12,
  },
  submitButtonDisabled: {
    backgroundColor: colors.textMuted,
  },
  submitButtonText: {
    color: colors.textInverted,
    fontSize: 16,
    fontWeight: '600',
  },
  skipButton: {
    padding: 12,
    alignItems: 'center',
  },
  skipButtonText: {
    color: colors.textSecondary,
    fontSize: 14,
  },
});
