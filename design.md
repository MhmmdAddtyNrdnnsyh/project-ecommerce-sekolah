# EduCart Design System

> Dokumen ini menjadi sumber acuan utama untuk seluruh desain UI/UX aplikasi **EduCart**.  
> Semua halaman, komponen, warna, tipografi, spacing, animasi, dan interaksi harus mengikuti aturan di dokumen ini agar tampilan aplikasi konsisten.

---

## 1. Product Overview

**EduCart** adalah aplikasi ecommerce modern yang memudahkan pengguna menemukan, melihat, dan membeli produk secara cepat dan nyaman.

### Tujuan desain

- Membuat pengalaman belanja yang sederhana dan tidak membingungkan.
- Menampilkan produk secara jelas dengan visual yang menarik.
- Mempermudah pengguna dari proses pencarian hingga checkout.
- Memberikan kesan modern, profesional, ramah, dan terpercaya.
- Tetap nyaman digunakan pada desktop, tablet, dan mobile.

### Target pengguna

- Pelajar dan pengguna umum.
- Pengguna yang terbiasa menggunakan marketplace modern.
- Pengguna desktop maupun mobile.
- Admin yang mengelola produk, kategori, pesanan, dan pengguna.

---

## 2. Design Direction

Gunakan gaya visual:

- Modern
- Minimal
- Clean
- Friendly
- Professional
- Soft rounded
- Tidak terlalu ramai
- Fokus pada produk dan kemudahan navigasi

Hindari desain yang:

- Terlalu banyak warna.
- Menggunakan gradient berlebihan.
- Memiliki terlalu banyak shadow.
- Menggunakan animasi yang mengganggu.
- Memenuhi layar dengan card tanpa whitespace.
- Memakai border tebal pada semua komponen.

### Design keywords

`clean`, `modern`, `blue`, `soft`, `spacious`, `accessible`, `responsive`, `product-focused`

---

## 3. Brand Identity

### Nama brand

**EduCart**

### Brand personality

- Pintar
- Ramah
- Cepat
- Aman
- Terpercaya
- Mudah digunakan

### Logo

- Gunakan logo tanpa teks apabila ruang terbatas.
- Jangan mengubah rasio logo.
- Jangan memberi efek glow berlebihan.
- Jangan menempatkan logo pada warna yang membuatnya sulit terlihat.
- Berikan clear space minimal `8px` di sekitar logo.

---

## 4. Color System

### Primary color

| Token | Hex | Penggunaan |
|---|---:|---|
| `primary-50` | `#EFF8FF` | Background lembut |
| `primary-100` | `#D9EEFF` | Hover ringan |
| `primary-200` | `#BCE0FF` | Border aktif |
| `primary-300` | `#8ECCFF` | Icon dekoratif |
| `primary-400` | `#56AEFF` | Secondary accent |
| `primary-500` | `#0080FF` | Warna utama brand |
| `primary-600` | `#006FE0` | Hover tombol utama |
| `primary-700` | `#0059B8` | Active state |
| `primary-800` | `#064B91` | Teks atau background kuat |
| `primary-900` | `#0A3F76` | Dark accent |

### Neutral color

| Token | Hex | Penggunaan |
|---|---:|---|
| `white` | `#FFFFFF` | Surface utama |
| `slate-50` | `#F8FAFC` | Background aplikasi |
| `slate-100` | `#F1F5F9` | Background sekunder |
| `slate-200` | `#E2E8F0` | Border |
| `slate-300` | `#CBD5E1` | Disabled border |
| `slate-400` | `#94A3B8` | Placeholder |
| `slate-500` | `#64748B` | Secondary text |
| `slate-600` | `#475569` | Body text |
| `slate-700` | `#334155` | Strong text |
| `slate-800` | `#1E293B` | Heading |
| `slate-900` | `#0F172A` | Primary text |

### Semantic color

| Status | Background | Text/Icon | Penggunaan |
|---|---:|---:|---|
| Success | `#ECFDF3` | `#16A34A` | Pembayaran berhasil |
| Warning | `#FFF7ED` | `#EA580C` | Stok hampir habis |
| Error | `#FEF2F2` | `#DC2626` | Validasi dan kegagalan |
| Info | `#EFF8FF` | `#0080FF` | Informasi umum |

