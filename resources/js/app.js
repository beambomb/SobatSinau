import Alpine from 'alpinejs';
import '../css/app.css';

window.Alpine = Alpine;
Alpine.start();

const root = document.querySelector('#app');
const TOKEN_KEY = 'pintaria_token';
const USER_KEY = 'pintaria_user';
const state = {
    token: localStorage.getItem(TOKEN_KEY),
    user: JSON.parse(localStorage.getItem(USER_KEY) || 'null'),
    page: 'dashboard',
    classrooms: [],
    admin: null,
    users: [],
    activeClassroom: null,
    posts: [],
    assignments: [],
    students: [],
    tab: 'stream',
    comments: {},
    modal: null,
    modalAssignment: null,
    modalData: null,
    loading: false,
    toast: null,
};

const role = () => state.user?.roles?.[0] || 'siswa';
const roleLabel = () => ({ admin: 'Admin', guru: 'Guru', siswa: 'Siswa' })[role()] || 'Pengguna';
const initials = (name = '') => name.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase() || 'P';
const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[char]);
const formatDate = (value) => value ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value)) : '—';
const isManager = () => role() === 'guru' || role() === 'admin';

function saveSession(token, user) { state.token = token; state.user = user; localStorage.setItem(TOKEN_KEY, token); localStorage.setItem(USER_KEY, JSON.stringify(user)); }
function clearSession() { state.token = null; state.user = null; localStorage.removeItem(TOKEN_KEY); localStorage.removeItem(USER_KEY); }

async function api(path, options = {}) {
    const config = { ...options, headers: { Accept: 'application/json', ...(options.headers || {}) } };
    if (state.token) config.headers.Authorization = `Bearer ${state.token}`;
    if (config.body && !(config.body instanceof FormData)) { config.headers['Content-Type'] = 'application/json'; config.body = JSON.stringify(config.body); }
    const response = await fetch(`/api${path}`, config);
    const payload = await response.json().catch(() => ({}));
    if (response.status === 401) { clearSession(); render(); }
    if (!response.ok) {
        const validation = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
        throw new Error(validation || payload.message || 'Terjadi kesalahan.');
    }
    return payload;
}

function notify(message, type = 'success') {
    state.toast = { message, type }; render();
    window.setTimeout(() => { state.toast = null; render(); }, 3200);
}

function render() {
    if (!state.user) return renderLogin();
    const titles = { dashboard: 'Ringkasan aktivitas', classes: 'Kelas saya', users: 'Pengguna', classroom: state.activeClassroom?.title || 'Ruang kelas' };
    root.innerHTML = `<div class="app-shell"><aside class="sidebar" id="sidebar"><div class="brand"><span class="brand-mark">P</span><span>Pintaria</span></div><div class="sidebar-profile"><div class="avatar avatar-light">${escapeHtml(initials(state.user.name))}</div><div><strong>${escapeHtml(state.user.name)}</strong><small>${roleLabel()}</small></div></div><nav class="main-nav"><button class="nav-item ${state.page === 'dashboard' ? 'active' : ''}" data-nav="dashboard"><span>⌂</span> Beranda</button><button class="nav-item ${state.page === 'classes' ? 'active' : ''}" data-nav="classes"><span>▦</span> Kelas saya</button>${role() === 'admin' ? `<button class="nav-item ${state.page === 'users' ? 'active' : ''}" data-nav="users"><span>♙</span> Pengguna</button>` : ''}</nav><div class="sidebar-help"><span>✦</span><strong>Belajar lebih terarah</strong><small>Kelola kelas dan tugas dari satu tempat.</small></div><button class="nav-item logout-item" data-logout><span>↪</span> Keluar</button></aside><main class="main-content"><header class="topbar"><button class="icon-button mobile-menu" data-menu aria-label="Buka menu">☰</button><div><p class="eyebrow">${roleLabel()} workspace</p><h1 id="page-title">${titles[state.page] || 'Ruang kelas'}</h1></div><div class="topbar-actions"><button class="icon-button" title="Notifikasi">♢</button><div class="avatar avatar-brand">${escapeHtml(initials(state.user.name))}</div></div></header><div class="page-content" id="page-content">${pageView()}</div></main></div>${state.toast ? `<div class="toast toast-${state.toast.type}">${state.toast.type === 'success' ? '✓' : '!'} ${escapeHtml(state.toast.message)}</div>` : ''}`;
    bindShellEvents();
}

