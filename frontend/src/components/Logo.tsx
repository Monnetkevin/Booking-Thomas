import { useTranslation } from 'react-i18next'
import { cn } from '@/lib/cn'

/** Logo du site (dessin carré du propriétaire). Décoratif : le nom l'accompagne toujours. */
export function LogoMark({ className }: { className?: string }) {
  return (
    <img
      src="/images/logo.webp"
      alt=""
      width={40}
      height={40}
      className={cn('size-10 shrink-0 rounded-lg shadow-sm', className)}
    />
  )
}

/** `nameClassName` permet de masquer le nom sur petit écran (il reste lu par les lecteurs d'écran). */
export function Logo({ className, nameClassName }: { className?: string; nameClassName?: string }) {
  const { t } = useTranslation()

  return (
    <span className={cn('flex items-center gap-3 text-[17px] font-bold', className)}>
      <LogoMark className="size-12" />
      <span className={nameClassName}>{t('site.name')}</span>
    </span>
  )
}
