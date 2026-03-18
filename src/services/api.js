// src/services/api.js
import { BACKEND } from './backend';

async function request(url, options = {}) {
  const res = await fetch(url, options);
  const text = await res.text();

  let data;
  try { data = JSON.parse(text); } 
  catch { data = { raw: text }; }

  if (!res.ok) {
    throw new Error(data?.message || data?.error || 'Error en la petición');
  }

  return data;
}

// ================= BASE URLs =================
const CLUBS = `${BACKEND}/Clubs.php`;
const ALUMNOS = `${BACKEND}/Alumnos.php`;
const CARRERAS = `${BACKEND}/carreras.php`;
const ASISTENCIAS = `${BACKEND}/asistencias.php`;
const USUARIOS = `${BACKEND}/Usuarios.php`;
const UPLOAD = `${BACKEND}/upload.php`;
const MONITORES = `${BACKEND}/getMonitores.php`;
const ASIGNAR = `${BACKEND}/asignarMonitor.php`;

// ================= CLUBS =================
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

// ================= CARRERAS =================
export const getCarreras = () => request(CARRERAS);

// ================= ASISTENCIAS =================
export const getAsistenciasPorClub = (clubId) =>
  request(`${ASISTENCIAS}?club_id=${clubId}`);

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

// ================= USUARIOS =================
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

// ================= UPLOAD =================
export const uploadFoto = async (file) => {
  const form = new FormData();
  form.append('foto', file);

  return request(UPLOAD, {
    method: 'POST',
    body: form
  });
};

// ================= MONITORES =================
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

// ================= ALUMNOS =================
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