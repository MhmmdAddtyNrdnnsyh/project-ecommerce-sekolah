# Seller Completion Prompts Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengganti roadmap umum di `prompt.md` dengan 11 prompt bertahap yang menuntaskan seluruh scope seller yang telah disetujui.

**Architecture:** `prompt.md` menjadi urutan kerja backend-first. Setiap bagian berdiri sebagai prompt yang dapat diberikan satu per satu kepada agen coding dan mempunyai batas scope serta verification gate sendiri.

**Tech Stack:** Markdown, Laravel 12, Pest, Inertia.js, React 19, TypeScript, Wayfinder, Tailwind CSS 4.

---

### Task 1: Tulis rangkaian prompt seller

**Files:**
- Modify: `prompt.md`
- Reference: `docs/superpowers/specs/2026-06-21-seller-completion-prompts-design.md`

- [x] **Step 1: Ganti roadmap lama dengan aturan eksekusi bersama**

Tuliskan bahwa prompt dijalankan berurutan, agen wajib membaca kondisi repo, mempertahankan fitur yang sudah lulus, tidak memakai dummy data atau dependency baru, mengikuti `design.md`, dan tidak lanjut sebelum pemeriksaan tahap aktif lolos.

- [x] **Step 2: Tulis enam prompt backend**

Urutkan prompt audit baseline, produk, inventori, fondasi status order item, endpoint pesanan seller, lalu dashboard nyata. Setiap prompt menyebut route/model/controller/request/test yang relevan, kontrak ownership, error penting, dan perintah test terfokus.

- [x] **Step 3: Tulis empat prompt frontend**

Urutkan pengelolaan produk, inventori, pesanan, lalu dashboard/navigasi. Setiap prompt mewajibkan props backend, komponen yang sudah tersedia, responsive state, Wayfinder, dan pemeriksaan TypeScript/ESLint/build.

- [x] **Step 4: Tulis prompt polish dan verifikasi akhir**

Batasi polish pada alur seller, hapus placeholder seller yang di luar scope, perbaiki hanya regresi terkait, dan jalankan seluruh pemeriksaan proyek yang tersedia.

### Task 2: Verifikasi kualitas prompt

**Files:**
- Verify: `prompt.md`

- [x] **Step 1: Periksa struktur dan placeholder**

Run:

```bash
rg -n '^## Prompt [0-9]+|TBD|TODO|FIXME' prompt.md
```

Expected: tepat 11 heading prompt dan tidak ada placeholder.

- [x] **Step 2: Periksa konsistensi scope**

Pastikan produk, inventori, order item status, pesanan, dashboard, frontend, navigasi, security, dan full verification masing-masing tercakup; reviews, reports, payout, notifikasi, dan profil toko tidak diminta untuk diimplementasikan.

- [x] **Step 3: Periksa diff**

Run:

```bash
git diff --check
git diff -- prompt.md
```

Expected: tidak ada whitespace error dan diff hanya mengganti roadmap lama dengan prompt seller.
