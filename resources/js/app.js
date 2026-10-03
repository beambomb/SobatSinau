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
    const titles = { dashboard: 'Ringkasan aktivitas', classes: 'Kelas saya', users: 'Pengguna' };
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
    if (state.page === 'users') return usersPlaceholder();
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

function usersPlaceholder() { return `<section class="empty-state card"><div class="empty-icon">♙</div><h3>Manajemen pengguna segera hadir</h3><p>Area admin sudah disiapkan pada navigasi dan API. Modul tabel pengguna akan ditambahkan di commit berikutnya.</p></section>`; }
function modalView() { return state.modal === 'join' ? `<div class="modal-backdrop" data-close-modal><div class="modal-card" data-stop-click><button class="modal-close" data-close-modal>×</button><p class="eyebrow">Gabung kelas</p><h2>Masukkan kode kelas</h2><p class="muted">Minta kode 7 karakter dari guru kamu.</p><form id="join-form" class="stack-form"><label>Kode kelas<input name="code" minlength="7" maxlength="7" placeholder="contoh: pintari" required autocomplete="off"></label><button class="button button-primary button-block">Gabung sekarang</button></form></div></div>` : `<div class="modal-backdrop" data-close-modal><div class="modal-card" data-stop-click><button class="modal-close" data-close-modal>×</button><p class="eyebrow">Kelas baru</p><h2>Buat ruang kelas</h2><p class="muted">Buat ruang yang nyaman untuk materi dan diskusi.</p><form id="create-class-form" class="stack-form"><label>Nama kelas<input name="title" placeholder="Contoh: Pemrograman Web" required maxlength="255"></label><label>Mata pelajaran<input name="subject" placeholder="Contoh: Teknologi Informasi" maxlength="255"></label><button class="button button-primary button-block">Buat kelas</button></form></div></div>`; }

function bindShellEvents() {
    root.querySelectorAll('[data-nav]').forEach((button) => button.addEventListener('click', () => openPage(button.dataset.nav)));
    root.querySelectorAll('[data-classroom]').forEach((card) => card.addEventListener('click', () => notify('Detail kelas akan tersedia di commit berikutnya.')));
    root.querySelector('[data-logout]').addEventListener('click', async () => { try { await api('/logout', { method: 'POST' }); } catch (_) {} clearSession(); render(); });
    root.querySelector('[data-menu]').addEventListener('click', () => root.querySelector('#sidebar').classList.toggle('open'));
    root.querySelectorAll('[data-modal]').forEach((button) => button.addEventListener('click', () => { state.modal = button.dataset.modal; render(); }));
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
