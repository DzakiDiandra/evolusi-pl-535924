# Panduan Praktikum KEPL: Tugas 3, 4, 5 dan UTS (dari Nol)

Laravel API, Vue 3, GitHub Actions, Docker, Docker Compose, dan GHCR, lengkap dengan mitigasi risiko di setiap langkah.

## 0. Gambaran Besar

Semua tugas dikerjakan dalam **satu monorepo** bernama `evolusi-pl-NIM` (ganti NIM dengan NIM kamu, huruf kecil semua agar aman untuk GHCR).

```
evolusi-pl-NIM/
├── backend/            # Laravel API
├── frontend/           # Vue 3 + Vite
├── docs/               # screenshot, koleksi Postman, bahan laporan
├── docker-compose.yml  # UTS
└── .github/workflows/  # frontend.yml, backend.yml, ghcr.yml
```

Peta tugas ke CPMK UTS, supaya satu pekerjaan menjawab banyak soal:

| Pekerjaan | Tugas | Soal UTS |
| --- | --- | --- |
| Laravel API + login token + feature test | Tugas 3 (no. 1) | Soal 1 (15) |
| Vue 3 + Axios + router | Tugas 3 (no. 2) | Soal 2 (10) |
| Repo, branch, PR, branch protection, CI dua job | Tugas 3 (no. 5, 7) | Soal 3 (15) |
| Deploy job, GitHub Secrets, image di GHCR | Tugas 5 (no. 5) | Soal 4 (15) |
| Dockerfile backend + frontend multi-stage, .dockerignore | Tugas 4 dan 5 | Soal 5 (20) |
| Docker Compose db + backend + frontend | Hanya UTS | Soal 6 (25) |
| FrankenPHP | Hanya UTS | Bonus (+10) |

**Urutan kerja yang disarankan:** Fase 1 persiapan, Fase 2 backend, Fase 3 frontend, Fase 4 CI frontend (Tugas 3), Fase 5 Docker backend (Tugas 4), Fase 6 multi-stage dan GHCR (Tugas 5), Fase 7 pelengkap UTS, Fase 8 laporan PDF.

**Aturan emas:** setiap langkah selesai langsung **screenshot dan simpan di `docs/`**. Laporan tanpa bukti tidak dinilai, dan banyak bukti (waktu build, status merah, status skipped) mudah hilang kalau tidak diambil saat kejadian.

## 1. Fase 1: Persiapan

### 1.1 Alat yang dipasang

- Git, akun GitHub
- PHP 8.3 dan Composer 2 (atau Laravel Herd / XAMPP)
- Node.js 22 LTS dan npm
- Docker Desktop (aktifkan WSL2 bila Windows)
- Postman

Cek semuanya:

```bash
git --version
php -v
composer -V
node -v && npm -v
docker version
docker compose version
```

### 1.2 Buat repository

```bash
mkdir evolusi-pl-NIM && cd evolusi-pl-NIM
git init -b main
```

Buat repo kosong di GitHub dengan nama yang sama (**tanpa** README/gitignore otomatis supaya tidak konflik), lalu:

```bash
git remote add origin https://github.com/USERNAME/evolusi-pl-NIM.git
```

### Risiko dan mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Repo Laravel lama sudah ada di root, bukan di `backend/` | Pindahkan dengan `git mv` ke `backend/` dan commit sebelum lanjut. Semua path di panduan ini mengasumsikan `backend/`. |
| Docker Desktop tidak jalan (virtualisasi mati) | Aktifkan virtualization di BIOS/WSL2, restart Docker, cek `docker run hello-world`. |
| Nama repo/image berhuruf besar | Pakai huruf kecil sejak awal. GHCR menolak huruf besar. |
| Versi PHP lokal berbeda dari yang dipakai Docker/CI | Samakan: PHP 8.3 di lokal, Dockerfile, dan workflow. |

## 2. Fase 2: Backend Laravel (Soal 1 UTS, Tugas 3 no. 1)

### 2.1 Buat proyek

```bash
composer create-project laravel/laravel backend
cd backend
php artisan install:api        # memasang Sanctum + routes/api.php
```

Untuk awal pakai SQLite supaya sederhana. Di `.env`:

```
DB_CONNECTION=sqlite
```

(file `database/database.sqlite` dibuat otomatis oleh Laravel saat migrate; jika diminta, jawab yes.)

### 2.2 Model, migration, controller (CRUD tabel `tugas`)

```bash
php artisan make:model Tugas -mf
php artisan make:controller Api/TugasController --api --model=Tugas
```

Isi migration `create_tugas_table`:

```php
Schema::create('tugas', function (Blueprint $table) {
    $table->id();
    $table->string('judul');
    $table->text('deskripsi')->nullable();
    $table->boolean('selesai')->default(false);
    $table->timestamps();
});
```

Di `app/Models/Tugas.php` tambahkan:

```php
protected $table = 'tugas';
protected $fillable = ['judul', 'deskripsi', 'selesai'];
```

(Nama tabel diset eksplisit karena Laravel bisa menebak jamak `tugas` secara tak terduga.)

Isi `TugasController` (versi ringkas):

```php
public function index()  { return response()->json(Tugas::latest()->get()); }
public function show(Tugas $tugas) { return response()->json($tugas); }

public function store(Request $request)
{
    $data = $request->validate([
        'judul' => 'required|string|max:255',
        'deskripsi' => 'nullable|string',
        'selesai' => 'boolean',
    ]);
    return response()->json(Tugas::create($data), 201);
}

public function update(Request $request, Tugas $tugas)
{
    $data = $request->validate([
        'judul' => 'sometimes|required|string|max:255',
        'deskripsi' => 'nullable|string',
        'selesai' => 'boolean',
    ]);
    $tugas->update($data);
    return response()->json($tugas);
}

public function destroy(Tugas $tugas)
{
    $tugas->delete();
    return response()->noContent();
}
```

Seeder kecil agar ada data demo (`database/seeders/DatabaseSeeder.php`): buat user dan beberapa tugas.

```php
User::factory()->create(['name' => 'Admin', 'email' => 'admin@example.com']); // password default factory: "password"
Tugas::factory()->count(5)->create();
```

Isi `TugasFactory`: `judul => fake()->sentence(3)`, `deskripsi => fake()->paragraph()`, `selesai => fake()->boolean()`.

```bash
php artisan migrate --seed
```

### 2.3 Route dan login token (`routes/api.php`)

Baca (GET) dibuat publik agar Vue dan Postman tugas Docker bisa langsung memanggil; tulis (POST/PUT/DELETE) dilindungi token.

```php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TugasController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/tugas', [TugasController::class, 'index']);
Route::get('/tugas/{tugas}', [TugasController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/tugas', [TugasController::class, 'store']);
    Route::put('/tugas/{tugas}', [TugasController::class, 'update']);
    Route::delete('/tugas/{tugas}', [TugasController::class, 'destroy']);
});
```

```bash
php artisan make:controller Api/AuthController
```

```php
public function login(Request $request)
{
    $cred = $request->validate(['email' => 'required|email', 'password' => 'required']);
    $user = User::where('email', $cred['email'])->first();
    if (! $user || ! Hash::check($cred['password'], $user->password)) {
        return response()->json(['message' => 'Kredensial salah'], 401);
    }
    return response()->json(['token' => $user->createToken('api')->plainTextToken]);
}

public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();
    return response()->noContent();
}
```

(import `User` dan `Hash`; pastikan model User memakai trait `HasApiTokens`. Kalau `install:api` sudah menambahkannya, tidak perlu diubah.)

### 2.4 CORS agar Vue di laptop boleh mengakses

```bash
php artisan config:publish cors
```

Di `config/cors.php`:

```php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:5173', 'http://localhost:8080'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

Jalankan `php artisan serve` dan uji `http://localhost:8000/api/tugas` di browser.

### 2.5 Uji dengan Postman

1. Buat Collection dan Environment (`base_url = http://localhost:8000/api`).
2. Request `POST {{base_url}}/login` dengan body JSON `{"email":"admin@example.com","password":"password"}`. Di tab Tests tambahkan: `pm.environment.set("token", pm.response.json().token);`
3. Request CRUD lain: Authorization → Bearer Token → `{{token}}`. Header `Accept: application/json` wajib.
4. Screenshot setiap request (URL, status, JSON). **Export collection** ke `docs/postman/`.

### 2.6 Feature test

```bash
php artisan make:test TugasApiTest
```

```php
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TugasApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_json_list(): void
    {
        Tugas::factory()->count(3)->create();
        $this->getJson('/api/tugas')->assertOk()->assertJsonCount(3);
    }

    public function test_store_requires_login(): void
    {
        $this->postJson('/api/tugas', ['judul' => 'x'])->assertUnauthorized();
    }

    public function test_authenticated_user_can_create(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/tugas', ['judul' => 'Belajar Docker'])
            ->assertCreated()->assertJsonFragment(['judul' => 'Belajar Docker']);
    }

    public function test_validation_fails_without_title(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/tugas', [])->assertStatus(422);
    }
}
```

```bash
php artisan test
```

Commit pertama:

```bash
cd ..
git add . && git commit -m "feat: laravel api tugas dengan sanctum" && git push -u origin main
```

### Risiko dan mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Postman mengembalikan HTML/redirect, bukan JSON | Tambahkan header `Accept: application/json`. |
| Error CORS di browser walau API jalan | Cek `allowed_origins` memuat port Vite (5173), jalankan `php artisan config:clear`, restart serve. |
| Tabel tidak ditemukan (`tugases`) | Set `protected $table = 'tugas'` di model. |
| 401 padahal token benar | Format harus `Bearer <token>`, token tanpa spasi/kutip. |
| Test gagal karena DB | Test memakai SQLite memori bawaan `phpunit.xml`; pakai `RefreshDatabase`. |
| `.env` ter-commit | Pastikan `backend/.gitignore` memuat `.env`. Jangan pernah commit APP\_KEY asli. |

