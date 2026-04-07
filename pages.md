# PAGES.md — Page Specifications
## Daily Execution Monitoring System

---

## Cara Baca

Setiap halaman berisi:
- **Route** — URL dan Livewire component class
- **Layout** — layout yang dipakai
- **Sections** — urutan section di halaman (ikuti urutan ini)
- **Mobile behavior** — perbedaan khusus di mobile
- **Empty states** — teks empty state yang wajib ada
- **Error states** — pesan error yang mungkin muncul
- **Loading states** — bagian mana yang perlu skeleton/spinner

---

## LOGIN

**Route:** `/login`
**Component:** `App\Livewire\Auth\LoginForm`
**Layout:** `layouts/auth`

### Desktop Layout
Split 2 kolom:
- Kiri (40%): background `primary`, logo putih di tengah, tagline sistem
- Kanan (60%): form login centered

### Mobile Layout
Full screen, card login centered secara vertikal.

### Sections
1. **Logo** — tampil di atas form
2. **Heading** — "Login"
3. **Subtext** — "Masuk untuk melanjutkan ke sistem"
4. **Form:**
   - Email Address (input email, required)
   - Password (input password + toggle show/hide via Alpine)
   - Remember Me (checkbox)
5. **Error alert** — jika credentials salah: "Email atau password salah. Coba lagi."
6. **Button** — "Login" full width, disabled + spinner saat loading

### States
- Loading: button disabled + text "Masuk..." + spinner kecil
- Error: alert merah di atas form, field tidak di-reset

---

## ADMIN — HOME

**Route:** `/admin`
**Component:** `App\Livewire\Admin\HomePage`
**Layout:** `layouts/app` (role: admin)

### Sections (urutan)
1. **Page header**
   - Title: "Home"
   - Subtitle: tanggal hari ini (format: Senin, 7 Juli 2025)
   - Status line: "Ada 3 exception hari ini" (merah) ATAU "Tidak ada exception hari ini" (hijau)
   - Status line: "Ada 2 pending leave" (oranye) ATAU "Tidak ada pending leave" (hijau)

2. **Summary cards** — 2 kolom mobile, 4 kolom desktop
   - Total Exception Hari Ini (danger jika > 0)
   - Pending Leave (warning jika > 0)
   - Notifikasi Gagal (danger jika > 0)
   - User Aktif (neutral)

3. **Shortcut actions** — grid 2x2 mobile, row desktop
   - Tambah User → buka user modal
   - Setujui Leave → link ke leave page
   - Riwayat Notifikasi → link ke notif history
   - Override Entry → link ke override page

4. **Pending Leave** — list 5 terbaru
   - Nama, Divisi, Tipe, Tanggal, badge status
   - "Lihat semua" link ke leave page

5. **Notifikasi Gagal** — list 5 terbaru
   - Waktu kirim, channel, error singkat
   - "Lihat semua" link ke notif history

### Empty States
- Pending leave kosong: "Tidak ada permintaan leave yang pending"
- Notifikasi gagal kosong: "Tidak ada notifikasi gagal"

---

## ADMIN — USERS

**Route:** `/admin/users`
**Component:** `App\Livewire\Admin\UsersPage`
**Layout:** `layouts/app` (role: admin)

### Sections
1. **Page header**
   - Title: "Users"
   - Description: "Kelola akun pengguna sistem"
   - Actions: [Tambah User] [Bulk Upload]

2. **Filter bar**
   - Desktop: Search | Role | Divisi | Status | [Reset]
   - Mobile: Search + [Filter] button → bottom sheet

3. **Main content**
   - Desktop: table
   - Mobile: card list

4. **Pagination**

### Table columns (desktop)
Nama | Email | Role | Divisi | Status | Dibuat | Aksi

### Card (mobile)
- Baris 1: Nama (bold) + Status badge (kanan)
- Baris 2: Email (muted, kecil)
- Baris 3: Role · Divisi (muted, kecil)
- Footer: tombol Edit (kiri) + tombol Arsipkan (kanan, merah)

### Action menu (desktop, kebab menu)
- Lihat Detail
- Edit
- Arsipkan (confirmation)
- Hapus (hanya jika non-aktif/archived, confirmation, danger)

### Empty States
- Hasil pencarian kosong: "Tidak ada user yang cocok dengan filter ini" + [Reset Filter]
- Belum ada user: "Belum ada user. Tambah user pertama." + [Tambah User]

