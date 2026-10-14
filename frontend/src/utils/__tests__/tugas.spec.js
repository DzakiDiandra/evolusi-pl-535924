import { describe, it, expect } from 'vitest'
import { hitungSelesai, persenSelesai } from '../tugas'

describe('logika tugas', () => {
  const data = [{ selesai: true }, { selesai: false }, { selesai: true }, { selesai: false }]
  it('menghitung tugas selesai', () => expect(hitungSelesai(data)).toBe(2))
  it('menghitung persen', () => expect(persenSelesai(data)).toBe(50))
  it('aman untuk daftar kosong', () => expect(persenSelesai([])).toBe(0))
})
