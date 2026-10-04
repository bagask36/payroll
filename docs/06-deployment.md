# Dokumentasi Deployment Docker SIM SDM RS Juwita

Dokumen ini memuat panduan deployment containerized pada Server Pribadi (VPS / Dedicated Server on-premise) menggunakan Docker & Docker Compose.

---

## 1. Topologi Container

Sesuai arsitektur sistem (`/docs/02-architecture.md` Section 17), sistem dipecah menjadi service-service terisolasi:

1. **`web` (Nginx:alpine)**:
   - Reverse proxy FastCGI ke service `app`.
   - Melayani static asset Vite (`public/build`) dan file storage yang diunggah.
   - Mengatur header keamanan, gzip kompresi, dan batasan ukuran upload (64MB).
   - Port eksternal default: `80` (dapat diarahkan ke `443` menggunakan reverse proxy SSL seperti Certbot/Nginx host/Cloudflare).

2. **`app` (PHP-FPM 8.4 Alpine)**:
   - Monolith Laravel 13, Livewire 4, Flux UI.
   - Menjalankan core logika aplikasi, kalkulasi payroll, absensi, dan otorisasi.
   - Terkoneksi ke PostgreSQL dan Redis melalui internal bridge network (`payroll_network`).

3. **`queue` (Laravel Queue Worker)**:
   - Menjalankan worker `php artisan queue:work` untuk background jobs (sinkronisasi dokumen, perhitungan berkas, audit log async).

4. **`scheduler` (Laravel Scheduler)**:
   - Menjalankan cron internal `php artisan schedule:work` untuk recurring tasks (generate slip gaji bulanan, rekap absensi, dsb).

5. **`postgres` (PostgreSQL 16 Alpine)**:
   - Relational Database utama.
   - Volume persisten: `postgres_data`.

6. **`redis` (Redis 7 Alpine)**:
   - Penyimpanan cache, session, dan driver antrean (queue).
   - Volume persisten: `redis_data`.

---

## 2. Persiapan Server

Pastikan server memiliki:
- Docker Engine versi 24+
- Docker Compose v2 (perintah `docker compose`)
- Git

---

## 3. Langkah-Langkah Deployment

### Langkah 1: Clone Repositori di Server
```bash
git clone <url-repo-anda> /opt/payroll-juwita
cd /opt/payroll-juwita
```

### Langkah 2: Buat File `.env` Produksi
Salin template konfigurasi Docker yang sudah disediakan:
```bash
cp .env.docker.example .env
```

Sesuaikan parameter kredensial dan URL pada `.env`:
```env
APP_NAME="SIM SDM RS Juwita"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://ip-server-atau-domain-anda

# Keamanan Database
DB_PASSWORD=buat_password_database_yang_kuat_disini

# Port Nginx di Server (misal 80 atau 8080)
APP_PORT=80
```

### Langkah 3: Build & Jalankan Container
Jalankan Docker Compose dengan parameter `--build`:
```bash
docker compose up -d --build
```

### Langkah 4: Generate App Key & Jalankan Migrasi Database
Setelah container aktif dan healthy:
```bash
# 1. Generate Application Encryption Key
docker compose exec app php artisan key:generate --force

# 2. Jalankan Migrasi & Database Seeder
docker compose exec app php artisan migrate --seed --force

# 3. Optimasi Cache Laravel untuk Production
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

---

## 4. Perintah Operasional & Monitoring

### Memeriksa Status Container
```bash
docker compose ps
```

### Memeriksa Log Aplikasi atau Error
```bash
# Log seluruh service
docker compose logs -f

# Log khusus aplikasi Laravel / PHP-FPM
docker compose logs -f app

# Log worker antrean
docker compose logs -f queue

# Log web server Nginx
docker compose logs -f web
```

### Masuk ke Shell Container Aplikasi
```bash
docker compose exec -it app bash
```

### Restart Service Tertentu
```bash
docker compose restart queue
docker compose restart scheduler
```

### Update Versi Aplikasi (CI/CD atau Manual Git Pull)
```bash
git pull origin main
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose restart queue scheduler
```

---

## 5. Keamanan & Backup Data

- **Database Backup harian**:
  ```bash
  docker compose exec -T postgres pg_dump -U postgres payroll_juwita > /backup/payroll_$(date +%Y%m%d).sql
  ```
- **Volume Storage Backup**:
  Direktori `storage_data` dapat di-backup menggunakan mekanisme tar volume Docker untuk menyimpan dokumen upload KTP, STR, dan kontrak kerja.
