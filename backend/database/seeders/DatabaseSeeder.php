<?php

namespace Database\Seeders;

use App\Models\Tugas;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create(['name' => 'Admin', 'email' => 'admin@example.com']);
        Tugas::factory()->count(5)->create();
    }
}
