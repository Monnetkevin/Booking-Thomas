import { useMutation } from '@tanstack/react-query'
import { useCallback, useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { useSearchParams } from 'react-router'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { SelectField, TextArea, TextField } from '@/components/ui/Field'
import { Container } from '@/components/ui/Container'
import { useLocale } from '@/i18n/useLocale'
import { ApiError } from '@/lib/api'
import { useApartments } from '@/site/catalog/useCatalog'
import { Seo } from '@/site/components/Seo'
import { contactService, type ContactInput } from '@/site/contact/contactService'
import { Turnstile } from '@/site/contact/Turnstile'
import { turnstileEnabled } from '@/site/contact/turnstileConfig'

const empty = { name: '', email: '', phone: '', message: '', website: '' }

export function ContactPage() {
  const { t } = useTranslation()
  const { locale } = useLocale()
  const [params] = useSearchParams()
  const apartments = useApartments()
  const [values, setValues] = useState(empty)
  const [apartment, setApartment] = useState(params.get('logement') ?? '')
  const [token, setToken] = useState<string | null>(null)
  const onToken = useCallback((value: string | null) => setToken(value), [])
  const send = useMutation({ mutationFn: (input: ContactInput) => contactService.send(input) })

  const fieldErrors = send.error instanceof ApiError ? send.error.fieldErrors : {}
  const error = (field: string) => fieldErrors[field]?.[0]
  const general = send.error instanceof ApiError && send.error.status !== 422 ? t('common.error') : (error('captcha') ?? null)

  const bind = (field: keyof typeof empty) => ({
    name: field,
    value: values[field],
    error: error(field),
    onChange: (event: { target: { value: string } }) => setValues((current) => ({ ...current, [field]: event.target.value })),
  })

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    send.mutate({ ...values, apartment: apartment || null, captcha_token: token })
  }

  return (
    <Container className="grid gap-10 py-10 sm:py-14 lg:grid-cols-[1fr_1.2fr]">
      <Seo title={t('contact.title')} description={t('contact.metaDescription')} path="/contact" />
      <header className="flex flex-col gap-3">
        <h1 className="text-4xl font-semibold sm:text-[44px]">{t('contact.title')}</h1>
        <p className="text-[17px] leading-relaxed text-muted">{t('contact.intro')}</p>
      </header>

      {send.isSuccess ? (
        <div className="flex flex-col items-start gap-4 rounded-[var(--radius-card)] border border-border bg-white p-6">
          <h2 className="text-2xl font-semibold">{t('contact.sentTitle')}</h2>
          <Alert tone="success">{send.data.message}</Alert>
          <Button
            variant="secondary"
            onClick={() => {
              setValues(empty)
              send.reset()
            }}
          >
            {t('contact.another')}
          </Button>
        </div>
      ) : (
        <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4 rounded-[var(--radius-card)] border border-border bg-white p-5 sm:p-6">
          {general && <Alert tone="danger">{general}</Alert>}
          <div className="grid gap-4 sm:grid-cols-2">
            <TextField label={t('contact.name')} autoComplete="name" required maxLength={120} {...bind('name')} />
            <TextField label={t('contact.email')} type="email" autoComplete="email" required {...bind('email')} />
            <TextField label={t('contact.phone')} hint={t('contact.phoneHint')} type="tel" autoComplete="tel" maxLength={40} {...bind('phone')} />
            <SelectField
              label={t('contact.apartment')}
              value={apartment}
              error={error('apartment')}
              onChange={setApartment}
              options={[
                { value: '', label: t('contact.apartmentNone') },
                ...(apartments.data ?? []).map((item) => ({ value: item.slug, label: item.name })),
              ]}
            />
          </div>
          <TextArea label={t('contact.message')} hint={t('contact.messageHint')} required rows={6} maxLength={5000} {...bind('message')} />
          {/* Champ piège : masqué aux humains et aux lecteurs d'écran, rempli par les robots. */}
          <div aria-hidden="true" className="absolute -left-[9999px] h-px w-px overflow-hidden">
            <label>
              {t('contact.website')}
              <input type="text" tabIndex={-1} autoComplete="off" {...{ value: values.website, onChange: bind('website').onChange }} />
            </label>
          </div>
          <Turnstile onToken={onToken} language={locale} />
          <p className="text-sm text-muted">{t('contact.privacy')}</p>
          <Button type="submit" size="lg" loading={send.isPending} disabled={turnstileEnabled && !token} className="self-start">
            {t('contact.submit')}
          </Button>
        </form>
      )}
    </Container>
  )
}