function renderLogin() {
    root.innerHTML = `<div class="auth-page"><div class="auth-art"><div class="auth-orbit orbit-one"></div><div class="auth-orbit orbit-two"></div><div class="auth-copy"><div class="brand brand-large"><span class="brand-mark">P</span><span>Pintaria</span></div><h1>Belajar lebih mudah,<br><em>bertumbuh bersama.</em></h1><p>Ruang belajar digital yang rapi untuk guru dan siswa.</p><div class="auth-points"><span>✓ Kelola kelas dengan praktis</span><span>✓ Tugas dan diskusi terpusat</span></div></div></div><div class="auth-panel"><div class="auth-card"><div class="mobile-brand brand"><span class="brand-mark">P</span><span>Pintaria</span></div><p class="eyebrow">Selamat datang kembali</p><h2>Masuk ke ruang belajar</h2><p class="muted">Gunakan akun Pintaria untuk melanjutkan.</p><form id="login-form" class="stack-form"><label>Email<input name="email" type="email" placeholder="nama@email.com" required autocomplete="email"></label><label>Password<div class="password-field"><input name="password" type="password" placeholder="Masukkan password" required autocomplete="current-password"><button type="button" data-toggle-password>lihat</button></div></label><button class="button button-primary button-block" type="submit"><span data-submit-label>Masuk</span></button></form><div class="demo-login"><span>Demo cepat</span><div><button data-demo="admin@lms.test">Admin</button><button data-demo="guru@lms.test">Guru</button><button data-demo="siswa@lms.test">Siswa</button></div><small>Password demo: <strong>password123</strong></small></div><p class="form-error" id="login-error"></p></div><p class="auth-footer">Pintaria LMS · Ruang belajar yang nyaman</p></div></div>`;
    root.querySelectorAll('[data-demo]').forEach((button) => button.addEventListener('click', () => { root.querySelector('[name=email]').value = button.dataset.demo; root.querySelector('[name=password]').value = 'password123'; root.querySelector('#login-form').requestSubmit(); }));
    root.querySelector('[data-toggle-password]').addEventListener('click', (event) => { const input = event.currentTarget.parentElement.querySelector('input'); input.type = input.type === 'password' ? 'text' : 'password'; event.currentTarget.textContent = input.type === 'password' ? 'lihat' : 'sembunyikan'; });
    root.querySelector('#login-form').addEventListener('submit', login);
}

async function login(event) {
    event.preventDefault();
    const form = new FormData(event.currentTarget); const error = root.querySelector('#login-error'); const button = event.currentTarget.querySelector('button[type=submit]');
    button.disabled = true; button.querySelector('[data-submit-label]').textContent = 'Memeriksa...'; error.textContent = '';
    try { const response = await api('/login', { method: 'POST', body: { email: form.get('email'), password: form.get('password') } }); saveSession(response.token, response.user); state.page = 'dashboard'; await loadPage(); notify('Login berhasil. Selamat datang!'); }
    catch (exception) { error.textContent = exception.message; button.disabled = false; button.querySelector('[data-submit-label]').textContent = 'Masuk'; }
}

function pageView() {
    if (state.loading) return '<div class="loading-state"><span class="loader"></span><p>Menyiapkan ruang belajar...</p></div>';
    if (state.page === 'classes') return classesView();
    if (state.page === 'classroom') return classroomView();
    if (state.page === 'users') return usersView();
    return dashboardView();
}

function dashboardView() {
    const classCount = state.classrooms.length;
    const countLabel = role() === 'siswa' ? 'kelas diikuti' : 'kelas dikelola';
    const stats = role() === 'admin' && state.admin ? [['Pengguna', state.admin.stats.total_users, '♙'], ['Kelas aktif', state.admin.stats.total_classrooms, '▦'], ['Tugas', state.admin.stats.total_assignments, '✓'], ['Pengumpulan', state.admin.stats.total_submissions, '↗']] : [['Total kelas', classCount, '▦'], ['Peran', roleLabel(), '✦'], ['Status', 'Aktif', '●']];
    return `<section class="welcome-banner"><div><span class="pill pill-white">${roleLabel()} · Hari ini</span><h2>Ruang belajar yang lebih teratur.</h2><p>Lihat kelas, tugas, dan aktivitas terbaru dalam satu dashboard.</p></div><div class="banner-shape">✦</div></section><section class="stat-grid">${stats.map(([label, value, icon]) => `<div class="stat-card card"><span class="stat-icon">${icon}</span><div><small>${label}</small><strong>${value}</strong></div></div>`).join('')}</section><div class="section-heading"><div><p class="eyebrow">Aktivitas utama</p><h2>${countLabel}</h2></div><button class="button button-soft" data-nav="classes">Lihat semua <span>→</span></button></div>${classGrid(state.classrooms.slice(0, 3))}`;
}