## 3. Fase 3: Frontend Vue 3 (Soal 2 UTS, Tugas 3 no. 2 dan 6)

```bash
npm create vue@latest frontend
```

Pilih: **Router: Yes, Vitest: Yes, ESLint: Yes**, TypeScript/JSX bebas (panduan ini JS). Lalu:

```bash
cd frontend
npm install
npm install axios
```

### 3.1 Alamat API dari environment

`frontend/.env` (jangan di-commit) dan `frontend/.env.example` (di-commit):

```
VITE_API_URL=http://localhost:8000/api
```

`src/api.js`:

```js
import axios from 'axios'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: { Accept: 'application/json' },
})
```

**Jangan menulis `http://localhost:8000` langsung di komponen.**

### 3.2 Logika yang diuji dan halaman

`src/utils/tugas.js` (logika murni, mudah di-unit-test):

```js
export function hitungSelesai(daftar) {
  return daftar.filter((t) => t.selesai).length
}

export function persenSelesai(daftar) {
  if (daftar.length === 0) return 0
  return Math.round((hitungSelesai(daftar) / daftar.length) * 100)
}
```

`src/views/TugasView.vue`:

```vue
<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import { persenSelesai } from '../utils/tugas'

const daftar = ref([])
const error = ref('')

onMounted(async () => {
  try {
    daftar.value = (await api.get('/tugas')).data
  } catch (e) {
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
```

`src/router/index.js`: pastikan minimal dua route (Home `/` dan Tugas `/tugas`), dan tambahkan link `<RouterLink>` di `App.vue`. Gunakan `createWebHashHistory` jika nanti deploy ke GitHub Pages.

### 3.3 Unit test Vitest (tanpa Laravel)

`src/utils/__tests__/tugas.spec.js`:

```js
import { describe, it, expect } from 'vitest'
import { hitungSelesai, persenSelesai } from '../tugas'

describe('logika tugas', () => {
  const data = [{ selesai: true }, { selesai: false }, { selesai: true }, { selesai: false }]
  it('menghitung tugas selesai', () => expect(hitungSelesai(data)).toBe(2))
  it('menghitung persen', () => expect(persenSelesai(data)).toBe(50))
  it('aman untuk daftar kosong', () => expect(persenSelesai([])).toBe(0))
})
```

Tes ini murni fungsi, tidak memanggil jaringan, sehingga lulus di GitHub Actions tanpa Laravel.

```bash
npm run lint
npm run test:unit -- --run
npm run build
npm run dev      # buka http://localhost:5173/tugas bersama php artisan serve
```

### Risiko dan mitigasi

| Risiko | Mitigasi |
| --- | --- |
| `import.meta.env.VITE_API_URL` bernilai undefined | Nama variabel **wajib** berawalan `VITE_`. Restart `npm run dev` setelah mengubah `.env`. |
| Halaman kosong tanpa pesan | Buka DevTools → Network/Console; cek CORS dan apakah Laravel menyala. |
| `package-lock.json` tidak ter-commit, `npm ci` gagal di CI | Commit `package-lock.json`. |
| Vitest masuk mode watch dan tidak selesai di CI | Pakai `--run` (atau script `vitest run`). |
| Lint gagal karena format | Jalankan `npm run lint` (dan format bila ada) sebelum commit. |
| Unit test bergantung pada API | Uji hanya fungsi murni atau mock axios. |

## 4. Fase 4: Workflow Frontend, 4 Job (Tugas 3 no. 3 sampai 7)

Buat `.github/workflows/frontend.yml`:

```yaml
name: Frontend CI/CD

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

defaults:
  run:
    working-directory: frontend

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: 22
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json
      - run: npm ci
      - run: npm run lint

  test:
    needs: lint
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: 22
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json
      - run: npm ci
      - run: npm run test:unit -- --run

  build:
    needs: test
    runs-on: ubuntu-latest
    env:
      VITE_API_URL: ${{ vars.VITE_API_URL || 'http://localhost:8000/api' }}
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: 22
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json
      - run: npm ci
      - run: npm run build
      - uses: actions/upload-artifact@v4
        with:
          name: dist
          path: frontend/dist
          if-no-files-found: error

  deploy:
    needs: build
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: .
    steps:
      - uses: actions/download-artifact@v4
        with:
          name: dist
          path: dist
      - name: Tampilkan isi dist
        run: ls -laR dist
      - name: Deploy (simulasi)
        run: echo "Deploy artifact dist ke server/hosting"
```

Poin yang dinilai:

- `needs:` merangkai lint → test → build → deploy.
- `npm ci` dan `cache: 'npm'` ada di setiap job.
- Job deploy **tidak** menjalankan `npm run build`, hanya mengunduh artifact hasil job build, lalu `ls` menampilkan isi `dist/` ke log.
- Kondisi `if` membuat deploy hanya jalan dari `main`, sementara Pull Request tetap menjalankan lint, test, build.

### 4.1 Bukti yang diminta (Tugas 3 no. 7)

**Bukti A: PR membuat deploy skipped**

```bash
git switch -c fitur/uji-pr
# ubah teks kecil di frontend, mis. judul halaman
git add . && git commit -m "chore: uji pull request" && git push -u origin fitur/uji-pr
```

Buka Pull Request di GitHub. Screenshot tab Actions/Checks: lint, test, build hijau, **deploy abu-abu (skipped)**.

**Bukti B: test gagal, pipeline merah, lalu diperbaiki**

```bash
git switch -c fitur/test-gagal
# di tugas.spec.js ubah toBe(50) menjadi toBe(99)
git add . && git commit -m "test: sengaja gagal" && git push -u origin fitur/test-gagal
```

Screenshot: job test merah, job build dan deploy tidak jalan (skipped). Lalu kembalikan ke `toBe(50)`, commit, push, screenshot hijau. Dokumentasikan keduanya di laporan (commit gagal dan commit perbaikan).

### Risiko dan mitigasi

| Risiko | Mitigasi |
| --- | --- |
| `npm ci` gagal: lockfile tidak sinkron | Jalankan `npm install` lokal, commit `package-lock.json`, pakai Node versi sama dengan CI. |
| Cache tidak ditemukan | Isi `cache-dependency-path: frontend/package-lock.json` karena lockfile ada di subfolder. |
| `download-artifact` gagal / `dist` kosong | Nama artifact upload dan download harus sama; pakai `if-no-files-found: error`. |
| Deploy ikut jalan di PR | Pastikan kondisi `if` memuat `github.ref == 'refs/heads/main'` **dan** `event_name == 'push'`. |
| Deploy tidak pernah jalan | Cek branch default bernama `main`, bukan `master`. |
| Salah indentasi YAML | Validasi di editor; cek tab Actions untuk pesan error parse. |
| Versi action usang | Cek tag versi terbaru di halaman masing-masing action (checkout, setup-node, upload/download-artifact). |

## 5. Fase 5: Dockerfile Laravel (Tugas 4)

Di `backend/`.

### 5.1 `.dockerignore`

```
node_modules
vendor
.git
.env
.env.*
!.env.example
storage/logs/*
storage/framework/cache/data/*
tests
```

(Jika tes ingin dijalankan di dalam image, hapus baris `tests`.)

### 5.2 `Dockerfile` (satu tahap, urutan layer demi cache)

```dockerfile
FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# 1) Salin composer.json + composer.lock DULU, pasang dependency
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

# 2) Baru salin kode aplikasi
COPY . .
RUN composer dump-autoload --optimize \
    && touch database/database.sqlite

EXPOSE 8000
CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]
```

Kunci cache: layer `composer install` hanya dibangun ulang jika `composer.json`/`composer.lock` berubah. `--no-scripts` dipakai karena `artisan` belum disalin pada tahap itu.

### 5.3 Bukti cache (no. 4)

```bash
cd backend
# BUILD 1 (pertama, paling lama)
time docker build -t laravel-app:v1 .

# BUILD 2 (tanpa ubah apa pun: harus nyaris instan, banyak baris CACHED)
time docker build -t laravel-app:v1 .

# Ubah SATU huruf di kode (mis. di routes/web.php atau sebuah controller), lalu:
# BUILD 3
time docker build -t laravel-app:v1 .
```

Screenshot terminal ketiganya **dengan baris `real` waktu terlihat**. Tambahkan `--progress=plain` jika ingin baris `CACHED` terlihat jelas. Build 3 harus jauh lebih cepat daripada build 1 karena langkah `composer install` terpakai dari cache. Tempel ketiganya dalam satu PDF.

### 5.4 Jalankan, buka di browser, uji Postman (no. 5 dan 6)

Buat APP\_KEY (karena `.env` tidak ikut ke image):

```bash
php artisan key:generate --show      # salin hasilnya, mis. base64:xxxx

docker run -d --name laravel-api -p 8000:8000 \
  -e APP_KEY="base64:xxxx" \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/app/database/database.sqlite \
  laravel-app:v1

docker ps
docker logs laravel-api
```

Buka `http://localhost:8000` di browser, lalu screenshot **browser + output `docker ps` dalam satu layar** bila bisa. Untuk data, jalankan `docker exec laravel-api php artisan db:seed --force`.

Postman: `GET http://localhost:8000/api/tugas` → screenshot yang menampilkan **URL, status 200 OK, dan body JSON**.

