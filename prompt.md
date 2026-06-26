# Prompt Bertahap Penyelesaian Fitur Seller EduCart

Jalankan prompt berikut secara berurutan, satu prompt per sesi kerja. Jangan lanjut ke prompt berikutnya sebelum seluruh acceptance criteria dan verification pada prompt aktif lulus.

Aturan untuk semua tahap:

- Baca implementasi dan test yang sudah ada sebelum mengubah kode. Pertahankan fitur yang sudah benar dan jangan mengulang fondasi produk, cart, checkout, atau moderasi admin.
- Ikuti pola Laravel, Inertia React, TypeScript, Wayfinder, Tailwind, dan komponen UI yang sudah digunakan repo.
- Gunakan props backend nyata. Jangan menambahkan dummy data, dependency, service/repository layer, atau abstraksi spekulatif.
- Semua data seller wajib dibatasi pada seller login. Terapkan middleware `auth`, `verified`, `EnsureUserIsSeller`, validasi backend, dan ownership check.
- Ikuti `design.md` untuk UI. Jangan mengubah business logic di luar kebutuhan tahap aktif.
- Tambahkan test terkecil yang membuktikan perilaku penting. Jalankan pemeriksaan tahap aktif, perbaiki kegagalan terkait, lalu laporkan file yang berubah dan hasil perintah.
- Jangan implementasikan profil toko, payout, notifikasi, Reviews, atau Reports. Fitur tersebut di luar scope penyelesaian seller ini.

## Prompt 1 - Audit Seller dan Baseline

Audit kondisi fitur seller saat ini sebelum menambah perilaku baru.

1. Baca `routes/web.php`, middleware role seller, model Product/Order/OrderItem/User, controller seller, request seller, migration terkait, halaman `resources/js/pages/seller`, sidebar, dan seluruh test seller/dashboard/checkout.
2. Catat fitur yang sudah bekerja, kontrak data yang tersedia, placeholder yang masih ada, serta risiko ownership atau regresi. Jangan membuat ulang index/create/edit produk yang sudah berfungsi.
3. Jalankan test seller yang sudah ada, `CheckoutTest`, dan `DashboardTest`. Jalankan juga pemeriksaan PHP serta frontend yang tersedia.
4. Jika ada kegagalan baseline yang langsung terkait seller, perbaiki dengan perubahan minimum dan jalankan ulang pemeriksaannya. Masalah di luar scope cukup dilaporkan.

Verification minimum:

```bash
php artisan test tests/Feature/SellerProductIndexTest.php tests/Feature/SellerProductCreateTest.php tests/Feature/SellerProductEditTest.php tests/Feature/CheckoutTest.php tests/Feature/DashboardTest.php
composer lint:check
composer types:check
pnpm run types:check
pnpm run lint:check
```

Selesai jika baseline seller terpetakan, test terkait lulus, dan tidak ada perubahan fitur spekulatif.

## Prompt 2 - Backend Pengelolaan Produk Seller

Lengkapi backend daftar dan penghapusan produk seller tanpa mengubah flow create/edit yang sudah lulus.

1. Tambahkan query parameter tervalidasi pada index produk: `q`, `status`, `category_id`, dan `stock`. Nilai `stock` hanya `all`, `low`, atau `out`; gunakan batas stok rendah tetap `5` pada domain Product agar dapat dipakai ulang.
2. Search mencocokkan nama atau slug. Filter status memakai nilai `ProductStatus`, kategori harus valid, `out` berarti stok `0`, dan `low` berarti stok `1..5`.
3. Ganti collection penuh menjadi pagination 10 item, urutan terbaru, serta pertahankan query string. Bentuk props harus eksplisit dan hanya berisi field yang diperlukan frontend.
4. Tambahkan route `DELETE /seller/products/{product}` dan method destroy. Seller hanya boleh menghapus produknya sendiri.
5. Tolak penghapusan dengan validation error yang jelas jika produk mempunyai `orderItems`. Jika aman dihapus, hapus file image dari disk public bila ada, lalu hapus produk dan redirect dengan flash sukses.
6. Bagikan flash `success` dan `error` melalui middleware Inertia bila kontrak tersebut belum tersedia.
7. Tambahkan feature test untuk search/filter/pagination, query seller tidak bocor, hapus sukses, file image terhapus, produk seller lain `403`, produk yang pernah dipesan ditolak dan tetap utuh, serta role non-seller ditolak.