function classesView() {
    const action = role() === 'siswa' ? `<button class="button button-primary" data-modal="join">+ Gabung kelas</button>` : `<button class="button button-primary" data-modal="create-class">+ Buat kelas</button>`;
    return `<div class="page-toolbar"><div><p class="eyebrow">Ruang belajar</p><h2>Semua kelas</h2><p class="muted">${role() === 'siswa' ? 'Kelas yang sedang kamu ikuti.' : 'Kelola ruang kelas dan aktivitas belajar.'}</p></div>${action}</div>${classGrid(state.classrooms, true)}${state.modal ? modalView() : ''}`;
}

function classGrid(classrooms, full = false) {
    if (!classrooms.length) return `<section class="empty-state card"><div class="empty-icon">▦</div><h3>Belum ada kelas</h3><p>${role() === 'siswa' ? 'Gabung menggunakan kode kelas dari gurumu.' : 'Buat kelas pertama untuk mulai mengajar.'}</p></section>`;
    return `<section class="class-grid">${classrooms.map((item, index) => `<article class="class-card card" data-classroom="${item.id}"><div class="class-cover cover-${index % 4}"><span>${escapeHtml((item.subject || 'KELAS').toUpperCase())}</span><strong>${escapeHtml(item.title)}</strong><small>${escapeHtml(item.teacher?.name || state.user.name)}</small><div class="cover-symbol">${['✦', '◌', '△', '○'][index % 4]}</div></div><div class="class-card-body"><div class="class-meta"><span>▦ ${item.assignments_count || 0} tugas</span><span>♙ ${item.students_count || 0} siswa</span></div><p>${item.posts_count || 0} aktivitas forum</p><button class="button button-outline button-block">Buka kelas <span>→</span></button></div></article>`).join('')}</section>`;
}

function usersView() {
    return `<div class="page-toolbar"><div><p class="eyebrow">Kontrol akses</p><h2>Pengguna Pintaria</h2><p class="muted">Kelola akun dan peran pengguna dengan aman.</p></div><button class="button button-primary" data-user-modal="create">+ Tambah pengguna</button></div><section class="user-table card"><div class="user-table-head"><span>Pengguna</span><span>Peran</span><span>Bergabung</span><span>Aksi</span></div>${state.users.length ? state.users.map((user) => `<div class="user-row"><div class="user-identity"><div class="avatar avatar-soft">${escapeHtml(initials(user.name))}</div><div><strong>${escapeHtml(user.name)}</strong><small>${escapeHtml(user.email)}</small></div></div><div><span class="role-badge role-${user.roles?.[0]?.name || 'siswa'}">${escapeHtml(user.roles?.[0]?.name || 'siswa')}</span></div><small class="user-date">${formatDate(user.created_at)}</small><div class="user-actions"><button class="button button-soft" data-user-modal="edit" data-user-id="${user.id}">Edit</button>${user.id !== state.user.id ? `<button class="icon-button user-delete" data-delete-user="${user.id}">×</button>` : ''}</div></div>`).join('') : '<div class="empty-table">Belum ada pengguna.</div>'}</section>${state.modal === 'user-form' ? modalView() : ''}`;
}