### Aturan penggunaan warna

- Gunakan `#0080FF` sebagai warna aksi utama.
- Gunakan warna merah hanya untuk error, hapus, atau tindakan berbahaya.
- Gunakan warna hijau untuk status berhasil.
- Jangan menggunakan lebih dari satu warna accent utama dalam satu section.
- Pastikan teks memiliki kontras yang jelas dengan background.

---

## 5. Typography

### Font utama

Gunakan:

```css
font-family: "Inter", "Plus Jakarta Sans", system-ui, sans-serif;
```

Prioritas:

1. Inter
2. Plus Jakarta Sans
3. System UI
4. Sans-serif

### Typography scale

| Style | Size | Weight | Line Height | Penggunaan |
|---|---:|---:|---:|---|
| Display | `48px` | `700` | `1.1` | Hero desktop |
| H1 | `36px` | `700` | `1.2` | Judul halaman |
| H2 | `30px` | `700` | `1.25` | Judul section |
| H3 | `24px` | `600` | `1.3` | Judul card besar |
| H4 | `20px` | `600` | `1.4` | Judul komponen |
| Body Large | `18px` | `400` | `1.6` | Deskripsi utama |
| Body | `16px` | `400` | `1.6` | Teks umum |
| Body Small | `14px` | `400` | `1.5` | Metadata |
| Caption | `12px` | `500` | `1.4` | Label kecil |

### Aturan tipografi

- Gunakan `slate-900` untuk heading utama.
- Gunakan `slate-600` untuk body text.
- Gunakan `slate-500` untuk metadata.
- Maksimal gunakan tiga ukuran font dalam satu card.
- Hindari teks center untuk paragraf panjang.
- Harga produk harus mudah terlihat dan lebih kuat dari metadata lain.

---

## 6. Spacing System

Gunakan sistem kelipatan `4px`.

| Token | Value |
|---|---:|
| `space-1` | `4px` |
| `space-2` | `8px` |
| `space-3` | `12px` |
| `space-4` | `16px` |
| `space-5` | `20px` |
| `space-6` | `24px` |
| `space-8` | `32px` |
| `space-10` | `40px` |
| `space-12` | `48px` |
| `space-16` | `64px` |
| `space-20` | `80px` |

### Aturan spacing

- Padding card default: `16px` sampai `24px`.
- Jarak antar section desktop: `64px` sampai `80px`.
- Jarak antar section mobile: `40px` sampai `48px`.
- Jarak icon dan teks: `8px`.
- Hindari elemen terlalu rapat.
- Gunakan whitespace untuk memperjelas hierarki.

---

## 7. Border Radius

| Token | Value | Penggunaan |
|---|---:|---|
| `radius-sm` | `6px` | Badge |
| `radius-md` | `10px` | Input |
| `radius-lg` | `14px` | Button dan card |
| `radius-xl` | `18px` | Modal dan section |
| `radius-2xl` | `24px` | Hero banner |
| `radius-full` | `9999px` | Avatar dan pill |

Gunakan rounded yang lembut, tetapi jangan membuat seluruh komponen terlihat seperti kapsul.

---

## 8. Shadow System

Gunakan shadow secara halus.

```css
--shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.05);
--shadow-md: 0 8px 24px rgba(15, 23, 42, 0.08);
--shadow-lg: 0 16px 40px rgba(15, 23, 42, 0.10);
```

### Aturan shadow

- Card biasa menggunakan `shadow-sm` atau tanpa shadow.
- Dropdown menggunakan `shadow-md`.
- Modal menggunakan `shadow-lg`.
- Hover card boleh menaikkan shadow sedikit.
- Hindari shadow hitam yang pekat.

---

## 9. Layout

### Container

```css
max-width: 1280px;
margin-inline: auto;
padding-inline: 24px;
```

Mobile:

```css
padding-inline: 16px;
```

### Grid produk

