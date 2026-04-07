# Summary – Daily Execution Monitoring System

Dokumen ini merangkum sistem **Daily Execution Monitoring System (Dayta)** dalam bahasa awam dan sudut pandang bisnis, berdasarkan desain, struktur halaman, dan tampilan yang sudah dibuat di proyek ini.

---

## 1. Tentang Apa Website Ini & Peran Masing-Masing

### 1.1 Gambaran Umum Sistem

Secara sederhana, Dayta adalah **sistem untuk memantau pelaksanaan kerja harian** di perusahaan.  
Tujuan utamanya:

- Memastikan **rencana kerja (plan)** dan **realisasi harian (execution)** tercatat dengan rapi.
- Mengurangi **masalah berulang** seperti:
  - laporan telat,
  - laporan tidak diisi,
  - alasan yang tidak jelas,
  - dan temuan / exception yang dibiarkan berulang.
- Memberikan **gambaran menyeluruh** kepada manajemen: dari level Manager, Head of Division (HoD), sampai Director.
- Menyatukan informasi besar seperti **Big Rock / Sasaran Besar**, **Roadmap**, dan **kegiatan harian** dalam satu alur yang terhubung.

Sistem ini sudah disusun untuk dipakai oleh beberapa peran utama:

- **Admin**
- **Director**
- **Head of Division (HoD)**
- **Manager**

Keempat peran ini saling terkait, tetapi fokus dan tampilan halamannya berbeda, menyesuaikan kebutuhan masing‑masing.

---

### 1.2 Peran: Admin

**Tujuan utama Admin**: menjadi “petugas administrasi sistem” yang memastikan semua data dasar dan pengaturan berjalan rapi.

Tanggung jawab utama Admin:

- **Mengelola pengguna (Users)**
  - Membuat akun baru.
  - Mengatur role (Admin, Director, HoD, Manager).
  - Menentukan divisi dan status aktif/nonaktif.
  - Mengarsipkan atau menghapus akun yang sudah tidak dipakai.
- **Mengelola divisi (Divisions)**
  - Membuat daftar divisi di perusahaan.
  - Mengatur status aktif divisi.
  - Melihat jumlah user per divisi.
- **Mengatur penugasan HoD (Assignment)**
  - Menentukan HoD memegang divisi apa saja.
  - Memastikan tidak ada divisi yang “tidak punya orang bertanggung jawab”.
- **Mengelola cuti & izin (Leave)**
  - Melihat, menyetujui, atau menolak permintaan cuti/izin.
  - Memastikan cuti tercatat sehingga laporan harian bisa dibaca dengan konteks yang benar.
- **Mengelola override entry**
  - Saat ada kondisi khusus (misal: lupa isi, salah input, atau case tertentu), Admin bisa membantu melakukan “override” dengan aturan tertentu.
- **Memantau notifikasi**
  - Melihat riwayat notifikasi (sukses/gagal).
  - Mengetahui apakah pengingat lewat WhatsApp, email, atau channel lain berhasil atau tidak.
- **Mengelola pengaturan laporan**
  - Mengatur konfigurasi terkait laporan (misal jadwal, format, atau hal lain yang akan diatur di backend nantinya).

Secara bisnis, Admin adalah pihak yang **menjaga kebersihan dan kerapihan data** serta memastikan sistem siap digunakan oleh semua peran lain.

---

### 1.3 Peran: Director

**Tujuan utama Director**: melihat gambaran besar kondisi perusahaan secara cepat dan jelas, tanpa harus membuka terlalu banyak detail teknis.

Halaman utama Director:

- **Dashboard Director**
  - Menampilkan ringkasan kondisi hari ini:
    - apakah banyak exception,
    - bagaimana status rencana dan realisasi,
    - siapa saja yang perlu perhatian.
  - Fokus pada “lampu indikator” yang menunjukkan kesehatan pelaksanaan di seluruh perusahaan.
- **Company Page**
  - Menampilkan **Company Health Score** (skor kesehatan perusahaan).
  - Menampilkan total exception dan pecahan berdasarkan tingkat keparahan (major, medium, minor).
  - Menampilkan **trend** (grafik) health & compliance (patuh/tidak patuh) per periode.
  - Menampilkan **temuan terbaru** di seluruh perusahaan (siapa, divisi apa, kasus apa).
  - Menampilkan **kontribusi per divisi** terhadap jumlah temuan (divisi mana yang paling banyak bermasalah).
