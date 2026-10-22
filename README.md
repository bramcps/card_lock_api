# card_lock_api

REST API untuk sistem kunci pintu berbasis kartu **RFID**: mengelola pintu, kartu, pengguna, dan mencatat tiap akses. Ini backend-nya; dashboard web-nya ada di [**card_lock_app**](https://github.com/bramcps/card_lock_app).

Dibangun dengan **Laravel 12** dan autentikasi token lewat **Sanctum**, dengan pemisahan peran (admin vs pengguna biasa).

## Yang bisa dilakukan

- **Autentikasi**: sign in / sign out / refresh token (Sanctum)
- **Pintu**: daftar, detail, riwayat, ubah status (buka/kunci), CRUD khusus admin
- **Kartu RFID**: daftar dan kelola kartu, kaitkan ke pengguna (CRUD admin)
- **Log akses**: riwayat tiap kartu dipakai, plus endpoint statistik
- **Deteksi gerakan**: endpoint untuk perangkat mengirim event `movement`
- **Alert**: peringatan untuk kejadian yang perlu perhatian
- **Pengguna & izin**: kelola pengguna, aktif/nonaktif, dan hak akses (admin)

## Endpoint utama

```
POST   /api/signin
POST   /api/signout            (auth)
GET    /api/user               (auth)
GET    /api/doors              /api/doors/{id}  /api/doors/{id}/history
GET    /api/rfid_cards         /api/rfid_cards/{id}
GET    /api/access_logs        /api/access_logs/statistics
GET    /api/alerts
POST   /api/movement           (perangkat mencatat gerakan)
GET    /api/door_status        /api/door/{id}/status

# admin
apiResource /api/users         + activate / deactivate
POST/PUT/DELETE /api/doors, /api/rfid_cards, /api/permissions
```

## Skema data

Migrasi menyiapkan tabel: `users`, `rfid_cards`, `doors`, `door_statuses`, `access_logs`, `movement_detections`, `alerts`, `user_permissions`.

## Menjalankan

Butuh PHP 8.2+, Composer, dan MySQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
# atur DB_* di .env
php artisan migrate --seed      # membuat tabel + user admin awal
php artisan serve               # http://localhost:8000
```

Semua endpoint di bawah `/api`. Setelah `signin`, kirim token di header `Authorization: Bearer <token>`.

## Stack

Laravel 12 · Laravel Sanctum · MySQL · PHP 8.2
