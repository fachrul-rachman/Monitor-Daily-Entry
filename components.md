# COMPONENTS.md — Livewire Component Reference
## Daily Execution Monitoring System

---

## Cara Baca Dokumen Ini

Setiap component entry berisi:
- **File path** — lokasi file di project
- **Tujuan** — apa yang dikerjakan component ini
- **Properties** — data yang tersedia dari backend (jangan dibuat ulang, tinggal pakai)
- **Actions** — method yang bisa dipanggil via `wire:click` dll
- **Events** — Livewire dispatch events yang perlu dihandle
- **UI Notes** — instruksi khusus untuk tampilan

---

## SHARED COMPONENTS

### `shared.filter-bar`
**File:** `resources/views/livewire/shared/filter-bar.blade.php`
**Tujuan:** Filter reusable dengan search + filter tambahan

**Props yang diterima:**
- `$search` — string
- `$filters` — array filter aktif
- `$filterCount` — jumlah filter aktif (untuk badge di tombol filter)

**UI Notes:**
- Mobile: search input + tombol "Filter" yang buka bottom sheet
- Desktop: search + semua filter tampil horizontal
- Debounce search: `wire:model.live.debounce.300ms`
- Tombol reset hanya muncul jika ada filter aktif

---

### `shared.status-badge`
**File:** `resources/views/components/ui/status-badge.blade.php`
**Tujuan:** Badge status reusable

**Penggunaan:**
```blade
<x-ui.status-badge :status="$user->status" />
<x-ui.status-badge :status="$plan->status" />
```

**Mapping status → style** (implementasikan di component):

| Status value | Label | Style |
|---|---|---|
| `active` | Aktif | success |
| `inactive` | Non Aktif | danger |
| `archived` | Diarsipkan | muted |
| `submitted` | Submitted | primary |
| `draft` | Draft | warning |
| `late` | Terlambat | danger |
| `missing` | Missing | danger |
| `pending` | Pending | warning |
| `approved` | Disetujui | success |
| `rejected` | Ditolak | danger |
| `cancelled` | Dibatalkan | muted |
| `finished` | Selesai | success |
| `in_progress` | Sedang Berjalan | warning |
| `blocked` | Blocked | danger |
| `planned` | Planned | info |
| `done` | Done | success |
| `sent` | Terkirim | success |
| `failed` | Gagal | danger |

---

### `shared.severity-badge`
**File:** `resources/views/components/ui/severity-badge.blade.php`

**Penggunaan:**
```blade
<x-ui.severity-badge :severity="$finding->severity" />
```

| Severity value | Label | Style |
|---|---|---|
| `major` | Major | danger (border) |
| `medium` | Medium | warning (border) |
| `minor` | Minor | info (border) |

---

### `shared.empty-state`
**File:** `resources/views/components/ui/empty-state.blade.php`

**Penggunaan:**
```blade
<x-ui.empty-state
  icon="document"
  title="Belum ada data"
  description="Deskripsi singkat apa yang bisa dilakukan."
  :cta-label="'Tambah Sekarang'"
  :cta-action="'openModal'"
/>
```

---

### `shared.confirmation-modal`
**File:** `resources/views/components/ui/confirmation-modal.blade.php`

**Penggunaan:**
```blade
<x-ui.confirmation-modal
  x-show="showConfirm"
  title="Hapus User"
  message="Yakin ingin menghapus user ini? Aksi ini tidak bisa dibatalkan."
  confirm-label="Ya, Hapus"
  confirm-action="deleteUser"
  danger
/>
```

---

### `shared.audit-timeline`
**File:** `resources/views/components/ui/audit-timeline.blade.php`

**Penggunaan:**
```blade
<x-ui.audit-timeline :logs="$auditLogs" />
```

Setiap item log memiliki: `action`, `user`, `created_at`, `notes (nullable)`

---

### `shared.pagination`
**File:** `resources/views/components/ui/pagination.blade.php`

**Penggunaan:**
```blade
<x-ui.pagination :paginator="$users" />
```

Gunakan Livewire's built-in pagination tapi dengan custom view simpel.

---

### `shared.chart-card`
**File:** `resources/views/components/ui/chart-card.blade.php`