- **Divisions Page**
  - Menampilkan ringkasan per divisi: kesehatan, exception, pola masalah, dan siapa HoD/manager yang terkait.
- **AI Chat Page**
  - Rencananya menjadi tempat Director bisa bertanya dalam bahasa natural (misal: “Divisi mana yang paling banyak bermasalah minggu ini?”) dan mendapatkan ringkasan otomatis.

Secara bisnis, Director mendapatkan **dashboard strategis**: cukup buka 1–2 halaman untuk mengetahui apakah pelaksanaan di lapangan berjalan sehat atau tidak, dan divisi mana yang perlu diintervensi.

---

### 1.4 Peran: Head of Division (HoD)

**Tujuan utama HoD**: menjadi “pemilik kesehatan divisi” yang menjembatani antara target perusahaan dan aktivitas manager di bawahnya.

Tanggung jawab HoD di dalam sistem:

- **Dashboard HoD**
  - Melihat ringkasan kondisi divisi hari ini:
    - status plan dan realisasi manager,
    - jumlah temuan,
    - hal-hal yang perlu segera di-follow up.
- **Daily Entry (Pribadi HoD)**
  - Mengisi entry harian untuk dirinya sendiri sebagai HoD.
  - Melihat dan memantau entry harian dari para manager di divisinya.
  - Memastikan rencana dan realisasi (HoD maupun manager) sejalan dengan Big Rock dan Roadmap pribadi masing‑masing.
- **History**
  - Melihat riwayat aktivitas dan temuan dalam periode tertentu.
  - Menyaring berdasarkan tanggal, status, dan kondisi lain.
- **Big Rock & Roadmap**
  - Menentukan sasaran besar (Big Rock) pribadinya sebagai HoD (per individu), yang tetap selaras dengan sasaran divisi.
  - Mengatur roadmap (langkah-langkah besar) yang harus ditempuh untuk mencapai sasaran tersebut.
  - Mengatur status dan progres setiap item.
- **Division Entries**
  - Melihat daftar lengkap entry harian di divisi: siapa mengisi apa, kapan, statusnya bagaimana.
  - Bisa fokus hanya pada entry yang punya temuan (findings).
- **Division Summary**
  - Melihat ringkasan tingkat kesehatan divisi dalam periode tertentu:
    - skor kesehatan divisi,
    - jumlah entry yang terlambat atau hilang,
    - temuan yang berulang,
    - roadmap yang tidak bergerak.
  - Menampilkan grafik: trend exception, trend kepatuhan, aktivitas roadmap.
- **AI Chat (Divisi)**
  - Mirip dengan Director, tetapi fokus hanya di data divisi yang dipegang HoD.

Secara bisnis, HoD adalah **pemimpin operasional divisi**: memantau apakah para manager menjalankan rencana, apakah masalah berulang, dan apakah roadmap benar‑benar bergerak, bukan hanya di atas kertas.

---

### 1.5 Peran: Manager

**Tujuan utama Manager**: mencatat rencana dan realisasi harian secara disiplin, sekaligus melihat dampak dan riwayat aktivitasnya sendiri.

Tanggung jawab Manager:

- **Dashboard Manager**
  - Melihat status plan dan realisasi hari ini.
  - Melihat apakah ada temuan terbaru yang perlu perhatian.
  - Melihat Big Rock dan roadmap yang sedang aktif untuk dirinya.
- **Daily Entry (Pribadi)**
  - Mengisi rencana harian (plan) sesuai target.
  - Mengisi realisasi (apa yang benar‑benar dilakukan) dan mencatat jika ada masalah/exception.
  - Menandai temuan, keterlambatan, atau kendala lain.
- **History**
  - Melihat riwayat aktivitas harian miliknya sendiri (bukan milik orang lain).
  - Bisa meninjau kembali apa yang sudah dilakukan dan apa yang menjadi temuan.
- **Big Rock**
  - Untuk versi awal, mirip dengan HoD Big Rock (bisa mengelola Big Rock sendiri).
  - Ke depan bisa diubah menjadi hanya “view only” jika kebijakan perusahaan menempatkan penentuan Big Rock di level HoD saja.

Secara bisnis, Manager adalah **pelaksana harian** yang menjadi sumber data utama. Tanpa input disiplin dari Manager, gambaran di level HoD dan Director akan kabur.

