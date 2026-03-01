// src/services/api.js
// Servicio simple para consumir el backend PHP de Clubs y Alumnos
import { BACKEND } from './backend';

const CLUBS_BASE = `${BACKEND}/Clubs.php`;
const ALUMNOS_BASE = `${BACKEND}/Alumnos.php`;
const CARRERAS_BASE = `${BACKEND}/carreras.php`;
const ASISTENCIAS_BASE = `${BACKEND}/asistencias.php`;
const USUARIOS_BASE = `${BACKEND}/Usuarios.php`;
const UPLOAD_BASE = `${BACKEND}/upload.php`;
const GET_MONITORES_BASE = `${BACKEND}/getMonitores.php`;
const ASIGNAR_MONITOR_BASE = `${BACKEND}/asignarMonitor.php`;

async function toJson(res) {
  const text = await res.text();
  try { return JSON.parse(text); } catch { return { raw: text }; }
}

// Clubs
export async function getClubs() {
  const res = await fetch(CLUBS_BASE, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.error || 'Error al obtener clubs');
  return data.data || [];
}

export async function createClub(payload) {
  const res = await fetch(CLUBS_BASE, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error((data && (data.message || data.error)) || 'Error al crear club');
  return data.data;
}

export async function updateClub(id, payload) {
  const url = `${CLUBS_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error((data && (data.message || data.error)) || 'Error al actualizar club');
  return data.data;
}

export async function deleteClub(id) {
  const url = `${CLUBS_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, { method: 'DELETE' });
  const data = await toJson(res);
  if (!res.ok) throw new Error((data && (data.message || data.error)) || 'Error al eliminar club');
  return data;
}

// Carreras
export async function getCarreras() {
  const res = await fetch(CARRERAS_BASE, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error((data && (data.message || data.error)) || 'Error al obtener carreras');
  return data.data || [];
}

// Asistencias
export async function getAsistenciasPorClub(clubId) {
  const url = `${ASISTENCIAS_BASE}?club_id=${encodeURIComponent(clubId)}`;
  const res = await fetch(url, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al obtener asistencias');
  return data.data || { fechas: [], asistencias: {}, alumnos: [] };
}

export async function crearFechaAsistencias({ club_id, fecha, registros }) {
  const res = await fetch(ASISTENCIAS_BASE, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ club_id, fecha, registros })
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al crear fecha/asistencias');
  return data;
}

export async function actualizarAsistencia({ alumno_id, fecha, presente }) {
  const res = await fetch(ASISTENCIAS_BASE, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ alumno_id, fecha, presente })
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al actualizar asistencia');
  return data;
}

// Usuarios
export async function getUsuarios() {
  const res = await fetch(USUARIOS_BASE, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al obtener usuarios');
  return data.data || [];
}

export async function updateUsuario(id, payload) {
  const url = `${USUARIOS_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al actualizar usuario');
  return data;
}

export async function deleteUsuario(id) {
  const url = `${USUARIOS_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, { method: 'DELETE' });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al eliminar usuario');
  return data;
}

export async function uploadFoto(file) {
  const form = new FormData();
  form.append('foto', file);
  const res = await fetch(UPLOAD_BASE, { method: 'POST', body: form });
  const data = await toJson(res);
  if (!res.ok) throw new Error(data?.message || 'Error al subir imagen');
  return data; // { status, file, filename }
}

// Monitores por Club
export async function getMonitoresPorClub(clubId) {
  const url = `${GET_MONITORES_BASE}?club_id=${encodeURIComponent(clubId)}`;
  const res = await fetch(url, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) {
    console.error('Error fetching monitores:', res.status, data);
    return { club_id: clubId, monitores: [] };
  }
  return data.data || { club_id: clubId, monitores: [] };
}

// Obtener todos los monitores con sus clubs asignados
export async function getAllMonitoresWithClubs() {
  const res = await fetch(USUARIOS_BASE, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) {
    console.error('Error fetching all usuarios:', res.status, data);
    return [];
  }
  // Filtrar solo monitores que tengan club asignado
  return (data.data || []).filter(u => u.tipo === 'MONITOR' && u.club_asignado);
}

// Asignar monitor a un club
export async function asignarMonitorAClub(monitorId, clubId) {
  const url = ASIGNAR_MONITOR_BASE;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ monitor_id: monitorId, club_id: clubId })
  });
  const data = await toJson(res);
  if (!res.ok) {
    console.error('Error asigning monitor:', res.status, data);
    throw new Error(data?.message || 'Error al asignar monitor');
  }
  return data.data;
}

// Alumnos
export async function getAlumnos() {
  const res = await fetch(ALUMNOS_BASE, { method: 'GET' });
  const data = await toJson(res);
  if (!res.ok) throw new Error((data && (data.message || data.error)) || 'Error al obtener alumnos');
  return data.data || [];
}

export async function createAlumno(payload) {
  const res = await fetch(ALUMNOS_BASE, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await toJson(res);
  if (!res.ok) {
    const msg = data?.message || data?.error || (Array.isArray(data?.details) ? data.details.join(', ') : 'Error al crear alumno');
    throw new Error(msg);
  }
  return data.data;
}

export async function updateAlumno(id, payload) {
  const url = `${ALUMNOS_BASE}?id=${encodeURIComponent(id)}`;
  const res = await fetch(url, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await toJson(res);
  if (!res.ok) {
    const msg = data?.message || data?.error || (Array.isArray(data?.details) ? data.details.join(', ') : 'Error al actualizar alumno');
    throw new Error(msg);
  }
  return data.data;
}
