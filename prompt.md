1. Product foundation
Buat fondasi produk EduCart: migration, model, factory, dan seeder untuk categories dan products. Product harus punya seller_id, category_id, name, slug, description, price, stock, status draft/pending/approved/rejected, image nullable. Category punya name dan slug. Tambahkan relasi User seller -> products, Category -> products, Product -> seller/category. Jangan buat UI dulu. Tambahkan test model/relasi sederhana.

2. Seller product list
Buat halaman seller products index. Route hanya untuk seller verified. Data produk harus difilter milik seller login. Tampilkan table/list produk dengan nama, kategori, harga, stok, status. Frontend menerima props dari controller, tidak pakai dummy data. Tambahkan test seller hanya melihat produknya sendiri dan role lain tidak bisa akses.

3. Seller create product
Tambahkan fitur seller membuat produk. Buat halaman create product dengan form name, category, description, price, stock, image optional. Saat submit status default pending. Validasi backend harus jelas. Tambahkan test produk berhasil dibuat oleh seller dan buyer/admin tidak bisa memakai endpoint seller ini.

4. Seller edit product
Tambahkan fitur seller edit product. Seller hanya boleh edit produk miliknya sendiri. Jika produk approved lalu diedit, status kembali pending. Tambahkan test ownership, validasi, dan perubahan status.

5. Admin product moderation
Buat halaman admin moderasi produk pending. Admin bisa melihat produk pending, approve, atau reject dengan alasan opsional. Seller tidak boleh akses. Setelah approve produk bisa tampil di katalog buyer. Tambahkan test admin approve/reject dan akses role lain forbidden.

6. Buyer catalog
Buat halaman katalog produk untuk buyer/public. Tampilkan hanya produk approved dan stok > 0. Tambahkan search sederhana dan filter kategori via query string. Data dari backend props. UI mengikuti design.md: clean, product-focused, responsive. Tambahkan test produk draft/pending/rejected tidak tampil.

7. Product detail
Buat halaman detail produk berdasarkan slug. Hanya produk approved yang bisa dilihat publik. Tampilkan gambar, nama, kategori, seller, harga, stok, deskripsi, dan tombol tambah ke cart disabled jika stok 0. Tambahkan test visibility by status.

8. Cart minimal
Buat fitur cart minimal untuk user login: tambah produk ke cart, update quantity, hapus item. Quantity tidak boleh melebihi stok. Buat model/migration cart_items. Tambahkan halaman cart. Tambahkan test add/update/remove dan validasi stok.

9. Checkout jadi order
Buat checkout dari cart menjadi order. Buat tables orders dan order_items. Saat checkout, stok produk berkurang dan cart kosong. Status order default pending. Tambahkan test transaksi berhasil, stok berkurang, dan quantity melebihi stok ditolak.

10. Seller order management
Buat halaman seller orders. Seller hanya melihat order item yang berisi produk miliknya. Seller bisa ubah status item: pending, packed, sent. Tambahkan test seller tidak bisa melihat/mengubah order milik seller lain.

11. Admin dashboard real metrics
Update dashboard admin dan seller agar memakai data products/orders/order_items yang sudah ada. Ganti placeholder produk/order/stok menjadi query nyata. Tetap gunakan props backend dan tambahkan test jumlah metrik sesuai data factory.2

12. Polish production
Audit UI/UX seluruh flow product -> cart -> checkout -> order sesuai design.md. Perbaiki empty state, loading state, error validation, responsive mobile, dan copywriting. Jangan ubah business logic besar. Jalankan full check.
