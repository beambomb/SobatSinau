# Pintaria - Backend API (Google Classroom Clone)

Pintaria adalah backend RESTful API untuk platform Learning Management System (LMS) terinspirasi dari Google Classroom. Dibangun menggunakan **Laravel**, mengusung arsitektur berbasis standar industri dengan **Role-Based Access Control (RBAC)** berjenjang, autentikasi **Laravel Sanctum**, serta manajemen berkas terintegrasi.

---

## 🛠️ Tech Stack & Ekosistem

- **Framework:** Laravel 12 (PHP 8.5+)
- **Database:** MySQL / MariaDB (via Laragon)
- **Autentikasi API:** Laravel Sanctum (Token-based Bearer Authentication)
- **Otorisasi (RBAC):** `spatie/laravel-permission` (Role: `admin`, `guru`, `siswa`)
- **File Storage:** Laravel Storage (Public disk dengan symlink `public/storage`)
- **Code Formatter:** Laravel Pint (`vendor/bin/pint`)

---

## 👥 Role & Hak Akses (RBAC Matrix)

Aplikasi memiliki 3 peran (*roles*) dengan batasan hak akses yang ketat:

| Fitur / Modul | Admin (Super Admin) | Guru (Instruktur) | Siswa (Murid) |
| :--- | :---: | :---: | :---: |
| **Login & Dapatkan Token Sanctum** | ✅ | ✅ | ✅ |
| **Buat & Kelola Kelas Sendiri** | ✅ | ✅ (Otomatis generate kode unik) | ❌ |
| **Join Kelas via Kode Unik** | ❌ (Bypass via Admin) | ❌ (Pemilik kelas dicegah) | ✅ |
| **Daftar Kelas yang Diikuti** | ❌ | ❌ | ✅ (`/api/my-classrooms`) |
| **Keluar dari Kelas (Leave)** | ❌ | ❌ | ✅ |
| **Lihat & Kick Siswa dari Kelas** | ✅ | ✅ (Khusus kelas miliknya) | ❌ |
| **Posting Pengumuman / Materi (+ File)** | ✅ | ✅ | ✅ (Tipe diskusi forum) |
| **Komentar di Postingan Forum** | ✅ | ✅ | ✅ |
| **Buat Tugas & Soal (+ File Soal)** | ✅ | ✅ | ❌ |
| **Kumpul Tugas (Upload Jawaban)** | ❌ | ❌ | ✅ |
| **Tarik Tugas (Unsubmit jika salah kirim)**| ❌ | ❌ | ✅ (Sebelum dinilai guru) |
| **Penilaian Tugas Siswa (Grading)** | ✅ | ✅ (Khusus tugas kelasnya) | ❌ (Hanya lihat nilai sendiri) |
| **CRUD Pengguna & Sinkronisasi Role** | ✅ | ❌ | ❌ |
| **Dashboard Statistik Sistem** | ✅ | ❌ | ❌ |
| **Pengawasan & Hapus Kelas Global** | ✅ | ❌ | ❌ |

---

## 🗄️ Skema Database & Relasi Eloquent

### 1. `users` & RBAC (`roles`, `permissions`, `model_has_roles`)
- Menyimpan identitas akun (`name`, `email`, `password`).
- Terhubung dengan Spatie Permission melalui Trait `HasRoles` dan Sanctum melalui Trait `HasApiTokens`.

### 2. `classrooms` (Tabel Kelas)
- `id` (PK)
- `teacher_id` (FK -> `users.id`, onDelete cascade)
- `title` (VARCHAR 255)
- `subject` (VARCHAR 255, Nullable)
- `code` (VARCHAR 6-10, UNIQUE & INDEXED) -> Kode acak untuk join murid.
- **Relasi:** `teacher()` (BelongsTo User), `students()` (BelongsToMany User via `classroom_user`), `posts()` (HasMany Post), `assignments()` (HasMany Assignment).

### 3. `classroom_user` (Pivot Anggota Kelas)
- `classroom_id` (FK -> `classrooms.id`, cascade)
- `user_id` (FK -> `users.id`, cascade)
- `UNIQUE(classroom_id, user_id)` -> Mencegah siswa mendaftar ganda ke kelas yang sama.

### 4. `posts` (Forum Diskusi, Pengumuman & Materi)
- `id` (PK)
- `classroom_id` (FK -> `classrooms.id`, cascade)
- `user_id` (FK -> `users.id`, cascade)
- `content` (TEXT)
- `type` (ENUM/VARCHAR: `'announcement'`, `'material'`, `'discussion'`)
- `attachment_path` (VARCHAR, Nullable) -> Path file dokumen/media di storage.
- **Relasi:** `classroom()` (BelongsTo), `user()` (BelongsTo), `comments()` (HasMany Comment).