---

## 2. Acceptance Criteria per Peran (Bahasa Bisnis)

Bagian ini merangkum “ceklist keberhasilan” dari sudut pandang bisnis.  
Artinya: **kapan kita bisa bilang fitur untuk suatu peran sudah “cukup” untuk dipakai?**

### 2.1 Admin

Seorang **Admin** dianggap berhasil bila:

- Bisa **membuat user baru** dengan informasi:
  - nama,
  - email,
  - role,
  - divisi,
  - status aktif/nonaktif.
- Bisa **menemukan user** dengan cepat melalui:
  - pencarian nama/email,
  - filter role, divisi, dan status.
- Bisa **mengubah data user**:
  - mengganti role (misal dari Manager jadi HoD),
  - memindahkan divisi,
  - mengaktifkan atau menonaktifkan akun.
- Bisa **mengarsipkan atau menghapus user** dengan aman, dengan:
  - konfirmasi sebelum tindakan,
  - penjelasan singkat konsekuensi (misalnya data historis tetap disimpan).
- Bisa **mengelola divisi**:
  - menambah divisi baru,
  - melihat jumlah user di setiap divisi,
  - mengarsipkan divisi yang sudah tidak aktif.
- Bisa **mengatur penugasan HoD**:
  - menentukan HoD untuk masing-masing divisi,
  - memastikan tidak ada divisi yang “yatim”.
- Bisa **melihat dan memproses permintaan cuti/izin**:
  - melihat daftar permintaan,
  - menyetujui atau menolak,
  - memastikan status cuti tercermin di tampilan lain yang butuh konteks ini.
- Bisa **melihat riwayat notifikasi**:
  - tahu notifikasi mana yang gagal,
  - punya dasar untuk follow up (misal: salah nomor WA, email bounce, dll.).
- Semua tindakan penting (hapus, arsip, override) **selalu ada konfirmasi** dan tidak dilakukan “tanpa sengaja”.

Jika semua poin di atas dapat dilakukan dengan lancar, peran Admin secara fungsional bisa dianggap memenuhi standar minimum.

---

### 2.2 Director

Seorang **Director** dianggap berhasil menggunakan sistem bila:

- Dalam **satu tampilan dashboard**, Director sudah bisa menjawab pertanyaan:
  - “Apakah pelaksanaan di perusahaan hari ini dalam kondisi sehat?”
  - “Ada masalah besar apa yang perlu saya tahu hari ini?”
- Di **Company Page**, Director:
  - melihat skor kesehatan perusahaan yang jelas (misal 58/100) dengan warna yang tegas,
  - melihat jumlah exception per periode,
  - mengetahui berapa banyak kasus major, medium, dan minor.
- Director bisa **melihat daftar temuan terbaru**:
  - siapa yang bermasalah,
  - dari divisi mana,
  - kapan kejadiannya,
  - dan tingkat keparahan masalahnya.
- Director bisa **melihat kontribusi tiap divisi terhadap masalah**:
  - misal: divisi Operasional menyumbang 50% dari total temuan,
  - sehingga tahu divisi mana yang perlu diskusi khusus.
- Director bisa **melihat tren** (trend health & compliance) dalam periode tertentu:
  - apakah situasi membaik atau memburuk dari waktu ke waktu,
  - bukan hanya snapshot satu hari.
- Melalui **halaman Divisions**, Director bisa:
  - membandingkan kesehatan antar divisi,
  - mengidentifikasi divisi yang konsisten bermasalah.
- Melalui **AI Chat**, Director (ke depannya) bisa:
  - mengetik pertanyaan sederhana dalam bahasa Indonesia,
  - dan mendapat ringkasan yang mudah dicerna tanpa harus membuka banyak halaman.

Jika Director membutuhkan **kurang dari 5 menit** untuk memahami “kondisi hari ini” dari sistem ini, maka fungsi untuk peran Director dapat dianggap berhasil.

---

### 2.3 HoD (Head of Division)

Seorang **HoD** dianggap berhasil bila:

- Di **Dashboard HoD**, ia bisa:
  - melihat dengan cepat status plan dan realisasi manager di divisinya,
  - mengetahui jika ada banyak entry yang terlambat, hilang, atau bermasalah.
