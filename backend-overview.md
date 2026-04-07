# Backend Overview – Daily Execution Monitoring System (Dayta)

Dokumen ini menjelaskan **rencana backend Dayta** dalam bahasa awam dan sudut pandang bisnis. Tujuannya supaya semua orang (bukan hanya tim teknis) paham:

- data apa saja yang akan disimpan,
- alur kerja (workflow) seperti apa yang didukung,
- aturan bisnis utama yang perlu dijaga,
- dan tahapan apa saja untuk membuat sistem ini siap dipakai sungguhan (production).

---

## 1. Tujuan Backend Secara Bisnis

Secara sederhana, backend adalah “otak dan lemari arsip” sistem Dayta.  
Backend yang baik harus bisa:

- Menyimpan data **rencana dan realisasi harian** dengan aman dan rapi.
- Menjaga **hubungan antara level organisasi**: Manager → HoD → Director.
- Menyimpan dan menghitung **temuan (exception)** sehingga pola masalah bisa terlihat.
- Memberikan angka dan data yang **konsisten** ke dashboard (health score, grafik, ringkasan).
- Mengatur **hak akses** sehingga setiap peran hanya melihat dan mengubah hal yang memang menjadi kewenangannya.
- Menyediakan dasar untuk **notifikasi otomatis** (reminder, alert, dsb.).

Dengan kata lain: backend membuat semua tampilan yang sudah bagus saat ini **benar‑benar hidup dan bisa dipercaya**.

---

## 2. Peran & Data yang Dibutuhkan

### 2.1 Admin

Hal yang perlu disiapkan di backend untuk Admin:

- **Data pengguna (users)**
  - nama, email, role (Admin/Director/HoD/Manager), divisi, status (aktif/nonaktif/arsip).
  - tanggal dibuat, siapa yang membuat, dan jejak perubahan penting.
- **Data divisi**
  - nama divisi, status aktif/nonaktif,
  - jumlah user di setiap divisi (bisa dihitung dari data user).
- **Data assignment HoD**
  - informasi siapa HoD dan memegang divisi mana saja.
- **Data cuti & izin (leave)**
  - siapa yang mengajukan, divisi apa, tanggal/tanggal rentang, alasan, status (pending/approved/rejected).
- **Data override**
  - entry mana yang di‑override, apa perubahannya, siapa yang melakukan, kapan, dan alasan override.
- **Riwayat notifikasi**
  - notifikasi apa yang dikirim, ke siapa, lewat channel apa (email/WA/dll), status (sukses/gagal), dan pesan error bila ada.

Dengan data di atas, Admin bisa menjalankan tugas sehari‑hari tanpa perlu “catatan tambahan” di luar sistem.

---

### 2.2 Director

Backend perlu menyiapkan:

- **Data agregat perusahaan**
  - total entry harian,
  - jumlah entry yang terlambat/missing,
  - jumlah temuan per tingkat keparahan (major/medium/minor),
  - skor kesehatan perusahaan (health score) per hari/periode.
- **Data per divisi**
  - health score per divisi,
  - jumlah temuan per divisi,
  - tren (trend) kepatuhan dan exception per divisi.
- **Riwayat temuan besar (major findings)**
  - apa temuan yang terjadi,
  - siapa yang terlibat,
  - divisi apa, dan kapan.

Data ini dipakai untuk:

- mengisi **Dashboard Director**,  
- menampilkan **Company Page**,  
- dan menjadi bahan untuk **AI Chat Director** ke depan.

---

### 2.3 HoD (Head of Division)

Untuk HoD, backend butuh:

- **Data entry harian Manager di divisinya**
  - rencana (plan) dan realisasi (execution) per hari,
  - status (submitted/finished/late/missing/dll),
  - apakah entry tersebut memiliki temuan (finding) atau tidak.
- **Data Big Rock & Roadmap per individu (HoD & Manager)**
  - sasaran besar (Big Rock) tiap orang,
  - langkah‑langkah (Roadmap) yang terhubung dengan Big Rock tersebut,
  - status dan progres setiap langkah.
- **Data ringkasan divisi**
  - skor kesehatan divisi per periode,
  - jumlah entry terlambat/missing,
  - temuan yang berulang,
  - roadmap yang tidak bergerak.

Data ini yang menghidupi halaman:

- Dashboard HoD,  
- Daily Entry (pribadi HoD),  
- Division Entries,  
- Division Summary,  
- serta AI Chat khusus divisi.

---

### 2.4 Manager

Untuk Manager, backend perlu:

- **Data entry harian pribadi**
  - rencana: apa yang akan dilakukan hari ini, berhubungan dengan Big Rock/roadmap apa, berapa jam yang direncanakan, dsb.
  - realisasi: apa yang benar‑benar dilakukan, status (selesai/tidak selesai/blocked), dan alasan jika tidak sesuai rencana.
  - informasi apakah ada temuan/exception yang muncul dari entry tersebut.
- **Data Big Rock & Roadmap pribadi**
  - sasaran besar yang menjadi konteks pekerjaannya,
  - langkah‑langkah (roadmap) yang sedang ia kerjakan,
  - hubungan entry harian dengan Big Rock/roadmap ini.
- **Riwayat pribadi**
  - yang mengisi halaman History Manager.

Ini memastikan Manager bisa menjadikan sistem sebagai “buku kerja harian” yang terstruktur, bukan sekadar formalitas laporan.

---

## 3. Alur Kerja Utama (Workflow) yang Harus Didukung

Di bawah ini adalah alur besar yang perlu jelas agar backend bisa disusun dengan benar.

### 3.1 Alur Harian Manager & HoD

1. **Sebelum bekerja (pagi/awal shift)**  
   - Manager dan HoD mengisi **rencana harian (plan)**:
     - pilih Big Rock dan Roadmap terkait,
     - tulis kegiatan yang akan dilakukan,
     - tentukan estimasi waktu atau prioritas.
2. **Saat atau setelah bekerja (siang/sore)**  
   - Manager dan HoD mengisi **realisasi**:
     - apakah kegiatan selesai, sedang berjalan, atau tidak selesai,
     - bila tidak selesai atau bermasalah, mereka menulis alasan,
     - bila ada temuan, ditandai dengan severity yang sesuai (minor/medium/major).
3. **Sistem mencatat otomatis**:
   - apakah entry terlambat diisi,
   - apakah entry sama sekali tidak diisi (missing),
   - hubungan entry dengan Big Rock/roadmap.

Hasil alur ini mengalir ke dashboard HoD dan Director.

---

### 3.2 Alur Pengelolaan Big Rock & Roadmap

1. Setiap **individu (HoD dan Manager)** punya daftar Big Rock pribadi.
2. Setiap Big Rock punya daftar **roadmap item** (langkah besar) dengan status:
   - planned, in progress, finished, blocked, archived, dll.
3. Entry harian bisa **ditautkan** ke salah satu roadmap item untuk menunjukkan:
   - hari ini Manager atau HoD mengerjakan bagian mana dari perjalanan menuju Big Rock tersebut.
4. Backend perlu menyimpan:
   - siapa pemilik Big Rock,
   - kapan dibuat, kapan selesai, dan histori perubahannya.

Dengan alur ini, perusahaan bisa melihat apakah aktivitas harian benar‑benar mendorong sasaran besar, bukan hanya “sibuk”.

---

### 3.3 Alur Cuti & Izin

1. Karyawan (di luar scope UI sekarang) mengajukan cuti/izin.  
2. Permintaan ini masuk ke backend dengan status **pending**.
3. Admin atau atasan yang ditunjuk:
   - melihat daftar permintaan,
   - menyetujui atau menolak,
   - menulis alasan bila ditolak.
4. Status cuti/izin ini memengaruhi:
   - pembacaan entry harian (misalnya: hari cuti tidak dianggap missing),
   - laporan ke Director/HoD.

Backend perlu mengatur agar hari cuti tidak mengganggu penilaian kepatuhan secara tidak adil.

---

### 3.4 Alur Override

Override adalah “jalur khusus” ketika ada:

- kesalahan input,
- kondisi teknis (misalnya tidak bisa login di hari itu),
- atau alasan bisnis lain yang disetujui.

Alurnya:

1. Admin (atau peran khusus) memilih entry yang ingin di‑override.
2. Admin mengubah data tertentu (misal status, jam, atau teks).
3. Sistem menyimpan:
   - nilai lama dan nilai baru,
   - siapa yang mengubah,
   - kapan diubah,
   - alasan override.

Ini penting agar pengambilan keputusan di level Director tetap punya **jejak audit** yang jelas.

---

## 4. Aturan Bisnis Utama yang Perlu Disepakati

Sebelum backend dibangun, beberapa aturan bisnis perlu disepakati dulu:

1. **Batas waktu pengisian plan dan realisasi**
   - Plan boleh diisi mulai jam berapa dan berakhir jam berapa?
   - Realisasi boleh diisi sampai jam berapa?
   - Apa yang terjadi kalau lewat waktu? (masih boleh isi dengan status “late” atau benar‑benar dikunci?)
2. **Definisi “temuan/exception”**
   - Kapan sebuah entry dianggap temuan?
   - Apakah berdasarkan aturan otomatis (misal terlambat > 3x seminggu) atau input manual HoD?