### Risiko dan mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Build 3 tidak lebih cepat | Pastikan `COPY composer.*` dan `RUN composer install` ada **sebelum** `COPY . .`. Periksa `.dockerignore` agar file yang sering berubah tidak masuk. |
| Build 2 tetap lama | Ada file yang berubah otomatis (log, sqlite) masuk konteks; tambahkan ke `.dockerignore`. |
| `composer install` gagal di build | Pastikan `composer.lock` di-commit, dan `--no-scripts` dipakai sebelum kode disalin. |
| Error `No application encryption key` | Kirim `APP_KEY` lewat `-e`. |
| Browser tidak bisa membuka | Pakai `--host=0.0.0.0` (bukan 127.0.0.1) dan `-p 8000:8000`. |
| Port 8000 bentrok | Hentikan `php artisan serve` lokal atau pakai `-p 8001:8000`. |
| `.env` terbawa ke image | Pastikan ada di `.dockerignore`. Jangan menaruh secret di image. |
| Error 500 di dalam container | `docker logs laravel-api`; biasanya APP\_KEY, izin `storage/`, atau DB belum dimigrasi. |

## 6. Fase 6: Multi-stage, USER, HEALTHCHECK, GHCR (Tugas 5)

### 6.1 Simpan ukuran SEBELUM

Sebelum mengubah Dockerfile, tandai image Tugas 4:

```bash
docker tag laravel-app:v1 laravel-app:single
docker images laravel-app      # screenshot "sebelum"
```

### 6.2 Dockerfile multi-stage

Ganti isi `backend/Dockerfile`:

```dockerfile
# ---------- Tahap 1: build (boleh besar) ----------
FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---------- Tahap 2: runtime (kecil) ----------
FROM php:8.3-cli-alpine AS runtime

LABEL org.opencontainers.image.source="https://github.com/USERNAME/evolusi-pl-NIM"

RUN docker-php-ext-install pdo_mysql

RUN addgroup -g 1001 app && adduser -D -u 1001 -G app app

WORKDIR /app
COPY --from=vendor --chown=app:app /app /app
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && touch database/database.sqlite \
    && chown -R app:app storage bootstrap/cache database

USER app
EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD wget -qO- http://127.0.0.1:8000/up || exit 1

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]
```

Catatan:

- Tahap pertama memuat Composer dan semua alat build; tahap kedua hanya PHP runtime plus aplikasi dan `vendor` tanpa dev dependency.
- Tag diganti dari `latest` menjadi **versi tertentu** (`composer:2.8`, `php:8.3-cli-alpine`) dan varian **alpine**. Kunci lebih ketat (sampai patch) bila ingin, setelah mengecek tag yang tersedia di Docker Hub.
- `/up` adalah endpoint health bawaan Laravel 11+. Jika versimu tidak punya, buat route `/api/health` yang mengembalikan 200 dan arahkan HEALTHCHECK ke sana.
- `pdo_mysql` aman dipasang di Alpine; jika menambah ekstensi lain, periksa log build karena bisa butuh paket tambahan.

### 6.3 Build, bandingkan ukuran, uji USER dan HEALTHCHECK

```bash
docker build -t laravel-app:multi .
docker images laravel-app      # screenshot "sesudah" (single vs multi)
```

Hitung selisih dari kolom SIZE (satuan sama, MB):

```
selisih (MB)  = ukuran_sebelum - ukuran_sesudah
persentase    = selisih / ukuran_sebelum x 100%
```

Pakai angka dari screenshot kamu sendiri, lalu tulis dalam format: *sebelum X MB, sesudah Y MB, turun Z MB (P%)*.

Hentikan container lama, jalankan versi baru:

```bash
docker rm -f laravel-api
docker run -d --name laravel-api -p 8000:8000 \
  -e APP_KEY="base64:xxxx" -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/app/database/database.sqlite laravel-app:multi

docker exec laravel-api whoami     # harus: app (BUKAN root)
docker ps                          # tunggu ~30 detik sampai (healthy)
```

Screenshot `whoami` dan `docker ps` yang menunjukkan **(healthy)**.

### 6.4 Workflow GHCR (`.github/workflows/ghcr.yml`)

```yaml
name: Push image ke GHCR

on:
  push:
    branches: [main]

permissions:
  contents: read
  packages: write

jobs:
  push-image:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Nama image huruf kecil
        run: echo "IMAGE=ghcr.io/${GITHUB_REPOSITORY,,}-backend" >> "$GITHUB_ENV"

      - uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - uses: docker/setup-buildx-action@v3

      - uses: docker/build-push-action@v6
        with:
          context: ./backend
          push: true
          tags: |
            ${{ env.IMAGE }}:${{ github.sha }}
            ${{ env.IMAGE }}:latest
```

Setelah run hijau:

1. Buka profil GitHub → tab **Packages** → pilih package.
2. **Package settings → Danger Zone → Change visibility → Public**.
3. Screenshot halaman package (tag SHA terlihat) dan run workflow hijau; simpan URL halaman package untuk dikumpulkan.
4. Uji: `docker pull ghcr.io/USERNAME/evolusi-pl-nim-backend:<sha>` tanpa login (setelah Public).

