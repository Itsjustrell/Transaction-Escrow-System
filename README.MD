# Transaction Escrow System  
Final Project UAS — Laravel & MySQL  

---

## 📌 Project Overview
Transaction Escrow System adalah aplikasi website berbasis **Laravel** yang mensimulasikan sistem **escrow** sebagai penengah transaksi antara **buyer** dan **seller**. Dana bersifat **simulasi** dan ditahan oleh sistem hingga kondisi transaksi terpenuhi.

Project ini dirancang **bukan sebagai CRUD biasa**, melainkan sebagai **state-based system (state machine)** yang menekankan:
- keadilan transaksi,
- kontrol sistem,
- auditability,
- dan pemisahan peran yang jelas.

Fokus utama project adalah **logika sistem transaksi**, bukan integrasi pembayaran nyata atau blockchain.

---

## 🧠 Core Concept
- Escrow berbasis **state machine**
- Perubahan status dibatasi oleh **aturan & role**
- Dana bersifat simulasi (tanpa payment gateway)
- Semua aksi penting tercatat (audit trail)
- Mendukung **keputusan otomatis oleh sistem (scheduler)**

---
## How to Run This Project
[Klik disini untuk menjalankan project](#how-to-run-this-project-local-development)

---

## 🗺️ Development Roadmap

### PHASE 0 — Project Initialization
Fondasi agar aplikasi dapat berjalan dengan stabil.

- [x] Install Laravel
- [x] Setup environment (`.env`)
- [x] Konfigurasi database MySQL
- [x] Install authentication (Laravel Breeze)
- [x] Test server (`php artisan serve`)
- [x] Setup frontend assets (Vite)

**Status:** ✅ Selesai

---

### PHASE 1 — Role & Access Control (RBAC)
Menentukan **siapa boleh melakukan apa** di dalam sistem.

- [x] Migration `roles`
- [x] Pivot table `role_user`
- [x] Model `Role`
- [x] Relasi User ↔ Role (many-to-many)
- [x] Seeder role:
  - buyer
  - seller
  - admin
  - arbiter
- [x] Middleware role-based access

**Status:** ✅ Selesai

---

### PHASE 2 — Escrow Database Design (ERD → Migration)
Membangun struktur data escrow yang relasional dan konsisten.

- [x] Migration `escrows`
- [x] Migration `escrow_participants`
- [x] Migration `escrow_transactions`
- [x] Migration `escrow_status_logs`
- [x] Migration `escrow_disputes`
- [x] Migration `dispute_evidences`
- [x] Model & relasi Eloquent

**Status:** ✅ Selesai

---

### PHASE 3 — Escrow State Machine
Mengubah sistem dari CRUD menjadi **rule-based system**.

- [x] Definisi status escrow (`EscrowStatus`)
- [x] Definisi aturan transisi status
- [x] Validasi transisi status (`canTransition`)
- [x] Validasi role + transisi (`canTransitionByRole`)

**Status:** ✅ Selesai

---

### PHASE 4 — Escrow Action Controller (Manual Flow)
Implementasi alur escrow normal berbasis aturan.

- [x] Buyer: `created → funded`
- [x] Seller: `funded → shipping`
- [x] Seller: `shipping → delivered`
  - set `delivered_at`
  - hitung `confirm_deadline`
- [x] Buyer: `delivered → released`
- [x] Status log & transaction log (audit trail)
- [x] Atomic transaction (`DB::transaction`)

**Status:** ✅ Selesai

---

### PHASE 5 — System Automation (Auto-Release)
Keputusan otomatis oleh sistem berbasis waktu.

- [x] Command `escrow:auto-release`
- [x] Auto transisi `delivered → released`
- [x] Scheduler di `Kernel.php`
- [x] System dianggap sebagai aktor (audit-safe)

**Catatan Akademik:**  
Auto-release dijalankan sebagai **simulasi background processing** untuk memenuhi kebutuhan UAS, bukan sistem finansial nyata.

**Status:** ✅ Selesai

---

### PHASE 6 — Dispute Flow & Arbitration
Mekanisme penyelesaian sengketa secara adil dan terstruktur.

**Checklist:**
- [x] Buyer: `delivered → disputed`
- [x] Create `escrow_disputes` record
- [x] Upload bukti dispute (file upload)
- [x] Arbiter review dispute & evidence
- [x] Arbiter: `disputed → released` atau `disputed → refunded`
- [x] Catat keputusan arbiter (status log & transaction log)

**Status:** ✅ Selesai

---

### PHASE 7 — State Guard Middleware
Validasi state escrow sebelum controller dijalankan.

**Checklist:**
- [ ] Middleware `EnsureEscrowState`
- [ ] Validasi state di level route
- [ ] Kombinasi dengan role middleware

**Status:** ⏳ Belum

---

### PHASE 8 — Seeder Dummy Data (Demo Ready)
Menyiapkan data percobaan untuk demo dan presentasi.

**Checklist:**
- [ ] Seeder user (buyer, seller, admin, arbiter)
- [ ] Seeder escrow dummy
- [ ] Seeder escrow participants
- [ ] Seeder status logs
- [ ] Seeder transactions
- [ ] Total data ≥ 15 record

**Status:** ⏳ Belum

---

### PHASE 9 — UI Logic & Role-Based View
Menghubungkan core logic dengan antarmuka pengguna.

**Checklist:**
- [ ] Dashboard Buyer
- [ ] Dashboard Seller
- [ ] Dashboard Admin
- [ ] Dashboard Arbiter
- [ ] Button muncul sesuai role & state
- [ ] Pagination, search, modal action

**Status:** ⏳ Belum

---

### PHASE 10 — Admin Monitoring & Reporting
Admin berperan sebagai pengawas sistem (read-only).

**Checklist:**
- [ ] List semua escrow
- [ ] Filter berdasarkan status & tanggal
- [ ] View histori status & transaksi
- [ ] Admin tidak memiliki hak ubah status escrow

**Status:** ⏳ Belum

---

### PHASE 11 — Final Polishing & Presentation Prep
Tahap akhir sebelum UAS.

**Checklist:**
- [ ] Error handling & flash message
- [ ] UI consistency (header, footer, layout)
- [ ] README final
- [ ] Demo flow rehearsal
- [ ] Mapping fitur → rubrik UAS

**Status:** ⏳ Belum

---

## 🧩 Escrow Lifecycle Summary

```text
created
  ↓ (buyer fund)
funded
  ↓ (seller ship)
shipping
  ↓ (seller deliver)
delivered
  ↓ (buyer release / system auto-release)
released
```


---

## How to Run This Project (Local Development)

Panduan ini menjelaskan langkah-langkah untuk menjalankan **Transaction Escrow System** di environment lokal setelah repository di-clone dari GitHub.

> ⚠️ Catatan  
> File `.env`, `vendor/`, dan `node_modules/` **tidak disertakan di repository** karena bersifat environment-specific dan dapat direkonstruksi ulang.

---

### 📋 Prerequisites
Pastikan environment Anda sudah memiliki:
- PHP ≥ 8.1
- Composer
- Node.js & NPM
- MySQL
- Git

---

### 1️⃣ Clone Repository
```bash
git clone https://github.com/Itsjustrell/Transaction-Escrow-System.git
cd Transaction-Escrow-System
```

### 2️⃣ Install PHP Dependencies
```bash
composer install
```
Ini akan meng-generate folder vendor/ berdasarkan composer.json

### 3️⃣ Setup Environment File
Salin file environment contoh:
```bash
cp .env.example .env
```
Generate application key:
```bash
php artisan key:generate
``` 

### 4️⃣ Konfigurasi Database
Edit file .env dan sesuaikan konfigurasi database:
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=escrow_system
DB_USERNAME=root
DB_PASSWORD=
```
Buat database kosong di MySQL:
```bash
CREATE DATABASE escrow_system;
```

### 5️⃣ Migrasi & Seeder Database
Jalankan migration dan seeder:
```bash
php artisan migrate --seed
```
Perintah ini akan:
Membuat seluruh tabel database
Mengisi data awal (role, user, dll)

### 6️⃣ Install Frontend Dependencies
```bash
npm install
```
Ini akan meng-generate folder node_modules/.

7️⃣ Jalankan Frontend Asset Builder
```bash
npm run dev
```
Digunakan untuk:
Compile Tailwind CSS
Menjalankan Vite (hot reload)

8️⃣ Jalankan Laravel Development Server
```bash
php artisan serve
```
Akses aplikasi melalui browser:
http://127.0.0.1:8000
