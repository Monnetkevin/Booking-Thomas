/** Clé de site Cloudflare Turnstile : absente en local, le défi est alors désactivé. */
export const TURNSTILE_SITE_KEY = import.meta.env.VITE_TURNSTILE_SITE_KEY

export const turnstileEnabled = Boolean(TURNSTILE_SITE_KEY)