| Breakpoint | Kolom |
|---|---:|
| `< 640px` | 2 kolom |
| `640px - 767px` | 2 kolom |
| `768px - 1023px` | 3 kolom |
| `1024px - 1279px` | 4 kolom |
| `>= 1280px` | 5 kolom jika ruang cukup |

### Breakpoints

```txt
sm: 640px
md: 768px
lg: 1024px
xl: 1280px
2xl: 1536px
```

### Header layout

Desktop:

- Logo di kiri.
- Search bar di tengah.
- Navigasi dan action di kanan.
- Header sticky.
- Tinggi sekitar `72px`.

Mobile:

- Logo dan action utama di baris pertama.
- Search bar berada di bawahnya.
- Gunakan menu drawer untuk navigasi tambahan.

---

## 10. Core Components

## 10.1 Button

### Primary button

- Background: `primary-500`
- Text: putih
- Hover: `primary-600`
- Active: `primary-700`
- Radius: `12px`
- Tinggi default: `44px`
- Font weight: `600`

### Secondary button

- Background: `primary-50`
- Text: `primary-600`
- Border: `primary-200`

### Outline button

- Background: transparan
- Text: `slate-700`
- Border: `slate-200`

### Destructive button

- Background: `#DC2626`
- Text: putih
- Digunakan hanya untuk tindakan berbahaya.

### Button states

Setiap button wajib memiliki:

- Default
- Hover
- Active
- Focus
- Disabled
- Loading

Gunakan spinner saat proses loading dan cegah klik berulang.

---

## 10.2 Input

### Style

- Tinggi: `44px`
- Radius: `10px`
- Border: `1px solid #E2E8F0`
- Background: putih
- Padding horizontal: `14px`
- Focus ring: biru lembut
- Placeholder: `slate-400`

### State

- Default
- Focus
- Filled
- Error
- Disabled

Error message diletakkan tepat di bawah input dengan ukuran `14px`.

---

## 10.3 Search Bar

Search bar merupakan elemen penting.

Fitur:

- Icon pencarian di kiri.
- Placeholder: `Cari produk, kategori, atau brand...`
- Tombol clear ketika ada teks.
- Mendukung suggestion atau recent search.
- Pada mobile menggunakan lebar penuh.
- Search result harus tampil cepat dan tidak menutupi seluruh halaman secara berlebihan.

---

## 10.4 Product Card

Product card wajib memiliki:

1. Product image.
2. Badge diskon atau status jika tersedia.
3. Nama produk.
4. Harga.
5. Harga awal jika diskon.
6. Rating dan jumlah ulasan.
7. Informasi stok atau jumlah terjual jika relevan.
8. Tombol wishlist.
9. Tombol tambah ke keranjang atau quick action.

### Product image

- Rasio `1:1`.
- Gunakan `object-fit: cover`.
- Background gambar menggunakan `slate-50`.
- Radius bagian atas mengikuti card.
- Gunakan skeleton ketika loading.

### Product name

- Maksimal dua baris.
- Gunakan line clamp.
- Jangan memotong teks tanpa tooltip pada desktop jika informasi penting.

### Price

- Harga utama menggunakan `font-weight: 700`.
- Warna `slate-900` atau `primary-600`.
- Harga lama menggunakan strikethrough dan `slate-400`.

### Hover

Pada desktop:

- Card naik maksimal `2px`.
- Shadow sedikit meningkat.
- Gambar boleh zoom maksimal `1.03`.
- Transisi sekitar `180ms`.

Pada mobile:

- Jangan bergantung pada hover.
- Semua action penting harus tetap dapat diakses.

---

## 10.5 Badge

Jenis badge:

- Diskon
- Produk baru
- Terlaris
- Stok sedikit
- Status pesanan
- Status pembayaran

Gunakan ukuran kecil, teks singkat, dan warna sesuai konteks.

---

## 10.6 Navbar

Menu utama:

- Beranda
- Kategori
- Produk
- Promo
- Tentang
- Bantuan

Action:

- Search
- Wishlist
- Keranjang
- Profil

Keranjang dapat menampilkan jumlah item dalam badge kecil.

---

## 10.7 Breadcrumb

Gunakan pada:

