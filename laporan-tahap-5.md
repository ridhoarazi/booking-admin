# Laporan Tahap 5 — Backend Booking Lapangan (booking-admin)

## Status: SELESAI, semua uji lolos (30 tests, 103 assertions)

## Yang dibangun (Tahap 5 — Web Admin Blade)

Satu project Laravel 13 untuk REST API (Sanctum) + Web Admin (session Blade), MySQL `booking-admin`.

**Views** (resources/views/admin):
- layouts/admin.blade.php — shell + sidebar + flash + CSRF meta
- auth/login.blade.php — form login admin, validasi, alert error
- errors/403.blade.php — halaman ditolak (non-admin)
- dashboard.blade.php — statistik lapangan/kategori/booking pending/revenue + booking terbaru
- categories/{index,create,edit}.blade.php — CRUD kategori
- fields/{index,create,edit}.blade.php — CRUD lapangan + upload foto (storage public)
- schedules/{index,create}.blade.php — jadwal operasional per lapangan
- bookings/index.blade.php — daftar booking, filter status/tanggal/q, aksi Konfirmasi/Batalkan
- public/css/admin.css — gaya shell, sidebar, kartu, tombol, badge

**Controllers & Actions** (baru):
- Auth/LoginController — attempt login, cek isAdmin (user biasa ditolak), regenerate session, logout
- Admin/DashboardController — stats + booking terbaru
- Admin/CategoryController — resource CRUD (kecuali show)
- Admin/FieldController — CRUD + upload foto storage public disk
- Admin/ScheduleController — index/create/store + generate slot
- Admin/BookingController — index dengan filter status/q/date
- App\Actions\GenerateFieldScheduleSlots — generate slot 60 menit dari open_time→close_time (firstOrCreate)

**Requests** (baru): Admin/{Category,Field,Schedule}Request — validasi + authorize isAdmin

**Rute web** (routes/web.php):
- / → redirect admin.dashboard
- login (guest, throttle 5,1), logout (auth)
- grup admin (auth, admin): prefix /admin — dashboard, categories (resource), fields (resource), fields/{field}/schedules (nested), bookings (index + confirm/cancel PATCH)

**Perbaikan views setelah smoke test live:**
- Enum BookingStatus di Blade dipakai pakai `->value` (error "Object of class BookingStatus could not be converted to string")
- `number_format($stats['revenue'] ?? 0)` agar tidak error saat kosong
- `Str::limit` dipanggil dengan FQN di Blade

## API (Tahap 4, tetap utuh)
- POST /api/v1/register (role=user, phone nullable), /api/v1/login, /api/v1/logout, /api/v1/me (Sanctum)
- GET/POST bookings, PATCH cancel (hanya Pending; Confirmed → 409), unique index field_slot_id anti double-booking di DB
- categories, fields, fields/{id}/schedules publik

## Uji
- `php artisan test` → 30 passed, 103 assertions (19 API + 11 admin web)
- Smoke test HTTP nyata: login admin 200, dashboard/categories/fields/bookings/schedules 200, non-admin → 403, guest → redirect login

## Akun uji
- admin@booking.test (admin), user@booking.test (user), DB root tanpa password

## Sisa / catatan
- Confirm booking dari admin belum ada route terpisah di routes/web.php (BookingController hanya index). Tambahkan PATCH route confirm/cancel jika diperlukan admin untuk aksi konfirmasi.
- Flutter app (C:\Project_PBM\flutter-booking-lapangan) tidak diubah perjanjian tim.
