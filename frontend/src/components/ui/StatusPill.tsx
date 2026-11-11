import type { ReactNode } from 'react'

/** Étiquette arrondie précédée d'un point de couleur (« Réservation en direct… »). */
export function StatusPill({ children }: { children: ReactNode }) {
  return (
    <p className="flex items-center gap-2 self-start rounded-full border border-line bg-white px-3.5 py-2 text-[13px] font-bold">
      <span aria-hidden="true" className="size-2 rounded-full bg-[#2e9e5b]" />
      {children}
    </p>
  )
}
