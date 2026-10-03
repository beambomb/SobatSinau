# SobatSinau - Backend API (Google Classroom Clone)

SobatSinau adalah backend RESTful API untuk platform Learning Management System (LMS) terinspirasi dari Google Classroom. Dibangun menggunakan **Laravel**, mengusung arsitektur berbasis standar industri dengan **Role-Based Access Control (RBAC)** berjenjang, autentikasi **Laravel Sanctum**, serta manajemen berkas terintegrasi.

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