function usersPlaceholder() { return usersView(); }
function modalView() {
    if (state.modal === 'user-form') {
        const user = state.modalUser || {}; const editing = Boolean(user.id);
        return `<div class="modal-backdrop" data-close-modal><div class="modal-card" data-stop-click><button class="modal-close" data-close-modal>×</button><p class="eyebrow">${editing ? 'Edit pengguna' : 'Pengguna baru'}</p><h2>${editing ? 'Perbarui profil' : 'Tambah pengguna'}</h2><p class="muted">Tetapkan akses sesuai tanggung jawab pengguna.</p><form id="user-form" class="stack-form"><input type="hidden" name="id" value="${user.id || ''}"><label>Nama lengkap<input name="name" value="${escapeHtml(user.name || '')}" required maxlength="255"></label><label>Email<input name="email" type="email" value="${escapeHtml(user.email || '')}" required></label><label>Peran<select name="role" required><option value="admin" ${user.roles?.[0]?.name === 'admin' ? 'selected' : ''}>Admin</option><option value="guru" ${user.roles?.[0]?.name === 'guru' ? 'selected' : ''}>Guru</option><option value="siswa" ${!user.id || user.roles?.[0]?.name === 'siswa' ? 'selected' : ''}>Siswa</option></select></label><label>Password${editing ? '<small class="muted">Kosongkan jika tidak diubah.</small>' : ''}<input name="password" type="password" ${editing ? '' : 'required'} minlength="8" placeholder="Minimal 8 karakter"></label><button class="button button-primary button-block">${editing ? 'Simpan perubahan' : 'Buat pengguna'}</button></form></div></div>`;
    }
 return `<div class="modal-backdrop" data-close-modal><div class="modal-card" data-stop-click><button class="modal-close" data-close-modal>×</button><p class="eyebrow">Gabung kelas</p><h2>Masukkan kode kelas</h2><p class="muted">Minta kode 7 karakter dari guru kamu.</p><form id="join-form" class="stack-form"><label>Kode kelas<input name="code" minlength="7" maxlength="7" placeholder="contoh: pintari" required autocomplete="off"></label><button class="button button-primary button-block">Gabung sekarang</button></form></div></div>`;
    if (state.modal === 'create-class') return `<div class="modal-backdrop" data-close-modal><div class="modal-card" data-stop-click><button class="modal-close" data-close-modal>×</button><p class="eyebrow">Kelas baru</p><h2>Buat ruang kelas</h2><p class="muted">Buat ruang yang nyaman untuk materi dan diskusi.</p><form id="create-class-form" class="stack-form"><label>Nama kelas<input name="title" placeholder="Contoh: Pemrograman Web" required maxlength="255"></label><label>Mata pelajaran<input name="subject" placeholder="Contoh: Teknologi Informasi" maxlength="255"></label><button class="button button-primary button-block">Buat kelas</button></form></div></div>`;
    if (state.modal === 'submit-assignment') {
        const assignment = state.modalAssignment; const submission = state.modalData;
        return `<div class="modal-backdrop" data-close-modal><div class="modal-card modal-wide" data-stop-click><button class="modal-close" data-close-modal>×</button><p class="eyebrow">Pengumpulan tugas</p><h2>${escapeHtml(assignment.title)}</h2><p class="muted">Nilai maksimal ${assignment.max_points} · Deadline ${formatDate(assignment.due_date)}</p>${submission?.status === 'graded' ? `<div class="grade-result"><span>Nilai kamu</span><strong>${submission.grade}/${assignment.max_points}</strong><p>${escapeHtml(submission.feedback || 'Belum ada catatan dari guru.')}</p></div>` : `<form id="submit-work-form" class="stack-form"><label>File jawaban${submission?.file_name ? `<small class="current-file">File saat ini: ${escapeHtml(submission.file_name)}</small>` : ''}<input name="file" type="file" ${submission ? '' : 'required'}></label><label>Catatan untuk guru<textarea name="notes" rows="3" placeholder="Tambahkan catatan jika perlu">${escapeHtml(submission?.notes || '')}</textarea></label><button class="button button-primary button-block">${submission ? 'Perbarui & kirim' : 'Kirim tugas'}</button></form>${submission ? '<button class="button button-danger button-block unsubmit-button" data-unsubmit>Tarik pengumpulan</button>' : ''}`}</div></div>`;
    }
    if (state.modal === 'submissions') {
        const assignment = state.modalAssignment; const submissions = state.modalData || [];
        return `<div class="modal-backdrop" data-close-modal><div class="modal-card modal-wide" data-stop-click><button class="modal-close" data-close-modal>×</button><p class="eyebrow">Review tugas</p><h2>${escapeHtml(assignment.title)}</h2><p class="muted">${submissions.length} pengumpulan masuk · Maksimal ${assignment.max_points} poin</p><div class="submission-list">${submissions.length ? submissions.map((item) => `<div class="submission-row"><div class="avatar avatar-soft">${escapeHtml(initials(item.student?.name))}</div><div class="submission-student"><strong>${escapeHtml(item.student?.name || 'Siswa')}</strong><small>${escapeHtml(item.file_name || 'Tanpa file')} · ${formatDate(item.submitted_at)}</small></div>${item.status === 'graded' ? `<div class="graded-score">${item.grade}/${assignment.max_points}</div>` : `<form class="grade-form" data-grade-submission="${item.id}"><input name="grade" type="number" min="0" max="${assignment.max_points}" placeholder="Nilai" required><input name="feedback" placeholder="Feedback singkat"><button class="button button-primary">Simpan</button></form>`}</div>`).join('') : '<p class="muted">Belum ada siswa yang mengumpulkan tugas.</p>'}</div></div></div>`;
    }
    return '';
}

