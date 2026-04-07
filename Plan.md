## Judul
Sesi 1 – Implementasi Auth, Role, dan Proteksi Halaman (PostgreSQL)

---

## Ringkasan

Di sesi ini, backend Dayta akan:

- terhubung ke PostgreSQL dan menjalankan migrasi dasar,
- menambahkan konsep **role** ke tabel `users`,
- mengatur **redirect setelah login** berdasarkan role,
- dan melindungi semua halaman `/admin`, `/director`, `/hod`, `/manager` dengan middleware `auth` + `role`.

Setelah sesi ini, Anda sudah bisa:

- login dengan user contoh (Admin/Director/HoD/Manager),
- otomatis diarahkan ke halaman masing‑masing,
- tidak bisa membuka halaman role lain tanpa login/role yang tepat.

---

## Perubahan Inti & Detail Implementasi

### 1. Migrasi dasar ke PostgreSQL

**Tujuan:** laravel + Fortify siap pakai dengan pgsql.

**Langkah detail (dijalankan manual oleh Anda):**

- Pastikan `.env` sudah diset ke `DB_CONNECTION=pgsql` dan kredensial benar.
- Jalankan:
  - `php artisan migrate`
- Verifikasi tabel dasar:
  - `users`, `password_resets` (kalau ada), `personal_access_tokens`, `jobs`, `cache`, dll.

*(Tidak ada perubahan kode di langkah ini; hanya menjalankan migrasi bawaan.)*

---

### 2. Tambah kolom `role` di `users` + update model

**Tujuan:** setiap user punya role yang jelas dan dapat digunakan untuk redirect / proteksi halaman.

**Skema & aturan:**

- Kolom baru di tabel `users`:
  - `role` → tipe `string`, panjang cukup (misal 20),
  - default: `'manager'` (supaya user baru otomatis dianggap Manager kalau tidak ditentukan),
  - bisa di‐index sederhana untuk pencarian.
- Nilai yang dipakai:
  - `'admin'`, `'director'`, `'hod'`, `'manager'`.

**Detail implementasi:**

- Buat migration baru (misal `2026_xx_xx_xxxx_add_role_to_users_table.php`):
  - `up()`:
    - `Schema::table('users', function (Blueprint $table) { $table->string('role', 20)->default('manager')->index(); });`
  - `down()`:
    - drop kolom `role`.
- Di `app/Models/User.php`:
  - Tambah `role` ke attribute yang boleh diisi:
    - `#[Fillable(['name', 'email', 'password', 'role'])]`
  - (Tidak perlu mengubah casts untuk saat ini.)

**Catatan bisnis:**

- Untuk MVP, kita belum menyimpan divisi di user (itu masuk Sesi 2), jadi role saja sudah cukup untuk mengarahkan ke dashboard yang tepat.

---

### 3. Seeder pengguna contoh per role

**Tujuan:** memudahkan testing UI tanpa harus manual daftar user.

**Skema user contoh:**

- Admin:
  - name: `Admin User`
  - email: `admin@example.com`
  - role: `admin`
  - password: `password` (bisa diubah nanti).
- Director:
  - name: `Direktur Utama`
  - email: `director@example.com`
  - role: `director`
- HoD:
  - name: `Budi Hartono`
  - email: `hod@example.com`
  - role: `hod`
- Manager:
  - name: `Rudi Santoso`
  - email: `manager@example.com`
  - role: `manager`

**Detail implementasi:**

- Update `database/seeders/DatabaseSeeder.php` atau buat seeder khusus (mis. `RoleUserSeeder`):
  - Gunakan `User::updateOrCreate([...email...], [... name, role, password => bcrypt('password')])` untuk idempotent.
- Jalankan:
  - `php artisan db:seed` (atau dengan seeder kelas spesifik).

**Manfaat:**

- Anda bisa langsung login sebagai tiap role dan lihat sisi kiri (sidebar) berubah sesuai peran.

---

### 4. Redirect setelah login berdasarkan role

**Tujuan:** setelah login, user langsung masuk ke “rumahnya” (admin home, director dashboard, dst).

**Strategi (sesuai setup Fortify):**

- Di `config/fortify.php` nilai `'home'` saat ini `/dashboard`.  
- Kita buat route `/dashboard` yang **hanya bertugas redirect** ke halaman sesuai role.

**Detail implementasi:**

- Tambah route di `routes/web.php`:

  - `Route::get('/dashboard', function () {`
    - pastikan `auth()` sudah berisi user (route dilindungi `auth`),
    - baca `$role = auth()->user()->role;`,
    - gunakan `match` / `switch`:
      - `'admin'` → `return redirect()->route('admin.home');`
      - `'director'` → `redirect()->route('director.dashboard');`
      - `'hod'` → `redirect()->route('hod.dashboard');`
      - `'manager'` → `redirect()->route('manager.dashboard');`
      - default/fallback (role tak dikenal) → bisa redirect ke login atau 403.
  - Route ini dibungkus `->middleware('auth');`.

**Manfaat:**

- Tidak perlu mengutak‑atik internal Fortify; cukup satu route `/dashboard` sebagai “hub” redirect.

---

### 5. Middleware Role untuk proteksi halaman

**Tujuan:** pastikan hanya role yang tepat yang dapat mengakses grup route `/admin`, `/director`, `/hod`, `/manager`.