- Di **Daily Entry (Pribadi HoD)**, HoD bisa:
  - mengisi entry harian untuk dirinya sendiri sebagai HoD,
  - melihat entry harian yang diisi para manager,
  - menyaring berdasarkan tanggal, user, dan status,
  - fokus hanya pada entry yang punya temuan bila diperlukan.
- Di **History**, HoD bisa:
  - menelusuri kembali riwayat kejadian di divisinya,
  - menemukan kembali entry tertentu bila ada investigasi.
- Di **Big Rock & Roadmap**, HoD bisa:
  - membuat sasaran besar pribadinya sebagai HoD (Big Rock per individu) dengan periode dan status, lalu menyelaraskannya dengan sasaran divisi,
  - menyusun langkah-langkah (Roadmap) untuk mencapai sasaran tersebut,
  - memantau progres setiap item dan mengarsipkan yang sudah tidak relevan.
- Di **Division Entries**, HoD bisa:
  - melihat “antrian” aktivitas harian seluruh manager,
  - dengan jelas melihat siapa yang sering bermasalah.
- Di **Division Summary**, HoD:
  - melihat skor kesehatan divisi,
  - melihat grafik tren exception dan kepatuhan,
  - serta daftar manager yang butuh perhatian khusus (sering terlambat, sering ada temuan, dsb.).
- Di **AI Chat (Divisi)**, HoD (ke depannya) bisa:
  - bertanya seperti “Manager mana paling sering missing minggu ini?”
  - dan mendapat jawaban ringkas yang bisa langsung dipakai untuk coaching.

Jika HoD merasa sistem ini bisa menggantikan berbagai file Excel/WhatsApp pribadi yang selama ini dipakai untuk memantau divisi, berarti acceptance criteria untuk peran HoD cukup terpenuhi.

---

### 2.4 Manager

Seorang **Manager** dianggap berhasil memanfaatkan sistem bila:

- Setiap hari kerja, Manager:
  - **mengisi rencana (plan)** sebelum bekerja,
  - **mengisi realisasi** setelah bekerja,
  - mencatat jika ada halangan atau exception.
- Di **Dashboard Manager**, ia bisa:
  - langsung melihat apakah hari ini sudah “selesai” dari sisi pelaporan,
  - melihat Big Rock dan roadmap yang menjadi konteks pekerjaannya,
  - melihat temuan terbaru yang berkaitan dengannya.
- Di **Daily Entry**, Manager:
  - merasa proses pengisian rencana dan realisasi **mudah dan cepat** (tidak lebih dari beberapa menit),
  - tidak kebingungan dengan istilah,
  - tahu apa yang harus diisi dan apa yang boleh dikosongkan.
- Di **History**, Manager:
  - bisa meninjau kembali apa yang sudah dikerjakan dalam satu minggu/bulan,
  - bisa menggunakannya sebagai bahan evaluasi pribadi atau bahan diskusi dengan HoD.
- Di **Big Rock** (untuk versi saat ini), Manager:
  - bisa melihat sasaran besar yang terkait dengan pekerjaannya,
  - dan memahami bagaimana entry hariannya membantu mencapai sasaran tersebut.

Acceptance criteria yang sederhana: jika Manager **tidak perlu “bantuan khusus” setiap hari** untuk mengisi sistem ini, dan merasa sistem **membantu** (bukan sekedar beban tambahan), maka fungsi untuk peran Manager sudah berjalan baik.

---

## 3. Review UI & UX (Fokus: Pengguna Usia 30+ dan Banyak via HP)

### 3.1 Kekuatan UI/UX Saat Ini

- **Desain modern tapi tidak berlebihan**
  - Warna netral dan biru navy membuat tampilan terlihat profesional dan tenang.
  - Tidak ada elemen yang terlalu “mainan” atau norak – cocok untuk pengguna corporate.
- **Struktur layout jelas**
  - Sidebar di kiri dengan kelompok menu yang rapi.
  - Konten utama di kanan dengan header halaman yang konsisten (judul + deskripsi singkat).
- **Status selalu punya label teks**
  - Tidak hanya mengandalkan warna.
  - Ini penting untuk pengguna yang mungkin kesulitan membedakan warna.
- **Ukuran tombol dan area sentuh cukup besar**
  - Minimal tinggi 44px sudah mengikuti standar kenyamanan jari di layar sentuh.