**Penggunaan:**
```blade
<x-ui.chart-card
  title="Trend Exception"
  chart-id="exception-trend"
  :loading="$loadingChart"
/>
```

Chart data dikirim via Alpine/JS setelah Livewire selesai load.

---

## AUTH

### `auth.login-form`
**File:** `resources/views/livewire/auth/login-form.blade.php`
**Layout:** `layouts/auth.blade.php`

**Properties:**
- `$email` — string
- `$password` — string
- `$remember` — boolean
- `$loading` — boolean

**Actions:**
- `login()` — proses auth, redirect berdasarkan role

**UI Notes:**
- Mobile: card centered, full screen
- Desktop: split layout — kiri branding ringan (logo + tagline), kanan form
- Password field ada toggle show/hide via Alpine
- Error auth tampil sebagai alert di atas form (bukan toast)
- Button disabled saat `$loading`
- Redirect setelah login:
  - admin → `/admin`
  - director → `/director/dashboard`
  - hod → `/hod/dashboard`
  - manager → `/manager/dashboard`

---

## ADMIN

### `admin.home-page`
**File:** `resources/views/livewire/admin/home-page.blade.php`

**Properties:**
- `$todayDate` — string (formatted)
- `$hasExceptions` — boolean
- `$hasPendingLeave` — boolean
- `$summaryCards` — array:
  - `exceptions_today` — int
  - `pending_leave` — int
  - `failed_notifications` — int
  - `active_users` — int
- `$pendingLeaveList` — collection (5 terbaru)
- `$failedNotifications` — collection (5 terbaru)

**Actions:**
- Tidak ada — halaman ini hanya display + navigasi

**UI Notes:**
- Summary cards: 2 kolom di mobile, 4 kolom di desktop
- `exceptions_today` pakai border kiri danger jika > 0
- `pending_leave` pakai border kiri warning jika > 0
- `failed_notifications` pakai border kiri danger jika > 0
- Pending list tampil sebagai list sederhana, tap untuk ke halaman detail
- Shortcut actions: grid 2x2 di mobile, row di desktop

---

### `admin.users-page`
**File:** `resources/views/livewire/admin/users-page.blade.php`

**Properties:**
- `$search` — string
- `$filterRole` — string
- `$filterDivision` — string
- `$filterStatus` — string
- `$users` — paginated collection
- `$roles` — array (untuk filter dropdown)
- `$divisions` — array (untuk filter dropdown)
- `$showAddModal` — boolean
- `$showImportModal` — boolean

**Actions:**
- `openAddModal()` — buka modal tambah user
- `openImportModal()` — buka modal bulk upload
- `archiveUser($id)` — arsipkan user (butuh confirmation)
- `deleteUser($id)` — hapus user (butuh confirmation, hanya jika non-aktif)
- `resetFilters()` — reset semua filter

**UI Notes:**
- Desktop: table + action kolom dengan dropdown kebab menu
- Mobile: card list + kebab menu
- Tombolt "Add User" dan "Bulk Upload" di header, sticky di mobile
- Filter mobile masuk bottom sheet
- Pagination di bawah table/list

---

### `admin.user-form-modal`
**File:** `resources/views/livewire/admin/user-form-modal.blade.php`

**Properties:**
- `$userId` — nullable (null = create, ada = edit)
- `$name`, `$email`, `$role`, `$division`, `$password`, `$isActive`
- `$availableDivisions` — berubah berdasarkan `$role` yang dipilih
- `$errors` — validation errors

**Actions:**
- `save()` — simpan user baru atau update

**UI Notes:**
- Modal centered, max-w-lg
- Role dipilih dulu, lalu division list update (via `updatedRole()`)
- Password field hanya wajib saat create; saat edit bisa kosong
- Submit button disabled saat loading

---

### `admin.user-import-modal`
**File:** `resources/views/livewire/admin/user-import-modal.blade.php`

**Properties:**
- `$file` — uploaded file
- `$previewData` — array setelah file diproses
- `$totalRows`, `$validRows`, `$invalidRows`
- `$showPreview` — boolean