Jangan membuat UI filter atau dialog delete pada tahap ini.

Verification minimum:

```bash
php artisan test tests/Feature/SellerProductIndexTest.php tests/Feature/SellerProductDeleteTest.php
composer lint:check
composer types:check
```

Selesai jika seluruh kontrak produk berasal dari backend, terpaginasikan, aman terhadap ownership, dan test lulus.

## Prompt 3 - Backend Inventori Seller

Buat kontrak backend inventori terpisah yang tetap memakai Product sebagai sumber stok tunggal.

1. Tambahkan route seller untuk `GET /seller/inventory` dan `PATCH /seller/inventory/{product}` menggunakan controller seller yang fokus pada inventori.
2. Index hanya mengambil produk seller login, terpaginasikan 10 item, dan mendukung `q` serta filter `stock=all|low|out`. Gunakan batas stok rendah Product yang dibuat pada tahap sebelumnya.
3. Props inventori minimal memuat id, nama, slug, gambar, status produk, stok, kategori, dan penanda stok rendah/habis. Sertakan filter aktif dan ringkasan jumlah total, stok rendah, serta stok habis milik seller.
4. Buat Form Request untuk update stok. Terima integer non-negatif dengan batas atas yang masuk akal dan pesan validasi Bahasa Indonesia yang jelas.
5. Update stok hanya untuk produk seller login. Resource seller lain menghasilkan `403`; jangan mengubah status moderasi produk ketika stok berubah.
6. Tambahkan feature test untuk list/filter/search, ringkasan, update sukses termasuk stok nol, invalid input, ownership, dan role non-seller.

Jangan membuat halaman inventori pada tahap ini dan jangan membuat tabel riwayat stok.

Verification minimum:

```bash
php artisan test tests/Feature/SellerInventoryTest.php
composer lint:check
composer types:check
```

Selesai jika backend inventori memakai data nyata, tidak menggandakan penyimpanan stok, dan test lulus.

## Prompt 4 - Fondasi Status Order Item

Tambahkan status pemenuhan per order item karena satu order dapat berisi produk dari beberapa seller.

1. Buat enum `OrderItemStatus` dengan nilai `pending`, `packed`, dan `sent`, label Bahasa Indonesia, daftar values, serta aturan status berikutnya yang eksplisit.
2. Buat migration baru yang menambah kolom enum `status` pada `order_items`, default `pending`, dan index. Jangan mengedit migration lama yang mungkin sudah dijalankan.
3. Tambahkan cast dan fillable status pada `OrderItem`. Pertahankan `OrderStatus` global yang sudah dipakai checkout; jangan memaksakan status seller ke seluruh order.
4. Pastikan checkout membuat order item berstatus pending, baik melalui default database maupun assignment eksplisit yang konsisten.
5. Tambahkan factory Order dan OrderItem secukupnya dengan relasi valid agar test seller berikutnya ringkas. Jangan membuat factory state yang belum dipakai.
6. Tambahkan satu test terfokus yang membuktikan status default, enum cast, relasi order/product, dan checkout lama tetap bekerja.

Verification minimum:

```bash
php artisan test tests/Feature/OrderItemStatusTest.php tests/Feature/CheckoutTest.php
composer lint:check
composer types:check
```

Selesai jika setiap order item memiliki status seller yang valid tanpa merusak checkout atau status order global.

## Prompt 5 - Backend Pesanan Seller

Buat backend seller untuk melihat dan memproses hanya order item dari produknya sendiri.