- Halaman detail produk.
- Halaman kategori.
- Checkout.
- Halaman admin bertingkat.

Contoh:

```txt
Beranda / Elektronik / Headphone / Nama Produk
```

---

## 10.8 Modal dan Dialog

Gunakan modal untuk:

- Konfirmasi hapus.
- Konfirmasi keluar.
- Quick view.
- Pilihan variasi singkat.

Aturan:

- Fokus keyboard harus tetap berada di dalam modal.
- Modal dapat ditutup dengan tombol close dan tombol `Escape`.
- Destructive dialog harus menjelaskan konsekuensi tindakan.
- Jangan menggunakan modal untuk proses panjang.

---

## 10.9 Toast

Toast digunakan untuk feedback singkat:

- Produk berhasil ditambahkan.
- Data berhasil disimpan.
- Terjadi kesalahan.
- Item berhasil dihapus.

Posisi:

- Desktop: kanan atas.
- Mobile: bagian atas atau bawah dengan jarak aman.

Durasi:

- Success: sekitar `3 detik`.
- Error penting: sekitar `5 detik`.

---

## 10.10 Skeleton

Gunakan skeleton untuk:

- Product card.
- Detail produk.
- Tabel admin.
- Ringkasan dashboard.
- Riwayat pesanan.

Hindari spinner besar untuk halaman yang memuat banyak konten.

---

## 11. Customer Pages

## 11.1 Home Page

Urutan section:

1. Header.
2. Hero banner.
3. Shortcut kategori.
4. Promo atau flash sale.
5. Produk terlaris.
6. Produk terbaru.
7. Rekomendasi produk.
8. Benefit atau keunggulan toko.
9. Newsletter jika diperlukan.
10. Footer.

### Hero section

Hero harus:

- Menjelaskan value utama EduCart.
- Memiliki CTA yang jelas.
- Tidak terlalu tinggi.
- Tetap terlihat baik pada mobile.
- Menggunakan maksimal dua tombol.
- Tidak menampilkan terlalu banyak teks.

Contoh CTA:

- `Belanja Sekarang`
- `Lihat Promo`

---

## 11.2 Product Listing Page

Fitur:

- Judul kategori.
- Jumlah produk.
- Search.
- Filter.
- Sort.
- Grid produk.
- Pagination atau load more.
- Empty state.

Filter dapat berisi:

- Kategori.
- Rentang harga.
- Rating.
- Stok.
- Brand.
- Promo.

Pada mobile, filter tampil dalam drawer atau bottom sheet.

---

## 11.3 Product Detail Page

Struktur:

- Breadcrumb.
- Gallery produk.
- Informasi produk.
- Nama produk.
- Rating.
- Harga.
- Diskon.
- Deskripsi singkat.
- Pilihan variasi.
- Quantity selector.
- Stok.
- Tombol tambah ke keranjang.
- Tombol beli sekarang.
- Wishlist.
- Detail produk.
- Ulasan.
- Produk terkait.

### CTA priority

1. `Beli Sekarang`
2. `Tambah ke Keranjang`
3. `Tambah ke Wishlist`

CTA utama harus mudah ditemukan tanpa perlu scroll terlalu jauh pada mobile.

---

## 11.4 Cart Page

Tampilkan:

- Daftar item.
- Gambar produk.
- Nama dan variasi.
- Harga.
- Quantity control.
- Subtotal.
- Hapus item.
- Pilih item.
- Ringkasan belanja.
- Voucher.
- Total.
- CTA checkout.

Aturan:

- Perubahan quantity langsung memperbarui subtotal.
- Hapus item memerlukan konfirmasi ringan atau undo.
- Ringkasan belanja sticky pada desktop.
- CTA checkout tetap mudah diakses pada mobile.

---

## 11.5 Checkout Page

Gunakan alur yang sederhana.

Section:

1. Alamat pengiriman.
2. Metode pengiriman.
3. Daftar pesanan.
4. Voucher.
5. Metode pembayaran.
6. Ringkasan pembayaran.
7. Tombol buat pesanan.

Aturan:

- Jangan meminta data yang tidak diperlukan.
- Tampilkan validasi dengan jelas.
- Tampilkan biaya secara transparan.
- Tombol pembayaran hanya aktif jika data wajib sudah lengkap.
- Cegah double submit.

---

## 11.6 Order Success Page

Tampilkan:

- Icon sukses.
- Judul konfirmasi.
- Nomor pesanan.
- Ringkasan pembayaran.
- Instruksi berikutnya.
- Tombol lihat pesanan.
- Tombol kembali belanja.

---

## 11.7 User Profile

Menu:

- Profil saya.
- Alamat.
- Pesanan.
- Wishlist.
- Notifikasi.
- Keamanan akun.
- Keluar.

Gunakan layout sidebar pada desktop dan list menu pada mobile.

---

## 11.8 Authentication Pages

Halaman:

- Login.
- Register.
- Lupa password.
- Reset password.

Gaya:

- Form sederhana.
- Satu fokus utama.
- Tidak terlalu banyak dekorasi.
- Logo terlihat jelas.
- Password memiliki tombol show/hide.
- Error login tidak membocorkan informasi sensitif.

---

## 12. Admin Pages

## 12.1 Admin Layout

Struktur:

- Sidebar kiri.
- Topbar.
- Content area.
- Breadcrumb.
- Page title.
- Action utama di kanan.

Menu sidebar:

- Dashboard.
- Produk.
- Kategori.
- Pesanan.
- Pengguna.
- Promo.
- Ulasan.
- Laporan.
- Pengaturan.

Sidebar dapat collapse pada desktop dan menjadi drawer pada mobile.

---

## 12.2 Dashboard

Tampilkan:

- Total penjualan.
- Total pesanan.
- Total produk.
- Total pengguna.
- Grafik penjualan.
- Pesanan terbaru.
- Produk stok rendah.
- Produk terlaris.

Jangan menampilkan terlalu banyak chart dalam satu layar.

---

## 12.3 Data Table

Tabel admin harus memiliki:

- Search.
- Filter.
- Sort.
- Pagination.
- Bulk select jika diperlukan.
- Loading state.
- Empty state.
- Error state.
- Action menu.

Pada mobile:

- Gunakan horizontal scroll atau ubah menjadi card list.
- Prioritaskan data paling penting.
- Jangan memaksa seluruh kolom masuk ke layar kecil.

---

## 12.4 Product Form

Field:

- Nama produk.
- Slug.
- Kategori.
- Harga.
- Harga diskon.
- Stok.
- SKU.
- Deskripsi.
- Gambar.
- Status.
- Berat.
- Variasi jika tersedia.

Form panjang dibagi menjadi beberapa section.

Gunakan sticky action bar untuk tombol:

- Simpan draft.
- Simpan dan terbitkan.
- Batal.

---

## 13. Navigation and User Flow

### Flow pembelian utama

```txt
Home
→ Cari atau pilih produk
→ Detail produk
→ Tambah ke keranjang
→ Keranjang
→ Checkout
→ Pembayaran
→ Pesanan berhasil
```

### Flow admin produk

```txt
Dashboard
→ Produk
→ Tambah produk
→ Isi data
→ Preview
→ Simpan
→ Produk tampil di katalog
```

Setiap flow harus memiliki:

- Feedback loading.
- Feedback berhasil.
- Feedback gagal.
- Cara kembali.
- Empty state.
- Validasi yang jelas.

---

## 14. Empty States

Setiap empty state harus memiliki:

1. Icon atau ilustrasi sederhana.
2. Judul yang jelas.
3. Deskripsi singkat.
4. CTA yang relevan.

Contoh:

```txt
Keranjangmu masih kosong

Yuk, temukan produk yang kamu suka dan tambahkan ke keranjang.

[Mulai Belanja]
```

Jangan hanya menampilkan teks `Data kosong`.

---

## 15. Error States

Error harus:

- Menjelaskan apa yang terjadi.
- Memberikan solusi.
- Tidak menyalahkan pengguna.
- Menyediakan tombol coba lagi jika relevan.

Contoh:

```txt
Produk gagal dimuat

Periksa koneksi internetmu, lalu coba lagi.

[Coba Lagi]
```