3. **Klasifikasi severity (minor/medium/major)**
   - Kriteria sederhananya apa?
   - Boleh diubah manual oleh HoD/Director atau harus dari aturan otomatis?
4. **Perhitungan health score**
   - Komponen apa saja yang memengaruhi (misal: on‑time rate, jumlah temuan, jumlah missing)?
   - Apakah bobotnya sama atau berbeda?
5. **Hak akses per peran**
   - Data apa yang boleh dilihat/diedit oleh Admin, Director, HoD, Manager?
   - Apakah Director boleh mengedit data, atau hanya melihat?
6. **Retensi data**
   - Data historis disimpan berapa lama?
   - Apakah ada kebutuhan untuk arsip jangka panjang?

Backend akan mengikuti keputusan di atas; kalau aturan berubah di tengah jalan, backend juga perlu ikut disesuaikan.

---

## 5. Integrasi & Notifikasi

Supaya sistem benar‑benar membantu perilaku disiplin, backend perlu memikirkan:

### 5.1 Notifikasi

- **Pengingat harian** untuk mengisi plan dan realisasi.
- **Pemberitahuan** bila ada temuan major/medium tertentu.
- **Ringkasan berkala** (misalnya mingguan) ke HoD/Director.

Channel yang bisa dipertimbangkan:

- Email,
- WhatsApp / chat internal,
- notifikasi di aplikasi internal perusahaan (jika ada).

### 5.2 Integrasi Lain (opsional / jangka menengah)

- Integrasi ke HRIS (untuk sinkron data karyawan dan cuti).
- Integrasi ke sistem ticketing / incident (untuk temuan tertentu).

Semua ini butuh perencanaan tambahan (hak akses, keamanan, privacy), tapi penting untuk gambaran jangka panjang.

---

## 6. Tahapan Implementasi Backend (Roadmap Teknis Tingkat Tinggi)

Disusun dalam bahasa sederhana supaya bisa dijadikan pegangan bersama.

1. **Fondasi data & akses**
   - Menyusun struktur database untuk user, divisi, Big Rock, Roadmap, entry harian, temuan, cuti, notifikasi, dan audit log.
   - Menyiapkan autentikasi dan role (Admin, Director, HoD, Manager) dengan akses dasar.
2. **Fitur harian Manager & HoD**
   - Implementasi pengisian plan dan realisasi harian.
   - Pengaitan dengan Big Rock dan Roadmap.
   - Penandaan temuan dan pelacakan terlambat/missing.
3. **Dashboard HoD & Director**
   - Menyusun query untuk ringkasan dan grafik.
   - Menghitung health score dan statistik lain yang muncul di UI.
4. **Cuti & Izin + Pengaruh ke laporan**
   - Implementasi alur pengajuan dan persetujuan cuti/izin.
   - Penyesuaian logika perhitungan kepatuhan agar hari cuti diperlakukan dengan benar.
5. **Override & Audit Trail**
   - Implementasi mekanisme override yang aman dan transparan.
   - Menyimpan jejak perubahan untuk keperluan audit.
6. **Notifikasi & (opsional) AI Chat**
   - Menentukan skenario notifikasi dan menghubungkannya dengan channel yang dipilih.
   - Menyediakan data terstruktur yang nantinya bisa dimanfaatkan AI Chat untuk menjawab pertanyaan Director/HoD.

Tahapan ini bisa dijalankan bertahap, misalnya mulai dari fitur yang paling dekat dengan pengguna (entry harian Manager & HoD), lalu naik ke ringkasan dan dashboard, dan seterusnya.

---

## 7. Ringkasan Singkat

- Backend Dayta akan menyimpan dan mengatur **seluruh data eksekusi harian**, dari level individu sampai level perusahaan.
- Setiap peran (Admin, Director, HoD, Manager) punya **kebutuhan data dan alur kerja** yang berbeda; backend harus mengakomodasi semuanya dengan aturan hak akses yang jelas.
- Sebelum mulai coding, penting untuk **menyepakati aturan bisnis inti** (batas waktu, definisi temuan, perhitungan health score, hak akses).
- Implementasi bisa dijalankan bertahap, dimulai dari fondasi data dan alur kerja harian, lalu berkembang ke dashboard, cuti, override, notifikasi, dan integrasi lain.

Dokumen ini bisa menjadi “peta besar” saat mendiskusikan backend dengan tim teknis maupun dengan stakeholder non‑teknis, sehingga semua orang punya bayangan yang sama tentang apa yang akan dibangun dan bagaimana sistem ini akan bekerja mendukung bisnis sehari‑hari.

