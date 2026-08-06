# Join Guide for Team Members

Panduan ini dibuat supaya teman satu tim bisa langsung ikut kerja tanpa bingung urutan setup.

## 0. Pastikan Tools Sudah Terpasang

Sebelum mulai, pastikan perangkat punya:

- PHP 8.3
- Composer
- Node.js dan npm
- MySQL
- Git

Kalau di Windows dan memakai Laragon, pastikan Apache/Nginx dan MySQL sudah aktif.

## 1. Clone Repository

Jalankan command ini di terminal:

```bash
git clone <url-repository-github>
cd BACT2027-main
```

Kalau folder project ingin memakai nama lain, boleh ubah bagian `cd` sesuai nama folder hasil clone.

## 2. Install Dependency PHP

```bash
composer install
```

Command ini akan mengunduh package backend yang ada di `composer.json`.

Jika muncul error karena versi PHP, cek dulu versi PHP aktif dengan:

```bash
php -v
```

## 3. Install Dependency Frontend

```bash
npm install
```

Ini akan memasang Vite, Tailwind, dan dependency frontend lain.

Kalau ada error node module, biasanya solusi awal adalah hapus `node_modules` lalu jalankan `npm install` ulang.

## 4. Siapkan File Environment

Salin file contoh environment:

```bash
copy .env.example .env
```

Kalau memakai macOS atau Linux, command setaranya:

```bash
cp .env.example .env
```

Lalu buka file `.env` dan sesuaikan bagian penting berikut:

- `APP_NAME`
- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

Contoh yang dipakai project ini:

- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=bact_event_system`
- `DB_USERNAME=root`
- `DB_PASSWORD=`

## 5. Generate App Key

```bash
php artisan key:generate
```

Command ini wajib dijalankan supaya Laravel punya application key untuk enkripsi session dan data aplikasi.

## 6. Buat Database Lokal

Buat database kosong di MySQL dengan nama yang sama seperti `DB_DATABASE` di `.env`.

Kalau pakai phpMyAdmin, langkahnya:

1. Buka phpMyAdmin.
2. Buat database baru bernama `bact_event_system`.
3. Pastikan user MySQL punya akses ke database tersebut.

## 7. Jalankan Migrasi dan Seeder

```bash
php artisan migrate --seed
```

Command ini akan membuat tabel database dan mengisi data awal yang diperlukan project.

Kalau ingin reset total lalu isi ulang data, gunakan:

```bash
php artisan migrate:fresh --seed
```

## 8. Jalankan Frontend

Untuk mode development dengan hot reload:

```bash
npm run dev
```

Kalau ingin build asset sekali saja:

```bash
npm run build
```

## 9. Jalankan Backend Laravel

Di terminal lain, jalankan:

```bash
php artisan serve
```

Biasanya aplikasi akan bisa diakses di:

```bash
http://127.0.0.1:8000
```

## 10. Jalankan Keduanya Sekaligus

Kalau ingin backend dan frontend berjalan bersama, gunakan:

```bash
composer run dev
```

Command ini akan menjalankan server Laravel, queue listener, dan Vite bersamaan.

## 11. Alur Kerja Git Untuk Tim

Sebelum mulai kerja, sinkronkan branch main dulu:

```bash
git checkout main
git pull origin main
```

Lalu buat branch baru untuk fitur atau perbaikan:

```bash
git checkout -b nama-fitur
```

Contoh:

```bash
git checkout -b fitur-login
```

Setelah selesai kerja:

```bash
git add .
git commit -m "Nama perubahan"
git push origin nama-fitur
```

Setelah itu buat pull request ke branch `main`.

## 12. Troubleshooting Singkat

- Kalau `php artisan` gagal, cek dulu `vendor` sudah terpasang lewat `composer install`.
- Kalau halaman kosong atau asset tidak muncul, pastikan `npm run dev` atau `npm run build` sudah dijalankan.
- Kalau error database, cek `.env`, nama database, username, password, dan status service MySQL.
- Kalau perubahan config tidak terbaca, jalankan:

```bash
php artisan optimize:clear
```

## 13. Hal Yang Jangan Dilakukan

- Jangan commit file `.env`.
- Jangan commit folder `vendor` dan `node_modules`.
- Jangan mengubah branch `main` langsung tanpa branch kerja jika sedang kerja tim.