interface ParsedResetLink {
  token: string;
  email: string;
}

export function parseResetLink(url: string): ParsedResetLink | null {
  try {
    if (!url || typeof url !== 'string') {
      return null;
    }

    const normalizedUrl = url.trim();

    const match = normalizedUrl.match(/password-reset\/([^/?]+)/);

    if (!match || !match[1]) {
      return null;
    }

    const token = match[1];

    let email: string | null = null;

    const queryMatch = normalizedUrl.match(/[?&]email=([^&]+)/);
    if (queryMatch && queryMatch[1]) {
      email = decodeURIComponent(queryMatch[1]);
    }

    if (!email) {
      return null;
    }

    return { token, email };
  } catch {
    return null;
  }
}