**Actions:**
- `downloadTemplate()` — download file template
- `previewImport()` — proses file dan tampilkan preview
- `applyImport()` — import row valid saja

**UI Notes:**
- Step 1: upload area + download template link
- Step 2 (setelah preview): summary (total/valid/invalid) + preview table + invalid reasons
- Tombol "Terapkan Row Valid" hanya aktif jika ada `$validRows > 0`

---

### `admin.divisions-page`
**File:** `resources/views/livewire/admin/divisions-page.blade.php`

**Properties:**
- `$search` — string
- `$divisions` — paginated collection
- `$showFormModal` — boolean
- `$editingId` — nullable

**Actions:**
- `openCreate()`, `openEdit($id)`, `archive($id)`

**UI Notes:**
- Lebih sederhana dari users page
- Table desktop / card mobile
- Form modal: nama divisi + status aktif

---

### `admin.assignments-page`
**File:** `resources/views/livewire/admin/assignments-page.blade.php`

**Properties:**
- `$assignments` — collection (HoD dengan divisi yang diassign)
- `$hodUsers` — list HoD untuk dropdown
- `$availableDivisions` — list semua divisi
- `$showFormModal` — boolean

**Actions:**
- `openAdd()`, `openEdit($id)`, `delete($id)`

**UI Notes:**
- Multi-select untuk divisi — gunakan Alpine-based checkboxes dalam modal
- Tampilkan assigned divisions sebagai badge list di row/card

---

### `admin.report-settings-page`
**File:** `resources/views/livewire/admin/report-settings-page.blade.php`

**Properties:**
- `$planOpenTime`, `$planCloseTime`
- `$realizationOpenTime`, `$realizationCloseTime`
- `$currentSettings` — summary setting aktif
- `$hasWarning` — boolean (jam tidak logis)
- `$warningMessage` — string

**Actions:**
- `save()` — simpan setting baru

**UI Notes:**
- Halaman form tunggal, bukan table
- Gunakan input `type="time"` native
- Tampilkan current active settings di atas form sebagai info box
- Jika `$hasWarning`, tampilkan warning box sebelum tombol save
- Success feedback: toast

---

### `admin.leave-page`
**File:** `resources/views/livewire/admin/leave-page.blade.php`

**Properties:**
- `$filterDateFrom`, `$filterDateTo`, `$filterStatus`, `$filterDivision`, `$filterUser`, `$filterType`
- `$leaveRequests` — paginated collection
- `$summaryCount` — array (pending, approved, rejected)
- `$selectedLeave` — nullable (untuk detail panel)
- `$showDetailPanel` — boolean

**Actions:**
- `approve($id)`, `reject($id)` — butuh confirmation
- `openDetail($id)` — buka slide over panel

**UI Notes:**
- Summary count tampil sebagai 3 badge/chip di bawah header
- Detail panel: slide over dari kanan
- Panel berisi: info request + aksi approve/reject + audit trail

---

### `admin.override-page`
**File:** `resources/views/livewire/admin/override-page.blade.php`

**Properties:**
- `$filterUser`, `$filterDivision`, `$filterDate`, `$filterType`
- `$entries` — paginated collection
- `$selectedEntry` — nullable
- `$overrideReason` — string
- `$editValues` — array field yang diedit
- `$showOverridePanel` — boolean

**Actions:**
- `selectEntry($id)` — pilih entry untuk dioverride
- `saveOverride()` — simpan override (butuh confirmation)

**UI Notes:**
- Warning banner di atas halaman: "Semua perubahan override akan dicatat dalam log audit."
- Override panel: tampilkan original vs field edit berdampingan
- Audit section tampil setelah override berhasil disimpan
- Visual pembeda yang jelas antara nilai original (abu/muted) dan nilai baru (primary)

---

### `admin.notification-history-page`
**File:** `resources/views/livewire/admin/notification-history-page.blade.php`

**Properties:**
- `$filterDateFrom`, `$filterDateTo`, `$filterStatus`, `$filterSeverity`, `$filterDivision`
- `$notifications` — paginated collection
- `$selectedNotif` — nullable

**Actions:**
- `openDetail($id)` — tampilkan detail payload