- **Responsif dan mobile‑first**
  - Di mobile, banyak tabel yang diubah menjadi kartu (card) sehingga lebih mudah digulir.
  - Filter dipecah: search tetap di atas, filter lanjutan bisa diakses lewat tombol khusus.
- **Empty state dan pesan kesalahan sudah dirancang dengan kalimat manusia**
  - Contoh: “Belum ada user. Tambah user pertama.”
  - Ini membantu pengguna tidak merasa salah, tapi diarahkan dengan lembut.

Secara umum, **rasa UI/UX sudah cukup bersahabat** untuk pengguna usia 30+ yang terbiasa dengan aplikasi kantor.

---

### 3.2 Risiko / Kekurangan yang Perlu Diwaspadai

Beberapa potensi masalah bila digunakan oleh karyawan usia 30+ yang mungkin:
- sering pakai HP,
- tidak terlalu akrab dengan aplikasi kompleks.

Beberapa catatan jujur:

- **Beban informasi di beberapa halaman cukup berat**
  - Contoh: halaman untuk Director dan HoD memuat banyak kartu, grafik, dan list sekaligus.
  - Bagi sebagian pengguna, ini bisa terasa “penuh” dan membuat lelah saat pertama kali masuk.
- **Beberapa teks berukuran kecil (12px)**
  - Untuk badge, caption, dan metadata.
  - Untuk pengguna yang penglihatannya mulai menurun, ini bisa membuat mereka sering menyipitkan mata.
- **Istilah khusus (Big Rock, Roadmap, Exception, Findings, Compliance)**
  - Jika belum pernah diperkenalkan di organisasi, istilah‑istilah ini bisa membuat bingung.
  - Apalagi jika muncul di banyak tempat (dashboard, form, grafik) sekaligus.
- **Navigasi masih satu dimensi (sidebar saja)**
  - Di HP, pengguna harus membuka hamburger menu untuk berpindah halaman.
  - Jika mereka sering berpindah antara 2–3 halaman favorit, mungkin akan terasa sedikit repot.
- **Chart saat ini masih placeholder**
  - Karena masih mockup, belum terasa bagaimana kenyamanannya ketika grafik benar‑benar aktif dan padat data.
  - Tampilan final bisa saja terasa terlalu penuh bila tidak dibatasi.

---

### 3.3 Saran Perbaikan UI/UX (Empatik terhadap Pengguna 30+)

Beberapa saran yang realistis dan tetap sejalan dengan desain yang sudah ada:

1. **Pertebal hierarki informasi**
   - Untuk dashboard Director/HoD, mulai dari 2–3 informasi utama (misalnya: skor kesehatan, jumlah exception besar, dan daftar “butuh perhatian hari ini”).
   - Detail lainnya bisa ditempatkan di bawah atau di tab/kartu lain.
2. **Perbesar teks yang sering dibaca**
   - Naikkan sedikit ukuran font untuk caption dan metadata yang penting (misal dari 12px ke 13–14px).
   - Khusus di mobile, prioritaskan keterbacaan dibanding jumlah informasi per layar.
3. **Tambahkan penjelasan singkat / tooltip**
   - Misal: ikon “i” kecil di samping istilah “Big Rock”, “Exception”, “Compliance”.
   - Saat ditekan, muncul penjelasan satu kalimat dengan bahasa sederhana.
4. **Buat “jalur cepat” untuk halaman paling sering dipakai**
   - Untuk Manager: misalnya tombol besar “Isi Hari Ini” di dashboard, yang langsung membawa ke halaman Daily Entry.
   - Untuk HoD/Director: tombol “Lihat yang Bermasalah Saja” di dashboard yang langsung menampilkan daftar temuan penting.
5. **Pastikan kontras warna cukup tinggi**
   - Utamakan kombinasi warna teks dan latar dengan kontras jelas (hindari abu‑abu muda di atas putih untuk teks penting).
6. **Kurangi kebutuhan scroll tanpa arah**
   - Bagi pengguna yang memakai HP, terlalu banyak section panjang akan membuat mereka merasa tersesat.
   - Beri judul section yang jelas dan konsisten, sehingga ketika scroll terasa seperti membaca bab demi bab, bukan satu halaman tak berujung.

