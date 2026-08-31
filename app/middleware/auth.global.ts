import { authService } from '../../src/services/auth'

export default defineNuxtRouteMiddleware(async (to) => {
  if (import.meta.server) return

  const requiresAuth = Boolean(to.meta.requiresAuth)
  const isPublic = Boolean(to.meta.public)
  if (requiresAuth && !authService.isAuthenticated()) {
    return navigateTo({ path: '/', query: { redirect: to.fullPath } })
  }
  if (!authService.isAuthenticated()) return

  let user = authService.getUserData()
  if (!user?.tipo) {
    try {
      user = await authService.getCurrentUser()
    } catch {
      authService.clearAuth()
      return navigateTo('/')
    }
  }

  const role = user?.rol || user?.tipo
  const allowedRoles = Array.isArray(to.meta.roles) ? to.meta.roles : []
  if (allowedRoles.length && !allowedRoles.includes(role)) {
    return navigateTo(role === 'MONITOR' ? '/monitor' : '/oficina')
  }
  if (isPublic && to.path === '/') {
    return navigateTo(role === 'MONITOR' ? '/monitor' : '/oficina')
  }
})
