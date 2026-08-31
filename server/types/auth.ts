export type UserRole = 'SUPERADMIN' | 'ADMIN' | 'MONITOR' | 'OFICINA'

export interface AuthUser {
  id: number
  usuario: string
  nombre: string
  apellidoP: string
  apellidoM: string
  tipo: UserRole
  rol: UserRole
  club_asignado: number | null
}