**UI Notes:**
- Desktop: table dengan kolom Sent At, Channel, Status, Summary + tombol detail
- Mobile: expandable cards
- Status badge: Terkirim (success) atau Gagal (danger)
- Detail: payload summary + exceptions included + error message jika gagal

---

## DIRECTOR

### `director.dashboard-page`
**File:** `resources/views/livewire/director/dashboard-page.blade.php`

**Properties:**
- `$selectedDate` — date
- `$summaryCards` — array:
  - `company_health_score` — int (0–100)
  - `divisions_with_exceptions` — int
  - `major_findings_today` — int
  - `unresolved_recurring` — int
- `$attentionDivisions` — collection (divisi perlu perhatian)
- `$exceptionTrendData` — array (untuk chart)
- `$severityDistributionData` — array (untuk chart)
- `$divisionHealthData` — array (untuk chart)
- `$recentMajorFindings` — collection

**Actions:**
- `updatedSelectedDate()` — refresh semua data

**UI Notes:**
- Health score tampil besar dengan warna dinamis (≥70: success, 40–69: warning, <40: danger)
- Mobile: 1 chart terpenting saja (exception trend)
- Desktop: 2 chart berdampingan
- Attention list: tap ke halaman Director Divisions
- Quick access buttons di bawah: View Company, View Divisions, Open AI Chat

---

### `director.company-page`
**File:** `resources/views/livewire/director/company-page.blade.php`

**Properties:**
- `$filterDateFrom`, `$filterDateTo`
- `$summaryMetrics` — array
- `$healthTrendData`, `$complianceTrendData`, `$categoryDistributionData`
- `$recentFindings` — collection
- `$divisionContributions` — collection

---

### `director.divisions-page`
**File:** `resources/views/livewire/director/divisions-page.blade.php`

**Properties:**
- `$selectedDivision` — division id
- `$filterDateFrom`, `$filterDateTo`
- `$divisions` — list untuk selector
- `$summaryCards` — per division
- `$chartData` — array
- `$peopleWithFindings` — collection
- `$latestMajorFindings` — collection
- `$stagnantRoadmapItems` — collection
- `$selectedFinding` — nullable
- `$showDetailDrawer` — boolean

**Actions:**
- `selectFinding($id)` — buka detail drawer
- `updatedSelectedDivision()`, `updatedFilterDateFrom()`, etc.

**UI Notes:**
- Detail drawer berisi: Big Rock → Roadmap → Plan → Realization chain + triggered rules

---

### `director.finding-detail-panel`
**File:** `resources/views/livewire/director/finding-detail-panel.blade.php`

Shared detail panel untuk director. Tampilkan:
- Chain hierarchy: Big Rock > Roadmap > Plan > Realization
- Triggered rules list
- Timestamps (submitted at, window open/close)
- Attachments jika ada

---

### `director.ai-chat-page`
**File:** `resources/views/livewire/director/ai-chat-page.blade.php`

**Properties:**
- `$messages` — array chat history `[role, content, timestamp]`
- `$inputText` — string
- `$loading` — boolean
- `$suggestedPrompts` — array

**Actions:**
- `sendMessage()` — kirim pesan ke backend AI service
- `useSuggestedPrompt($prompt)` — isi input dengan prompt

**UI Notes:**
- Mobile: suggested prompts chips di atas, chat thread, input sticky bottom
- Desktop: left panel suggested prompts/history, right panel chat
- AI response tampil dengan `ai-response-block` component
- Disclaimer di bawah setiap AI response
- Input: textarea auto-resize, tombol kirim di kanan

---

## HOD

### `hod.dashboard-page`
**File:** `resources/views/livewire/hod/dashboard-page.blade.php`

**Properties:**
- `$planStatus` — string (submitted/missing/late)
- `$realizationStatus` — string
- `$personalFindings` — collection (3 terbaru)
- `$divisionFindingsSummary` — array
- `$activeBigRocks` — collection
- `$managersNeedingAttention` — collection

**Actions:**
- Quick navigation actions (tidak ada logika, hanya link/redirect)

