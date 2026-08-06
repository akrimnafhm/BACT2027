# BACT 2027 Main

Aplikasi web Laravel untuk project BACT 2027.

## Requirement

Lihat panduan lengkap di [REQUIREMENTS.md](REQUIREMENTS.md).

## Setup Cepat

1. Clone repository ini.
2. Jalankan `composer install`.
3. Jalankan `npm install`.
4. Salin `.env.example` menjadi `.env` lalu sesuaikan konfigurasi database.
5. Generate application key dengan `php artisan key:generate`.
6. Jalankan migrasi dan seeder dengan `php artisan migrate --seed`.
7. Build asset frontend dengan `npm run build` atau gunakan `npm run dev` saat development.

## Menjalankan Project

Untuk development lokal:

```bash
php artisan serve
```

Jika ingin menjalankan backend dan frontend bersamaan, gunakan script bawaan:

```bash
composer run dev
```

## Konfigurasi Penting

- Database default di `.env.example` memakai MySQL.
- Session, queue, dan cache menggunakan driver database.
- Integrasi pembayaran memakai package `midtrans/midtrans-php`.

## GitHub Collaboration

Setelah dokumen ini selesai, tambahkan project ke repository GitHub agar tim bisa clone dan bekerja bersama. Alur umum yang bisa dipakai:

```bash
git init
git add .
git commit -m "Initial project setup"
git branch -M main
git remote add origin <url-repository-github>
git push -u origin main
```

## Cara Join untuk Tim

Lihat panduan detail di [JOIN.md](JOIN.md).

## Catatan

File ini dibuat sebagai panduan ringkas. Jika ada perubahan environment atau dependency baru, update juga [REQUIREMENTS.md](REQUIREMENTS.md).
