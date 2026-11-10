import { useLayoutEffect } from 'react'
import { Outlet } from 'react-router'
import { applyLocale } from '@/i18n'
import { LanguageSwitcher } from '@/i18n/LanguageSwitcher'
import { useLocale } from '@/i18n/useLocale'

export function PublicLayout() {
  const { locale } = useLocale()

  useLayoutEffect(() => applyLocale(locale), [locale])

  return (
    <div className="min-h-dvh">
      <header>
        <LanguageSwitcher />
      </header>
      <main>
        <Outlet />
      </main>
    </div>
  )
}
