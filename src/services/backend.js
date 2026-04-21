const envBackendUrl = import.meta.env.VITE_BACKEND_URL;
const runtimeOrigin = typeof window !== "undefined" ? window.location.origin : "";
const defaultBackendUrl = envBackendUrl ? runtimeOrigin : "https://club-systems-d6d201372863.herokuapp.com";
const backendUrl = envBackendUrl || defaultBackendUrl;

// Avoid double slashes when building endpoint URLs in api.js
export const BACKEND = backendUrl.replace(/\/+$/, "");
