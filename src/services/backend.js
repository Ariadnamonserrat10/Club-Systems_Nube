const backendUrl = import.meta.env.VITE_BACKEND_URL || "https://clubsystem-backend-ghexckbjh4dxhgff.canadacentral-01.azurewebsites.net";

// Avoid double slashes when building endpoint URLs in api.js
export const BACKEND = backendUrl.replace(/\/+$/, "");
