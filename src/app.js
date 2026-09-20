(() => {
  'use strict';

  const SESSION_KEY = 'smk_active_session';
  const readSession = () => {
    try { return JSON.parse(localStorage.getItem(SESSION_KEY) || 'null'); } catch { return null; }
  };

  const portalFor = (role) => role === 'admin' ? 'admin.html' : 'siswa.html';
  const iconRefresh = () => window.lucide?.createIcons?.();

  window.spm = {
    session: readSession,
    toast(message, type = 'success') {
      let container = document.getElementById('toast-container');
      if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-5 right-5 z-[100] flex max-w-[calc(100vw-2rem)] flex-col gap-2';
        document.body.appendChild(container);
      }
      const palette = type === 'error' ? 'bg-red-600' : type === 'warning' ? 'bg-amber-500' : 'bg-emerald-600';
      const item = document.createElement('div');
      item.className = `toast-message ${palette} flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-xl`;
      item.innerHTML = `<i data-lucide="${type === 'error' ? 'circle-alert' : type === 'warning' ? 'triangle-alert' : 'circle-check'}" class="h-4 w-4 shrink-0"></i><span></span>`;
      item.querySelector('span').textContent = message;
      container.appendChild(item);
      iconRefresh();
      window.setTimeout(() => item.remove(), 3600);
    },
    async api(url, options = {}) {
      const response = await fetch(url, options);
      const payload = await response.json().catch(() => ({ success: false, message: 'Respons server tidak valid.' }));
      if (!response.ok || payload.success === false) throw new Error(payload.message || 'Permintaan gagal.');
      return payload;
    },
    logout() {
      localStorage.removeItem(SESSION_KEY);
      window.location.href = 'index.html';
    }
  };

  function setupDrawer() {
    const button = document.querySelector('[data-menu-toggle]') || document.getElementById('mobile-menu-btn');
    const drawer = document.querySelector('[data-mobile-drawer]') || document.getElementById('mobile-menu');
    const backdrop = document.querySelector('[data-menu-backdrop]');
    if (!button || !drawer) return;
    const setOpen = (open) => {
      drawer.classList.toggle('is-open', open);
      drawer.classList.toggle('hidden', !open && drawer.id === 'mobile-menu');
      backdrop?.classList.toggle('is-open', open);
      button.setAttribute('aria-expanded', String(open));
    };
    button.addEventListener('click', () => setOpen(!drawer.classList.contains('is-open') && drawer.classList.contains('hidden')));
    backdrop?.addEventListener('click', () => setOpen(false));
    drawer.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setOpen(false)));
  }

  function setupSessionLinks() {
    const session = readSession();
    document.querySelectorAll('a[href="portal_siswa.html"], a[href="portal_admin.html"]').forEach(link => {
      link.href = portalFor(session?.role);
    });
    document.querySelectorAll('[data-session-action]').forEach(node => {
      if (!session) return;
      const isAdmin = session.role === 'admin';
      node.innerHTML = `<i data-lucide="${isAdmin ? 'shield-check' : 'circle-user-round'}" class="h-4 w-4"></i><span>${session.nama || session.email || 'Portal Saya'}</span>`;
      node.href = portalFor(session.role);
      node.classList.remove('hidden');
    });
    document.querySelectorAll('[data-logout]').forEach(button => button.addEventListener('click', () => window.spm.logout()));
    iconRefresh();
  }

  function setupFaqSearch() {
    const input = document.getElementById('faq-search');
    if (!input) return;
    input.addEventListener('input', () => {
      const query = input.value.toLowerCase().trim();
      document.querySelectorAll('.faq-item').forEach(item => {
        item.classList.toggle('hidden', !item.textContent.toLowerCase().includes(query));
      });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    setupDrawer();
    setupSessionLinks();
    setupFaqSearch();
    iconRefresh();
  });
})();