### Risiko dan mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Ukuran image tidak turun banyak | Pastikan tahap runtime memakai base `alpine`, `--no-dev`, dan tidak menyalin Composer. Cek `docker history`. |
| Container exit karena izin | `storage`, `bootstrap/cache`, `database` harus dimiliki user `app`. Lihat `docker logs`. |
| `whoami` menampilkan `root` | Pastikan baris `USER app` ada setelah semua `RUN` yang butuh root. |
| Status tidak pernah `healthy` | `docker inspect --format '{{json .State.Health}}' laravel-api`; pastikan endpoint `/up` ada dan `wget` tersedia (BusyBox di Alpine). |
| `denied: permission_denied` saat push | Tambahkan `permissions: packages: write`, dan set Workflow permissions repo ke Read and write (Settings → Actions → General). |
| `repository name must be lowercase` | Pakai langkah `${GITHUB_REPOSITORY,,}` seperti di atas. |
| Package tidak tertaut ke repo | Tambahkan `LABEL org.opencontainers.image.source` di Dockerfile. |
| Package masih private, tidak bisa di-pull | Ubah visibility ke Public manual setelah push pertama. |
| Laptop Apple Silicon: arsitektur beda dengan runner | Runner membangun `amd64`; saat pull lokal tambahkan `--platform linux/amd64`. |

## 7. Fase 7: Pelengkap UTS

Bagian ini menutup soal 3, 4, 5, 6, dan bonus yang belum terpenuhi oleh Tugas 3 sampai 5.

### 7.1 Soal 3 dan 4: CI dua job, deploy job, GitHub Secrets

Buat `.github/workflows/backend.yml` dengan **dua job CI** (lint dan test), plus job image dan deploy:

```yaml
name: Backend CI/CD

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          tools: composer:v2
      - run: composer install --no-interaction --prefer-dist
        working-directory: backend
      - run: vendor/bin/pint --test
        working-directory: backend

  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          tools: composer:v2
      - run: composer install --no-interaction --prefer-dist
        working-directory: backend
      - run: cp .env.example .env && php artisan key:generate
        working-directory: backend
      - run: php artisan test
        working-directory: backend

  image:
    needs: [lint, test]
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write
    steps:
      - uses: actions/checkout@v4
      - run: echo "IMAGE=ghcr.io/${GITHUB_REPOSITORY,,}-backend" >> "$GITHUB_ENV"
      - uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}
      - uses: docker/build-push-action@v6
        with:
          context: ./backend
          push: true
          tags: |
            ${{ env.IMAGE }}:${{ github.sha }}
            ${{ env.IMAGE }}:latest

  deploy:
    needs: image
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: read
    steps:
      - run: echo "IMAGE=ghcr.io/${GITHUB_REPOSITORY,,}-backend" >> "$GITHUB_ENV"
      - uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}
      - name: Pull dan smoke test image
        env:
          APP_KEY: ${{ secrets.APP_KEY }}
        run: |
          docker pull $IMAGE:${{ github.sha }}
          docker run -d --name api -p 8000:8000 \
            -e APP_KEY="$APP_KEY" -e DB_CONNECTION=sqlite \
            -e DB_DATABASE=/app/database/database.sqlite \
            $IMAGE:${{ github.sha }}
          sleep 15
          curl -fsS http://localhost:8000/api/tugas
```

Untuk UTS simpan `backend.yml` ini dan hapus `ghcr.yml` supaya image tidak dibangun dua kali.

**GitHub Secrets:** repo → Settings → Secrets and variables → Actions → New repository secret. Buat `APP_KEY` (isi hasil `php artisan key:generate --show`). Di workflow dipanggil dengan `${{ secrets.APP_KEY }}` dan nilainya tersamar (`***`) di log. Screenshot daftar secret (nilai tak terlihat) dan log yang menampilkan `***`.

**Branch dan Pull Request (alur kerja yang didokumentasikan):**

```bash
git switch -c fitur/backend-ci
# ... ubah/tambah file ...
git add . && git commit -m "ci: tambah workflow backend"
git push -u origin fitur/backend-ci
```

Buka PR ke `main`, tunggu check hijau, lalu merge. Screenshot daftar branch, halaman PR, dan hasil check.

**Branch protection:** Settings → Branches (atau Rulesets) → aturan untuk `main`:

- Require a pull request before merging
- Require status checks to pass (pilih `lint`, `test`, dan job frontend)
- Block force pushes

Screenshot halaman aturan, serta bukti push langsung ke `main` ditolak atau tombol merge terkunci saat check merah.

### Risiko dan mitigasi (CI, deploy, protection)

