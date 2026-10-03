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
    loading: false,
    toast: null,
};

const role = () => state.user?.roles?.[0] || 'siswa';
const roleLabel = () => ({ admin: 'Admin', guru: 'Guru', siswa: 'Siswa' })[role()] || 'Pengguna';
const initials = (name = '') => name.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase() || 'P';
const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[char]);

function saveSession(token, user) {
    state.token = token;
    state.user = user;
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
}

function clearSession() {
    state.token = null;
    state.user = null;
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
}

async function api(path, options = {}) {
    const config = { ...options, headers: { Accept: 'application/json', ...(options.headers || {}) } };
    if (state.token) config.headers.Authorization = `Bearer ${state.token}`;
    if (config.body && !(config.body instanceof FormData)) {
        config.headers['Content-Type'] = 'application/json';
        config.body = JSON.stringify(config.body);
    }

    const response = await fetch(`/api${path}`, config);
    const payload = await response.json().catch(() => ({}));
    if (response.status === 401) {
        clearSession();
        render();
    }
    if (!response.ok) {
        const validation = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
        throw new Error(validation || payload.message || 'Terjadi kesalahan.');
    }
    return payload;
}

function notify(message, type = 'success') {
    state.toast = { message, type };
    render();
    window.setTimeout(() => {
        state.toast = null;
        render();
    }, 3200);
}

function render() {
    if (!state.user) {
        renderLogin();
        return;
    }
    root.innerHTML = `
        <div class="app-shell">
            <aside class="sidebar" id="sidebar">
                <div class="brand"><span class="brand-mark">P</span><span>Pintaria</span></div>
                <div class="sidebar-profile">
                    <div class="avatar avatar-light">${escapeHtml(initials(state.user.name))}</div>
                    <div><strong>${escapeHtml(state.user.name)}</strong><small>${roleLabel()}</small></div>
                </div>
                <nav class="main-nav">
                    <button class="nav-item active" data-nav="dashboard"><span>⌂</span> Beranda</button>
                    <button class="nav-item" data-nav="classes"><span>▦</span> Kelas saya</button>
                    ${role() === 'admin' ? '<button class="nav-item" data-nav="users"><span>♙</span> Pengguna</button>' : ''}
                </nav>
                <div class="sidebar-help"><span>✦</span><strong>Belajar lebih terarah</strong><small>Kelola kelas dan tugas dari satu tempat.</small></div>
                <button class="nav-item logout-item" data-logout><span>↪</span> Keluar</button>
            </aside>
            <main class="main-content">
                <header class="topbar">
                    <button class="icon-button mobile-menu" data-menu aria-label="Buka menu">☰</button>
                    <div><p class="eyebrow">${roleLabel()} workspace</p><h1 id="page-title">Selamat datang, ${escapeHtml(state.user.name.split(' ')[0])}!</h1></div>
                    <div class="topbar-actions"><button class="icon-button" title="Notifikasi">♢</button><div class="avatar avatar-brand">${escapeHtml(initials(state.user.name))}</div></div>
                </header>
                <div class="page-content" id="page-content">${dashboardView()}</div>
            </main>
        </div>
        ${state.toast ? `<div class="toast toast-${state.toast.type}">${state.toast.type === 'success' ? '✓' : '!'} ${escapeHtml(state.toast.message)}</div>` : ''}
    `;
    bindShellEvents();
}

