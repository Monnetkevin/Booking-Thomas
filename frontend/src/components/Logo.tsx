import { useTranslation } from 'react-i18next'
import { cn } from '@/lib/cn'

/** Pictogramme maison provisoire, en attendant le logo du client. */
export function LogoMark({ className }: { className?: string }) {
  return (
    <svg aria-hidden="true" viewBox="0 0 28 28" className={cn('size-7 shrink-0', className)}>
      <rect width="28" height="28" rx="8" className="fill-accent" />
      <path
        d="M8 20V12l6-4 6 4v8"
        fill="none"
        stroke="#fff"
        strokeWidth="2"
        strokeLinejoin="round"
        strokeLinecap="round"
      />
    </svg>
  )
}

export function Logo({ className }: { className?: string }) {
  const { t } = useTranslation()

  return (
    <span className={cn('flex items-center gap-2.5 text-[17px] font-bold', className)}>
      <LogoMark />
      <span>{t('site.name')}</span>
    </span>
  )
}
