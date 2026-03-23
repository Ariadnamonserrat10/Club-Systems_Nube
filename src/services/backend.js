const envBackendUrl = import.meta.env.VITE_BACKEND_URL;
const runtimeOrigin = typeof window !== "undefined" ? window.location.origin : "";
const runtimeBackendUrl = runtimeOrigin ? `${runtimeOrigin}/Backend` : "";
const defaultBackendUrl = runtimeBackendUrl || "https://clubsystem-backend-ghexckbjh4dxhgff.canadacentral-01.azurewebsites.net";
const backendUrl = envBackendUrl || defaultBackendUrl;

// Avoid double slashes when building endpoint URLs in api.js
export const BACKEND = backendUrl.replace(/\/+$/, "");