Sebagian perbaikan ini sudah mulai diterapkan di UI:
- Sidebar sekarang benar‑benar **fixed di desktop**, sehingga konten utama muncul sejajar di kanan tanpa perlu scroll panjang dulu.
- Baris chip/badge yang berpotensi penuh di mobile (misalnya severity breakdown dan beberapa kartu override) sudah diatur agar **bisa membungkus ke baris berikutnya** (wrap), sehingga tidak lagi “keluar” dari kartu.
- Di sidebar Director, kini ada blok **“Kelola”** khusus yang memberi akses langsung ke pengelolaan Users, Divisi, Assignment HoD, dan Cuti & Izin, sehingga peran Director sebagai pengambil keputusan juga terasa di level administrasi ringan tanpa harus berpindah role.

Secara keseluruhan, UI/UX saat ini **sudah di jalur yang benar**, hanya perlu disesuaikan agar lebih “ramah mata” dan “ramah beban otak” bagi pengguna yang mungkin tidak lagi muda dan sering memakai HP.

---

## 4. Jika Ingin Dibuat Backend‑nya: Apa yang Perlu Disiapkan?

Saat ini, project lebih berfungsi sebagai **preview UI** tanpa logika backend penuh.  
Untuk membawa sistem ini ke tahap **siap produksi**, beberapa hal yang perlu disiapkan (bahasa awam, tidak teknis mendalam):

### 4.1 Data & Struktur Informasi

- Menentukan **data apa saja yang mau disimpan** secara jelas:
  - data user (nama, email, role, divisi, status),
  - data divisi,
  - data Big Rock & Roadmap,
  - data entry harian (plan dan realisasi),
  - data temuan/exception (termasuk tingkat keparahan),
  - data cuti/izin,
  - data notifikasi yang dikirim,
  - log aktivitas penting (siapa mengubah apa dan kapan).
- Menyusun **struktur database** yang rapi sehingga:
  - hubungan antara Manager–HoD–Director–Divisi jelas,
  - hubungan antara Big Rock–Roadmap–Entry harian jelas.

### 4.2 Hak Akses & Keamanan

- Menentukan **aturan hak akses**:
  - Admin boleh melihat dan mengubah apa saja.
  - Director boleh melihat seluruh perusahaan, tetapi tidak mengubah hal teknis seperti user.
  - HoD boleh melihat dan mengelola data divisinya saja.
  - Manager hanya boleh melihat dan mengubah data miliknya sendiri (dan mungkin beberapa data yang diberi wewenang).
- Mengatur **proses login**:
  - memastikan hanya orang yang berhak yang bisa masuk,
  - memastikan setelah login, mereka diarahkan ke halaman sesuai peran.

### 4.3 Logika Bisnis Harian

- Menentukan dengan jelas aturan seperti:
  - jam berapa batas mengisi plan dan realisasi,
  - apa yang terjadi jika terlambat (apakah masih bisa diisi dengan status terlambat, atau dikunci total),
  - bagaimana sebuah temuan dikategorikan menjadi minor/medium/major,
  - bagaimana skor kesehatan (health score) dihitung.
- Menentukan bagaimana **exception dan temuan** dihasilkan:
  - apakah otomatis berdasarkan aturan,
  - atau ada input manual dari HoD/Manager.

### 4.4 Notifikasi & Integrasi

- Menentukan **channel notifikasi**:
  - email, WhatsApp, aplikasi internal, atau kombinasi.
- Menyusun skenario:
  - kapan pengingat dikirim,
  - siapa yang menerima pengingat,
  - bagaimana bila notifikasi gagal (perlu tindakan manual atau cukup tercatat).

### 4.5 Laporan & Dashboard

- Menyusun **rumus laporan**:
  - apa saja yang dihitung di Company Health,
  - apa yang ditampilkan di grafik exception dan compliance,
  - bagaimana data difilter per tanggal, divisi, role.
- Memastikan hasil di dashboard **selalu konsisten** dengan data mentah yang diinput.

### 4.6 Stabilitas, Audit, dan Monitoring

- Menyusun **catatan jejak perubahan**:
  - kalau ada data diubah (misal override), harus ada catatan siapa yang mengubah dan alasannya.
- Menyusun **monitoring**:
  - supaya jika terjadi error di sistem, tim teknis bisa cepat tahu dan memperbaiki tanpa mengganggu operasional terlalu lama.