### Loading
- Skeleton: 5 baris skeleton card/table saat pertama load atau filter berubah

---

## ADMIN — DIVISIONS

**Route:** `/admin/divisions`
**Component:** `App\Livewire\Admin\DivisionsPage`
**Layout:** `layouts/app` (role: admin)

### Sections
1. **Page header** — Title: "Divisi" + [Tambah Divisi]
2. **Search bar**
3. **Main content** — table/card list
4. **Pagination**

### Table columns
Nama Divisi | Status | Jumlah User | Dibuat | Aksi

### Action menu
- Edit
- Arsipkan (confirmation)

### Form Modal
- Nama Divisi (required)
- Status Aktif (toggle/checkbox)

### Empty States
- "Belum ada divisi. Tambah divisi pertama." + [Tambah Divisi]

---

## ADMIN — ASSIGNMENT

**Route:** `/admin/assignment`
**Component:** `App\Livewire\Admin\AssignmentsPage`
**Layout:** `layouts/app` (role: admin)

### Sections
1. **Page header** — Title: "Assignment HoD" + [Tambah Assignment]
2. **Description** — "Tentukan HoD yang bertanggung jawab atas setiap divisi"
3. **Main content** — table/card

### Table columns
Nama HoD | Email | Divisi yang Di-assign | Aksi

### Card (mobile)
- Nama + Email
- Divisi: badge list horizontal
- Actions: Edit | Hapus

### Form Modal
- Pilih HoD (searchable select, only HoD role users)
- Pilih Divisi (multi-select via checkbox list dalam modal)
- [Simpan] [Batal]

### UI Notes
- Multi-select divisi: checkbox list dengan search di dalam modal
- Assigned divisions tampil sebagai badge pills (max 3 tampil, sisanya "+N lagi")

### Empty States
- "Belum ada assignment. Assign HoD ke divisi terlebih dahulu."

---

## ADMIN — REPORT SETTINGS

**Route:** `/admin/report-settings`
**Component:** `App\Livewire\Admin\ReportSettingsPage`
**Layout:** `layouts/app` (role: admin)

### Sections
1. **Page header** — Title: "Pengaturan Window Laporan"
2. **Current settings info box**
   - Tampilkan setting aktif saat ini dengan format ringkas
   - Background `primary-light`, border `primary/20`

3. **Form** (max-w-xl)
   - Section "Plan"
     - Jam Buka Plan (time input)
     - Jam Tutup Plan (time input)
   - Section "Realisasi"
     - Jam Buka Realisasi (time input)
     - Jam Tutup Realisasi (time input)
   - Helper text: "Plan hanya dapat diisi dalam rentang waktu ini setiap hari kerja"

4. **Warning box** (jika jam tidak logis)
   - Background warning-bg, "Perhatian: Jam tutup lebih awal dari jam buka. Periksa kembali."

5. **[Simpan Pengaturan]** — full width mobile, auto width desktop

### States
- Loading: button spinner
- Success: toast "Pengaturan berhasil disimpan"

---

## ADMIN — ABSENCE & LEAVE

**Route:** `/admin/absence-leave`
**Component:** `App\Livewire\Admin\LeavePage`
**Layout:** `layouts/app` (role: admin)

### Sections
1. **Page header** — Title: "Cuti & Izin"
2. **Summary chips** — Pending: N | Disetujui: N | Ditolak: N
3. **Filter bar**
   - Desktop: Date range | Status | Divisi | User | Tipe
   - Mobile: search + filter button → bottom sheet
4. **Main content** — table/card
5. **Detail panel** — slide over kanan saat tap row

### Table columns
User | Divisi | Tipe | Tanggal | Alasan (truncated) | Status | Aksi

### Aksi per row
- Setujui (confirmation jika pending)
- Tolak (confirmation, reason required)
- Lihat Detail

### Detail Panel
- Info lengkap request
- Alasan lengkap
- Tombol Setujui / Tolak (jika masih pending)
- Audit trail singkat

### Empty States
- "Tidak ada permintaan pada periode ini"

---

## ADMIN — OVERRIDE

**Route:** `/admin/override`
**Component:** `App\Livewire\Admin\OverridePage`
**Layout:** `layouts/app` (role: admin)

### Sections
1. **Page header** — Title: "Override Entry"
2. **Warning banner** (selalu tampil)
   - Background danger-bg: "Perhatian: Semua perubahan override dicatat dalam log audit dan tidak bisa dihapus."