---

## 16. Responsive Design

### Mobile first

Desain harus dimulai dari mobile, lalu dikembangkan ke layar lebih besar.

### Mobile rules

- Target sentuh minimal `44x44px`.
- Hindari teks terlalu kecil.
- CTA utama mudah dijangkau.
- Gunakan bottom sheet untuk filter.
- Gunakan sticky bottom action pada detail produk dan checkout jika diperlukan.
- Jangan menampilkan tabel desktop secara paksa.

### Desktop rules

- Gunakan whitespace lebih luas.
- Maksimalkan grid.
- Gunakan hover sebagai feedback tambahan, bukan satu-satunya feedback.
- Sidebar atau summary dapat dibuat sticky.

---

## 17. Accessibility

Wajib memperhatikan:

- Kontras warna.
- Keyboard navigation.
- Focus state.
- Label pada form.
- `aria-label` pada icon button.
- Alt text pada gambar.
- Error message yang terhubung dengan input.
- Jangan menggunakan warna sebagai satu-satunya indikator status.
- Respect `prefers-reduced-motion`.

Focus ring:

```css
outline: 2px solid rgba(0, 128, 255, 0.45);
outline-offset: 2px;
```

---

## 18. Motion and Animation

Gunakan animasi yang halus dan cepat.

### Durasi

| Jenis | Durasi |
|---|---:|
| Micro interaction | `120ms - 180ms` |
| Dropdown | `160ms - 220ms` |
| Modal | `200ms - 280ms` |
| Page transition | `220ms - 320ms` |

### Easing

```css
cubic-bezier(0.22, 1, 0.36, 1)
```

Gunakan animasi untuk:

- Hover.
- Dropdown.
- Modal.
- Toast.
- Add to cart.
- Expand/collapse.
- Skeleton.

Hindari:

- Animasi memantul berlebihan.
- Parallax berat.
- Transition lebih dari `400ms` untuk interaksi utama.
- Animasi yang menghambat proses belanja.

---

## 19. Iconography

Gunakan icon library yang konsisten, misalnya:

- Lucide React
- Radix Icons
- Heroicons

Aturan:

- Default size: `20px`.
- Icon kecil: `16px`.
- Icon besar: `24px`.
- Stroke harus konsisten.
- Jangan mencampur terlalu banyak gaya icon.
- Icon button wajib memiliki tooltip pada desktop.

---

## 20. Image Guidelines

### Product image

- Gunakan rasio konsisten.
- Background bersih.
- Resolusi cukup tinggi.
- Optimalkan ukuran file.
- Gunakan lazy loading.
- Gunakan fallback image ketika gagal dimuat.

### Banner

- Tidak boleh mengandung terlalu banyak teks di dalam gambar.
- Informasi penting tetap ditulis sebagai HTML.
- Pastikan crop aman untuk mobile.

---

## 21. Content and Copywriting

Gunakan Bahasa Indonesia yang:

- Ringkas.
- Ramah.
- Jelas.
- Tidak terlalu formal.
- Tidak ambigu.

### Contoh label yang disarankan

Gunakan:

- `Tambah ke Keranjang`
- `Beli Sekarang`
- `Simpan Perubahan`
- `Coba Lagi`
- `Lihat Pesanan`
- `Lanjut ke Pembayaran`

Hindari:

- `Submit`
- `OK` untuk semua tindakan.
- Pesan error teknis mentah.
- Kalimat terlalu panjang.

---

## 22. Dark Mode

Dark mode bersifat opsional.

Jika digunakan:

- Jangan hanya membalik warna.
- Pertahankan hierarchy.
- Gunakan background gelap kebiruan.
- Kurangi shadow.
- Gunakan border untuk memisahkan surface.
- Pastikan gambar produk tetap terlihat baik.

Contoh:

```txt
Background: #0B1220
Surface: #111827
Border: #1F2937
Primary text: #F8FAFC
Secondary text: #94A3B8
Primary: #3B9CFF
```

---

## 23. Tailwind Theme Recommendation

