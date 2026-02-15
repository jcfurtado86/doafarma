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

export type UserStatusValue = UserStatus | 'pending' | 'approved' | 'rejected';

/**
 * Checks if user can access the app based on their status.
 * Mirrors backend logic from UserStatus::canAccess()
 */
export const canUserAccessApp = (status: UserStatusValue): boolean => {
  return String(status) === 'approved';
};

/**
 * Checks if user is blocked from accessing the app.
 * Inverse of canUserAccessApp for readability.
 */
export const isUserBlocked = (status: UserStatusValue): boolean => {
  return String(status) !== 'approved';
};