function bindShellEvents() {
    root.querySelectorAll('[data-nav]').forEach((button) => button.addEventListener('click', () => openPage(button.dataset.nav)));
    root.querySelectorAll('[data-classroom]').forEach((card) => card.addEventListener('click', () => openClassroom(Number(card.dataset.classroom))));
    root.querySelectorAll('[data-tab]').forEach((button) => button.addEventListener('click', () => { state.tab = button.dataset.tab; render(); }));
    root.querySelector('[data-back-classes]')?.addEventListener('click', () => openPage('classes'));
    root.querySelector('[data-copy-code]')?.addEventListener('click', async () => { await navigator.clipboard?.writeText(state.activeClassroom.code); notify('Kode kelas disalin.'); });
    root.querySelectorAll('[data-comments]').forEach((button) => button.addEventListener('click', () => loadComments(Number(button.dataset.comments))));
    root.querySelectorAll('[data-delete-post]').forEach((button) => button.addEventListener('click', () => deletePost(Number(button.dataset.deletePost))));
    root.querySelectorAll('[data-remove-student]').forEach((button) => button.addEventListener('click', () => removeStudent(Number(button.dataset.removeStudent))));
    root.querySelector('#create-post-form')?.addEventListener('submit', createPost);
    root.querySelector('#create-assignment-form')?.addEventListener('submit', createAssignment);
    root.querySelectorAll('[data-comment-form]').forEach((form) => form.addEventListener('submit', createComment));
    root.querySelectorAll('[data-submissions]').forEach((button) => button.addEventListener('click', () => openSubmissionsModal(Number(button.dataset.submissions))));
    root.querySelectorAll('[data-submit-assignment]').forEach((button) => button.addEventListener('click', () => openSubmissionModal(Number(button.dataset.submitAssignment))));
    root.querySelector('[data-unsubmit]')?.addEventListener('click', unsubmitWork);
    root.querySelector('#submit-work-form')?.addEventListener('submit', submitWork);
    root.querySelectorAll('[data-grade-submission]').forEach((form) => form.addEventListener('submit', gradeSubmission));
    root.querySelector('[data-logout]').addEventListener('click', async () => { try { await api('/logout', { method: 'POST' }); } catch (_) {} clearSession(); render(); });
    root.querySelector('[data-menu]').addEventListener('click', () => root.querySelector('#sidebar').classList.toggle('open'));
    root.querySelectorAll('[data-modal]').forEach((button) => button.addEventListener('click', () => { state.modal = button.dataset.modal; render(); }));
    root.querySelectorAll('[data-user-modal]').forEach((button) => button.addEventListener('click', () => openUserModal(button.dataset.userModal, Number(button.dataset.userId))));
    root.querySelectorAll('[data-delete-user]').forEach((button) => button.addEventListener('click', () => deleteUser(Number(button.dataset.deleteUser))));
    root.querySelector('#user-form')?.addEventListener('submit', saveUser);
    root.querySelectorAll('[data-close-modal]').forEach((element) => element.addEventListener('click', (event) => { if (event.target === element || event.currentTarget === element) { state.modal = null; render(); } }));
    root.querySelector('[data-stop-click]')?.addEventListener('click', (event) => event.stopPropagation());
    root.querySelector('#join-form')?.addEventListener('submit', joinClass);
    root.querySelector('#create-class-form')?.addEventListener('submit', createClass);
}

async function openPage(page) { state.page = page; state.modal = null; render(); await loadPage(); }
async function loadPage() {
    state.loading = true; render();
    try {
        if (state.page === 'classes' || state.page === 'dashboard') {
            const endpoint = role() === 'siswa' ? '/my-classrooms' : '/classrooms';
            state.classrooms = (await api(endpoint)).data || [];
            if (role() === 'admin') state.admin = (await api('/admin/dashboard')).data;
        }
        if (state.page === 'users' && role() === 'admin') {
            const response = await api('/admin/users?per_page=50');
            state.users = response.data?.data || [];
        }
    } catch (exception) { notify(exception.message, 'error'); }
    state.loading = false; render();
}
async function createClass(event) { event.preventDefault(); const form = new FormData(event.currentTarget); try { await api('/classrooms', { method: 'POST', body: { title: form.get('title'), subject: form.get('subject') } }); state.modal = null; await loadPage(); notify('Kelas berhasil dibuat.'); } catch (exception) { notify(exception.message, 'error'); } }
async function joinClass(event) { event.preventDefault(); const form = new FormData(event.currentTarget); try { await api('/classrooms/join', { method: 'POST', body: { code: form.get('code').toLowerCase() } }); state.modal = null; await loadPage(); notify('Berhasil bergabung ke kelas.'); } catch (exception) { notify(exception.message, 'error'); } }

