/* eslint-disable react/jsx-no-constructed-context-values */
import {
  createContext, useCallback, useContext, useMemo, useState,
} from 'react';
import { apiClient, getStoredToken, setStoredToken } from '../api/client';
import { parseAuthFromToken } from '../utils/jwt';

const AuthContext = createContext(null);

function readUser(token) {
  if (!token) {
    return null;
  }
  const parsed = parseAuthFromToken(token);
  if (!parsed?.email) {
    return null;
  }
  return parsed;
}

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => getStoredToken());
  const user = readUser(token || '');

  const login = useCallback(async (email, password) => {
    const { data } = await apiClient.post('/login', { email, password }, {
      headers: { 'Content-Type': 'application/json' },
    });
    const next = typeof data?.token === 'string' ? data.token : '';
    setStoredToken(next);
    setToken(next);
  }, []);

  const logout = useCallback(() => {
    setStoredToken(null);
    setToken(null);
  }, []);

  const value = useMemo(() => ({
    token,
    user,
    roles: user?.roles ?? [],
    login,
    logout,
    isAuthenticated: !!user?.email && !!token,
  }), [login, logout, token, user]);

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth must be used inside AuthProvider');
  }
  return ctx;
}

export function hasRole(roles, wanted) {
  return wanted.some((r) => roles.includes(r));
}

export const ROLE = {
  AUDITEUR: 'ROLE_AUDITEUR',
  ANALYSTE: 'ROLE_ANALYSTE',
  ADMIN: 'ROLE_ADMIN',
};
