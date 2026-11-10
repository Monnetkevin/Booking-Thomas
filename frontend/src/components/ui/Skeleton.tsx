import { cn } from '@/lib/cn'

/** Bloc gris animé affiché pendant un chargement (masqué aux lecteurs d'écran). */
export function Skeleton({ className }: { className?: string }) {
  return <div aria-hidden="true" className={cn('animate-pulse rounded-xl bg-cream', className)} />
}
