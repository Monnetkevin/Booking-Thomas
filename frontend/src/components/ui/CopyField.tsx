import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { CheckIcon, CopyIcon } from '@/components/icons'
import { Button } from './Button'
import { TextField } from './Field'

/** Valeur en lecture seule à copier (URL, code), avec confirmation « Copié ». */
export function CopyField({ label, value }: { label: string; value: string }) {
  const { t } = useTranslation()
  const [copied, setCopied] = useState(false)

  useEffect(() => {
    if (!copied) return
    const timer = setTimeout(() => setCopied(false), 2000)
    return () => clearTimeout(timer)
  }, [copied])

  const copy = async () => {
    await navigator.clipboard.writeText(value)
    setCopied(true)
  }

  return (
    <div className="flex flex-wrap items-end gap-2">
      <TextField
        label={label}
        value={value}
        readOnly
        onFocus={(event) => event.currentTarget.select()}
        className="min-w-0 flex-1 [&_input]:font-mono [&_input]:text-sm"
      />
      <Button variant="secondary" onClick={() => void copy()}>
        {copied ? <CheckIcon /> : <CopyIcon />}
        <span aria-live="polite">{copied ? t('common.copied') : t('common.copy')}</span>
      </Button>
    </div>
  )
}
