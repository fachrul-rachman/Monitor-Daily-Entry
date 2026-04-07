# DESIGN.md — Design System
## Daily Execution Monitoring System

---

## 1. Prinsip Visual

- Modern tapi konservatif — bukan playful, bukan gelap
- Warna netral dominan, 1 primary color
- Warna dipakai untuk fungsi, bukan dekorasi
- Hierarki visual kuat, layout bersih
- Target user usia 30+ — familiar, tidak eksperimental
- Semua status eksplisit dengan label teks

---

## 2. Color Tokens

Definisikan di `resources/css/app.css` sebagai CSS variables:

```css
:root {
  /* Neutral */
  --color-bg:          #F8F9FA;   /* background utama */
  --color-surface:     #FFFFFF;   /* card, panel, modal */
  --color-border:      #E2E6EA;   /* border elemen */
  --color-muted:       #6C757D;   /* teks sekunder, label kecil */
  --color-text:        #1A1D23;   /* teks utama */

  /* Primary — Biru Navy */
  --color-primary:     #1E3A5F;
  --color-primary-hover: #16304F;
  --color-primary-light: #EBF0F8;  /* background badge primary, highlight */

  /* Status */
  --color-success:     #1A7F4B;   /* hijau — safe, finished */
  --color-success-bg:  #EDFAF3;
  --color-warning:     #B45309;   /* oranye — warning, in progress */
  --color-warning-bg:  #FEF3C7;
  --color-danger:      #B91C1C;   /* merah — exception, major, error */
  --color-danger-bg:   #FEE2E2;
  --color-info:        #1D4ED8;   /* biru — info, minor */
  --color-info-bg:     #EFF6FF;

  /* Severity */
  --severity-major:    #B91C1C;
  --severity-major-bg: #FEE2E2;
  --severity-medium:   #B45309;
  --severity-medium-bg:#FEF3C7;
  --severity-minor:    #1D4ED8;
  --severity-minor-bg: #EFF6FF;

  /* Sidebar */
  --sidebar-bg:        #1A1D23;
  --sidebar-text:      #CBD5E1;
  --sidebar-active-bg: #1E3A5F;
  --sidebar-active-text: #FFFFFF;
  --sidebar-section:   #475569;
}
```

### Tailwind config extension

Tambahkan di `tailwind.config.js`:

```js
theme: {
  extend: {
    colors: {
      primary: {
        DEFAULT: '#1E3A5F',
        hover: '#16304F',
        light: '#EBF0F8',
      },
      surface: '#FFFFFF',
      'app-bg': '#F8F9FA',
      muted: '#6C757D',
      border: '#E2E6EA',
      danger: {
        DEFAULT: '#B91C1C',
        bg: '#FEE2E2',
      },
      success: {
        DEFAULT: '#1A7F4B',
        bg: '#EDFAF3',
      },
      warning: {
        DEFAULT: '#B45309',
        bg: '#FEF3C7',
      },
    }
  }
}
```

---

## 3. Typography

Font via Google Fonts:
```html
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
```

| Elemen | Font | Size (mobile) | Size (desktop) | Weight |
|---|---|---|---|---|
| Page heading | DM Sans | 22px | 26px | 700 |
| Section heading | DM Sans | 18px | 20px | 600 |
| Body | Inter | 15px | 15px | 400 |
| Label form | Inter | 13px | 14px | 500 |
| Metadata / caption | Inter | 12px | 12px | 400 |
| Button | Inter | 14px | 14px | 500 |
| Badge | Inter | 12px | 12px | 600 |

Aturan:
- Jangan pakai font di bawah 12px
- Line height body: 1.6
- Line height heading: 1.3

---

## 4. Spacing System

Gunakan Tailwind default spacing. Panduan konsistensi:

| Konteks | Value |
|---|---|
| Padding card | `p-4` (mobile) / `p-5` (desktop) |
| Gap antar card | `gap-4` |
| Gap antar section | `mb-8` |
| Padding page | `px-4 py-6` (mobile) / `px-8 py-8` (desktop) |
| Gap antar form field | `space-y-4` |
| Padding button | `px-4 py-2.5` |
| Padding input | `px-3 py-2.5` |

---

## 5. Komponen UI

### 5.1 Button

```html
<!-- Primary -->
<button class="inline-flex items-center gap-2 bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors min-h-[44px]">
  Label Aksi
</button>

<!-- Secondary -->
<button class="inline-flex items-center gap-2 bg-white border border-border text-text text-sm font-medium px-4 py-2.5 rounded-lg hover:bg-app-bg transition-colors min-h-[44px]">
  Label
</button>

<!-- Danger -->
<button class="inline-flex items-center gap-2 bg-danger text-white text-sm font-medium px-4 py-2.5 rounded-lg hover:opacity-90 transition-opacity min-h-[44px]">
  Hapus
</button>

<!-- Disabled state (selalu tambahkan ini saat loading) -->
<button disabled class="... opacity-50 cursor-not-allowed">
  <svg class="animate-spin h-4 w-4" ...></svg>
  Menyimpan...
</button>
```

