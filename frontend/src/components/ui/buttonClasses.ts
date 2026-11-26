import { cn } from '@/lib/cn'

export type Variant = 'primary' | 'dark' | 'secondary' | 'ghost' | 'danger'
export type Size = 'sm' | 'md' | 'lg' | 'xl'

const variants: Record<Variant, string> = {
  primary: 'bg-accent text-white hover:bg-accent-strong hover:text-white',
  dark: 'bg-ink text-white hover:bg-night-soft hover:text-white',
  secondary: 'border border-field bg-white text-ink hover:bg-sand hover:text-ink',
  ghost: 'bg-transparent text-ink hover:bg-sand hover:text-ink',
  danger: 'bg-danger text-white hover:bg-[#8a2818] hover:text-white',
}

const sizes: Record<Size, string> = {
  // Cibles tactiles d'au moins 44 px.
  sm: 'min-h-11 px-5 text-sm',
  md: 'min-h-11 px-4 text-[15px]',
  lg: 'min-h-12 px-5 text-base',
  xl: 'min-h-14 px-7 text-base',
}

/** Classes d'un bouton, réutilisables sur un <Link>. */
export function buttonClasses(variant: Variant = 'primary', size: Size = 'md', className?: string) {
  return cn(
    'btn inline-flex items-center justify-center gap-2 rounded-xl font-semibold no-underline transition-colors',
    'disabled:cursor-not-allowed disabled:opacity-60',
    variants[variant],
    sizes[size],
    className,
  )
}
