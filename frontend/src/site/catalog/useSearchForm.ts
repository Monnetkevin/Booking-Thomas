import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router'
import { useLocale } from '@/i18n/useLocale'
import { isValidStay, searchToParams } from './searchParams'
import { addDays, isoDate } from './stayCalendar'
import type { SearchCriteria } from './types'

/**
 * État et soumission du formulaire de recherche (dates, voyageurs) : mène à la page des
 * logements filtrée. L'affichage est laissé au composant.
 */
export function useSearchForm(initial?: SearchCriteria | null) {
  const { t } = useTranslation()
  const { to } = useLocale()
  const navigate = useNavigate()
  // Date du jour figée au premier rendu (minimum des champs de date).
  const [today] = useState(() => isoDate(new Date()))
  const [arrival, setArrivalValue] = useState(initial?.arrival ?? '')
  const [departure, setDepartureValue] = useState(initial?.departure ?? '')
  const [guests, setGuests] = useState(initial?.guests ?? 2)
  const [error, setError] = useState<string | null>(null)

  function setArrival(value: string) {
    setArrivalValue(value)
    // Un départ devenu antérieur à l'arrivée est effacé.
    if (departure && value >= departure) setDepartureValue('')
    setError(null)
  }

  function setDeparture(value: string) {
    setDepartureValue(value)
    setError(null)
  }

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!isValidStay(arrival, departure)) {
      setError(t('search.invalidDates'))
      return
    }
    navigate(`${to('/logements')}?${searchToParams({ arrival, departure, guests })}`)
  }

  return {
    arrival,
    departure,
    guests,
    error,
    minArrival: addDays(today, 1),
    minDeparture: arrival ? addDays(arrival, 1) : addDays(today, 2),
    setArrival,
    setDeparture,
    setGuests,
    submit,
  }
}
