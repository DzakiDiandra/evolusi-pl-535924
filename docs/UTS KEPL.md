TUGAS 3:  
1\. Di Laravel, sediakan satu endpoint JSON dari tabel CRUD kalian  
   (mis. GET /api/tugas). Pastikan Vue di laptop boleh mengaksesnya.

2\. Buat aplikasi Vue 3 di folder frontend/. Minimal dua halaman  
   ber-router: satu menampilkan data dari Laravel. Alamat API  
   diambil dari VITE\_API\_URL, bukan ditulis langsung di kode.

3\. Tulis workflow empat job untuk frontend, dirangkai dengan  
   needs:. Pakai npm ci dan cache: 'npm'.

4\. Hasil build dioper ke job deploy. Job deploy tidak boleh  
   menjalankan npm run build lagi. Tampilkan isi dist/ ke log.

5\. Deploy hanya jalan dari main. Pull Request tetap menjalankan  
   lint, test, dan build.

6\. Tulis minimal satu unit test Vitest untuk logika aplikasi  
   kalian. Test harus lulus di GitHub Actions tanpa Laravel  
   berjalan.

7\. Buktikan:  
   \- buka Pull Request → deploy skipped;  
   \- gagalkan satu test → pipeline berhenti merah. Lalu perbaiki.

DIKUMPULKAN  
• Laporan PDF P4\_NIM\_Nama.pdf (WAJIB, tanpa laporan tidak dinilai) 

TUGAS 4:  
LANGKAH  
1\. Tulis Dockerfile untuk aplikasi Laravel kalian, di repository  
evolusi-pl-NIM yang sama.

2\. Buat .dockerignore. Minimal kecualikan node\_modules, vendor,  
.git, dan .env.

3\. Urutkan instruksi supaya cache bekerja: composer.json dan  
composer.lock disalin dan dipasang SEBELUM kode aplikasi disalin.

4\. Buktikan cache-nya benar-benar bekerja. Jalankan build. Jalankan  
lagi tanpa mengubah apa pun. Ubah satu huruf di kode, lalu build  
sekali lagi. Build ketiga harus jauh lebih cepat daripada yang  
pertama, dan waktunya harus terlihat di screenshot.

5\. Jalankan container-nya, buka dari browser, tangkap layar hasilnya  
bersama output docker ps.

6\. Pastikan API-nya bisa dipanggil dari luar container. Uji minimal  
satu endpoint dengan Postman — boleh endpoint yang sudah dipakai  
Vue di tugas sebelumnya, boleh yang baru. Screenshot-nya harus  
memperlihatkan URL yang dipanggil, status 200, dan isi JSON-nya.

YANG DIKUMPULKAN  
• Tautan repository — Dockerfile dan .dockerignore sudah ter-commit  
• Satu PDF berisi screenshot waktu ketiga build  
• Screenshot aplikasi berjalan di browser \+ output docker ps  
• Screenshot Postman: request, status, dan JSON balasannya 

TUGAS 5:  
LANGKAH  
1\. Ubah Dockerfile Laravel dari Tugas 4 menjadi multi-stage. Pisahkan  
tahap membangun dan tahap menjalankan. Tahap pertama boleh besar —  
Composer, dev dependencies, apa pun. Tahap kedua hanya berisi yang  
dibutuhkan aplikasi saat melayani.

2\. Buktikan ukurannya turun. Jalankan docker images sebelum dan sesudah  
perubahan, sertakan kedua screenshot-nya, lalu tulis selisihnya  
dalam MB beserta persentasenya.

3\. Kunci versi dan pilih varian kecil. Ganti tag latest dengan versi  
tertentu, dan gunakan varian alpine atau slim kalau aplikasi kalian  
memungkinkan.

4\. Tambahkan USER dan HEALTHCHECK. Container tidak boleh berjalan  
sebagai root. Buktikan dengan docker exec \<nama\> whoami, dan  
tunjukkan status (healthy) pada output docker ps.

5\. Dorong ke GHCR lewat Actions. Buat workflow yang login ke ghcr.io  
dengan GITHUB\_TOKEN, lalu membangun dan mendorong image. Tag memakai  
nomor commit (\${{ github.sha }}), bukan latest saja. Nama image di  
GHCR wajib huruf kecil semua. Setelah image terbit, ubah visibilitas  
package-nya menjadi Public.

YANG DIKUMPULKAN  
• Tautan repository — Dockerfile multi-stage dan workflow sudah ter-commit  
• Tautan halaman package di GitHub yang menunjukkan image kalian  
• Satu PDF berisi: perbandingan docker images sebelum dan sesudah,  
output whoami, output docker ps dengan status (healthy), dan  
screenshot run workflow yang hijau 

UTS:  