Aturan button:
- Minimum height 44px (tap target)
- Primary action selalu paling menonjol
- Destructive action pakai warna danger
- Saat loading: disable + spinner + label berubah

### 5.2 Badge Status

```html
<!-- Active / Success -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-success-bg text-success">
  Aktif
</span>

<!-- Danger / Exception -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-danger-bg text-danger">
  Non Aktif
</span>

<!-- Warning -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-warning-bg text-warning">
  Pending
</span>

<!-- Info / Neutral -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-light text-primary">
  Draft
</span>
```

### 5.3 Severity Tag

```html
<!-- Major -->
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-semibold bg-danger-bg text-danger border border-danger/20">
  ● Major
</span>

<!-- Medium -->
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-semibold bg-warning-bg text-warning border border-warning/20">
  ● Medium
</span>

<!-- Minor -->
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-semibold bg-info-bg text-info border border-info/20">
  ● Minor
</span>
```

### 5.4 Card

```html
<div class="bg-surface border border-border rounded-xl p-4 md:p-5">
  <!-- isi konten -->
</div>
```

### 5.5 Summary Card (metric)

```html
<div class="bg-surface border border-border rounded-xl p-4 md:p-5">
  <p class="text-sm text-muted font-medium">Label Metrik</p>
  <p class="text-2xl font-bold text-text mt-1">42</p>
  <p class="text-xs text-muted mt-1">Konteks tambahan</p>
</div>
```

Gunakan border kiri berwarna untuk membedakan:
- Exception: `border-l-4 border-l-danger`
- Success: `border-l-4 border-l-success`
- Warning: `border-l-4 border-l-warning`
- Neutral: tanpa border kiri

### 5.6 Input & Form Field

```html
<div>
  <label class="block text-sm font-medium text-text mb-1.5">
    Label Field
    <span class="text-danger">*</span>
  </label>
  <input
    type="text"
    class="w-full px-3 py-2.5 border border-border rounded-lg text-sm text-text bg-white focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition min-h-[44px]"
    placeholder="Placeholder..."
  />
  <p class="text-xs text-muted mt-1">Helper text di sini</p>
  <!-- Error state -->
  <p class="text-xs text-danger mt-1">Pesan error di sini</p>
</div>
```

Error state border: tambah class `border-danger focus:ring-danger/30 focus:border-danger`

### 5.7 Table (Desktop)

```html
<div class="overflow-x-auto rounded-xl border border-border">
  <table class="w-full text-sm">
    <thead>
      <tr class="bg-app-bg border-b border-border">
        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Kolom</th>
        <!-- ... -->
      </tr>
    </thead>
    <tbody class="divide-y divide-border">
      <tr class="hover:bg-app-bg transition-colors">
        <td class="px-4 py-3.5 text-text">Data</td>
        <!-- ... -->
      </tr>
    </tbody>
  </table>
</div>
```

### 5.8 Mobile Card List (pengganti table di mobile)

```html
<!-- Tampil hanya di mobile: block md:hidden -->
<div class="space-y-3">
  <div class="bg-surface border border-border rounded-xl p-4">
    <div class="flex items-start justify-between">
      <div>
        <p class="font-semibold text-text">Nama User</p>
        <p class="text-sm text-muted mt-0.5">email@domain.com</p>
      </div>
      <!-- Badge -->
      <span class="badge-success">Aktif</span>
    </div>
    <div class="mt-3 flex items-center gap-2 text-xs text-muted">
      <span>Manager</span>
      <span>·</span>
      <span>Divisi A</span>
    </div>
    <!-- Action menu -->
    <div class="mt-3 pt-3 border-t border-border flex gap-2">
      <button class="text-sm text-primary font-medium">Edit</button>
      <button class="text-sm text-danger font-medium ml-auto">Arsipkan</button>
    </div>
  </div>
</div>
```

### 5.9 Empty State

```html
<div class="flex flex-col items-center justify-center py-16 text-center">
  <!-- Icon ringan -->
  <div class="w-12 h-12 rounded-full bg-app-bg flex items-center justify-center mb-4">
    <svg class="w-6 h-6 text-muted" ...></svg>
  </div>
  <p class="text-base font-semibold text-text">Belum ada data</p>
  <p class="text-sm text-muted mt-1 max-w-xs">Deskripsi singkat kenapa kosong atau apa yang bisa dilakukan.</p>
  <!-- CTA opsional -->
  <button class="mt-4 btn-primary">Tambah Sekarang</button>
</div>
```