### 5. `comments` (Komentar Diskusi Postingan)
- `id` (PK)
- `post_id` (FK -> `posts.id`, cascade)
- `user_id` (FK -> `users.id`, cascade)
- `content` (TEXT)
- **Relasi:** `post()` (BelongsTo Post), `user()` (BelongsTo User).

### 6. `assignments` (Tugas & Soal dari Guru)
- `id` (PK)
- `classroom_id` (FK -> `classrooms.id`, cascade)
- `teacher_id` (FK -> `users.id`, cascade)
- `title` (VARCHAR 255)
- `instructions` (TEXT, Nullable)
- `attachment_path` (VARCHAR, Nullable) -> File soal lampiran guru.
- `due_date` (DATETIME, Nullable)
- `max_points` (INT, default 100)
- **Relasi:** `classroom()` (BelongsTo), `teacher()` (BelongsTo), `submissions()` (HasMany Submission).

### 7. `submissions` (Pengumpulan Tugas Siswa)
- `id` (PK)
- `assignment_id` (FK -> `assignments.id`, cascade)
- `student_id` (FK -> `users.id`, cascade)
- `file_path` (VARCHAR, Nullable) -> File jawaban siswa.
- `notes` (TEXT, Nullable) -> Catatan pengumpulan siswa.
- `status` (`'submitted'`, `'unsubmitted'`, `'graded'`)
- `grade` (INT, Nullable) -> Nilai dari guru.
- `submitted_at` (DATETIME, Nullable)
- `UNIQUE(assignment_id, student_id)` -> 1 siswa 1 record submission per tugas.
- **Relasi:** `assignment()` (BelongsTo Assignment), `student()` (BelongsTo User).

---

## 📡 Dokumentasi Endpoint REST API (Total 36 Endpoints)

Base URL: `http://127.0.0.1:8000/api`

### 1. Autentikasi
| Method | Endpoint | Akses | Deskripsi |
| :--- | :--- | :--- | :--- |
| `POST` | `/login` | Public | Login akun & dapatkan Bearer Token |
| `POST` | `/logout` | Authenticated | Cabut token aktif |
| `GET` | `/user` | Authenticated | Cek profil dan role user yang sedang login |

---

### 2. Kelas - Manajemen Guru (`role:guru|admin`)
| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `GET` | `/classrooms` | Menampilkan seluruh kelas yang diajar oleh guru |
| `POST` | `/classrooms` | Buat kelas baru (otomatis generate kode unik 6 digit) |
| `GET` | `/classrooms/{classroom}` | Detail ruang kelas |
| `PUT` | `/classrooms/{classroom}` | Edit judul / mata pelajaran kelas |
| `DELETE` | `/classrooms/{classroom}` | Hapus kelas (hanya pemilik kelas/admin) |
| `GET` | `/classrooms/{classroom}/students` | Daftar seluruh siswa yang terdaftar di kelas |
| `DELETE` | `/classrooms/{classroom}/students/{student}` | Guru mengeluarkan (kick) siswa dari kelas |

---

### 3. Kelas - Khusus Murid
| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `GET` | `/my-classrooms` | Daftar seluruh kelas yang sedang diikuti siswa |
| `POST` | `/classrooms/join` | Siswa bergabung ke kelas menggunakan kode unik (Body: `{"code": "..."}`) |
| `POST` | `/classrooms/{classroom}/leave` | Siswa keluar dari kelas |

---

### 4. Forum Stream, Materi & Komentar (Guru & Murid)
| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `GET` | `/classrooms/{classroom}/posts` | Linimasa postingan pengumuman & materi |
| `POST` | `/classrooms/{classroom}/posts` | Buat postingan (+ upload media file via form-data) |
| `DELETE` | `/posts/{post}` | Hapus postingan (pembuat post, guru kelas, atau admin) |
| `GET` | `/posts/{post}/comments` | Daftar seluruh komentar di postingan |
| `POST` | `/posts/{post}/comments` | Kirim komentar balasan di postingan |
| `DELETE` | `/comments/{comment}` | Hapus komentar |

---