**UI Notes:**
- Summary cards 2x2 di mobile, 4 in row di desktop
- Plan/Realization status card pakai warna sesuai status
- CTA utama: "Isi Plan" atau "Lanjutkan Plan" tergantung status
- Managers needing attention: list sederhana nama + badge findings

---

### `hod.daily-entry-page`
**File:** `resources/views/livewire/hod/daily-entry-page.blade.php`

**Properties:**
- `$todayDate` — string
- `$planWindowOpen` — boolean
- `$realizationWindowOpen` — boolean
- `$planWindowInfo` — string (jam buka–tutup)
- `$realizationWindowInfo` — string
- `$activeTab` — string ('plan' | 'realization')
- `$planItems` — array of plan items hari ini
- `$activeBigRocks` — collection
- `$roadmapItems` — collection (berubah berdasarkan big rock)
- `$realizationItems` — array (plan yang bisa diisi realization)
- `$newPlanItem` — object (form state untuk item baru)
- `$editingPlanId` — nullable

**Actions:**
- `switchTab($tab)`
- `addPlanItem()` — tambah plan baru
- `savePlanItem()` — simpan plan
- `removePlanItem($id)` — hapus plan
- `updatedNewPlanItemBigRockId()` — update roadmap list
- `saveRealization($planId)` — simpan realization
- `submitAllPlan()` — submit semua plan

**UI Notes:**
- INI HALAMAN PALING PENTING — harus paling sederhana
- Tabs Plan / Realization jelas di atas
- Window status info selalu tampil: "Plan terbuka hingga 17:00" atau "Plan sudah ditutup"
- Jika window tutup: form disabled + pesan informatif
- Hierarchy Big Rock > Roadmap > Plan tampil secara visual dalam form
- Bisa add multiple plan items
- Realization tab: tampilkan list plan hari ini, tap untuk isi realization
- Status realization (Finished/In Progress/Blocked) dengan warna
- Jika Blocked atau In Progress: kolom Alasan wajib

---

### `hod.history-page`
**File:** `resources/views/livewire/hod/history-page.blade.php`

**Properties:**
- `$filterDateFrom`, `$filterDateTo`, `$filterType`, `$filterStatus`, `$filterSeverity`, `$filterBigRock`
- `$entries` — paginated, grouped by date
- `$bigRocks` — untuk filter dropdown
- `$selectedEntry` — nullable
- `$showDetailDrawer` — boolean

**Actions:**
- `openDetail($id)`

**UI Notes:**
- Grouped by date (timeline style)
- Per entry: Big Rock badge, status badge, severity badge jika ada finding
- Detail drawer: full chain + findings + timestamps

---

### `hod.big-rock-page`
**File:** `resources/views/livewire/hod/big-rock-page.blade.php`

**Properties:**
- `$bigRocks` — paginated collection
- `$filterStatus`, `$filterPeriod`
- `$selectedBigRock` — nullable (untuk detail/manage)
- `$showFormModal` — boolean
- `$showRoadmapPanel` — boolean
- `$editingBigRockId` — nullable

**Actions:**
- `openCreate()`, `openEdit($id)`, `openRoadmapManager($id)`
- `archive($id)`, `saveBigRock()`

**UI Notes:**
- Big Rock list: card per item dengan info ringkas + roadmap count badge
- Roadmap panel: slide over dari kanan
- Dalam roadmap panel: list roadmap items + add/edit/reorder inline
- Bulk upload roadmap: tombol + modal upload dengan template download

---

### `hod.big-rock-form-modal`
**File:** `resources/views/livewire/hod/big-rock-form-modal.blade.php`

**Fields:** title, description, period (start–end), status

---

### `hod.roadmap-manager-panel`
**File:** `resources/views/livewire/hod/roadmap-manager-panel.blade.php`

**Properties:**
- `$bigRockId` — int
- `$bigRockTitle` — string
- `$roadmapItems` — collection
- `$showAddForm` — boolean
- `$newItem` — form state

**Actions:**
- `addItem()`, `editItem($id)`, `saveItem()`, `archiveItem($id)`

**UI Notes:**
- Panel slide over
- List roadmap items dengan status badge
- Add form inline di bawah list (bukan modal dalam modal)
- Sequence/order tampil sebagai angka kecil di kiri

---

