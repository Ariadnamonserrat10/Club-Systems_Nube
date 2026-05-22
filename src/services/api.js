import { BACKEND } from './backend';
import { authService } from './auth';

function normalizeApiErrorMessage(data) {
  const detailsArray = Array.isArray(data?.details) ? data.details.filter(Boolean) : [];
  const detailsString = typeof data?.details === 'string' ? data.details.trim() : '';
  const baseMsg = (data?.message || data?.error || '').toString().trim();

  if (detailsArray.length) return detailsArray.join('. ');
  if (detailsString) return detailsString;

  if (/^validaci[oó]n$/i.test(baseMsg)) {
    return 'Los datos enviados no son válidos. Verifica texto, números y caracteres permitidos.';
  }

  return baseMsg || 'Error en la petición';
}

function getAuthHeaders() {
  const headers = {};
  const token = authService.getToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  return headers;
}

async function handleAuthError(response) {
  if (response.status === 401) {
    try {
      const newToken = await authService.refreshToken();
      return true;
    } catch (e) {
      authService.clearAuth();
      if (typeof window !== 'undefined' && window.location) {
        const hash = window.location.hash || '';
        if (hash.includes('oficina') || hash.includes('monitor')) {
          window.location.href = '#/';
        }
      }
      return false;
    }
  }
  return true;
}

async function request(url, options = {}) {
  const finalOptions = {
    ...options,
    headers: {
      ...getAuthHeaders(),
      ...options.headers
    }
  };

  let res = await fetch(url, finalOptions);
  
  if (res.status === 401) {
    const retrySuccess = await handleAuthError(res);
    if (retrySuccess && authService.getToken()) {
      finalOptions.headers = {
        ...getAuthHeaders(),
        ...options.headers
      };
      res = await fetch(url, finalOptions);
    }
  }

  const text = await res.text();

  let data;
  try { data = JSON.parse(text); } 
  catch { data = { raw: text }; }

  const finalMsg = normalizeApiErrorMessage(data);

  if (!res.ok || data?.status === 'error') {
    throw new Error(finalMsg);
  }

  return data;
}

const CLUBS = `${BACKEND}/Clubs.php`;
const ALUMNOS = `${BACKEND}/Alumnos.php`;
const CARRERAS = `${BACKEND}/carreras.php`;
const ASISTENCIAS = `${BACKEND}/asistencias.php`;
const USUARIOS = `${BACKEND}/Usuarios.php`;
const UPLOAD = `${BACKEND}/upload.php`;
const MONITORES = `${BACKEND}/getMonitores.php`;
const ASIGNAR = `${BACKEND}/asignarMonitor.php`;
const EVALUACION = `${BACKEND}/evaluacion.php`;
const SAVE_EVALUACION = `${BACKEND}/saveEvaluacion.php`;
const GET_EVALUADOS = `${BACKEND}/getEvaluatedStudents.php`;
const AUDITORIA = `${BACKEND}/auditoria.php`;
const FIRMAS = `${BACKEND}/firmas.php`;
const CONFIG = `${BACKEND}/config.php`;

export const getClubs = async () => {
  const data = await request(CLUBS);
  return Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);
};

export const createClub = (payload) =>
  request(CLUBS, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

export const updateClub = (id, payload) =>
  request(`${CLUBS}?id=${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

export const deleteClub = (id) =>
  request(`${CLUBS}?id=${id}`, { method: 'DELETE' });

export const getCarreras = async () => {
  const data = await request(CARRERAS);
  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.data)) return data.data;
  if (Array.isArray(data?.carreras)) return data.carreras;
  if (Array.isArray(data?.rows)) return data.rows;
  return [];
};

export const getAsistenciasPorClub = async (clubId) => {
  const data = await request(`${ASISTENCIAS}?club_id=${clubId}`);
  const payload = data && typeof data === 'object' && !Array.isArray(data) && data.data
    ? data.data
    : data;

  return {
    fechas: Array.isArray(payload?.fechas) ? payload.fechas : [],
    alumnos: Array.isArray(payload?.alumnos) ? payload.alumnos : [],
    asistencias: payload?.asistencias && typeof payload.asistencias === 'object' ? payload.asistencias : {}
  };
};

export const crearFechaAsistencias = (payload) =>
  request(ASISTENCIAS, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

export const actualizarAsistencia = (payload) =>
  request(ASISTENCIAS, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

export const getUsuarios = async () => {
  const data = await request(USUARIOS);
  if (Array.isArray(data)) return data;
  return Array.isArray(data?.data) ? data.data : [];
};

export const updateUsuario = (id, payload) =>
  request(`${USUARIOS}?id=${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

export const deleteUsuario = (id) =>
  request(`${USUARIOS}?id=${id}`, { method: 'DELETE' });

export const uploadFoto = async (file) => {
  const form = new FormData();
  form.append('foto', file);

  return request(UPLOAD, {
    method: 'POST',
    body: form
  });
};

export const getMonitoresPorClub = (clubId) =>
  request(`${MONITORES}?club_id=${clubId}`);

export const getAllMonitoresWithClubs = async () => {
  const data = await request(USUARIOS);
  const usuarios = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);
  return usuarios.filter(u => u.tipo === 'MONITOR' && u.club_asignado);
};

export const asignarMonitorAClub = (monitorId, clubId) =>
  request(ASIGNAR, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ monitor_id: monitorId, club_id: clubId })
  });

export const getEvaluacion = async (params = {}) => {
  const query = new URLSearchParams(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== '')
  ).toString();

  return request(query ? `${EVALUACION}?${query}` : EVALUACION);
};

export const getFirmas = () => request(FIRMAS);

export const asignarCargo = (cargo, idUsuario) =>
  request(FIRMAS, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ cargo, id_usuario: idUsuario })
  });

export const saveConfig = (clave, valor) =>
  request(CONFIG, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ clave, valor })
  });

export const getAlumnos = async () => {
  const data = await request(ALUMNOS);
  return Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);
};

export const createAlumno = (payload) =>
  request(ALUMNOS, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

export const updateAlumno = (id, payload) =>
  request(`${ALUMNOS}?id=${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

export const saveEvaluacion = (payload) =>
  request(SAVE_EVALUACION, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

export const getEvaluatedStudents = async (clubName) => {
  const data = await request(`${GET_EVALUADOS}?club_name=${encodeURIComponent(clubName)}`);
  return Array.isArray(data?.data) ? data.data : [];
};

export const registrarAuditoria = (payload) =>
  request(AUDITORIA, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
