# Requirements and Setup

Dokumen ini berisi kebutuhan dasar agar project bisa dijalankan di mesin anggota tim lain.

## Minimum Requirement

- PHP 8.3
- Composer
- Node.js dan npm
- MySQL
- Ekstensi PHP umum untuk Laravel, seperti `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, dan `curl`

## Dependency Project

### Backend

- `laravel/framework`
- `laravel/tinker`
- `midtrans/midtrans-php`

### Development

- `fakerphp/faker`
- `laravel/pail`
- `laravel/pao`
- `laravel/pint`
- `mockery/mockery`
- `nunomaduro/collision`
- `pestphp/pest`
- `pestphp/pest-plugin-laravel`

### Frontend

- `vite`
- `laravel-vite-plugin`
- `tailwindcss`
- `@tailwindcss/vite`
- `concurrently`

## Instalasi

```bash
composer install
npm install
```

## Konfigurasi Environment

1. Salin `.env.example` menjadi `.env`.
2. Isi konfigurasi database di `.env`.
3. Jalankan generate key:

```bash
php artisan key:generate
```

## Database Default

Contoh default pada `.env.example`:

- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=bact_event_system`
- `DB_USERNAME=root`
- `DB_PASSWORD=`

## Jalankan Migrasi dan Seeder

```bash
php artisan migrate --seed
```

## Build Frontend

```bash
npm run build
```

Untuk development dengan hot reload:

```bash
npm run dev
```

## Menjalankan Aplikasi

```bash
php artisan serve
```

Atau jalankan stack development gabungan:

```bash
composer run dev
```

## Catatan Tim

- Pastikan `.env` tidak di-commit ke GitHub.
- Jika ada service pihak ketiga baru, tambahkan variabelnya ke `.env.example` dan update dokumen ini.