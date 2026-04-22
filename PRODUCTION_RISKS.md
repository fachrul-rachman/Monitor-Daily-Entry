# Production Gaps & Defects (Dayta) — fokus “fitur cacat/kurang”

Dokumen ini merangkum kekurangan yang benar-benar berdampak ke operasional harian dan kepercayaan dashboard di production. Format tiap poin: masalahnya apa, kondisi sekarang, saran perbaikan.

## P0 (Mengganggu operasi harian / realtime)

### 1) Import user rawan salah format (bisa bikin data masuk tidak sesuai)
- Masalah: kalau file tidak sesuai template, hasilnya bisa kacau (kolom ketukar, role salah, divisi tidak terbaca) dan baru ketahuan belakangan.
- Sekarang: sistem masih berusaha “menebak” kolom jika header tidak persis sesuai.
- Saran:
  - Wajibkan format template (kalau header tidak cocok: hentikan dan tampilkan alasan yang jelas).
  - Tampilkan ringkasan sebelum proses: kolom apa yang terbaca dan kolom apa yang tidak terbaca.

### 2) Import user belum enak untuk skala besar (berpotensi lambat saat baris banyak)
- Masalah: kalau baris banyak, proses import jadi lama dan bisa mengganggu user lain.
- Sekarang: import dilakukan interaktif dengan preview; belum ada batas jumlah baris yang eksplisit.
- Saran:
  - Tetapkan batas jumlah baris yang aman untuk import interaktif.
  - Untuk import besar: jadikan proses background, lalu user cukup melihat hasilnya (berapa sukses/gagal).

### 3) Realtime belum terjamin konsisten saat beban naik
- Masalah: kamu butuh “begitu tim mengisi telat, dashboard langsung berubah”; kalau update tertunda, dashboard jadi tidak bisa dipakai untuk tindakan cepat.
- Sekarang: update ringkasan berjalan dari beberapa jalur; saat trafik naik, ini berisiko membuat update tertunda (mengantri) atau terasa tidak realtime.
- Saran:
  - Tetapkan target realtime yang jelas (maksimal keterlambatan tampil berapa menit).
  - Pastikan update ringkasan dibuat sekecil mungkin per perubahan (hanya bagian yang berubah), bukan hitung luas.
  - Tampilkan di dashboard: “data terakhir diperbarui jam X” agar user bisa percaya angka.

### 4) Definisi “hari kerja” belum konsisten antar halaman
- Masalah: angka kepatuhan/tren bisa beda antar halaman kalau “hari kerja” dihitung beda; ini cepat merusak kepercayaan dashboard.
- Sekarang: ada halaman yang menghitung hari kerja hanya dari weekend, sementara proses lain sudah mempertimbangkan tanggal merah.
- Saran:
  - Bekukan 1 definisi hari kerja dan pakai sama di semua halaman ringkasan.

## P1 (Fitur ada, tapi kualitas operasionalnya belum cukup)

### 5) Dashboard Director bisa “terasa berat” saat data membesar
- Masalah: user Director butuh buka cepat; kalau terasa berat, outputnya “nggak kepakai untuk keputusan”.
- Sekarang: dashboard menarik banyak ringkasan dalam sekali buka.
- Saran:
  - Prioritaskan yang wajib tampil dulu (ringkasan inti), sisanya bisa “detail”/halaman lanjutan.
  - Batasi default periode yang ditampilkan (biar tidak kebanyakan).

### 6) AI Chat: akses sudah dibatasi (Director/HoD), tapi kontrol operasionalnya belum cukup
- Masalah: walau akses sudah benar, AI tetap bisa jadi sumber masalah operasional (gagal tanpa jelas, pemakaian tidak terkontrol, sulit ditelusuri saat ada komplain).
- Sekarang: kalau AI tidak bisa menjawab, user hanya dapat pesan gagal yang generik.
- Saran:
  - Buat status yang jelas: AI aktif/nonaktif + alasan bila nonaktif.
  - Buat pembatasan pemakaian (limit) agar biaya dan beban tidak liar.
  - Buat jejak aktivitas yang rapi untuk troubleshooting (tanpa mengekspos data sensitif ke sembarang orang).

### 7) Notifikasi Discord: tidak spam, tapi masih butuh indikator cepat kalau gagal
- Masalah: kalau pengiriman gagal, manajemen bisa mengira aman padahal ringkasan tidak terkirim.
- Sekarang: ada pencatatan sukses/gagal, ada jam kirim, ada deduplikasi, dan tidak kirim jika tidak ada temuan.
- Saran:
  - Tampilkan indikator yang mudah dilihat admin: ringkasan terakhir terkirim kapan dan gagal terakhir kapan.
  - Tetapkan SOP jika gagal: siapa yang follow up dan kapan harus dianggap kritis.

## Catatan
- Poin “password terlihat di preview import” dihapus dari dokumen ini sesuai instruksi, walau itu tetap berpotensi jadi cacat jika masih dipakai.