| Risiko | Mitigasi |
| --- | --- |
| Branch protection tidak bisa diaktifkan | Pada repo private akun gratis, fiturnya bisa terbatas. Jadikan repo public, atau cek ketersediaan di Settings. |
| Nama status check tidak muncul di daftar | Check baru muncul setelah workflow pernah berjalan sekali; jalankan dulu lewat PR. |
| Test CI gagal: `No application encryption key` | Tambahkan langkah `cp .env.example .env && php artisan key:generate`. |
| `pint --test` gagal di CI | Jalankan `vendor/bin/pint` lokal dan commit hasil formatnya. |
| Secret terlihat di log | Jangan `echo` secret; gunakan lewat `env:` seperti contoh. |
| Secret tidak tersedia di PR dari fork | Itu perilaku bawaan GitHub; uji dari branch di repo yang sama. |
| Image terbit dari commit yang test-nya gagal | Job `image` memakai `needs: [lint, test]`. |

### 7.2 Soal 5: Dockerfile frontend multi-stage

`frontend/.dockerignore`:

```
node_modules
dist
.git
.env
```

`frontend/nginx.conf`:

```nginx
server {
  listen 8080;
  root /usr/share/nginx/html;
  index index.html;
  location / {
    try_files $uri $uri/ /index.html;
  }
}
```

`frontend/Dockerfile`:

```dockerfile
FROM node:22-alpine AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
ARG VITE_API_URL
ENV VITE_API_URL=$VITE_API_URL
RUN npm run build

FROM nginxinc/nginx-unprivileged:1.27-alpine AS runtime
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 8080
```

Vite menanamkan `VITE_API_URL` **saat build**, bukan saat container berjalan. Karena itu nilainya dikirim lewat `ARG`.

### 7.3 Soal 6: Docker Compose (bobot terbesar, 25 poin)

`.env` di root (jangan di-commit; sediakan `.env.example`):

```
MYSQL_DATABASE=tugas_db
MYSQL_USER=tugas
MYSQL_PASSWORD=ganti-password
MYSQL_ROOT_PASSWORD=ganti-root-password
APP_KEY=base64:xxxx
VITE_API_URL=http://localhost:8000/api
```

`docker-compose.yml`:

```yaml
services:
  db:
    image: mysql:8.4
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${MYSQL_DATABASE}
      MYSQL_USER: ${MYSQL_USER}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-uroot", "-p${MYSQL_ROOT_PASSWORD}"]
      interval: 10s
      timeout: 5s
      retries: 10
      start_period: 30s

  backend:
    build: ./backend
    restart: unless-stopped
    ports:
      - "8000:8000"
    environment:
      APP_KEY: ${APP_KEY}
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_PORT: 3306
      DB_DATABASE: ${MYSQL_DATABASE}
      DB_USERNAME: ${MYSQL_USER}
      DB_PASSWORD: ${MYSQL_PASSWORD}
    depends_on:
      db:
        condition: service_healthy

  frontend:
    build:
      context: ./frontend
      args:
        VITE_API_URL: ${VITE_API_URL}
    restart: unless-stopped
    ports:
      - "8080:8080"
    depends_on:
      - backend

volumes:
  db_data:
```

Jalankan dan buktikan saling terhubung:

```bash
docker compose up -d --build
docker compose ps                       # semua Up, db (healthy)
docker compose logs backend | tail -20  # migrate berhasil ke host db
docker compose exec backend php artisan db:seed --force
curl http://localhost:8000/api/tugas    # JSON dari MySQL
# Buka http://localhost:8080/tugas di browser: Vue menampilkan data dari backend
```

Bukti persistensi data (nilai tambah laporan):

```bash
docker compose down        # tanpa -v
docker compose up -d
# data tetap ada karena volume db_data
```

Screenshot: `docker compose ps`, log migrasi, browser menampilkan Vue + data, Postman ke `localhost:8000/api/tugas`.

### Risiko dan mitigasi (Compose)

| Risiko | Mitigasi |
| --- | --- |
| Backend crash: `Connection refused` ke DB | Pakai `depends_on` dengan `condition: service_healthy`, `DB_HOST=db` (nama service, bukan localhost), dan `start_period` cukup. |
| Backend jalan sebelum MySQL siap | Healthcheck wajib ada di service `db`. |
| Vue memanggil `http://backend:8000` dan gagal di browser | Browser berjalan di host, jadi `VITE_API_URL=http://localhost:8000/api`. Nama service hanya dikenal antar container. |
| Mengubah `VITE_API_URL` tapi tidak berefek | Variabel ditanam saat build. Jalankan `docker compose build --no-cache frontend`. |
| Error CORS di port 8080 | Pastikan `http://localhost:8080` ada di `allowed_origins`, build ulang backend. |
| Password MySQL berubah tapi tidak berefek | Volume lama menyimpan kredensial lama. `docker compose down -v` (menghapus data) lalu `up` ulang. |
| Port 3306/8000/8080 bentrok | Hentikan layanan lain atau ubah port sisi host. |
| Secret masuk Git | `.env` di `.gitignore`; commit hanya `.env.example`. |
| `${MYSQL_ROOT_PASSWORD}` kosong | Pastikan `.env` ada di folder yang sama dengan `docker-compose.yml`. |

