# Join Guide for Team Members

Dokumen ini ditujukan untuk teman satu tim yang ingin ikut mengerjakan project ini.

## 1. Clone Project

```bash
git clone <url-repository-github>
cd BACT2027-main
```

## 2. Install Dependency

```bash
composer install
npm install
```

## 3. Siapkan Environment

```bash
copy .env.example .env
```

Lalu sesuaikan isi `.env` dengan database lokal masing-masing.

## 4. Generate Key dan Database

```bash
php artisan key:generate
php artisan migrate --seed
```

## 5. Jalankan Project

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

Kalau ingin menjalankan semuanya sekaligus:

```bash
composer run dev
```

## 6. Alur Kerja Git

```bash
git checkout main
git pull origin main
git checkout -b nama-fitur
```

Setelah selesai, push branch lalu buat pull request ke `main`.

## 7. Catatan Penting

- Jangan commit file `.env`.
- Kalau database berubah, jalankan ulang migrasi dan seeder sesuai kebutuhan.
- Jika ada perubahan asset frontend, jalankan kembali `npm run dev` atau `npm run build`.