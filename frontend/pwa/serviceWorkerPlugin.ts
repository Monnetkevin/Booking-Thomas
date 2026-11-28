import { createHash } from 'node:crypto'
import { readFileSync } from 'node:fs'
import type { Plugin } from 'vite'

/** Fichiers de code de la version (JS, CSS) : leur liste donne le numéro de version. */
export function codeFiles(fileNames: string[]): string[] {
  return fileNames.filter((name) => /\.(js|css)$/.test(name)).map((name) => `/${name}`).sort()
}

/**
 * Écrit dist/sw.js au build, avec un numéro de version tiré de la liste du code. Un
 * déploiement qui change le code change sw.js, ce qui déclenche « nouvelle version ».
 */
export function serviceWorkerPlugin(): Plugin {
  return {
    name: 'booking-service-worker',
    apply: 'build',
    generateBundle(_options, bundle) {
      const files = codeFiles(Object.keys(bundle))
      const version = createHash('sha256').update(files.join('\n')).digest('hex').slice(0, 12)
      const template = readFileSync(new URL('./sw.template.js', import.meta.url), 'utf8')
      this.emitFile({
        type: 'asset',
        fileName: 'sw.js',
        source: template.replace('__VERSION__', version),
      })
    },
  }
}