### 5. Tugas (Assignments) & Pengumpulan (Submissions)
| Method | Endpoint | Role | Deskripsi |
| :--- | :--- | :---: | :--- |
| `GET` | `/classrooms/{classroom}/assignments` | Guru & Siswa | Daftar tugas di kelas |
| `POST` | `/classrooms/{classroom}/assignments` | Guru & Admin | Buat tugas baru (+ upload file soal) |
| `GET` | `/assignments/{assignment}` | Guru & Siswa | Detail informasi tugas |
| `DELETE` | `/assignments/{assignment}` | Guru & Admin | Hapus tugas |
| `GET` | `/assignments/{assignment}/submissions` | Guru & Admin | Guru melihat seluruh tugas yang dikumpulkan siswa |
| `POST` | `/submissions/{submission}/grade` | Guru & Admin | Guru memberikan nilai (score) dan catatan |
| `GET` | `/assignments/{assignment}/my-submission`| Siswa | Siswa melihat status pengumpulan & nilai sendiri |
| `POST` | `/assignments/{assignment}/submit` | Siswa | Siswa mengumpulkan tugas (+ upload file jawaban) |
| `POST` | `/assignments/{assignment}/unsubmit` | Siswa | Siswa menarik tugas jika salah kirim (sebelum dinilai) |

---

### 6. Area Khusus Administrator (`role:admin`, Prefix `/admin`)
| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `GET` | `/admin/dashboard` | Statistik platform: total user, guru, siswa, kelas, tugas, submission |
| `GET` | `/admin/classrooms` | Pengawasan global seluruh kelas beserta jumlah murid & postingan |
| `DELETE` | `/admin/classrooms/{classroom}` | Super admin menghapus kelas manapun di sistem |
| `GET` | `/admin/users` | Daftar seluruh user dengan filter `?role=...` dan `?search=...` |
| `POST` | `/admin/users` | Admin membuat akun baru dan langsung menetapkan role |
| `GET` | `/admin/users/{user}` | Detail profil user, role, dan riwayat kelas |
| `PUT` | `/admin/users/{user}` | Edit profil dan ubah role user |
| `DELETE` | `/admin/users/{user}` | Hapus user (terproteksi: tidak bisa hapus akun admin sendiri) |

---

## 🔑 Akun Default Hasil Seeder

Database seeder (`php artisan db:seed`) menyediakan akun default untuk pengujian:

| Role | Email | Password | Kegunaan |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@lms.test` | `password123` | Uji coba fitur admin, kelola user, monitoring global |
| **Guru** | `guru@lms.test` | `password123` | Uji coba buat kelas, upload materi, bikin tugas, beri nilai |
| **Siswa** | `siswa@lms.test` | `password123` | Uji coba join kelas via kode, kumpul tugas, tarik tugas |

---

## 🚀 Perintah Dasar Pengembangan (Workflow)

```bash
# Menjalankan server API lokal (Port 8000)
php artisan serve

# Menghubungkan storage publik (upload berkas materi & tugas)
php artisan storage:link

# Menjalankan ulang migrasi bersih beserta data seeder
php artisan migrate:fresh --seed

# Memeriksa seluruh daftar endpoint API
php artisan route:list --path=api