async function bootstrap() {
    if (!state.token) return render();
    try { const response = await api('/user'); state.user = response.user; localStorage.setItem(USER_KEY, JSON.stringify(state.user)); }
    catch (_) { clearSession(); }
    render();
    if (state.user) loadPage();
}

bootstrap();

function classroomView() {
    const classroom = state.activeClassroom;
    if (!classroom) return '<section class="empty-state card"><h3>Kelas tidak ditemukan</h3></section>';
    const isTeacher = role() === 'guru' || role() === 'admin';
    return `<section class="classroom-hero"><div><button class="back-link" data-back-classes>← Kembali ke kelas</button><span class="pill pill-white">${escapeHtml((classroom.subject || 'KELAS').toUpperCase())}</span><h2>${escapeHtml(classroom.title)}</h2><p>Pengajar: ${escapeHtml(classroom.teacher?.name || '—')}</p></div><div class="class-code"><small>Kode kelas</small><strong>${escapeHtml(classroom.code)}</strong><button data-copy-code title="Salin kode">⧉</button></div></section><nav class="tab-nav"><button class="${state.tab === 'stream' ? 'active' : ''}" data-tab="stream">Forum & aktivitas</button><button class="${state.tab === 'assignments' ? 'active' : ''}" data-tab="assignments">Tugas <span>${state.assignments.length}</span></button>${isTeacher ? `<button class="${state.tab === 'people' ? 'active' : ''}" data-tab="people">Anggota <span>${state.students.length}</span></button>` : ''}</nav>${state.tab === 'assignments' ? assignmentsView(isTeacher) : state.tab === 'people' ? peopleView() : streamView(isTeacher)}${state.modal ? modalView() : ''}`;
}

function streamView(isTeacher) {
    const composer = `<section class="composer card"><div class="avatar avatar-brand">${escapeHtml(initials(state.user.name))}</div><form id="create-post-form" class="composer-form"><textarea name="content" rows="2" placeholder="Bagikan pengumuman, materi, atau mulai diskusi..." required></textarea><div class="form-row"><select name="type"><option value="discussion">Diskusi</option><option value="announcement">Pengumuman</option><option value="material">Materi</option></select><label class="file-button">＋ Lampiran<input name="attachment" type="file" hidden></label><button class="button button-primary" type="submit">Publikasikan</button></div></form></section>`;
    const posts = state.posts.length ? state.posts.map((post) => `<article class="post-card card"><div class="post-header"><div class="avatar avatar-soft">${escapeHtml(initials(post.user?.name))}</div><div><strong>${escapeHtml(post.user?.name || 'Pengguna')}</strong><small>${escapeHtml(post.type || 'discussion')} · ${formatDate(post.created_at)}</small></div>${(post.user_id === state.user.id || isTeacher || role() === 'admin') ? `<button class="icon-button post-delete" data-delete-post="${post.id}" title="Hapus">×</button>` : ''}</div><p class="post-content">${escapeHtml(post.content)}</p>${post.attachment_path ? `<a class="attachment" href="/storage/${post.attachment_path}" target="_blank">↗ ${escapeHtml(post.attachment_name || 'Lihat lampiran')}</a>` : ''}<div class="post-footer"><button class="comment-toggle" data-comments="${post.id}">◌ ${post.comments_count || 0} komentar</button></div>${state.comments[post.id] ? commentView(post) : ''}</article>`).join('') : '<div class="empty-state card"><div class="empty-icon">◌</div><h3>Belum ada aktivitas</h3><p>Jadilah yang pertama membagikan sesuatu di kelas ini.</p></div>';
    return `${composer}<div class="stream-list">${posts}</div>`;
}