3. **Filter**
   - User | Divisi | Tanggal | Tipe (Plan/Realisasi)

4. **Entry list** — table/card, pilih entry untuk di-override

5. **Override panel** (muncul setelah pilih entry)
   - Original values (abu, read-only)
   - Edit fields (aktif)
   - Alasan Override (textarea, required)
   - [Simpan Override] — confirmation modal dulu

6. **Audit section** (setelah simpan)
   - Before vs After table
   - Siapa yang override + kapan

### UI Notes
- Visual beda: nilai original background abu, nilai baru background putih dengan border primary
- Confirmation modal copy: "Override ini akan dicatat dalam log. Lanjutkan?"

---

## ADMIN — NOTIFICATION HISTORY

**Route:** `/admin/notification-history`
**Component:** `App\Livewire\Admin\NotificationHistoryPage`
**Layout:** `layouts/app` (role: admin)

### Sections
1. **Page header** — Title: "Riwayat Notifikasi"
2. **Filter** — Date range | Status | Severity | Divisi
3. **Main content** — table/expandable cards
4. **Pagination**

### Table columns
Waktu Kirim | Channel | Status | Ringkasan | [Detail]

### Detail (modal atau expand)
- Payload summary
- Exception yang termasuk
- Error message jika gagal (merah)

### Empty States
- "Tidak ada riwayat notifikasi pada periode ini"

---

## DIRECTOR — DASHBOARD

**Route:** `/director/dashboard`
**Component:** `App\Livewire\Director\DashboardPage`
**Layout:** `layouts/app` (role: director)

### Sections
1. **Page header**
   - Title: "Dashboard"
   - Date selector (default: hari ini)

2. **Summary cards** — 2 kolom mobile, 4 kolom desktop
   - Company Health Score (warna dinamis)
   - Divisi dengan Exception
   - Major Findings Hari Ini
   - Temuan Berulang Belum Selesai

3. **Chart section**
   - Mobile: hanya Exception Trend
   - Desktop: Exception Trend (kiri) + Severity Distribution (kanan)
   - Desktop bawah: Division Health Comparison (bar chart)

4. **Attention list**
   - Divisi yang perlu perhatian (max 5)
   - Per item: nama divisi, badge jumlah finding, health score mini
   - Tap → ke Director Divisions dengan divisi tersebut terpilih

5. **Recent major findings** — list 5 terbaru

6. **Quick access**
   - [Lihat Company] [Lihat Divisi] [Buka AI Chat]

### Health Score Display
```
≥ 70  → text-success, "Baik"
40–69 → text-warning, "Perlu Perhatian"
< 40  → text-danger, "Kritis"
```

### Empty States
- Tidak ada exception: "Tidak ada exception hari ini" + icon centang hijau

---

## DIRECTOR — COMPANY

**Route:** `/director/company`
**Component:** `App\Livewire\Director\CompanyPage`
**Layout:** `layouts/app` (role: director)

### Sections
1. **Page header** — Title: "Company" + date range filter
2. **Summary metrics** — row cards
   - Company Health | Total Exception | Major / Medium / Minor | On-Time Reporting Rate
3. **Charts** — Health trend | Compliance trend | Category distribution
4. **Recent company-wide findings** — list
5. **Division contribution** — table/list: divisi + jumlah finding

---

## DIRECTOR — DIVISIONS

**Route:** `/director/divisions`
**Component:** `App\Livewire\Director\DivisionsPage`
**Layout:** `layouts/app` (role: director)

### Sections
1. **Page header** — Title: "Divisi"
2. **Division selector + date range filter**
3. **Summary cards** per divisi terpilih
4. **Charts** — 2 kolom desktop, 1 mobile
5. **People with findings** — list orang dalam divisi yang punya temuan
6. **Latest major findings**
7. **Stagnant roadmap items**

### Detail Drawer (saat tap finding/person)
- Hierarchy chain: Big Rock → Roadmap → Plan → Realization
- Status tiap level
- Triggered rules (list)
- Timestamps

---

## DIRECTOR — AI CHAT

**Route:** `/director/ai-chat`
**Component:** `App\Livewire\Director\AiChatPage`
**Layout:** `layouts/app` (role: director)

### Mobile Layout
- Title + instruksi singkat di atas
- Suggested prompt chips (horizontal scroll)
- Chat thread (scrollable)
- Input sticky bottom