1. Tambahkan route seller untuk index pesanan, detail order item, dan update status. Gunakan URL serta nama route yang konsisten di bawah prefix `seller`.
2. Index berbasis `OrderItem`, eager-load order, buyer, dan product seperlunya, lalu selalu filter melalui `product.seller_id` seller login. Pagination 10 item harus mempertahankan query string.
3. Dukung `q` untuk nomor order, nama produk, atau nama pembeli serta filter `status` berdasarkan `OrderItemStatus`.
4. Props index memuat id order item, nomor order, pembeli, produk, quantity, subtotal, status, dan waktu. Props detail memuat data tersebut plus harga satuan; jangan menampilkan data pribadi yang tidak diperlukan.
5. Detail order item seller lain dan update milik seller lain harus menghasilkan `403`.
6. Buat Form Request update status. Hanya transisi `pending -> packed -> sent` yang valid. Tolak status sama, lompat, atau mundur dengan validation error yang jelas.
7. Jalankan update status dalam transaction dan lock baris order item sebelum memvalidasi status saat ini agar request bersamaan tidak melewati aturan transisi.
8. Tambahkan feature test untuk isolasi list, search/filter/pagination, detail, role, ownership, dua transisi sukses, serta penolakan status invalid/lompat/mundur/berulang.

Jangan membuat halaman pesanan pada tahap ini dan jangan menambahkan payment, shipping address, kurir, atau nomor resi karena datanya belum ada.

Verification minimum:

```bash
php artisan test tests/Feature/SellerOrderTest.php
composer lint:check
composer types:check
```

Selesai jika seller hanya dapat membaca dan memproses fulfillment miliknya dengan transisi status yang aman.

## Prompt 6 - Backend Dashboard Seller Nyata

Ganti seluruh placeholder `SellerDashboardController` dengan agregasi nyata yang dibatasi seller login.

1. Definisikan metrik secara konsisten: omzet bulan ini adalah jumlah subtotal order item seller yang dibuat bulan berjalan; pesanan masuk adalah jumlah order berbeda milik seller pada bulan berjalan; produk aktif adalah produk approved; stok rendah adalah produk dengan stok `1..5`.
2. Buat data penjualan tujuh hari berurutan termasuk hari tanpa transaksi. Setiap titik memuat total subtotal dan jumlah order berbeda untuk seller tersebut.
3. Buat komposisi status order item `pending`, `packed`, dan `sent`.
4. Isi pesanan terbaru maksimal 5 item, produk terlaris maksimal 5 berdasarkan total quantity dengan omzetnya, serta peringatan stok maksimal 5 untuk stok `0..5`.
5. Hapus copy backend yang menyatakan modul produk/pesanan belum tersedia. Jangan membuat metrik payout, review, atau laporan baru.
6. Hindari N+1 dan pastikan setiap aggregate/query mempunyai filter seller. Gunakan query Eloquent/query builder sederhana; jangan menambah service layer hanya untuk controller ini.
7. Perbarui test dashboard dengan minimal dua seller dan beberapa tanggal/status agar membuktikan angka, urutan tujuh hari, top product, stock alert, dan tidak ada kebocoran data seller lain.

Jangan merombak UI dashboard pada tahap ini.

Verification minimum:

```bash
php artisan test tests/Feature/DashboardTest.php
composer lint:check
composer types:check
```

Selesai jika semua props dashboard berasal dari database dan seluruh angka hanya mencakup seller login.

## Prompt 7 - Frontend Pengelolaan Produk Seller

Hubungkan halaman produk seller ke kontrak backend Prompt 2.

1. Sesuaikan type props index dengan paginator Laravel dan filters backend. Jangan memakai `any` atau menduplikasi data.
2. Tambahkan form search dan select filter status, kategori, serta stok. Submit dengan Inertia GET, pertahankan hanya filter yang aktif, sediakan tombol reset, dan jangan menambah debounce/helper abstrak yang tidak diperlukan.
3. Render pagination memakai link dari backend. Tampilkan ringkasan rentang/jumlah data bila tersedia.
4. Tambahkan dialog konfirmasi delete yang accessible. Tombol harus disabled saat request berlangsung, tampilkan validation/flash error bila produk pernah dipesan, dan flash sukses setelah terhapus.
5. Pertahankan link create/edit, badge status, format Rupiah, dan empty state. Pastikan tabel dapat digunakan pada mobile melalui layout responsif atau overflow yang jelas.
6. Gunakan route Wayfinder yang dihasilkan dari route backend. Jangan hardcode URL dan jangan mengubah halaman create/edit tanpa kebutuhan kompatibilitas.