### 5.10 Loading / Skeleton

```html
<!-- Skeleton bar -->
<div class="animate-pulse space-y-3">
  <div class="h-4 bg-border rounded w-3/4"></div>
  <div class="h-4 bg-border rounded w-1/2"></div>
  <div class="h-4 bg-border rounded w-5/6"></div>
</div>

<!-- Skeleton card -->
<div class="animate-pulse bg-surface border border-border rounded-xl p-4">
  <div class="h-3 bg-border rounded w-1/3 mb-3"></div>
  <div class="h-6 bg-border rounded w-1/2"></div>
</div>
```

### 5.11 Confirmation Modal

```html
<div x-show="showConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/40" @click="showConfirm = false"></div>
  <div class="relative bg-surface rounded-2xl p-6 w-full max-w-sm shadow-xl">
    <h3 class="text-base font-semibold text-text">Konfirmasi Aksi</h3>
    <p class="text-sm text-muted mt-2">Apakah kamu yakin? Aksi ini tidak bisa dibatalkan.</p>
    <div class="mt-6 flex gap-3">
      <button @click="showConfirm = false" class="btn-secondary flex-1">Batal</button>
      <button wire:click="confirmAction" class="btn-danger flex-1">Ya, Lanjutkan</button>
    </div>
  </div>
</div>
```

### 5.12 Toast Notifikasi

Gunakan event Livewire dispatch + Alpine listener:

```html
<!-- Di layout utama -->
<div
  x-data="{ show: false, message: '', type: 'success' }"
  x-on:toast.window="show = true; message = $event.detail.message; type = $event.detail.type; setTimeout(() => show = false, 3000)"
  x-show="show"
  x-transition
  class="fixed bottom-6 right-4 z-50 max-w-sm"
>
  <div :class="type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'"
    class="px-4 py-3 rounded-xl shadow-lg text-sm font-medium flex items-center gap-2">
    <span x-text="message"></span>
  </div>
</div>
```

Dari Livewire dispatch dengan:
```php
$this->dispatch('toast', message: 'Berhasil disimpan', type: 'success');
```

### 5.13 Filter Bar

Mobile — tombol filter membuka bottom sheet:
```html
<div class="flex gap-2 items-center">
  <div class="flex-1 relative">
    <input type="text" wire:model.live.debounce.300ms="search"
      class="w-full pl-9 pr-3 py-2.5 border border-border rounded-lg text-sm ..."
      placeholder="Cari..." />
    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" ...></svg>
  </div>
  <button @click="filterOpen = true" class="btn-secondary gap-2">
    <svg ...></svg>
    Filter
  </button>
</div>
```

Desktop — filter tampil horizontal:
```html
<div class="hidden md:flex gap-3 flex-wrap items-center">
  <input type="text" wire:model.live.debounce.300ms="search" class="input w-64" placeholder="Cari..." />
  <select wire:model.live="filterRole" class="input w-40">...</select>
  <select wire:model.live="filterDivision" class="input w-44">...</select>
  <button wire:click="resetFilters" class="text-sm text-muted hover:text-text">Reset</button>
</div>
```

### 5.14 Bottom Sheet Filter (Mobile)

```html
<div x-show="filterOpen" class="fixed inset-0 z-40 md:hidden">
  <div class="absolute inset-0 bg-black/40" @click="filterOpen = false"></div>
  <div class="absolute bottom-0 left-0 right-0 bg-surface rounded-t-2xl p-5 space-y-4"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="translate-y-full"
    x-transition:enter-end="translate-y-0">
    <div class="flex items-center justify-between mb-2">
      <p class="font-semibold text-text">Filter</p>
      <button @click="filterOpen = false" class="text-muted">✕</button>
    </div>
    <!-- Filter fields di sini -->
    <button @click="filterOpen = false" class="btn-primary w-full">Terapkan</button>
  </div>
</div>
```

### 5.15 Drawer / Slide Over (Detail Panel)

```html
<div x-show="drawerOpen" class="fixed inset-0 z-40 flex justify-end">
  <div class="absolute inset-0 bg-black/40" @click="drawerOpen = false"></div>
  <div class="relative w-full max-w-lg bg-surface h-full overflow-y-auto shadow-2xl"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0">
    <div class="p-5 border-b border-border flex items-center justify-between sticky top-0 bg-surface">
      <h3 class="font-semibold text-text">Judul Detail</h3>
      <button @click="drawerOpen = false" class="text-muted">✕</button>
    </div>
    <div class="p-5">
      <!-- Isi detail -->
    </div>
  </div>
</div>
```

