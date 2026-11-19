import type { AuditValues } from './types'

export interface AuditChange {
  field: string
  before: string | null
  after: string | null
}

function show(value: unknown): string | null {
  if (value === undefined || value === null || value === '') return null
  if (typeof value === 'boolean') return value ? 'oui' : 'non'
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

/** Champs qui diffèrent entre avant et après (création : tout l'après ; suppression : tout l'avant). */
export function auditChanges(before: AuditValues | null, after: AuditValues | null): AuditChange[] {
  const fields = [...new Set([...Object.keys(before ?? {}), ...Object.keys(after ?? {})])]
  return fields
    .map((field) => ({ field, before: show(before?.[field]), after: show(after?.[field]) }))
    .filter((change) => change.before !== change.after)
}