function commentView(post) {
    const comments = state.comments[post.id] || [];
    return `<div class="comment-panel">${comments.map((comment) => `<div class="comment"><div class="avatar avatar-tiny">${escapeHtml(initials(comment.user?.name))}</div><div><strong>${escapeHtml(comment.user?.name || 'Pengguna')}</strong><p>${escapeHtml(comment.content)}</p></div></div>`).join('') || '<p class="muted">Belum ada komentar.</p>'}<form class="comment-form" data-comment-form="${post.id}"><input name="content" placeholder="Tulis komentar..." required><button class="button button-soft">Kirim</button></form></div>`;
}

function assignmentsView(isTeacher) {
    const composer = isTeacher ? `<section class="assignment-composer card"><div><p class="eyebrow">Buat tugas baru</p><h3>Bagikan tugas untuk kelas</h3></div><form id="create-assignment-form" class="stack-form"><label>Judul tugas<input name="title" placeholder="Contoh: Latihan halaman profil" required></label><label>Instruksi<textarea name="instructions" rows="3" placeholder="Jelaskan tugas untuk siswa..."></textarea></label><div class="form-grid"><label>Batas pengumpulan<input name="due_date" type="datetime-local"></label><label>Nilai maksimal<input name="max_points" type="number" min="1" max="1000" value="100"></label></div><div class="form-row"><label class="file-button">＋ Lampiran soal<input name="attachment" type="file" hidden></label><button class="button button-primary">Publikasikan tugas</button></div></form></section>` : '';
    const cards = state.assignments.length ? state.assignments.map((assignment) => `<article class="assignment-card card"><div class="assignment-icon">✓</div><div class="assignment-main"><span class="eyebrow">Tugas · ${formatDate(assignment.created_at)}</span><h3>${escapeHtml(assignment.title)}</h3><p>${escapeHtml(assignment.instructions || 'Tidak ada instruksi tambahan.')}</p><div class="assignment-meta"><span>Nilai maksimal ${assignment.max_points}</span><span>Deadline ${formatDate(assignment.due_date)}</span></div></div>${isTeacher ? `<button class="button button-outline" data-submissions="${assignment.id}">Lihat jawaban</button>` : `<button class="button button-outline" data-submit-assignment="${assignment.id}">Kumpulkan</button>`}</article>`).join('') : '<div class="empty-state card"><div class="empty-icon">✓</div><h3>Belum ada tugas</h3><p>Tugas dari guru akan muncul di sini.</p></div>';
    return `${composer}<div class="assignment-list">${cards}</div>`;
}

function peopleView() {
    return `<section class="people-card card"><div class="section-heading"><div><p class="eyebrow">Komunitas kelas</p><h2>${state.students.length} anggota siswa</h2></div></div><div class="people-list">${state.students.map((student) => `<div class="person-row"><div class="avatar avatar-soft">${escapeHtml(initials(student.name))}</div><div><strong>${escapeHtml(student.name)}</strong><small>${escapeHtml(student.email)}</small></div><button class="button button-danger" data-remove-student="${student.id}">Keluarkan</button></div>`).join('') || '<p class="muted">Belum ada siswa.</p>'}</div></section>`;
}

async function openClassroom(id) {
    state.page = 'classroom'; state.tab = 'stream'; state.activeClassroom = state.classrooms.find((classroom) => classroom.id === id) || null; state.loading = true; render();
    try {
        const requests = [api(`/classrooms/${id}`), api(`/classrooms/${id}/posts`), api(`/classrooms/${id}/assignments`)];
        if (isManager()) requests.push(api(`/classrooms/${id}/students`));
        const [classroom, posts, assignments, students] = await Promise.all(requests);
        state.activeClassroom = classroom.data; state.posts = posts.data || []; state.assignments = assignments.data || []; state.students = students?.data || [];
    } catch (exception) { notify(exception.message, 'error'); state.page = 'classes'; }
    state.loading = false; render();
}

async function createPost(event) {
    event.preventDefault(); const form = new FormData(event.currentTarget); form.set('type', form.get('type') || 'discussion');
    try { await api(`/classrooms/${state.activeClassroom.id}/posts`, { method: 'POST', body: form }); await openClassroom(state.activeClassroom.id); notify('Postingan berhasil dipublikasikan.'); } catch (exception) { notify(exception.message, 'error'); }
}

async function createAssignment(event) {
    event.preventDefault(); const form = new FormData(event.currentTarget);
    try { await api(`/classrooms/${state.activeClassroom.id}/assignments`, { method: 'POST', body: form }); await openClassroom(state.activeClassroom.id); state.tab = 'assignments'; render(); notify('Tugas berhasil dibuat.'); } catch (exception) { notify(exception.message, 'error'); }
}

