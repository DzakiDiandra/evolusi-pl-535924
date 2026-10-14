<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import { persenSelesai } from '../utils/tugas'

const daftar = ref([])
const error = ref('')

onMounted(async () => {
  try {
    daftar.value = (await api.get('/tugas')).data
  } catch {
    error.value = 'Gagal memuat data API'
  }
})
</script>

<template>
  <h1>Daftar Tugas</h1>
  <p v-if="error">{{ error }}</p>
  <p>Progres: {{ persenSelesai(daftar) }}%</p>
  <ul>
    <li v-for="t in daftar" :key="t.id">{{ t.judul }} ({{ t.selesai ? 'selesai' : 'belum' }})</li>
  </ul>
</template>
