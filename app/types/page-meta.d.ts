declare module '#app' {
  interface PageMeta {
    public?: boolean
    requiresAuth?: boolean
    roles?: string[]
  }
}
export {}
