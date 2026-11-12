/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** Clé de site Cloudflare Turnstile ; absente en local (défi désactivé). */
  readonly VITE_TURNSTILE_SITE_KEY?: string
}