Verification minimum:

```bash
pnpm run format:check
pnpm run types:check
pnpm run lint:check
pnpm run build
php artisan test tests/Feature/SellerProductIndexTest.php tests/Feature/SellerProductDeleteTest.php
```

Selesai jika seller dapat mencari, memfilter, berpindah halaman, dan menghapus produk melalui UI tanpa dummy data.

## Prompt 8 - Frontend Inventori Seller

Buat halaman inventori berdasarkan kontrak backend Prompt 3.

1. Tambahkan halaman `resources/js/pages/seller/inventory/index.tsx` mengikuti layout seller dan `design.md`.
2. Tampilkan ringkasan total produk, stok rendah, dan stok habis; daftar inventori memuat produk, kategori, status moderasi, stok, dan kondisi stok.
3. Tambahkan search, filter semua/stok rendah/stok habis, pagination, serta empty state yang sesuai filter.
4. Sediakan aksi edit stok menggunakan input number atau dialog sederhana. Validasi client hanya membantu UX; backend tetap sumber kebenaran. Tampilkan error, disable saat processing, dan perbarui halaman setelah sukses.
5. Gunakan komponen UI yang sudah ada, label accessible, ukuran target sentuh yang layak, serta tampilan mobile yang tidak memotong aksi.
6. Gunakan route Wayfinder nyata dan type props eksplisit. Jangan menambahkan grafik, bulk update, import CSV, atau riwayat stok.

Verification minimum:

```bash
pnpm run format:check
pnpm run types:check
pnpm run lint:check
pnpm run build
php artisan test tests/Feature/SellerInventoryTest.php
```

Selesai jika seller dapat menemukan kondisi stok dan memperbarui stok produk miliknya melalui UI.

## Prompt 9 - Frontend Pesanan Seller

Buat UI pesanan seller berdasarkan kontrak backend Prompt 5.

1. Buat halaman index dan detail pada `resources/js/pages/seller/orders`. Ikuti pola layout, breadcrumb, Card, Table, Badge, Button, dan props Inertia yang sudah ada.
2. Index menampilkan nomor order, pembeli, produk, quantity, subtotal, status fulfillment, dan waktu. Tambahkan search, filter status, pagination, serta empty state.
3. Detail hanya menampilkan data yang disediakan backend: identitas order, pembeli, produk, harga, quantity, subtotal, status, dan waktu.
4. Tampilkan satu aksi status berikutnya saja: pending menjadi packed, packed menjadi sent, dan sent tidak mempunyai aksi lanjutan. Disable tombol saat processing dan tampilkan validation/flash feedback.
5. Gunakan format Rupiah dan tanggal lokal secara konsisten. Pastikan status tidak hanya dibedakan oleh warna, tetapi juga label teks.
6. Gunakan Wayfinder, type union dari kode status backend, dan desain responsif. Jangan menambah status, tracking, kurir, alamat, atau payment UI yang belum didukung backend.

Verification minimum:

```bash
pnpm run format:check
pnpm run types:check
pnpm run lint:check
pnpm run build
php artisan test tests/Feature/SellerOrderTest.php
```

Selesai jika seller dapat melihat fulfillment miliknya dan menjalankan transisi status yang valid dari UI.

## Prompt 10 - Frontend Dashboard dan Navigasi Seller

Hubungkan dashboard dan sidebar seller ke seluruh fitur nyata.

1. Sesuaikan type dan komp## Prompt 9 - Frontend Pesanan Seller

Buat UI pesanan seller berdasarkan kontrak backend Prompt 5.