### Desktop Layout
- Left panel (30%): suggested prompts + history chat
- Right panel (70%): chat thread + input sticky bottom

### Chat Thread
- User message: bubble kanan, background primary-light
- AI response: bubble kiri / full width, gunakan `ai-response-block` component
- Timestamp kecil di bawah tiap message
- Loading: "AI sedang memproses..." + dots animation

### Suggested Prompts
- "Divisi mana paling bermasalah minggu ini?"
- "Siapa yang paling sering terlambat input?"
- "Roadmap mana yang tidak bergerak?"
- "Ringkas exception besar hari ini"

### UI Notes
- Tidak seperti chatbot dekoratif — minimal, utility-first
- Copy button opsional di pojok kanan AI response
- Disclaimer selalu muncul di bawah AI response: "Hasil AI bersifat pendukung. Verifikasi dengan data aktual."

---

## HOD — DASHBOARD

**Route:** `/hod/dashboard`
**Component:** `App\Livewire\Hod\DashboardPage`
**Layout:** `layouts/app` (role: hod)

### Sections
1. **Page header** — Title: "Dashboard" + tanggal hari ini
2. **Summary cards** — 2x2 mobile, 4 kolom desktop
   - Status Plan Hari Ini
   - Status Realisasi Hari Ini
   - Temuan Personal Terbaru
   - Ringkasan Temuan Divisi

3. **CTA Section** (prominent)
   - Jika plan belum diisi: button besar "Isi Plan Sekarang" (primary, full width mobile)
   - Jika plan sudah diisi tapi realisasi belum: "Isi Realisasi"
   - Jika keduanya sudah: "Selesai untuk hari ini" (success state)

4. **Big Rocks aktif** — list 3 terbaru dengan progress

5. **Managers needing attention** — list nama + badge jumlah finding

6. **Quick actions** (secondary)
   - Lihat Division Entries
   - Lihat Division Summary
   - Buka AI Chat

---

## HOD — DAILY ENTRY

**Route:** `/hod/daily-entry`
**Component:** `App\Livewire\Hod\DailyEntryPage`
**Layout:** `layouts/app` (role: hod)

### PENTING: Halaman ini harus paling mudah digunakan

### Sections
1. **Page header** — Title: "Daily Entry" + tanggal hari ini

2. **Window Status Bar** — selalu tampil di atas
   - Plan: "Terbuka: 08:00 – 17:00" (success) ATAU "Sudah Ditutup" (danger)
   - Realisasi: sama

3. **Tab Control** — Plan | Realisasi
   - Jelas, min height 44px, full width

### TAB PLAN

**Jika window tertutup:**
- Info box: "Window plan sudah ditutup. Plan tidak bisa diubah hari ini."
- Tampilkan plan yang sudah ada sebagai read-only

**Jika window terbuka:**

4. **Existing plan items** — list plan yang sudah dibuat hari ini
   - Per item: Big Rock → Roadmap badge | Judul | Status badge
   - Actions: Edit | Hapus (confirmation)

5. **[+ Tambah Plan]** button

6. **Form plan baru** (muncul saat klik tambah/edit)
   - Big Rock (select, required) — helper: "Pilih Big Rock yang relevan"
   - Roadmap Item (select, depends on Big Rock, required) — helper: "Pilih roadmap yang akan dikerjakan"
   - Judul (text, required) — helper: "Deskripsi singkat aktivitas yang akan dilakukan"
   - Deskripsi (textarea) — helper: "Jelaskan lebih detail jika perlu"
   - Alasan (textarea) — helper: "Mengapa aktivitas ini mendukung roadmap yang dipilih?"
   - Jam Rencana (number, optional) — helper: "Estimasi waktu yang dibutuhkan"
   - [Simpan Item] [Batal]

7. **[Submit Semua Plan]** — sticky bottom mobile, di bawah list desktop
   - Hanya aktif jika ada minimal 1 plan item
   - Confirmation: "Plan hari ini akan disubmit. Lanjutkan?"

### TAB REALISASI

**Jika window tertutup:**
- Info box serupa dengan plan

**Jika window terbuka:**

4. **List plan hari ini** — setiap plan bisa diisi realisasi
   - Per item tampil: Judul plan | Big Rock badge | Status realisasi (jika sudah diisi)

