/** Déplace l'élément d'index `from` à l'index `to` (nouvelle liste, l'originale est intacte). */
export function moveItem<T>(items: readonly T[], from: number, to: number): T[] {
  if (from === to || from < 0 || to < 0 || from >= items.length || to >= items.length) return [...items]
  const copy = [...items]
  const [moved] = copy.splice(from, 1)
  copy.splice(to, 0, moved as T)
  return copy
}
