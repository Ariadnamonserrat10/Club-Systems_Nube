import axios from 'axios';
import { BACKEND } from './backend';
import { authService } from './auth';

const api = axios.create({
    baseURL: BACKEND,
    headers: {
        'Content-Type': 'application/json'
    }
});

api.interceptors.request.use(
    (config) => {
        const token = authService.getToken();
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        
        const csrfToken = authService.getCsrfToken();
        if (csrfToken && config.method !== 'get') {
            config.headers['X-CSRF-Token'] = csrfToken;
        }
        
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
    failedQueue.forEach((prom) => {
        if (error) {
            prom.reject(error);
        } else {
            prom.resolve(token);
        }
    });
    failedQueue = [];
};

api.interceptors.response.use(
    (response) => {
        if (response.headers['x-token-refreshed']) {
            const newToken = response.headers['x-token-refreshed'];
            authService.setToken(newToken);
        }
        
        if (response.data?.new_token) {
            authService.setToken(response.data.new_token);
        }
        
        return response;
    },
    async (error) => {
        const originalRequest = error.config;
        
        if (error.response?.status === 401 && !originalRequest._retry) {
            if (isRefreshing) {
                return new Promise((resolve, reject) => {
                    failedQueue.push({ resolve, reject });
                })
                .then((token) => {
                    originalRequest.headers.Authorization = `Bearer ${token}`;
                    return api(originalRequest);
                })
                .catch((err) => {
                    return Promise.reject(err);
                });
            }

            originalRequest._retry = true;
            isRefreshing = true;

            try {
                const newToken = await authService.refreshToken();
                processQueue(null, newToken);
                originalRequest.headers.Authorization = `Bearer ${newToken}`;
                return api(originalRequest);
            } catch (refreshError) {
                processQueue(refreshError, null);
                authService.clearAuth();
                
                if (typeof window !== 'undefined' && window.location) {
                    const currentPath = window.location.pathname || window.location.hash;
                    if (currentPath.includes('oficina') || currentPath.includes('monitor')) {
                        window.location.href = '#/';
                    }
                }
                
                return Promise.reject(refreshError);
            } finally {
                isRefreshing = false;
            }
        }

        return Promise.reject(error);
    }
);

export default api;