### 7.4 Bonus +10: Laravel di FrankenPHP

Pendekatan: Laravel Octane dengan server FrankenPHP, memakai Dockerfile terpisah supaya Dockerfile utama tetap aman.

```bash
cd backend
composer require laravel/octane
php artisan octane:install --server=frankenphp
```

`backend/Dockerfile.frankenphp` (sketsa; **cek dokumentasi resmi Laravel Octane dan image `dunglas/frankenphp` untuk tag dan sintaks terbaru** sebelum dipakai):

```dockerfile
FROM dunglas/frankenphp:1-php8.3-alpine
RUN install-php-extensions pcntl pdo_mysql zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev
EXPOSE 8000
CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000"]
```

Di Compose, ubah service `backend` sementara untuk bukti:

```yaml
  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile.frankenphp
```

Bukti: `docker compose logs backend` menampilkan Octane/FrankenPHP berjalan, dan `curl -i http://localhost:8000/api/tugas` memberi 200. Kerjakan bonus **terakhir**, setelah semua soal pokok aman, di branch terpisah.

### Risiko dan mitigasi (FrankenPHP)

| Risiko | Mitigasi |
| --- | --- |
| Octane gagal start (ekstensi `pcntl` tidak ada) | Pasang lewat `install-php-extensions`. |
| Composer tidak ada di image FrankenPHP | Salin dari image `composer:2` seperti contoh. |
| Waktu habis mengejar bonus | Bonus hanya +10; prioritaskan soal pokok dan laporan. |
| Merusak setup yang sudah jalan | Kerjakan di branch dan Dockerfile terpisah. |

## 8. Fase 8: Laporan PDF

Laporan wajib; **tanpa laporan, pekerjaan tidak dinilai**. Ikuti format nama file dari soal (Tugas 3 menyebut `P4_NIM_Nama.pdf`; untuk UTS ikuti instruksi lembar soal).

Kerangka (satu laporan menyelesaikan semua CPMK):

1. Halaman judul (nama, NIM, kelas, mata kuliah, tautan repo)
2. Tujuan dan peta CPMK
3. Lingkungan (versi PHP, Node, Docker)
4. Soal 1, Backend Laravel: route, model, migrasi, controller, screenshot Postman (login token, CRUD, JSON), hasil `php artisan test`
5. Soal 2, Frontend Vue: struktur, router, Axios dan `VITE_API_URL`, screenshot halaman data dari API
6. Soal 3, Repo, branch, PR, branch protection, workflow CI (lint dan test), bukti PR hijau dan bukti test gagal lalu diperbaiki
7. Soal 4, Workflow deploy, GitHub Secrets (tersamar), image di GHCR (package Public, tag SHA, run hijau)
8. Soal 5, Dockerfile backend dan frontend multi-stage, `.dockerignore`, tabel ukuran sebelum/sesudah (MB dan %), `whoami`, `docker ps (healthy)`, bukti tiga build (waktu)
9. Soal 6, Compose: file, `docker compose ps`, log migrasi, browser Vue + data, bukti persistensi volume
10. Bonus FrankenPHP (jika dikerjakan)
11. Kendala dan solusi (ambil dari tabel risiko yang benar-benar kamu alami)
12. Kesimpulan

Tips menyusun:

- Beri keterangan pada setiap screenshot: *apa yang dibuktikan* dan *perintah yang dijalankan*.
- Pastikan URL, status 200, dan JSON terlihat di screenshot Postman.
- Pastikan waktu `real` terlihat di screenshot tiga build.
- Tutup (blur) secret dan APP\_KEY di semua screenshot.
- Tulis angka ukuran dan persentase dari hasil nyata kamu.
- Cantumkan tautan repo dan tautan package GHCR di halaman awal.

## 9. Checklist Akhir Sebelum Dikumpulkan

- [ ] Repo `evolusi-pl-NIM` publik atau dosen punya akses; Dockerfile, `.dockerignore`, `docker-compose.yml`, dan workflow ter-commit
- [ ] `.env`, `vendor`, `node_modules` tidak ada di repo
- [ ] Workflow di `main` hijau; PR memperlihatkan deploy skipped
- [ ] Branch protection aktif dan terdokumentasi
- [ ] Image GHCR Public, tag memakai SHA, nama huruf kecil
- [ ] `docker compose up -d --build` dari clone bersih berhasil
- [ ] Semua screenshot lengkap dan terbaca
- [ ] Laporan PDF dinamai sesuai ketentuan dan semua CPMK tercakup

**Uji terakhir yang paling penting:** clone repo ke folder baru, salin `.env.example` menjadi `.env`, isi nilainya, lalu `docker compose up -d --build`. Jika berhasil dari nol, penilai yang menjalankannya juga akan berhasil.
