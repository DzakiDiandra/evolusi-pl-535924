<?php

namespace Database\Seeders;

use App\Models\Tugas;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if (Tugas::count() == 0) {
            Tugas::create(['judul' => 'Tugas 1: Setup Proyek', 'deskripsi' => 'Menyiapkan Laravel dan konfigurasi awal', 'selesai' => true]);
            Tugas::create(['judul' => 'Tugas 2: Konfigurasi Database', 'deskripsi' => 'Konfigurasi SQLite dan migrasi', 'selesai' => true]);
            Tugas::create(['judul' => 'Tugas 3: API Endpoints', 'deskripsi' => 'Membuat endpoint tugas dan auth', 'selesai' => false]);
            Tugas::create(['judul' => 'Tugas 4: Docker', 'deskripsi' => 'Membuat Dockerfile dan docker-compose', 'selesai' => false]);
            Tugas::create(['judul' => 'Tugas 5: CI/CD', 'deskripsi' => 'Konfigurasi GitHub Actions', 'selesai' => false]);
        }
    }
}
