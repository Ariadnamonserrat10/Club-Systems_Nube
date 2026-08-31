export function titleCase(value: string) {
  return value.trim().toLocaleLowerCase('es-MX').replace(/(^|[\s'-])([\p{L}])/gu, (_, prefix, letter) => `${prefix}${letter.toLocaleUpperCase('es-MX')}`)
}

export const textPattern = /^[\p{L}]+(?:[\s'-][\p{L}]+)*$/u
export const datePattern = /^\d{4}-\d{2}-\d{2}$/

export function positiveId(value: unknown) {
  const id = Number(value)
  if (!Number.isInteger(id) || id <= 0) throw createError({ statusCode: 400, statusMessage: 'ID inválido' })
  return id
}
