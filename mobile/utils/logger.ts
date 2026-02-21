/**
 * Logger wrapper that only logs in development mode.
 * Prevents console output in production builds.
 */

const isDev = __DEV__;

type LogArgs = Parameters<typeof console.log>;

export const logger = {
  log: (...args: LogArgs): void => {
    if (isDev) {
      console.log(...args);
    }
  },

  warn: (...args: LogArgs): void => {
    if (isDev) {
      console.warn(...args);
    }
  },

  error: (...args: LogArgs): void => {
    if (isDev) {
      console.error(...args);
    }
  },

  info: (...args: LogArgs): void => {
    if (isDev) {
      console.info(...args);
    }
  },

  debug: (...args: LogArgs): void => {
    if (isDev) {
      console.debug(...args);
    }
  },
};
