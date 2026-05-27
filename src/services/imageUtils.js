import { BACKEND } from './backend';

export function resolveImageUrl(foto) {
  if (!foto || typeof foto !== 'string') return null;
  if (foto.startsWith('blob:')) return null;
  if (/^https?:\/\//i.test(foto)) return foto;
  
  let path = foto.startsWith('/') ? foto.slice(1) : foto;
  
  if (path.startsWith('Backend/')) {
    path = path.slice(8);
  }
  if (path.startsWith('/')) {
    path = path.slice(1);
  }
  
  return `${BACKEND}/${path}`;
}

export const resolveFotoUrl = resolveImageUrl;

export function getUserInitials(usuario) {
  if (!usuario) return 'U';
  
  const nombre = (usuario.nombre || '').trim();
  const apellidoP = (usuario.apellidoP || '').trim();
  
  let initials = '';
  if (nombre) initials += nombre.charAt(0).toUpperCase();
  if (apellidoP) initials += apellidoP.charAt(0).toUpperCase();
  
  return initials || 'U';
}

export function getNameFromInitialsData(nombre, apellidoP) {
  let initials = '';
  const n = (nombre || '').trim();
  const a = (apellidoP || '').trim();
  
  if (n) initials += n.charAt(0).toUpperCase();
  if (a) initials += a.charAt(0).toUpperCase();
  
  return initials || 'U';
}

export const imageUtils = {
  resolveImageUrl,
  resolveFotoUrl,
  getUserInitials,
  getNameFromInitialsData
};

export default imageUtils;