# Merapikan gaya penulisan kode PHP (Pint)
vendor/bin/pint --format agent
```

---

## ⚠️ Status Pekerjaan & Catatan Titik Terputus (Handoff Log)

### 📌 Ringkasan Status
- **Commit Terakhir di GitHub (`main`):** `6966700 chore: ubah nama aplikasi menjadi Pintaria`
- **Waktu Terputus:** 03 Oktober 2026, ~19:37 WIB.
- **Penyebab:** Model Claude Opus 5.5 kehabisan kredit/token kuota saat sedang mengeksekusi instruksi perombakan arsitektur (migrasi dari Blade ke Headless API + Modern SPA).
- **Kondisi Working Tree Saat Ini:** Setengah jalan (*Uncommitted* & *Partially Broken* jika belum diperbaiki).

---

### ✅ 1. Pekerjaan yang Sudah Selesai Dibuat Opus (Uncommitted)
1. **Domain Enums (`app/Enums/`):**
   - [`UserRole.php`](file:///c:/Users/LENOVO%20X1%20CARBON/Documents/project_web/LMS_php/app/Enums/UserRole.php): Role `Admin`, `Teacher` (`guru`), `Student` (`siswa`) dengan helper label dan color.
   - [`PostType.php`](file:///c:/Users/LENOVO%20X1%20CARBON/Documents/project_web/LMS_php/app/Enums/PostType.php): Tipe postingan `Announcement`, `Material`, `Discussion`.
   - [`SubmissionStatus.php`](file:///c:/Users/LENOVO%20X1%20CARBON/Documents/project_web/LMS_php/app/Enums/SubmissionStatus.php): Status `Submitted`, `Graded`.
2. **Database Migration Baru:**
   - [`database/migrations/2026_10_03_120000_add_attachment_names_and_feedback.php`](file:///c:/Users/LENOVO%20X1%20CARBON/Documents/project_web/LMS_php/database/migrations/2026_10_03_120000_add_attachment_names_and_feedback.php): Kolom `attachment_name`, `file_name`, dan `feedback`.
3. **Penyempurnaan Model Eloquent:**
   - Casting enum, auto-delete file lampiran saat record dihapus (*deleting hook*), dan relasi di `User`, `Classroom`, `Post`, `Comment`, `Assignment`, `Submission`.
4. **Authorization Policies (`app/Policies/`):**
   - 5 file policy: `ClassroomPolicy`, `PostPolicy`, `CommentPolicy`, `AssignmentPolicy`, `SubmissionPolicy`.

---

### 🚨 2. Masalah yang Terjadi Akibat Terputus di Tengah Jalan (Broken State)
Sebelum sempat menulis controller baru dan frontend SPA, Opus telah menjalankan command `Remove-Item` yang menghapus file-file berikut:
1. **Controller API yang Terhapus:**
   - `app/Http/Controllers/ClassroomController.php`
   - `app/Http/Controllers/Api/ClassroomMemberController.php`
   - `app/Http/Controllers/Api/StudentClassroomController.php`
   - `app/Http/Controllers/Api/StudentSubmissionController.php`
   *(Catatan: File-file ini masih di-`import` dan dipanggil di [`routes/api.php`](file:///c:/Users/LENOVO%20X1%20CARBON/Documents/project_web/LMS_php/routes/api.php), sehingga API error jika controller ini tidak dipulihkan/direfaktor).*
2. **`routes/web.php` Error:**
   - Masih memanggil `require __DIR__.'/auth.php';` dan `ProfileController` milik Breeze yang sudah dihapus.
3. **Frontend UI SPA:**
   - Belum sempat dibuat sama sekali.

---

### 📋 3. Checklist Langkah untuk Melanjutkan (Next Action Plan)
- [ ] **Langkah 1:** Pulihkan 4 controller API yang terhapus dari git history (`git restore app/Http/Controllers/ClassroomController.php app/Http/Controllers/Api/...`).
- [ ] **Langkah 2:** Bersihkan [`routes/web.php`](file:///c:/Users/LENOVO%20X1%20CARBON/Documents/project_web/LMS_php/routes/web.php) dari dependensi Breeze auth yang sudah dihapus agar tidak fatal error.
- [ ] **Langkah 3:** Jalankan migrasi database `php artisan migrate` untuk menerapkan kolom lampiran & feedback baru.
- [ ] **Langkah 4:** Validasi `php artisan route:list` untuk memastikan semua 36 endpoint API berfungsi normal tanpa error.
- [ ] **Langkah 5:** Bangun antarmuka Frontend Modern SPA (Minimalis, interaktif, responsif, UX optimal) yang berkomunikasi dengan REST API Pintaria.
- [ ] **Langkah 6:** Buat commit dan push ke GitHub setelah seluruh aplikasi berfungsi normal.


---

## ✅ Status Implementasi Terbaru

Bagian ini memperbarui handoff di atas setelah implementasi Pintaria dilanjutkan:

- Backend REST API sudah dipulihkan dan dioptimalkan: controller kelas/member/siswa/submission tersedia, migration LMS lengkap, seeder role dan akun demo tersedia, serta akses detail kelas tervalidasi untuk member.
- Frontend sudah beralih ke SPA ringan berbasis Blade shell + Vite/Alpine + CSS custom. Alur yang tersedia: login Sanctum, dashboard role-aware, daftar/buat/gabung kelas, detail kelas, forum, komentar, lampiran, tugas, pengumpulan siswa, unsubmit, grading/feedback guru, dan CRUD pengguna admin.
- Akun demo tetap: `admin@lms.test`, `guru@lms.test`, dan `siswa@lms.test`, semuanya dengan password `password123` setelah `php artisan migrate:fresh --seed`.
- Validasi terakhir: migration fresh + seed berhasil, 36 route API terdaftar, Pint lulus, 4 API integration tests lulus dengan 16 assertions, dan `npm run build` berhasil.
- Scaffold Breeze Blade/auth lama dihapus karena tidak lagi digunakan oleh arsitektur headless API + SPA. Entry point web sekarang adalah `resources/views/app.blade.php`, sedangkan data aplikasi dikonsumsi melalui `routes/api.php`.
- Riwayat perubahan dibagi menjadi commit kecil agar mudah direview dan di-revert; jangan squash atau reset commit tersebut tanpa alasan yang jelas.