### 5.16 AI Response Block

```html
<div class="bg-primary-light border border-primary/20 rounded-xl p-4">
  <div class="flex items-center gap-2 mb-3">
    <div class="w-6 h-6 rounded-full bg-primary flex items-center justify-center">
      <svg class="w-3 h-3 text-white" ...></svg>
    </div>
    <span class="text-xs font-semibold text-primary uppercase tracking-wide">AI Response</span>
  </div>
  <p class="text-sm text-text leading-relaxed">{{ $aiResponse }}</p>
  <ul class="mt-3 space-y-1">
    @foreach($aiPoints as $point)
    <li class="flex items-start gap-2 text-sm text-text">
      <span class="text-primary mt-0.5">→</span>
      {{ $point }}
    </li>
    @endforeach
  </ul>
  <p class="text-xs text-muted mt-3 pt-3 border-t border-primary/20">
    Hasil AI bersifat pendukung. Verifikasi dengan data aktual.
  </p>
</div>
```

### 5.17 Audit Timeline

```html
<div class="space-y-4">
  @foreach($auditLogs as $log)
  <div class="flex gap-3">
    <div class="flex flex-col items-center">
      <div class="w-2 h-2 rounded-full bg-primary mt-1.5 shrink-0"></div>
      @if(!$loop->last)
        <div class="w-px flex-1 bg-border mt-1"></div>
      @endif
    </div>
    <div class="pb-4">
      <p class="text-sm font-medium text-text">{{ $log->action }}</p>
      <p class="text-xs text-muted mt-0.5">{{ $log->user }} · {{ $log->created_at->diffForHumans() }}</p>
      @if($log->notes)
        <p class="text-sm text-muted mt-1">{{ $log->notes }}</p>
      @endif
    </div>
  </div>
  @endforeach
</div>
```

### 5.18 Page Header

```html
<div class="mb-6">
  <h1 class="text-2xl font-bold text-text" style="font-family: 'DM Sans', sans-serif;">
    Judul Halaman
  </h1>
  <p class="text-sm text-muted mt-1">Deskripsi singkat atau status penting hari ini.</p>
</div>
```

---

## 6. Chart

Gunakan **ApexCharts** via CDN:
```html
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
```

Aturan chart:
- Maksimal 3 series per chart
- Legend selalu tampil dan cukup besar
- Warna chart ikuti color token: primary, success, danger, warning
- Mobile: height 200px; Desktop: height 280px
- Jika chart terlalu padat → tampilkan sebagai list/summary saja
- Chart container wajib dibungkus card dengan loading state

```html
<div class="bg-surface border border-border rounded-xl p-4 md:p-5">
  <h3 class="text-sm font-semibold text-text mb-4">Judul Chart</h3>
  <div wire:loading>
    <div class="animate-pulse h-48 bg-border rounded"></div>
  </div>
  <div wire:loading.remove>
    <div id="chart-exception-trend"></div>
  </div>
</div>
```

---

## 7. Status Badge Reference

| Status | Warna |
|---|---|
| Active / Finished / Approved / Sent | success |
| Pending / In Progress / Draft | warning |
| Non Active / Rejected / Failed / Blocked | danger |
| Archived / Missing / Late | muted (abu) |
| Submitted | primary |
| Planned | info |

Semua badge **wajib punya label teks**, tidak cukup hanya warna.

---

## 8. Responsiveness Rules

| Elemen | Mobile | Desktop |
|---|---|---|
| Layout | Single column | Sidebar + content |
| Table | Cards/list | Table |
| Filter | Bottom sheet | Horizontal bar |
| Modal | Full screen / bottom sheet | Centered dialog |
| Chart | 1 chart terpenting | 2 kolom |
| Sidebar | Drawer (hamburger) | Fixed kiri |
| Form | 1 kolom | 1 kolom (max-w-xl) |

---

## 9. Sidebar Design

```
Sidebar desktop: w-64, bg sidebar-bg (#1A1D23), text sidebar-text
Sidebar mobile: fixed drawer, overlay bg-black/50

Section title: text-xs uppercase tracking-widest text-sidebar-section, px-4 py-2
Nav item: flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 text-sm
Active item: bg-sidebar-active-bg text-sidebar-active-text
Hover item: bg-white/5

User area (bottom): border-t border-white/10, p-4
  - Avatar: w-8 h-8 rounded-full bg-primary flex items-center justify-center text-xs text-white font-bold
  - Nama: text-sm font-medium text-white
  - Role + Divisi: text-xs text-sidebar-text
  - Logout: text-xs text-sidebar-section hover:text-white
```