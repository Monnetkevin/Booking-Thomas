import { describe, expect, it } from 'vitest'
import { moveItem } from './arrays'

describe('moveItem', () => {
  it('avance, recule et met en tête', () => {
    expect(moveItem(['a', 'b', 'c'], 2, 0)).toEqual(['c', 'a', 'b'])
    expect(moveItem(['a', 'b', 'c'], 0, 1)).toEqual(['b', 'a', 'c'])
  })

  it('ignore un déplacement hors limites sans modifier l’original', () => {
    const items = ['a', 'b']
    expect(moveItem(items, 0, 5)).toEqual(['a', 'b'])
    expect(moveItem(items, 1, 0)).not.toBe(items)
    expect(items).toEqual(['a', 'b'])
  })
})