5. **Form realisasi per plan** (expand/drawer saat tap)
   - Plan yang dipilih: tampil sebagai read-only info
   - Status (required, radio/segmented):
     - ✅ Selesai
     - 🔄 Sedang Berjalan
     - ❌ Tidak Selesai / Blocked
   - Deskripsi Realisasi (textarea, required)
   - Alasan (textarea, required HANYA jika bukan Selesai)
   - Lampiran (file, optional)
   - [Simpan Realisasi]

### Empty States
- Plan tab kosong: "Belum ada plan hari ini. Tambah plan pertama." + [+ Tambah Plan]
- Realisasi tab: plan ada tapi belum ada realisasi: "Isi realisasi untuk setiap plan"

---

## HOD — HISTORY

**Route:** `/hod/history`
**Component:** `App\Livewire\Hod\HistoryPage`
**Layout:** `layouts/app` (role: hod)

### Sections
1. **Page header** — Title: "Riwayat"
2. **Filter** — Date range | Tipe | Status | Severity | Big Rock
3. **Timeline list** — grouped by date, newest first
4. **Detail drawer**

### Per date group
```
[Senin, 7 Juli 2025]
  Entry 1: Big Rock badge | Roadmap | Judul | Status | Severity badge
  Entry 2: ...
```

### Detail Drawer
- Big Rock info
- Roadmap item
- Plan detail
- Realization detail
- Findings yang triggered (jika ada) — dengan severity tag dan rule description
- Timestamps: submitted at, window range

### Empty States
- "Tidak ada riwayat pada periode ini"

---

## HOD — BIG ROCK

**Route:** `/hod/big-rock`
**Component:** `App\Livewire\Hod\BigRockPage`
**Layout:** `layouts/app` (role: hod)

### Sections
1. **Page header** — Title: "Big Rock" + [Tambah Big Rock] + [Bulk Upload Roadmap]
2. **Filter** — Status | Periode
3. **Big Rock list** — card per item

### Per Big Rock card
- Judul (bold, besar)
- Deskripsi singkat (2 baris, truncate)
- Status badge
- Periode (tanggal mulai – selesai)
- Roadmap count badge: "8 Roadmap Item"
- Actions: [Lihat] [Edit] [Kelola Roadmap] [Arsipkan]

### Big Rock Form Modal
- Judul (required)
- Deskripsi
- Tanggal Mulai (required)
- Tanggal Selesai (required)
- Status

### Roadmap Manager Panel (slide over)
- Header: nama Big Rock
- List roadmap items dengan:
  - Nomor urut
  - Judul
  - Status badge
  - Actions: Edit | Arsipkan
- [+ Tambah Roadmap Item] — form inline
- [Bulk Upload] — modal upload template

### Roadmap Item Form
- Judul (required)
- Deskripsi
- Status (default: Planned)
- Urutan (auto tapi bisa diubah)

### Empty States
- Big Rock kosong: "Belum ada Big Rock. Buat Big Rock pertama untuk mulai merencanakan."
- Roadmap kosong: "Belum ada roadmap item. Tambah atau upload dari template."

---

## HOD — DIVISION ENTRIES

**Route:** `/hod/division-entries`
**Component:** `App\Livewire\Hod\DivisionEntriesPage`
**Layout:** `layouts/app` (role: hod)

### Sections
1. **Page header** — Title: "Division Entries"
2. **Division selector** (jika HoD pegang lebih dari 1 divisi)
3. **Filter** — Tanggal | User | Status | [Toggle: Hanya Tampilkan Temuan]
4. **Main content** — table/accordion cards
5. **Detail drawer**

### Toggle "Hanya Tampilkan Temuan"
- Prominent, di atas list
- Saat aktif: hanya tampilkan entry yang punya finding
- Badge jumlah di toggle: "Hanya Temuan (12)"

### Per entry card (mobile)
- Nama user + tanggal
- Big Rock badge + Roadmap badge
- Status plan + status realisasi
- Findings badge (merah jika major/medium)

### Detail Drawer
- Full chain: Big Rock → Roadmap → Plan → Realization
- Setiap level: judul, deskripsi, status, timestamp
- Triggered rules: list dengan severity tag + penjelasan rule
- Lampiran jika ada

### Empty States
- "Tidak ada entry pada periode ini"
- Saat filter temuan: "Tidak ada temuan pada periode ini" + icon centang hijau

---

## HOD — DIVISION SUMMARY

**Route:** `/hod/division-summary`
**Component:** `App\Livewire\Hod\DivisionSummaryPage`
**Layout:** `layouts/app` (role: hod)