Secara singkat: backend perlu membuat semua yang sudah bagus di tampilan ini **benar‑benar hidup**, dengan aturan bisnis yang jelas, data yang aman, dan laporan yang bisa dipercaya manajemen.

---

## 5. Chart: Kondisi Sekarang & Pilihan Library

### 5.1 Kondisi Saat Ini

Di beberapa halaman (misalnya Company Page untuk Director dan Division Summary untuk HoD), **chart masih berupa placeholder**:

- Di dalam kode, sudah disiapkan tempat untuk grafik (dengan `div` khusus dan ID chart).
- Teks di dalamnya masih berupa tulisan seperti “Health Trend Chart” atau “Compliance Trend Chart”.
- Komentar di dokumen desain menyebutkan rencana menggunakan **ApexCharts** sebagai library utama.

Artinya, tampilan sudah siap, tetapi **belum disambungkan dengan data nyata dan library grafik**.

---

### 5.2 Rekomendasi Library Chart yang Cocok

Berikut beberapa pilihan library grafik yang cocok dengan konsep sistem ini, dijelaskan dalam bahasa bisnis:

1. **ApexCharts**
   - Sudah disebut di dokumen desain sebagai pilihan utama.
   - Kelebihan:
     - Tampilan modern dan bersih, cocok dengan gaya UI yang sudah dibuat.
     - Mendukung banyak jenis grafik: garis, batang, area, donat, dan lain‑lain.
     - Bisa diintegrasikan dengan cukup mudah ke halaman yang sudah ada.
   - Cocok untuk: grafik trend kesehatan, trend kepatuhan, jumlah exception per kategori, dan aktivitas roadmap.

2. **Chart.js**
   - Salah satu library grafik paling populer dan mudah dipahami.
   - Kelebihan:
     - Dokumentasi banyak, contoh penggunaan sangat melimpah.
     - Ringan dan cukup fleksibel untuk kebutuhan dashboard umum.
   - Cocok untuk: perusahaan yang ingin sesuatu yang sederhana tapi stabil, dengan tim teknis yang sudah familiar dengan Chart.js.

3. **ECharts (Apache ECharts)**
   - Library grafik yang sangat kuat, mampu menampilkan visualisasi yang kompleks.
   - Kelebihan:
     - Sangat kaya fitur, termasuk zoom, tooltip detail, dan grafik yang interaktif.
   - Cocok untuk: jika di masa depan ingin visualisasi yang lebih rumit, misalnya korelasi antara banyak variabel.

4. **Highcharts**
   - Library grafik komersial yang banyak dipakai di dunia enterprise.
   - Kelebihan:
     - Banyak pilihan jenis grafik,
     - tampilan sangat rapi dan cocok untuk laporan manajemen.
   - Catatan: lisensi komersial mungkin berbayar, sehingga perlu dipertimbangkan secara bisnis.

5. **ApexCharts melalui paket Laravel/Livewire (opsional)**
   - Ada paket pihak ketiga yang menghubungkan ApexCharts dengan ekosistem Laravel/Livewire.
   - Kelebihan:
     - Mempermudah pengambilan data dari sistem ke grafik tanpa terlalu banyak kode JavaScript.
   - Cocok untuk: tim yang ingin integrasi lebih “rapi” di sisi server.

Melihat arah desain yang sudah ditulis di dokumen project, **ApexCharts** adalah pilihan yang paling natural. Library lain bisa disiapkan sebagai opsi cadangan jika di masa depan ada kebutuhan khusus.

---

## Penutup

Secara keseluruhan, project ini sudah:

- punya **konsep bisnis yang jelas** (monitoring eksekusi harian, dari level Manager sampai Director),
- punya **pembagian peran yang sehat** (Admin, Director, HoD, Manager),
- dan **fondasi UI/UX yang kuat** untuk target pengguna usia 30+.

Yang masih perlu dikerjakan adalah:

- menyederhanakan beberapa tampilan untuk mengurangi beban informasi,
- memperhalus pengalaman mobile untuk pengguna yang sering memakai HP,
- serta membangun backend yang kokoh agar semua data, grafik, dan laporan benar‑benar bisa dipercaya untuk pengambilan keputusan manajemen.

Dokumen ini bisa dipakai sebagai referensi awal ketika menjelaskan sistem ke stakeholder non‑teknis (HR, manajemen puncak, atau user perwakilan) sebelum masuk ke diskusi teknis yang lebih detail.
