/** Cible de redirection après connexion, limitée au back-office (pas de redirection ouverte). */
export function safeAdminRedirect(target: string | null): string {
  if (!target || !target.startsWith('/admin') || target.startsWith('/admin/connexion')) return '/admin'
  return target
}

/** Page de connexion qui ramènera ensuite à la page demandée. */
export function loginPath(from: string): string {
  return `/admin/connexion?redirect=${encodeURIComponent(from)}`
}