### Sections
1. **Page header** — Title: "Division Summary"
2. **Division selector + date range**
3. **Summary cards** — 2x2 mobile, 4 desktop
   - Division Health Score | Missing Entries | Repeated Findings | Roadmap Tidak Bergerak
4. **Charts** — Exception trend | Compliance trend | Roadmap activity
5. **Attention list**
   - Managers perlu perhatian: nama + finding count
   - Entry generik berulang: user + frekuensi
   - Realisasi terlambat: user + berapa hari

---

## MANAGER — DASHBOARD

**Route:** `/manager/dashboard`
**Component:** `App\Livewire\Manager\DashboardPage`
**Layout:** `layouts/app` (role: manager)

### Sections
1. **Page header** — Title: "Dashboard" + tanggal hari ini

2. **Summary cards** — 2x2 mobile, 4 desktop
   - Status Plan Hari Ini
   - Status Realisasi Hari Ini
   - Big Rock Aktif
   - Temuan Terbaru

3. **CTA utama** — sama seperti HoD Dashboard:
   - "Isi Plan Sekarang" / "Isi Realisasi" / "Selesai hari ini"

4. **Active roadmap items** — list 3 terbaru

5. **Recent history** — timeline list 5 terbaru

### UI Notes
- Lebih simpel dari HoD — tidak ada section divisi
- Satu fokus: isi harian

---

## MANAGER — DAILY ENTRY

**Route:** `/manager/daily-entry`
**Component:** `App\Livewire\Manager\DailyEntryPage`

**Identik 100% dengan HOD — DAILY ENTRY.**
Copy layout, komponen, dan logika UI yang sama persis.

---

## MANAGER — HISTORY

**Route:** `/manager/history`
**Component:** `App\Livewire\Manager\HistoryPage`

**Identik dengan HOD — HISTORY** tapi scope data personal manager saja.

---

## MANAGER — BIG ROCK

**Route:** `/manager/big-rock`
**Component:** `App\Livewire\Manager\BigRockPage`

**Identik dengan HOD — BIG ROCK** untuk saat ini.
Jika nanti berubah menjadi view-only: sembunyikan tombol [Tambah Big Rock], [Edit], dan [Arsipkan]. Tombol [Kelola Roadmap] juga berubah menjadi [Lihat Roadmap].

---

## GLOBAL EMPTY STATES REFERENCE

| Halaman | Kondisi | Teks |
|---|---|---|
| Users | Tidak ada user | "Belum ada user. Tambah user pertama." |
| Users | Filter tidak cocok | "Tidak ada user yang cocok. Coba reset filter." |
| Divisions | Kosong | "Belum ada divisi terdaftar." |
| Assignment | Kosong | "Belum ada assignment HoD." |
| Leave | Tidak ada di periode | "Tidak ada permintaan cuti pada periode ini." |
| Notif History | Kosong | "Tidak ada riwayat notifikasi." |
| Director Dashboard | Tidak ada exception | "Tidak ada exception hari ini." |
| Daily Entry (plan) | Belum ada plan | "Belum ada plan hari ini. Mulai tambahkan." |
| Daily Entry (real) | Plan ada, real kosong | "Isi realisasi untuk setiap plan yang sudah dibuat." |
| History | Kosong | "Tidak ada riwayat pada periode yang dipilih." |
| Big Rock | Kosong | "Belum ada Big Rock aktif. Buat sekarang untuk mulai merencanakan." |
| Roadmap | Kosong | "Belum ada roadmap item. Tambah atau upload dari template." |
| Division Entries | Kosong | "Tidak ada entry pada periode ini." |
| Division Entries | Filter temuan, kosong | "Tidak ada temuan. Semua entry dalam kondisi baik." |

---

## GLOBAL ERROR STATES REFERENCE

| Kondisi | Pesan |
|---|---|
| Gagal load data | "Gagal memuat data. Coba muat ulang halaman." |
| Upload file salah format | "Format file tidak sesuai template. Unduh template yang benar." |
| Tidak punya akses | "Anda tidak memiliki akses ke halaman ini." |
| Plan window tutup | "Window plan sudah ditutup. Plan tidak bisa diubah hari ini." |
| Realisasi window tutup | "Window realisasi sudah ditutup." |
| Form validation | Tampil inline di bawah field masing-masing |
| Auth gagal | "Email atau password salah. Coba lagi." |