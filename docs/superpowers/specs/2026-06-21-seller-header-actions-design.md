# Seller Header Actions Design

## Tujuan

Mengaktifkan Search, Notification, Help, dan Support pada header seller tanpa menambah halaman, tabel, atau dependency baru.

## Perilaku

### Search

- Saat seller mengetik, tampilkan pilihan untuk mencari teks yang sama di Produk, Inventori, atau Pesanan.
- Setiap pilihan membuka index terkait dengan query string `q`; seluruh pencarian tetap diproses oleh controller yang sudah ada.
- Enter membuka pencarian Produk sebagai tujuan default.
- Query kosong tidak menjalankan navigasi.

### Notification

- Tombol membuka dropdown yang berisi pesanan berstatus pending terbaru serta produk dengan stok rendah atau habis milik seller login.
- Setiap item menuju halaman pesanan atau inventori terkait.
- Titik merah hanya tampil ketika terdapat notifikasi.
- Notifikasi tidak disimpan dan tidak memiliki status dibaca; isi selalu diturunkan dari kondisi data terkini.
- Dropdown memiliki empty state ketika tidak ada tindakan yang diperlukan.

### Help

- Tombol membuka dialog panduan singkat untuk alur Produk, Inventori, Pesanan, dan Dashboard.
- Dialog dapat ditutup dengan tombol, Escape, dan klik area luar sesuai perilaku komponen Dialog yang sudah terpasang.

### Support

- Tombol membuka dialog yang mengarahkan seller menghubungi admin sekolah melalui alamat email aplikasi dari konfigurasi `mail.from.address`.
- Gunakan tautan `mailto:`; tidak ada chat atau ticketing baru.

## Implementasi

- Pertahankan `AppSidebarHeader` sebagai pemilik interaksi header.
- Bagikan data notifikasi seller dan email support melalui shared Inertia props hanya untuk user dengan role seller.
- Batasi seluruh query notifikasi dengan `seller_id` user login dan batasi jumlah item agar header tidak memuat daftar panjang.
- Gunakan komponen Dropdown Menu dan Dialog yang sudah ada serta route Wayfinder yang sudah dibuat proyek.
- Jangan menambah endpoint pencarian global, model notifikasi, migrasi, atau halaman baru.

## Error dan Aksesibilitas

- Nilai email kosong menampilkan arahan umum untuk menghubungi admin sekolah tanpa tautan rusak.
- Tombol tetap memiliki label aksesibel, fokus keyboard, dan state terbuka dari komponen UI yang ada.
- Data seller lain tidak boleh muncul pada notifikasi atau hasil pencarian.

## Verifikasi

- Feature test memastikan shared notifications hanya memuat data seller login dan indikator kosong bekerja.
- Pemeriksaan frontend mencakup TypeScript, ESLint, format, dan production build.
- Test PHP yang terkait seller tetap lulus.
