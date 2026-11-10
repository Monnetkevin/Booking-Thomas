import type { HTMLAttributes } from 'react'
import { cn } from '@/lib/cn'

/** Largeur de lecture du site : 16 px de marge sur téléphone, 1200 px au plus. */
export function Container({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('mx-auto w-full max-w-[1200px] px-4 sm:px-6 lg:px-8', className)} {...props} />
}
