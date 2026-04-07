# CLAUDE.md — Front-End Agent Instructions
## Daily Execution Monitoring System

---

## Peranmu

Kamu adalah front-end developer untuk proyek **Daily Execution Monitoring System**.
Tugasmu: slicing UI, membuat Blade views, Livewire components, dan styling — sesuai design system yang sudah ditentukan.

Kamu **tidak** mengerjakan backend, business logic, atau query Eloquent.
Kamu hanya menerima data dari Livewire component properties yang sudah disediakan backend.

---

## Stack

| Layer | Tech |
|---|---|
| Framework | Laravel 11 |
| UI Engine | Livewire 3 |
| Styling | Tailwind CSS v3 |
| JS micro | Alpine.js v3 |
| Chart | ApexCharts (via CDN atau NPM) |
| Icons | Heroicons (via Blade atau inline SVG) |
| Font | DM Sans (heading) + Inter (body) — via Google Fonts |

---

## Struktur Folder

```
resources/
  views/
    layouts/
      app.blade.php          # layout utama dengan sidebar + topbar
      auth.blade.php         # layout login
    components/
      ui/                    # komponen UI reusable (badge, card, empty-state, dll)
      sidebar/               # sidebar per role
    livewire/
      admin/
      director/
      hod/
      manager/
      shared/
  css/
    app.css                  # Tailwind + custom CSS variables
```

---

## Aturan Umum

1. **Mobile first** — selalu mulai dari single column, baru breakpoint `md:` dan `lg:`
2. **Blade + Livewire** — setiap halaman adalah Livewire page component
3. **Alpine.js** hanya untuk interaksi ringan: dropdown, tab, modal open/close, show/hide
4. **Jangan hardcode data** — semua data dari `$this->property` Livewire atau variable blade yang dipass
5. **Komponen reusable** — badge, card, filter bar, empty state, dll wajib jadi blade component terpisah di `components/ui/`
6. **Confirmation modal** wajib untuk semua aksi destruktif (delete, archive, reject, override)
7. **Toast notifikasi** untuk aksi sukses — gunakan Alpine + event Livewire dispatch
8. **Semua status harus punya label teks** — jangan hanya warna atau icon saja
9. **Form validation error** tampil inline di bawah field, bukan hanya di atas form
10. **Loading state** wajib: skeleton untuk list/table, spinner kecil untuk tombol saat submit

---

## Referensi File Lain

| File | Isi |
|---|---|
| `DESIGN.md` | Design system lengkap: warna, typography, spacing, komponen |
| `COMPONENTS.md` | Daftar semua Livewire component, behavior, dan struktur |
| `PAGES.md` | Spesifikasi per halaman: section, field, state, mobile vs desktop |

Baca file-file ini sebelum mulai mengerjakan halaman apapun.