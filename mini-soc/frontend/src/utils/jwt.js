/**
 * Lexik JWT: payload includes `username` (email) and custom `roles`.
 * @param {string} token
 * @returns {Record<string, unknown>|null}
 */
export function decodeJwtPayload(token) {
  if (!token || typeof token !== 'string') {
    return null;
  }
  const parts = token.split('.');
  if (parts.length !== 3) {
    return null;
  }
  try {
    const base64Url = parts[1];
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const json = decodeURIComponent(
      atob(base64)
        .split('')
        .map((c) => `%${(`00${c.charCodeAt(0).toString(16)}`).slice(-2)}`)
        .join(''),
    );
    return JSON.parse(json);
  } catch {
    return null;
  }
}

export function parseAuthFromToken(token) {
  const payload = decodeJwtPayload(token);
  if (!payload) {
    return null;
  }
  const email = typeof payload.username === 'string' ? payload.username : null;
  const roles = Array.isArray(payload.roles) ? payload.roles : [];
  return { email, roles };
}
