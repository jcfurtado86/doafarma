/**
 * User types and helpers - mirrors backend enums
 *
 * Keep these in sync with:
 * - backend/app/Enums/UserStatus.php
 * - backend/app/Enums/UserRole.php
 */

export enum UserStatus {
  Pending = 'pending',
  Approved = 'approved',
  Rejected = 'rejected',
}

export enum UserRole {
  Doctor = 'doctor',
  Receptor = 'receptor',
}

/**
 * Checks if user can access the app based on their status.
 * Mirrors backend logic from UserStatus::canAccess()
 */
export const canUserAccessApp = (status: UserStatus): boolean => {
  return status === UserStatus.Approved;
};

/**
 * Checks if user is blocked from accessing the app.
 * Inverse of canUserAccessApp for readability.
 */
export const isUserBlocked = (status: UserStatus): boolean => {
  return status !== UserStatus.Approved;
};