function renderLogin() {
    root.innerHTML = `
        <div class="auth-page">
            <div class="auth-art"><div class="auth-orbit orbit-one"></div><div class="auth-orbit orbit-two"></div><div class="auth-copy"><div class="brand brand-large"><span class="brand-mark">P</span><span>Pintaria</span></div><h1>Belajar lebih mudah,<br><em>bertumbuh bersama.</em></h1><p>Ruang belajar digital yang rapi untuk guru dan siswa.</p><div class="auth-points"><span>✓ Kelola kelas dengan praktis</span><span>✓ Tugas dan diskusi terpusat</span></div></div></div>
            <div class="auth-panel"><div class="auth-card"><div class="mobile-brand brand"><span class="brand-mark">P</span><span>Pintaria</span></div><p class="eyebrow">Selamat datang kembali</p><h2>Masuk ke ruang belajar</h2><p class="muted">Gunakan akun Pintaria untuk melanjutkan.</p><form id="login-form" class="stack-form"><label>Email<input name="email" type="email" placeholder="nama@email.com" required autocomplete="email"></label><label>Password<div class="password-field"><input name="password" type="password" placeholder="Masukkan password" required autocomplete="current-password"><button type="button" data-toggle-password>lihat</button></div></label><button class="button button-primary button-block" type="submit"><span data-submit-label>Masuk</span></button></form><div class="demo-login"><span>Demo cepat</span><div><button data-demo="admin@lms.test">Admin</button><button data-demo="guru@lms.test">Guru</button><button data-demo="siswa@lms.test">Siswa</button></div><small>Password demo: <strong>password123</strong></small></div><p class="form-error" id="login-error"></p></div><p class="auth-footer">Pintaria LMS · Ruang belajar yang nyaman</p></div>
        </div>
    `;
    root.querySelectorAll('[data-demo]').forEach((button) => button.addEventListener('click', () => {
        root.querySelector('[name=email]').value = button.dataset.demo;
        root.querySelector('[name=password]').value = 'password123';
        root.querySelector('#login-form').requestSubmit();
    }));
    root.querySelector('[data-toggle-password]').addEventListener('click', (event) => {
        const input = event.currentTarget.parentElement.querySelector('input');
        input.type = input.type === 'password' ? 'text' : 'password';
        event.currentTarget.textContent = input.type === 'password' ? 'lihat' : 'sembunyikan';
    });
    root.querySelector('#login-form').addEventListener('submit', login);
}

async function login(event) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const error = root.querySelector('#login-error');
    const button = event.currentTarget.querySelector('button[type=submit]');
    button.disabled = true;
    button.querySelector('[data-submit-label]').textContent = 'Memeriksa...';
    error.textContent = '';
    try {
        const response = await api('/login', { method: 'POST', body: { email: form.get('email'), password: form.get('password') } });
        saveSession(response.token, response.user);
        notify('Login berhasil. Selamat datang!');
    } catch (exception) {
        error.textContent = exception.message;
        button.disabled = false;
        button.querySelector('[data-submit-label]').textContent = 'Masuk';
    }
}

function dashboardView() {
    return `<section class="welcome-banner"><div><span class="pill pill-white">${roleLabel()} · Hari ini</span><h2>Ruang belajar yang lebih teratur.</h2><p>Lihat kelas, tugas, dan aktivitas terbaru dalam satu dashboard.</p></div><div class="banner-shape">✦</div></section><section class="empty-state card"><div class="empty-icon">◌</div><h3>Dashboard sedang disiapkan</h3><p>Modul kelas akan tampil di sini. Navigasi sudah siap untuk dikembangkan.</p></section>`;
}

function bindShellEvents() {
    root.querySelectorAll('[data-nav]').forEach((button) => button.addEventListener('click', () => {
        root.querySelectorAll('.nav-item').forEach((item) => item.classList.remove('active'));
        button.classList.add('active');
        if (window.innerWidth < 900) root.querySelector('#sidebar').classList.remove('open');
        if (button.dataset.nav === 'dashboard') root.querySelector('#page-content').innerHTML = dashboardView();
    }));
    root.querySelector('[data-logout]').addEventListener('click', async () => {
        try { await api('/logout', { method: 'POST' }); } catch (_) { /* session is cleared locally regardless */ }
        clearSession();
        render();
    });
    root.querySelector('[data-menu]').addEventListener('click', () => root.querySelector('#sidebar').classList.toggle('open'));
}

async function bootstrap() {
    if (!state.token) {
        render();
        return;
    }
    try {
        const response = await api('/user');
        state.user = response.user;
        localStorage.setItem(USER_KEY, JSON.stringify(state.user));
    } catch (_) {
        clearSession();
    }
    render();
}

bootstrap();