async function loadComments(postId) {
    if (!state.comments[postId]) state.comments[postId] = (await api(`/posts/${postId}/comments`)).data || [];
    else delete state.comments[postId];
    render();
}

async function createComment(event) {
    event.preventDefault(); const form = new FormData(event.currentTarget); const postId = event.currentTarget.dataset.commentForm;
    try { await api(`/posts/${postId}/comments`, { method: 'POST', body: { content: form.get('content') } }); state.comments[postId] = (await api(`/posts/${postId}/comments`)).data || []; render(); } catch (exception) { notify(exception.message, 'error'); }
}
async function deletePost(id) { if (!window.confirm('Hapus postingan ini?')) return; try { await api(`/posts/${id}`, { method: 'DELETE' }); await openClassroom(state.activeClassroom.id); notify('Postingan dihapus.'); } catch (exception) { notify(exception.message, 'error'); } }
async function removeStudent(studentId) { if (!window.confirm('Keluarkan siswa dari kelas ini?')) return; try { await api(`/classrooms/${state.activeClassroom.id}/students/${studentId}`, { method: 'DELETE' }); state.students = state.students.filter((student) => student.id !== studentId); render(); notify('Siswa dikeluarkan dari kelas.'); } catch (exception) { notify(exception.message, 'error'); } }

async function openSubmissionModal(assignmentId) {
    state.modal = 'submit-assignment'; state.modalAssignment = state.assignments.find((assignment) => assignment.id === assignmentId); state.modalData = null; render();
    try { state.modalData = (await api(`/assignments/${assignmentId}/my-submission`)).data; render(); } catch (exception) { notify(exception.message, 'error'); }
}
async function submitWork(event) {
    event.preventDefault(); const form = new FormData(event.currentTarget);
    try { await api(`/assignments/${state.modalAssignment.id}/submit`, { method: 'POST', body: form }); state.modal = null; await openClassroom(state.activeClassroom.id); state.tab = 'assignments'; render(); notify('Tugas berhasil dikirim.'); } catch (exception) { notify(exception.message, 'error'); }
}
async function unsubmitWork() {
    if (!window.confirm('Tarik pengumpulan tugas ini?')) return;
    try { await api(`/assignments/${state.modalAssignment.id}/unsubmit`, { method: 'POST' }); state.modal = null; await openClassroom(state.activeClassroom.id); state.tab = 'assignments'; render(); notify('Pengumpulan berhasil ditarik.'); } catch (exception) { notify(exception.message, 'error'); }
}
async function openSubmissionsModal(assignmentId) {
    state.modal = 'submissions'; state.modalAssignment = state.assignments.find((assignment) => assignment.id === assignmentId); state.modalData = null; render();
    try { state.modalData = (await api(`/assignments/${assignmentId}/submissions`)).data || []; render(); } catch (exception) { notify(exception.message, 'error'); }
}
async function gradeSubmission(event) {
    event.preventDefault(); const form = new FormData(event.currentTarget); const submissionId = event.currentTarget.dataset.gradeSubmission;
    try { await api(`/submissions/${submissionId}/grade`, { method: 'POST', body: { grade: form.get('grade'), feedback: form.get('feedback') } }); state.modalData = (await api(`/assignments/${state.modalAssignment.id}/submissions`)).data || []; render(); notify('Nilai berhasil disimpan.'); } catch (exception) { notify(exception.message, 'error'); }
}

function openUserModal(mode, id = null) {
    state.modal = 'user-form'; state.modalUser = mode === 'edit' ? state.users.find((user) => user.id === id) : null; render();
}
async function saveUser(event) {
    event.preventDefault(); const form = new FormData(event.currentTarget); const id = form.get('id');
    const body = { name: form.get('name'), email: form.get('email'), role: form.get('role') }; if (form.get('password')) body.password = form.get('password');
    try { await api(id ? `/admin/users/${id}` : '/admin/users', { method: id ? 'PUT' : 'POST', body }); state.modal = null; await loadPage(); notify(id ? 'Profil diperbarui.' : 'Pengguna berhasil dibuat.'); } catch (exception) { notify(exception.message, 'error'); }
}
async function deleteUser(id) {
    if (!window.confirm('Hapus pengguna ini dari sistem?')) return;
    try { await api(`/admin/users/${id}`, { method: 'DELETE' }); await loadPage(); notify('Pengguna berhasil dihapus.'); } catch (exception) { notify(exception.message, 'error'); }
}