**Desain middleware:**

- Nama: misalnya `EnsureRole` atau `RoleMiddleware`.
- Bisa dipakai seperti:
  - `->middleware(['auth', 'role:admin'])`
  - `->middleware(['auth', 'role:director'])`
  - dsb.
- Mendukung parameter jamak misalnya `role:admin,director` (untuk hal umum nanti, kalau dibutuhkan).

**Detail implementasi:**

- Buat file baru `app/Http/Middleware/EnsureRole.php`:
  - `handle($request, Closure $next, ...$roles)`
  - Jika user belum login → redirect ke login (atau serahkan ke middleware `auth` yang sudah jalan).
  - Ambil `$userRole = $request->user()->role`.
  - Jika `$userRole` ada di `$roles` → lanjut `return $next($request);`
  - Kalau tidak → bisa:
    - abort 403, atau
    - redirect ke `/dashboard` dengan pesan “Tidak punya akses”.
- Daftarkan middleware di `app/Http/Kernel.php` pada `$routeMiddleware`:
  - `'role' => \App\Http\Middleware\EnsureRole::class`.

**Catatan bisnis:**

- Untuk MVP, kita bisa pakai 403 generik dulu; nanti bisa diperhalus dengan halaman error khusus yang bahasanya lebih “human”.

---

### 6. Lindungi route /admin, /director, /hod, /manager

**Tujuan:** preview UI sekarang jadi halaman “beneran” yang hanya bisa diakses setelah login dan dengan role tepat.

**Kondisi route sekarang (ringkasan):**

- `routes/web.php` punya grup:
  - `/admin` → `Route::prefix('admin')->name('admin.')->group(...)`
  - `/director` → `Route::prefix('director')->name('director.')->group(...)`
  - `/hod` → `Route::prefix('hod')->name('hod.')->group(...)`
  - `/manager` → `Route::prefix('manager')->name('manager.')->group(...)`
- Masing‑masing masih `Route::view(...)` tanpa middleware.

**Detail perubahan:**

- Bungkus setiap grup dengan middleware:

  ```php
  Route::prefix('admin')
      ->name('admin.')
      ->middleware(['auth', 'role:admin'])
      ->group(function () {
          // Route::view('/', 'livewire.admin.home-page')->name('home');
          // dst...
      });
  ```

- Direktor:

  ```php
  Route::prefix('director')
      ->name('director.')
      ->middleware(['auth', 'role:director'])
      ->group(...);
  ```

- HoD:

  ```php
  ->middleware(['auth', 'role:hod'])
  ```

- Manager:

  ```php
  ->middleware(['auth', 'role:manager'])
  ```

- Route lain yang sifatnya general (mis. settings profile) tetap mengikuti middleware yang sudah ada di `routes/settings.php`.

**Untuk halaman landing `/`:**

- Untuk sekarang:
  - bisa dibiarkan sebagai role switcher preview (public) jika Anda masih perlu,
  - atau diubah ke redirect `/login`.  
- Ini keputusan produk; default aman: biarkan dulu sebagai preview, tapi semua route “nyata” sudah terlindungi.

---

## Rencana Uji (Acceptance Criteria Sesi 1)

Setelah implementasi Sesi 1, hal‑hal berikut harus bisa diuji dan lulus:

1. **Login & redirect per role**
   - Login sebagai `admin@example.com` (password `password`) → diarahkan otomatis ke `/admin` (home Admin).
   - Login sebagai `director@example.com` → ke `/director/dashboard`.
   - Login sebagai `hod@example.com` → ke `/hod/dashboard`.
   - Login sebagai `manager@example.com` → ke `/manager/dashboard`.

2. **Proteksi halaman per role**
   - Tanpa login:
     - akses `/admin`, `/director/dashboard`, `/hod/dashboard`, `/manager/dashboard` → diarahkan ke login.
   - Login sebagai Manager:
     - bisa akses `/manager/*`.
     - mencoba `/admin/*` atau `/director/*` → mendapat 403 atau redirect ke `/dashboard` (sesuai desain middleware).
   - Login sebagai Director:
     - bisa akses `/director/*`,
     - tidak bisa akses `/admin/*` atau `/hod/*` (kecuali nanti kita tambahkan khusus).
   - Login sebagai Admin:
     - bisa akses `/admin/*`,
     - route lain tetap dicek; kalau menurut kebijakan Anda Admin boleh lihat semua, nanti kita atur di sesi berikutnya.

3. **Remember me**
   - Saat login dengan mencentang “ingat saya”, session tetap bertahan sesuai pengaturan Laravel (bisa diuji dengan menutup browser dan buka lagi).

4. **Data users di DB**
   - Tabel `users` punya kolom `role`.
   - Seeder membuat 4 user contoh dengan role berbeda.
   - Perubahan role manual (misalnya via `tinker` atau nanti UI Admin) langsung mengubah jalur redirect setelah login.

---

## Asumsi

- DB sudah PostgreSQL dan migrasi bawaan Laravel sudah sukses (`php artisan migrate`).
- Belum perlu peran “superadmin” terpisah; `admin` adalah role dengan akses paling luas.
- Hak akses lintas peran (mis. Admin boleh melihat semua route) bisa ditangani di sesi berikutnya jika dibutuhkan; untuk Sesi 1 kita terapkan aturan ketat: route hanya untuk role yang spesifik.

