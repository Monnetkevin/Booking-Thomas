import type { ReactNode } from 'react'
import { Link } from 'react-router'

/** Lien d'action souligné d'un trait épais (« Tout voir → »). */
export function UnderlineLink({ to, children }: { to: string; children: ReactNode }) {
  return (
    <Link
      to={to}
      className="border-b-2 border-ink pb-0.5 text-[15px] font-bold text-ink no-underline hover:border-accent-text hover:text-accent-text"
    >
      {children} <span aria-hidden="true">→</span>
    </Link>
  )
}
