export function hitungSelesai(daftar) {
  return daftar.filter((t) => t.selesai).length
}

export function persenSelesai(daftar) {
  if (daftar.length === 0) return 0
  return Math.round((hitungSelesai(daftar) / daftar.length) * 100)
}
