import axios from 'axios';
import { parseAuthFromToken } from '../utils/jwt';

const TOKEN_KEY = 'minisoc_token';

export function apiBaseURL() {
  const fromEnv = import.meta.env.VITE_API_BASE_URL;
  if (fromEnv !== undefined && fromEnv !== null && `${fromEnv}`.trim() !== '') {
    return `${fromEnv}`.replace(/\/$/, '');
  }
  return '/api';
}

export function getStoredToken() {
  return window.localStorage.getItem(TOKEN_KEY);
}

export function setStoredToken(token) {
  if (!token) {
    window.localStorage.removeItem(TOKEN_KEY);
    return;
  }
  window.localStorage.setItem(TOKEN_KEY, token);
}

export function clearStoredToken() {
  window.localStorage.removeItem(TOKEN_KEY);
}

/** @typedef {{ email: string | null; roles: string[] }} ParsedUser */

/** @returns {ParsedUser|null} */
export function getUserFromStorage() {
  const token = getStoredToken();
  if (!token) {
    return null;
  }
  return parseAuthFromToken(token);
}

const apiClient = axios.create({
  baseURL: apiBaseURL(),
});

apiClient.interceptors.request.use((config) => {
  const t = getStoredToken();
  if (t) {
    const next = config;
    next.headers.Authorization = `Bearer ${t}`;
    return next;
  }
  return config;
});

apiClient.interceptors.response.use(
  (res) => res,
  (err) => {
    const status = err.response?.status;
    const url = typeof err.config?.url === 'string' ? err.config.url : '';
    if (status === 401 && !url.includes('/login')) {
      clearStoredToken();
      if (!window.location.pathname.startsWith('/login')) {
        window.location.assign('/login');
      }
    }
    return Promise.reject(err);
  },
);

/** @param {{ status: number }} error */
export function isAxiosUnauthorized(error) {
  return !!(error?.status === 401);
}

export { apiClient, TOKEN_KEY };