1. Buat halaman index dan detail pada `resources/js/pages/seller/orders`. Ikuti pola layout, breadcrumb, Card, Table, Badge, Button, dan props Inertia yang sudah ada.
2. Index menampilkan nomor order, pembeli, produk, quantity, subtotal, status fulfillment, dan waktu. Tambahkan search, filter status, pagination, serta empty state.
3. Detail hanya menampilkan data yang disediakan backend: identitas order, pembeli, produk, harga, quantity, subtotal, status, dan waktu.
4. Tampilkan satu aksi status berikutnya saja: pending menjadi packed, packed menjadi sent, dan sent tidak mempunyai aksi lanjutan. Disable tombol saat processing dan tampilkan validation/flash feedback.
5. Gunakan format Rupiah dan tanggal lokal secara konsisten. Pastikan status tidak hanya dibedakan oleh warna, tetapi juga label teks.
6. Gunakan Wayfinder, type union dari kode status backend, dan desain responsif. Jangan menambah status, tracking, kurir, alamat, atau payment UI yang belum didukung backend.

Verification minimum:

```bash
pnpm run format:check
pnpm run types:check
pnpm run lint:check
pnpm run build
php artisan test tests/Feature/SellerOrderTest.php
```

Selesai jika seller dapat melihat fulfillment miliknya dan menjalankan transisi status yang valid dari UI.onen `resources/js/pages/seller/dashboard.tsx` dengan props Prompt 6. Hapus status/copy dummy seperti Paid, Issue, atau teks modul belum tersedia yang tidak cocok dengan backend.
2. Render empat metrik utama, grafik tujuh hari, komposisi pending/packed/sent, pesanan terbaru, produk terlaris, dan peringatan stok. Semua empty state harus tetap informatif saat seller belum punya transaksi atau produk.
3. Gunakan formatter Rupiah dan tanggal lokal, chart yang sudah terpasang, serta warna/status sesuai `design.md`. Jangan menambah dependency chart.
4. Perbarui sidebar seller: Dashboard, Products, Orders, dan Inventory harus memakai route Wayfinder nyata. Hapus placeholder Reviews dan Reports dari navigasi seller.
5. Pastikan active state bekerja pada route index dan turunannya, breadcrumb benar, sidebar tetap accessible, dan layout responsif.
6. Jangan menambah query atau kalkulasi bisnis baru di React; backend tetap sumber data dan agregasi.

Verification minimum:

```bash
pnpm run format:check
pnpm run types:check
pnpm run lint:check
pnpm run build
php artisan test tests/Feature/DashboardTest.php tests/Feature/SellerInventoryTest.php tests/Feature/SellerOrderTest.php
```

Selesai jika seluruh menu seller menuju halaman nyata dan dashboard tidak mempunyai dummy/placeholder data.

## Prompt 11 - Polish dan Verifikasi Seller End-to-End

Audit akhir seluruh flow seller tanpa memperluas scope.

1. Uji manual alur seller verified: dashboard, list/search/filter produk, create, edit, delete, inventori, update stok, list/detail pesanan, serta transisi pending ke packed ke sent.
2. Uji seller kedua, buyer, admin, seller unverified, resource seller lain, invalid query/input, produk yang sudah dipesan, stok nol, dan kondisi tanpa data.
3. Perbaiki hanya masalah seller yang ditemukan: validation feedback, flash message, tombol processing/disabled, empty state, fokus dialog, label form, kontras, mobile overflow, breadcrumb, active navigation, dan copy Bahasa Indonesia.
4. Pastikan tidak ada dummy seller, URL hardcoded, placeholder `#orders/#inventory`, link Reviews/Reports seller, N+1 yang jelas, atau query seller tanpa ownership filter.
5. Jangan melakukan refactor besar atau menambah fitur baru. Masalah non-seller yang tidak menghalangi flow cukup dicatat pada laporan akhir.
6. Jalankan seluruh pemeriksaan proyek. Perbaiki kegagalan terkait perubahan seller sampai lulus.

Full verification:

```bash
composer test
pnpm run format:check
pnpm run types:check
pnpm run lint:check
pnpm run build
```

Seller dinyatakan tuntas jika seluruh acceptance criteria Prompt 1-11 terpenuhi, seluruh pemeriksaan di atas lulus, dan tidak ada kebocoran data antar-seller.
