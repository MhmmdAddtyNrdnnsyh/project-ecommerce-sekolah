# EduCart

EduCart adalah aplikasi e-commerce sekolah berbasis Laravel, Inertia React, TypeScript, Tailwind, dan Bun. Aplikasi ini mengelola katalog produk, checkout buyer, dashboard seller, moderasi admin, operasional UP Jurusan, POS picket officer, laporan harian, dan pengajuan seller.

## Role Pengguna

- Buyer: melihat katalog, cart, checkout pembayaran tunai, dan memantau order.
- Seller: mengelola produk, inventori, order online, titipan UP Jurusan, dan penjualan offline dari POS.
- Admin: mengelola user, kategori, moderasi produk, pengajuan seller, dan memantau order/pembayaran.
- Admin Jurusan: mengelola UP Jurusan, akun picket, titipan seller, produk UP, dan laporan picket.
- Picket Officer: menerima barang titipan, menjalankan POS, mencetak nota, mengelola order titipan, dan mengirim laporan harian.

## Fitur Utama

- Katalog produk sekolah dengan owner seller atau UP Jurusan.
- Cart, checkout, metode pickup/delivery, dan payment minimal MVP.
- Pembayaran tunai untuk MVP. Pelunasan dikonfirmasi oleh seller untuk produk mandiri dan picket officer untuk produk yang dikelola UP Jurusan.
- Nomor transaksi konsisten untuk order website dan POS dengan format `TRX-YYYYMMDDHHMMSS-XXXX`.
- Seller dashboard dengan order online dan penjualan offline POS.
- Konsinyasi seller ke UP Jurusan dengan approval, receive barang, POS, komisi, dan payout tracking.
- Laporan picket berbasis transaksi harian yang dapat dilihat admin jurusan.
- Receipt/nota POS print-friendly.
- Seller application flow dari buyer ke seller.

## Setup Local

```bash
composer install
bun install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
bun run dev
php artisan serve
```

Alternatif setup dari fresh clone:

```bash
composer setup
php artisan serve
bun run dev
```

Gunakan Bun untuk frontend. Jangan gunakan `npm`, `pnpm-lock.yaml`, atau `pnpm-workspace.yaml`; project ini memakai `bun.lock`.

## Demo Data

Reset database demo dan isi ulang data deterministik:

```bash
php artisan migrate:fresh --seed
```

Jalankan command ini sebelum demo agar cart, order contoh, stok, POS, dan laporan kembali ke baseline deterministik.

Akun demo memakai password `password`:

| Role | Email |
| --- | --- |
| Admin | `admin@educart.test` |
| Buyer | `buyer@educart.test` |
| Seller | `seller@educart.test` |
| Admin Jurusan | `admin.jurusan@educart.test` |
| Picket Officer | `picket@educart.test` |

Seeder membuat UP RPL, akun picket yang sudah ter-assign ke UP RPL, kategori demo, produk seller, produk UP Jurusan, produk titipan, cart buyer, contoh order tunai, dan satu contoh transaksi POS. Laporan harian picket tidak dibuat otomatis agar demo masih bisa mencoba submit laporan manual.

## Quality Checks

```bash
bun run types:check
bun run lint:check
bun run build
./vendor/bin/pint --dirty --test
composer types:check
php artisan test
```

## Deployment Checklist

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Generate dan set `APP_KEY`.
- Set `APP_URL` ke domain production.
- Set database production dan jalankan `php artisan migrate --force`.
- Jalankan `php artisan storage:link`.
- Pastikan `storage/` dan `bootstrap/cache/` writable oleh user web server.
- Jalankan `composer install --no-dev --optimize-autoloader`.
- Jalankan `bun install --frozen-lockfile` lalu `bun run build`.
- Jalankan `php artisan optimize:clear` sebelum membuat cache baru setelah deploy.
- Jalankan `php artisan config:cache`, `route:cache`, dan `view:cache` setelah env final.
- Siapkan queue worker untuk job background jika queue tidak memakai sync.
- Siapkan scheduler cron: `* * * * * php /path/to/project/artisan schedule:run`.
- Restart PHP-FPM/web server dan queue worker setelah release baru aktif.

## Production Notes

- Jangan commit file `.env`.
- Gunakan `LOG_LEVEL=warning` atau `error` di production.
- Gunakan payment method tunai untuk MVP. Field QRIS/transfer/bukti bayar disiapkan di schema sebagai pengembangan berikutnya, tetapi flow aktif saat ini belum menggunakannya.
- Test suite memakai SQLite in-memory lewat `phpunit.xml`. Demo/production default `.env.example` memakai MySQL, jadi jalankan `php artisan migrate:fresh --seed` pada engine database target sebelum demo/deploy.
- Backup database secara berkala.
- Backup storage file upload, terutama gambar produk dan file upload lain yang aktif.
- Pastikan file upload di-disk `public` hanya menyimpan file yang sudah divalidasi.
- Jalankan queue worker dengan process manager seperti Supervisor/systemd.
- Pastikan `QUEUE_CONNECTION` sesuai infrastruktur production. Jika memakai `database` atau `redis`, worker harus selalu hidup.
- Monitor error log Laravel dan web server setelah deploy.
