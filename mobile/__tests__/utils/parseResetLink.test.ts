import { parseResetLink } from '@/utils/parseResetLink';

describe('parseResetLink', () => {
  it('should parse valid myapp:// deep link', () => {
    const result = parseResetLink('myapp://password-reset/abc123?email=user@test.com');

    expect(result).toEqual({ token: 'abc123', email: 'user@test.com' });
  });

  it('should parse valid http URL', () => {
    const result = parseResetLink('http://example.com/password-reset/abc123?email=user@test.com');

    expect(result).toEqual({ token: 'abc123', email: 'user@test.com' });
  });

  it('should parse valid https URL', () => {
    const result = parseResetLink(
      'https://example.com/password-reset/token456?email=test@example.com'
    );

    expect(result).toEqual({ token: 'token456', email: 'test@example.com' });
  });

  it('should return null when email query param is missing', () => {
    const result = parseResetLink('myapp://password-reset/abc123');

    expect(result).toBeNull();
  });

  it('should return null when token is missing from path', () => {
    const result = parseResetLink('myapp://password-reset/?email=user@test.com');

    expect(result).toBeNull();
  });

  it('should return null for empty string', () => {
    const result = parseResetLink('');

    expect(result).toBeNull();
  });

  it('should return null for random text', () => {
    const result = parseResetLink('just some random text');

    expect(result).toBeNull();
  });

  it('should extract correctly when URL has extra query params', () => {
    const result = parseResetLink(
      'myapp://password-reset/abc123?email=user@test.com&foo=bar&baz=qux'
    );

    expect(result).toEqual({ token: 'abc123', email: 'user@test.com' });
  });

  it('should handle encoded email in query param', () => {
    const result = parseResetLink('myapp://password-reset/abc123?email=user%40test.com');

    expect(result).toEqual({ token: 'abc123', email: 'user@test.com' });
  });

  it('should return null for URL without password-reset path', () => {
    const result = parseResetLink('myapp://some-other-path/abc123?email=user@test.com');

    expect(result).toBeNull();
  });
});
