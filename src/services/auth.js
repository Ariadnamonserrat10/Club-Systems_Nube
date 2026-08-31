import axios from 'axios';
import { BACKEND } from './backend';

const STORAGE_KEYS = {
    TOKEN: 'auth_token',
    REFRESH_TOKEN: 'auth_refresh_token',
    CSRF_TOKEN: 'csrf_token',
    USER_ID: 'usuarioId',
    USER_DATA: 'auth_user_data',
    TOKEN_EXPIRES_AT: 'token_expires_at'
};

let currentToken = null;
let isRefreshing = false;
let refreshSubscribers = [];
const isBrowser = () => typeof window !== 'undefined' && typeof sessionStorage !== 'undefined';

export const authService = {
    login: async (credentials) => {
        const response = await axios.post(`${BACKEND}/auth/login`, credentials);
        
        if (response.data.status === 'success') {
            const { token, csrf_token, remember_token, token_expires_at, ...userData } = response.data;
            
            authService.setToken(token);
            authService.setCsrfToken(csrf_token);
            authService.setUserData(userData);
            
            if (remember_token) {
                authService.setRefreshToken(remember_token);
            }
            
            if (token_expires_at) {
                authService.setTokenExpiry(token_expires_at);
            }
            
            return response.data;
        }
        
        throw new Error(response.data.message || 'Error al iniciar sesión');
    },

    logout: async (mode = 'current') => {
        const token = authService.getToken();
        
        if (token) {
            try {
                await axios.post(
                    `${BACKEND}/auth/logout`,
                    { mode, remember_token: authService.getRefreshToken() },
                    { headers: { 'Authorization': `Bearer ${token}` } }
                );
            } catch (e) {
                // Silencioso en producción - error no crítico
            }
        }
        
        authService.clearAuth();
    },

    refreshToken: async () => {
        if (isRefreshing) {
            return new Promise((resolve) => {
                refreshSubscribers.push(resolve);
            });
        }

        isRefreshing = true;
        const currentTokenValue = authService.getToken();

        try {
            const response = await axios.post(
                `${BACKEND}/auth/refresh`,
                { refresh_token: authService.getRefreshToken() },
                { headers: { 'Authorization': `Bearer ${currentTokenValue}` } }
            );

            if (response.data.status === 'success') {
                authService.setToken(response.data.token);
                if (response.data.csrf_token) {
                    authService.setCsrfToken(response.data.csrf_token);
                }
                if (response.data.token_expires_at) {
                    authService.setTokenExpiry(response.data.token_expires_at);
                }
                if (response.data.remember_token) {
                    authService.setRefreshToken(response.data.remember_token);
                }

                refreshSubscribers.forEach((callback) => callback(response.data.token));
                refreshSubscribers = [];

                return response.data.token;
            }

            throw new Error(response.data.message || 'Error al renovar token');
        } catch (error) {
            authService.clearAuth();
            throw error;
        } finally {
            isRefreshing = false;
        }
    },

    getCurrentUser: async () => {
        const token = authService.getToken();
        if (!token) {
            return null;
        }

        const response = await axios.get(`${BACKEND}/auth/me`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (response.data.status === 'success' || response.data.data) {
            const userData = response.data.data || response.data;
            authService.setUserData(userData);
            return userData;
        }

        return null;
    },

    setToken: (token) => {
        if (!isBrowser()) return;
        currentToken = token;
        sessionStorage.setItem(STORAGE_KEYS.TOKEN, token);
    },

    getToken: () => {
        if (!isBrowser()) return null;
        if (currentToken) {
            return currentToken;
        }
        return sessionStorage.getItem(STORAGE_KEYS.TOKEN);
    },

    setRefreshToken: (token) => {
        if (!isBrowser()) return;
        localStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, token);
    },

    getRefreshToken: () => {
        if (!isBrowser()) return null;
        return localStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN);
    },

    setCsrfToken: (token) => {
        if (!isBrowser()) return;
        sessionStorage.setItem(STORAGE_KEYS.CSRF_TOKEN, token);
    },

    getCsrfToken: () => {
        if (!isBrowser()) return null;
        return sessionStorage.getItem(STORAGE_KEYS.CSRF_TOKEN);
    },

    setTokenExpiry: (expiresAt) => {
        if (!isBrowser()) return;
        sessionStorage.setItem(STORAGE_KEYS.TOKEN_EXPIRES_AT, expiresAt);
    },

    getTokenExpiry: () => {
        if (!isBrowser()) return null;
        return sessionStorage.getItem(STORAGE_KEYS.TOKEN_EXPIRES_AT);
    },

    setUserData: (data) => {
        if (!isBrowser()) return;
        sessionStorage.setItem(STORAGE_KEYS.USER_DATA, JSON.stringify(data));
        if (data.id) {
            sessionStorage.setItem(STORAGE_KEYS.USER_ID, String(data.id));
        }
    },

    getUserData: () => {
        if (!isBrowser()) return null;
        const data = sessionStorage.getItem(STORAGE_KEYS.USER_DATA);
        if (data) {
            return JSON.parse(data);
        }
        return null;
    },

    getUserId: () => {
        if (!isBrowser()) return null;
        const fromUserData = authService.getUserData();
        if (fromUserData?.id) {
            return fromUserData.id;
        }
        const fromStorage = sessionStorage.getItem(STORAGE_KEYS.USER_ID);
        return fromStorage ? parseInt(fromStorage, 10) : null;
    },

    isTokenExpired: () => {
        const expiry = authService.getTokenExpiry();
        if (!expiry) {
            return false;
        }
        const expiryTime = new Date(expiry).getTime();
        const now = Date.now();
        const bufferMinutes = 5 * 60 * 1000;
        return now >= (expiryTime - bufferMinutes);
    },

    isAuthenticated: () => {
        const token = authService.getToken();
        if (!token) {
            return false;
        }
        if (authService.isTokenExpired()) {
            authService.clearAuth();
            return false;
        }
        return true;
    },

    clearAuth: () => {
        currentToken = null;
        if (!isBrowser()) return;
        sessionStorage.removeItem(STORAGE_KEYS.TOKEN);
        sessionStorage.removeItem(STORAGE_KEYS.CSRF_TOKEN);
        sessionStorage.removeItem(STORAGE_KEYS.USER_DATA);
        sessionStorage.removeItem(STORAGE_KEYS.USER_ID);
        sessionStorage.removeItem(STORAGE_KEYS.TOKEN_EXPIRES_AT);
        
        sessionStorage.removeItem('usuarioId');
        sessionStorage.removeItem('usuarioNombre');
        sessionStorage.removeItem('usuarioTipo');
        
        localStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN);
    },

    getActiveSessions: async () => {
        const token = authService.getToken();
        if (!token) {
            throw new Error('No autenticado');
        }

        const response = await axios.get(`${BACKEND}/sesiones`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });

        return response.data.data || response.data;
    },

    revokeSession: async (sessionId) => {
        const token = authService.getToken();
        if (!token) {
            throw new Error('No autenticado');
        }

        const response = await axios.delete(
            `${BACKEND}/sesiones`,
            {
                headers: { 'Authorization': `Bearer ${token}` },
                data: { session_id: sessionId, mode: 'single' }
            }
        );

        return response.data;
    },

    revokeOtherSessions: async () => {
        const token = authService.getToken();
        if (!token) {
            throw new Error('No autenticado');
        }

        const response = await axios.delete(
            `${BACKEND}/sesiones`,
            {
                headers: { 'Authorization': `Bearer ${token}` },
                data: { mode: 'others' }
            }
        );

        return response.data;
    }
};

export default authService;
