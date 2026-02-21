export const Pagination = {
  /**
   * Distance from the end of the list to trigger loadMore.
   * 0.5 = starts loading when user is 50% from the end.
   */
  END_REACHED_THRESHOLD: 0.5,

  /**
   * Initial page for all paginated lists.
   */
  INITIAL_PAGE: 1,
} as const;
