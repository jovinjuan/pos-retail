# POS Retail

Aplikasi Point of Sale (POS) berbasis web untuk toko retail, dibangun dengan Laravel, Filament v3, dan PostgreSQL. Sistem ini mencakup manajemen produk & inventori, transaksi penjualan (kasir), dan laporan analitik — semuanya dapat diakses oleh satu akun Owner melalui panel admin Filament.j

## Build Status 
[![Automated Tests](https://github.com/jovinjuan/pos-retail/actions/workflows/tests.yml/badge.svg)](https://github.com/jovinjuan/pos-retail/actions/workflows/tests.yml)

## Test Coverage 
[![codecov](https://codecov.io/github/jovinjuan/pos-retail/branch/main/graph/badge.svg?token=RFJSBJWVYV)](https://codecov.io/github/jovinjuan/pos-retail)

## Tech Stack

- **Backend**: Laravel 12
- **Admin Panel / UI**: Filament v3
- **Database**: PostgreSQL 16
- **Auth**: Filament built-in authentication
- **Containerization**: Docker + Nginx + PHP-FPM

---

## Cara Menjalankan Aplikasi

### Menggunakan Docker (Direkomendasikan)

**Prasyarat**: Docker Desktop terinstall dan berjalan.

```bash
# 1. Clone repo dan masuk ke direktori
cd pos-retail

# 2. Build dan jalankan container
docker-compose up -d --build

# 3. Jalankan migrasi dan seeder
docker-compose exec app php artisan migrate --seed
```

Akses aplikasi di: `http://localhost:8080/admin`

**Akun default Owner:**
- Email: `owner@pos.local`
- Password: `password`

### Tanpa Docker (Local)

**Prasyarat**: PHP 8.3+, PostgreSQL, Composer, Node.js.

```bash
# 1. Install dependencies
composer install
npm install && npm run build

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Konfigurasi database di .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_DATABASE=pos
# DB_USERNAME=postgres
# DB_PASSWORD=your_password

# 4. Migrasi dan seeder
php artisan migrate --seed

# 5. Jalankan server
php artisan serve
```

Akses aplikasi di: `http://localhost:8000/admin`

---

## Cara Menjalankan Test

### Semua Test

```bash
php artisan test
```

### Per Suite

```bash
# Unit tests saja
php artisan test --testsuite=Unit

# Integration tests saja
php artisan test --testsuite=Integration
```

### Dengan Coverage Report

```bash
php artisan test --testsuite=Integration --coverage-text
```

> Coverage membutuhkan ekstensi PCOV atau Xdebug. Pastikan sudah terinstall di PHP kamu.

---

## Strategi Pengujian

Pengujian menggunakan dua level yang saling melengkapi:

### Unit Tests (`tests/Unit/`)

Menguji logika bisnis murni di Service class dan Model **tanpa menyentuh database**. Setiap test berjalan secara terisolasi menggunakan mock atau objek sederhana.

| File | Yang Diuji |
|---|---|
| `Unit/Services/TransactionServiceTest` | Kalkulasi subtotal, total, kembalian, format invoice |
| `Unit/Services/InventoryServiceTest` | Validasi stok negatif |
| `Unit/Services/ReportServiceTest` | Kalkulasi rata-rata transaksi |
| `Unit/Models/ProductTest` | Scope `active` dan `belowMinStock` |

**Target**: 30 test cases, tidak ada dependency ke database.

### Integration Tests (`tests/Integration/`)

Menguji interaksi antar komponen dengan database PostgreSQL nyata. Menggunakan `RefreshDatabase` trait untuk memastikan setiap test dimulai dari state bersih.

| File | Yang Diuji |
|---|---|
| `Integration/Services/TransactionServiceTest` | Checkout flow, pengurangan stok, cancel transaksi |
| `Integration/Services/InventoryServiceTest` | Penyesuaian stok, validasi stok negatif di DB |
| `Integration/Services/ReportServiceTest` | Query laporan harian, top produk, distribusi pembayaran |
| `Integration/Pages/KasirPageTest` | Pencarian produk aktif, kalkulasi cart, kembalian |

**Target**: 15 test cases, coverage ≥ 80% pada `app/Services` dan `app/Models`.

### CI/CD

GitHub Actions otomatis menjalankan semua test pada setiap push ke branch `main` dan `develop`. Workflow tersedia di `.github/workflows/tests.yml`.

