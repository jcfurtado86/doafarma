import React, { useEffect } from 'react';
import { View, Text, StyleSheet, FlatList, RefreshControl, ActivityIndicator } from 'react-native';
import { useDoctorRatingStore } from '@/stores/doctorRatingStore';
import { colors } from '@/theme/tokens';
import { formatLongDate } from '@/utils/dateFormatters';
import { DoctorRating, RATING_LABELS } from '@/types/doctorRating';

export default function DoctorRatingsScreen() {
  const { myRatings, myRatingsSummary, isLoading, error, fetchMyRatings, clearError } =
    useDoctorRatingStore();

  useEffect(() => {
    fetchMyRatings();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const renderStars = (rating: number) => {
    return (
      <View style={styles.starsContainer}>
        {[1, 2, 3, 4, 5].map((star) => (
          <Text key={star} style={styles.star}>
            {star <= rating ? '★' : '☆'}
          </Text>
        ))}
      </View>
    );
  };

  const renderSummary = () => {
    if (!myRatingsSummary) return null;

    return (
      <View style={styles.summaryContainer}>
        <View style={styles.summaryHeader}>
          <View style={styles.averageContainer}>
            <Text style={styles.averageNumber}>{myRatingsSummary.average_rating.toFixed(1)}</Text>
            {renderStars(Math.round(myRatingsSummary.average_rating))}
            <Text style={styles.totalRatings}>
              {myRatingsSummary.total_ratings}{' '}
              {myRatingsSummary.total_ratings === 1 ? 'avaliacao' : 'avaliacoes'}
            </Text>
          </View>
        </View>

        <View style={styles.distributionContainer}>
          {[5, 4, 3, 2, 1].map((star) => {
            const count = myRatingsSummary.rating_distribution[star] || 0;
            const percentage =
              myRatingsSummary.total_ratings > 0
                ? (count / myRatingsSummary.total_ratings) * 100
                : 0;

            return (
              <View key={star} style={styles.distributionRow}>
                <Text style={styles.distributionStar}>{star} ★</Text>
                <View style={styles.barContainer}>
                  <View style={[styles.barFill, { width: `${percentage}%` }]} />
                </View>
                <Text style={styles.distributionCount}>{count}</Text>
              </View>
            );
          })}
        </View>
      </View>
    );
  };

  const renderItem = ({ item }: { item: DoctorRating }) => {
    return (
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <View style={styles.ratingInfo}>
            {renderStars(item.rating)}
            <Text style={styles.ratingLabel}>{RATING_LABELS[item.rating]}</Text>
          </View>
          <Text style={styles.date}>{formatLongDate(item.created_at)}</Text>
        </View>

        {item.comment && <Text style={styles.comment}>{item.comment}</Text>}

        <View style={styles.cardFooter}>
          <Text style={styles.receptorName}>Por: {item.receptor?.name || 'Anonimo'}</Text>
        </View>
      </View>
    );
  };

  const renderEmpty = () => {
    if (isLoading) return null;
    return (
      <View style={styles.emptyContainer}>
        <Text style={styles.emptyIcon}>⭐</Text>
        <Text style={styles.emptyTitle}>Nenhuma avaliacao ainda</Text>
        <Text style={styles.emptyText}>
          Quando receptores avaliarem suas doacoes, as avaliacoes aparecerao aqui.
        </Text>
        <Text style={styles.emptyHint}>
          Continue doando medicamentos para receber feedback dos beneficiarios.
        </Text>
      </View>
    );
  };

  if (error) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorIcon}>⚠️</Text>
        <Text style={styles.errorText}>{error}</Text>
        <Text
          style={styles.retryText}
          onPress={() => {
            clearError();
            fetchMyRatings();
          }}
        >
          Tentar novamente
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {isLoading && myRatings.length === 0 ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={colors.primaryPressed} />
          <Text style={styles.loadingText}>Carregando avaliacoes...</Text>
        </View>
      ) : (
        <FlatList
          data={myRatings}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={styles.listContent}
          ListHeaderComponent={renderSummary}
          refreshControl={
            <RefreshControl
              refreshing={isLoading}
              onRefresh={fetchMyRatings}
              colors={[colors.primaryPressed]}
              tintColor={colors.primaryPressed}
            />
          }
          ListEmptyComponent={renderEmpty}
          showsVerticalScrollIndicator={false}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  listContent: {
    padding: 16,
    flexGrow: 1,
  },
  summaryContainer: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
    shadowColor: colors.black,
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  summaryHeader: {
    alignItems: 'center',
    marginBottom: 16,
  },
  averageContainer: {
    alignItems: 'center',
  },
  averageNumber: {
    fontSize: 48,
    fontWeight: 'bold',
    color: colors.textPrimary,
  },
  starsContainer: {
    flexDirection: 'row',
    marginTop: 4,
  },
  star: {
    fontSize: 20,
    color: colors.warning,
  },
  totalRatings: {
    fontSize: 14,
    color: colors.textSecondary,
    marginTop: 4,
  },
  distributionContainer: {
    gap: 8,
  },
  distributionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  distributionStar: {
    fontSize: 14,
    color: colors.textSecondary,
    width: 40,
  },
  barContainer: {
    flex: 1,
    height: 8,
    backgroundColor: colors.border,
    borderRadius: 4,
    overflow: 'hidden',
  },
  barFill: {
    height: '100%',
    backgroundColor: colors.warning,
    borderRadius: 4,
  },
  distributionCount: {
    fontSize: 14,
    color: colors.textSecondary,
    width: 30,
    textAlign: 'right',
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: colors.black,
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 8,
  },
  ratingInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  ratingLabel: {
    fontSize: 14,
    color: colors.textPrimary,
    fontWeight: '500',
  },
  date: {
    fontSize: 12,
    color: colors.textMuted,
  },
  comment: {
    fontSize: 14,
    color: colors.textSecondary,
    lineHeight: 20,
    marginBottom: 12,
  },
  cardFooter: {
    borderTopWidth: 1,
    borderTopColor: colors.background,
    paddingTop: 12,
  },
  receptorName: {
    fontSize: 12,
    color: colors.textSecondary,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    gap: 16,
  },
  loadingText: {
    fontSize: 14,
    color: colors.textSecondary,
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 32,
    paddingTop: 64,
  },
  emptyIcon: {
    fontSize: 64,
    marginBottom: 16,
  },
  emptyTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: colors.textPrimary,
    marginBottom: 8,
  },
  emptyText: {
    fontSize: 16,
    color: colors.textSecondary,
    textAlign: 'center',
    marginBottom: 8,
  },
  emptyHint: {
    fontSize: 14,
    color: colors.textMuted,
    textAlign: 'center',
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
  },
  errorIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  errorText: {
    fontSize: 16,
    color: colors.error,
    textAlign: 'center',
    marginBottom: 16,
  },
  retryText: {
    fontSize: 16,
    color: colors.primaryPressed,
    fontWeight: '600',
  },
});