```ts
const colors = {
  primary: {
    50: "#EFF8FF",
    100: "#D9EEFF",
    200: "#BCE0FF",
    300: "#8ECCFF",
    400: "#56AEFF",
    500: "#0080FF",
    600: "#006FE0",
    700: "#0059B8",
    800: "#064B91",
    900: "#0A3F76",
  },
};
```

Gunakan class yang konsisten.

Contoh primary button:

```tsx
className="
  inline-flex h-11 items-center justify-center gap-2
  rounded-xl bg-primary-500 px-5
  text-sm font-semibold text-white
  transition duration-200
  hover:bg-primary-600
  active:bg-primary-700
  focus-visible:outline-none
  focus-visible:ring-2
  focus-visible:ring-primary-400
  focus-visible:ring-offset-2
  disabled:pointer-events-none
  disabled:opacity-50
"
```

---

## 24. Recommended Component Structure

```txt
components/
├── ui/
│   ├── button
│   ├── input
│   ├── select
│   ├── dialog
│   ├── drawer
│   ├── badge
│   ├── skeleton
│   └── toast
├── layout/
│   ├── header
│   ├── footer
│   ├── container
│   ├── admin-sidebar
│   └── admin-topbar
├── product/
│   ├── product-card
│   ├── product-grid
│   ├── product-gallery
│   ├── product-filter
│   ├── product-price
│   └── product-rating
├── cart/
│   ├── cart-item
│   ├── cart-summary
│   └── quantity-selector
└── checkout/
    ├── address-card
    ├── shipping-option
    ├── payment-method
    └── order-summary
```

---

## 25. UI Quality Checklist

Sebelum sebuah halaman dianggap selesai, pastikan:

- [ ] Sesuai dengan warna brand EduCart.
- [ ] Responsif pada mobile, tablet, dan desktop.
- [ ] Memiliki loading state.
- [ ] Memiliki empty state.
- [ ] Memiliki error state.
- [ ] Semua button memiliki hover, focus, active, dan disabled state.
- [ ] Tidak ada layout shift yang mengganggu.
- [ ] Gambar menggunakan ukuran dan rasio yang konsisten.
- [ ] Teks mudah dibaca.
- [ ] CTA utama terlihat jelas.
- [ ] Form memiliki label dan validasi.
- [ ] Icon memiliki arti yang jelas.
- [ ] Tidak ada animasi berlebihan.
- [ ] Navigasi keyboard dapat digunakan.
- [ ] Tampilan tetap rapi pada data yang panjang.
- [ ] Tidak ada teks teknis mentah yang terlihat oleh pengguna.

---

## 26. AI / Developer Implementation Rules

Saat membuat atau memperbarui UI EduCart:

1. Selalu ikuti warna, spacing, radius, dan typography dari dokumen ini.
2. Gunakan komponen reusable.
3. Jangan membuat style berbeda untuk komponen dengan fungsi sama.
4. Jangan menggunakan warna random di luar design token.
5. Prioritaskan mobile-first.
6. Gunakan semantic HTML.
7. Jangan menghapus focus outline tanpa pengganti.
8. Tambahkan loading, empty, error, dan disabled state.
9. Gunakan icon dari satu library yang konsisten.
10. Hindari file komponen yang terlalu besar.
11. Pisahkan business logic dari presentational component.
12. Pastikan layout tidak rusak oleh nama produk atau harga yang panjang.
13. Gunakan data dummy yang realistis saat membuat prototype.
14. Jangan membuat desain terlihat seperti dashboard pada halaman customer.
15. Jangan memenuhi halaman dengan terlalu banyak card.
16. Pertahankan hierarchy visual yang jelas.
17. Gunakan CTA utama maksimal satu per section.
18. Berikan feedback setelah setiap aksi pengguna.
19. Pastikan semua fitur penting dapat digunakan tanpa hover.
20. Jangan mengorbankan usability demi dekorasi.

---

## 27. Final Design Principle

> EduCart harus terasa cepat, bersih, modern, dan mudah dipercaya.  
> Setiap elemen desain harus membantu pengguna menemukan produk, memahami informasi, dan menyelesaikan pembelian tanpa kebingungan.
