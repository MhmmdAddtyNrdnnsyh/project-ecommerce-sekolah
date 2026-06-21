# Seller Completion Prompts Design

## Tujuan

Menyusun rangkaian prompt bertahap untuk menuntaskan fitur seller EduCart tanpa mengulang fitur yang sudah selesai. Seluruh kontrak backend diselesaikan lebih dahulu, kemudian frontend dibangun dari kontrak tersebut.

Seller dinyatakan tuntas ketika pengelolaan produk, inventori, pesanan, dashboard, dan navigasi seller memakai data nyata, terlindungi oleh role serta ownership, responsif, dan lolos seluruh pemeriksaan proyek.

## Scope

### Produk

- Pertahankan index, create, dan edit produk yang sudah berfungsi.
- Tambahkan pencarian, filter status, kategori, dan kondisi stok.
- Tambahkan pagination dengan query string yang tetap terbawa.
- Tambahkan penghapusan produk milik seller.
- Tolak penghapusan jika produk sudah tercatat pada order item agar riwayat transaksi tetap utuh.

### Inventori

- Sediakan daftar inventori khusus seller dari produk miliknya.
- Dukung filter semua, stok rendah, dan stok habis.
- Izinkan seller memperbarui stok produknya sendiri dengan validasi bilangan bulat non-negatif.
- Gunakan batas stok rendah yang tetap dan sederhana; tidak perlu konfigurasi baru.

### Pesanan

- Simpan status pemenuhan pada setiap order item karena satu order dapat berisi produk dari beberapa seller.
- Status hanya boleh bergerak `pending` ke `packed`, lalu `sent`.
- Seller hanya dapat melihat order item yang terkait dengan produk miliknya.
- Sediakan index, detail, pencarian/filter, dan aksi perubahan status.
- Data detail mencakup identitas pesanan, pembeli, produk, jumlah, nilai, status, dan waktu.

### Dashboard

- Ganti seluruh placeholder dengan query yang dibatasi pada produk seller login.
- Sajikan omzet bulan berjalan, pesanan masuk, produk aktif, dan stok rendah.
- Sajikan grafik penjualan tujuh hari, komposisi status, pesanan terbaru, produk terlaris, dan peringatan stok.
- Hilangkan teks yang menyatakan modul produk atau pesanan belum tersedia.

### Frontend dan Navigasi

- Bangun UI setelah kontrak backend dan feature test selesai.
- Ikuti komponen yang sudah terpasang dan `design.md`; jangan menambah dependency.
- Tambahkan UI produk, inventori, pesanan, dan dashboard berdasarkan props backend tanpa dummy data.
- Sediakan empty state, error state, konfirmasi tindakan destruktif, dan layout mobile.
- Hubungkan menu seller ke route nyata dan hapus placeholder Reviews serta Reports karena di luar scope.

## Urutan Prompt

1. Audit seller dan baseline test.
2. Backend produk: pencarian, filter, pagination, dan penghapusan aman.
3. Backend inventori: daftar/filter stok dan update stok.
4. Fondasi backend pesanan: migrasi, enum status item, relasi, serta factory.
5. Backend pesanan seller: index, detail, filter, dan transisi status.
6. Backend dashboard seller dengan metrik nyata.
7. Frontend pengelolaan produk.
8. Frontend inventori.
9. Frontend pesanan.
10. Frontend dashboard dan navigasi seller.
11. Polish dan verifikasi penuh.

Setiap prompt harus dapat dikerjakan berurutan. Agen wajib memeriksa hasil tahap sebelumnya, menjaga fitur yang sudah lulus, dan berhenti memperbaiki tahap aktif sampai pemeriksaannya lolos sebelum lanjut.

## Otorisasi dan Error

- Semua route seller memakai middleware `auth`, `verified`, dan role seller yang sudah ada.
- Akses resource seller lain menghasilkan `403` tanpa membocorkan data.
- Input tidak valid kembali sebagai validation error dengan pesan yang jelas.
- Produk yang pernah dipesan tidak dapat dihapus dan tidak boleh berubah ketika penolakan terjadi.
- Transisi status yang melompat, mundur, atau mengulang status ditolak.
- Semua list, detail, dan agregasi seller harus dibatasi berdasarkan seller login.

## Strategi Test

- Tahap backend menambah feature test untuk alur sukses, role, ownership, validasi, dan kegagalan penting.
- Test regresi seller yang sudah ada harus tetap lulus.
- Tahap frontend menjalankan pemeriksaan TypeScript, ESLint, dan build.
- Tahap akhir menjalankan seluruh test PHP, PHPStan, Pint, pemeriksaan format frontend, TypeScript, ESLint, dan production build menggunakan script proyek yang tersedia.
- Kegagalan yang ditemukan harus diperbaiki dalam scope seller; masalah di luar scope cukup dilaporkan tanpa refactor spekulatif.

## Batasan

- Tidak mencakup profil toko, payout, notifikasi, ulasan, atau laporan terpisah.
- Tidak menambah service layer, repository, dependency, atau abstraksi baru tanpa kebutuhan nyata dari kode saat ini.
- Tidak mengganti arsitektur Laravel, Inertia, React, Wayfinder, atau komponen UI yang sudah digunakan.
- Tidak menggunakan dummy data pada halaman seller.
