import 'leaflet/dist/leaflet.css'
import L from 'leaflet'
import { useEffect, useRef } from 'react'

interface LocationMapProps {
  latitude: number
  longitude: number
  label: string
}

/**
 * Carte OpenStreetMap du quartier : un cercle d'environ 300 m autour d'une position déjà
 * arrondie par l'API, jamais l'adresse exacte. Chargée à part (code découpé).
 */
export default function LocationMap({ latitude, longitude, label }: LocationMapProps) {
  const containerRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const container = containerRef.current
    if (!container) return

    const map = L.map(container, { scrollWheelZoom: false, attributionControl: true }).setView([latitude, longitude], 15)
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 17,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map)
    L.circle([latitude, longitude], { radius: 300, color: '#c05630', fillColor: '#c05630', fillOpacity: 0.15, weight: 2 }).addTo(map)

    return () => {
      map.remove()
    }
  }, [latitude, longitude])

  return <div ref={containerRef} role="img" aria-label={label} className="h-[260px] w-full overflow-hidden rounded-[28px] bg-cream" />
}