### `hod.division-entries-page`
**File:** `resources/views/livewire/hod/division-entries-page.blade.php`

**Properties:**
- `$selectedDivision` — nullable (jika multi divisi)
- `$filterDate`, `$filterUser`, `$filterStatus`, `$showFindingsOnly`
- `$entries` — paginated collection
- `$users` — untuk filter dropdown
- `$selectedEntry` — nullable
- `$showDetailDrawer` — boolean

**Actions:**
- `openDetail($id)`, `toggleFindingsOnly()`

**UI Notes:**
- Toggle "Hanya Tampilkan Temuan" — prominent di atas list
- Mobile: accordion cards
- Desktop: table dengan expand row atau side drawer
- Findings badge merah jika ada major/medium

---

### `hod.division-summary-page`
**File:** `resources/views/livewire/hod/division-summary-page.blade.php`

**Properties:**
- `$selectedDivision`
- `$filterDateFrom`, `$filterDateTo`
- `$summaryCards` — array
- `$exceptionTrendData`, `$complianceTrendData`, `$roadmapActivityData`
- `$managersNeedingAttention` — collection
- `$repeatingGenericEntries` — collection
- `$delayedRealizations` — collection

---

### `hod.ai-chat-page`
**File:** `resources/views/livewire/hod/ai-chat-page.blade.php`

Sama persis dengan `director.ai-chat-page`, tapi:
- Scope data hanya divisi yang dipegang HoD
- Suggested prompts berbeda:
  - "Manager mana paling sering missing minggu ini"
  - "Roadmap item mana yang tidak bergerak"
  - "Ringkas exception divisi saya hari ini"
  - "Siapa yang paling sering entry generik"

---

## MANAGER

### `manager.dashboard-page`
**File:** `resources/views/livewire/manager/dashboard-page.blade.php`

**Properties:**
- `$planStatus`, `$realizationStatus`
- `$activeBigRocksCount` — int
- `$recentFindings` — collection
- `$todayReportingStatus` — array
- `$activeRoadmapItems` — collection (3 terbaru)
- `$recentHistory` — collection (5 terbaru)

**UI Notes:**
- Lebih simpel dari HoD dashboard
- Primary CTA sangat jelas: "Isi Plan" atau "Isi Realization"
- Recent history sebagai timeline list kecil

---

### `manager.daily-entry-page`
**File:** `resources/views/livewire/manager/daily-entry-page.blade.php`

**Identik dengan `hod.daily-entry-page`.**
Gunakan komponen dan layout yang persis sama. Flow identik agar training ringan.

---

### `manager.history-page`
**File:** `resources/views/livewire/manager/history-page.blade.php`

**Identik dengan `hod.history-page`** tapi scope hanya data personal manager.

---

### `manager.big-rock-page`
**File:** `resources/views/livewire/manager/big-rock-page.blade.php`

**Identik dengan `hod.big-rock-page`** untuk saat ini.
Jika nanti Big Rock hanya bisa dibuat HoD ke atas, ubah halaman ini menjadi view-only (tanpa tombol Add/Edit).

---

## LAYOUTS

### `layouts/app.blade.php`
Layout utama semua halaman authenticated.

**Struktur:**
```
<body>
  <!-- Sidebar (desktop: fixed, mobile: drawer) -->
  <aside id="sidebar">...</aside>

  <!-- Main -->
  <div class="main-content">
    <!-- Topbar mobile (hamburger + page title) -->
    <header class="topbar md:hidden">...</header>

    <!-- Content -->
    <main>
      {{ $slot }}
    </main>
  </div>

  <!-- Toast container -->
  <x-ui.toast />

  <!-- Alpine + Livewire scripts -->
</body>
```

**UI Notes:**
- Sidebar mobile: `fixed inset-0 z-30`, drawer dari kiri, overlay di kanan
- Hamburger toggle pakai Alpine `x-data="{ sidebarOpen: false }"`
- Sidebar content berbeda per role — gunakan `@auth` + `@if(auth()->user()->role === 'admin')` dll
- User area di bawah sidebar selalu tampil

### `layouts/auth.blade.php`
Layout untuk halaman login saja. Tidak ada sidebar.