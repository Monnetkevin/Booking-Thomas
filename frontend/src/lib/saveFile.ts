import type { DownloadedFile } from './http'

/** Propose au navigateur d'enregistrer un fichier reçu de l'API. */
export function saveFile(file: DownloadedFile, fallbackName: string): void {
  const url = URL.createObjectURL(file.blob)
  const link = document.createElement('a')
  link.href = url
  link.download = file.filename ?? fallbackName
  document.body.append(link)
  link.click()
  link.remove()
  // Laisse au navigateur le temps de démarrer le téléchargement avant de libérer le blob.
  setTimeout(() => URL.revokeObjectURL(url), 1000)
}
