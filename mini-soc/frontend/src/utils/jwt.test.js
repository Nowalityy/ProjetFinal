import { describe, expect, it } from 'vitest';
import { decodeJwtPayload, parseAuthFromToken } from './jwt';

describe('decodeJwtPayload', () => {
  it('returns null for invalid input', () => {
    expect(decodeJwtPayload('')).toBeNull();
    expect(decodeJwtPayload('a.b')).toBeNull();
  });

  it('decodes a simple JWT payload', () => {
    // {"username":"a@b.c","roles":["ROLE_ADMIN"]}
    const payloadB64 = 'eyJ1c2VybmFtZSI6ImFAYi5jIiwicm9sZXMiOlsiUk9MRV9BRE1JTiJdfQ';
    const token = `x.${payloadB64}.y`;
    expect(decodeJwtPayload(token)).toEqual({
      username: 'a@b.c',
      roles: ['ROLE_ADMIN'],
    });
  });
});

describe('parseAuthFromToken', () => {
  it('maps username and roles', () => {
    const payloadB64 = 'eyJ1c2VybmFtZSI6InVAcy5pbyIsInJvbGVzIjpbIlJPTEVfQVVESVRFVVIiXX0';
    const token = `h.${payloadB64}.s`;
    expect(parseAuthFromToken(token)).toEqual({
      email: 'u@s.io',
      roles: ['ROLE_AUDITEUR'],
    });
  });
});
