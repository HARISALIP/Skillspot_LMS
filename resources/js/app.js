import * as Turbo from '@hotwired/turbo';

Turbo.config.drive.progressBarDelay = 120;

const storageKey = name => `skillspot:navigation:${name}`;
const settingsHash = tab => `#settings/${tab}`;
const settingTabFromHash = () => location.hash.match(/^#settings\/([a-z-]+)$/)?.[1];

function rememberScroll() {
  document.querySelectorAll('[data-navigation-sidebar]').forEach(element => sessionStorage.setItem(storageKey(`sidebar:${element.dataset.navigationSidebar}`), String(element.scrollTop)));
  const main = document.querySelector('[data-navigation-main]');
  if (main) history.replaceState({ ...history.state, skillspotMainScroll: main.scrollTop }, '', location.href);
}

function restoreScroll() {
  document.querySelectorAll('[data-navigation-sidebar]').forEach(element => {
    const value = sessionStorage.getItem(storageKey(`sidebar:${element.dataset.navigationSidebar}`));
    if (value !== null) element.scrollTop = Number(value);
  });
  const main = document.querySelector('[data-navigation-main]');
  if (main) main.scrollTop = Number(history.state?.skillspotMainScroll || 0);
}

function markNonDriveForms() {
  document.querySelectorAll('form').forEach(form => {
    if (!form.matches('[data-turbo], [data-turbo-ignore]') && (form.querySelector('input[type="file"]') || form.matches('[action$="/logout"]'))) form.dataset.turbo = 'false';
  });
}

function markNonDriveLinks() {
  document.querySelectorAll('a[download], a[target], a[href*="/download"], a[href*="/export"], a[href^="mailto:"], a[href^="tel:"]').forEach(link => { link.dataset.turbo = 'false'; });
}

function initialiseDirtyForms() {
  document.querySelectorAll('form:not([data-turbo="false"])').forEach(form => {
    if (form.dataset.dirtyTracking === 'true') return;
    form.dataset.dirtyTracking = 'true';
    form.addEventListener('input', () => { form.dataset.dirty = 'true'; }, { passive: true });
    form.addEventListener('change', () => { form.dataset.dirty = 'true'; }, { passive: true });
    form.addEventListener('submit', () => { form.dataset.dirty = 'false'; });
  });
}

const hasDirtyForm = () => Boolean(document.querySelector('form[data-dirty="true"]'));

function syncSettingsHash(tab, push = false) {
  if (!tab || location.hash === settingsHash(tab)) return;
  const target = `${location.pathname}${location.search}${settingsHash(tab)}`;
  if (push) history.pushState({ settingsTab: tab }, '', target);
  else history.replaceState({ ...history.state, settingsTab: tab }, '', target);
}

function visitSettingsInFrame(frame, tab, theme) {
  frame?.contentWindow?.postMessage({ type: 'skillspot:open-settings-tab', tab, theme }, location.origin);
}

function initialiseAdminWorkspace() {
  const sidebar = document.getElementById('sidebar');
  const appMenu = document.getElementById('appMenu');
  const themeMenu = document.getElementById('themeMenu');
  const palette = document.getElementById('commandPalette');
  const settingsModal = document.getElementById('settingsModal');
  const settingsFrame = document.getElementById('settingsFrame');
  const mobileOverlay = document.getElementById('mobileOverlay');
  if (!sidebar || !settingsModal || settingsModal.dataset.navigationReady === 'true') return;
  settingsModal.dataset.navigationReady = 'true';
  window.__skillspotAdminCleanup?.();
  const workspaceTheme = () => document.body.dataset.theme || 'dark';
  const applyWorkspaceTheme = preference => {
    const theme = preference === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : preference;
    sidebar.dataset.theme = theme;
    document.body.dataset.theme = theme;
    document.getElementById('settingsSurface')?.setAttribute('data-theme', theme);
    localStorage.setItem('skillspot-theme', preference);
    window.dispatchEvent(new Event('skillspot:themechange'));
  };
  applyWorkspaceTheme(localStorage.getItem('skillspot-theme') || localStorage.getItem('skillspot-sidebar-theme') || 'dark');
  const showSettings = (tab = 'general', addHistory = false) => {
    settingsModal.classList.remove('hidden');
    document.documentElement.classList.add('settings-open');
    document.body.classList.add('settings-open');
    appMenu?.classList.remove('is-open');
    if (addHistory) syncSettingsHash(tab, true);
    if (!settingsFrame) return;
    if (settingsFrame.dataset.initialised === 'true') return visitSettingsInFrame(settingsFrame, tab, workspaceTheme());
    const url = new URL(settingsFrame.dataset.settingsUrl, location.origin);
    url.searchParams.set('tab', tab); url.searchParams.set('embed', '1'); url.searchParams.set('theme', workspaceTheme());
    settingsFrame.src = url.toString(); settingsFrame.dataset.tab = tab;
  };
  const closeSettings = () => {
    settingsModal.classList.add('hidden'); document.documentElement.classList.remove('settings-open'); document.body.classList.remove('settings-open');
    if (settingTabFromHash()) history.pushState({}, '', `${location.pathname}${location.search}`);
  };
  settingsFrame?.addEventListener('load', () => { settingsFrame.dataset.initialised = 'true'; });
  document.getElementById('settingsModalButton')?.addEventListener('click', () => showSettings(settingTabFromHash() || 'general', !settingTabFromHash()));
  settingsModal.addEventListener('click', event => { if (event.target === settingsModal) closeSettings(); });
  document.getElementById('sidebarToggle')?.addEventListener('click', () => { sidebar.classList.toggle('is-collapsed'); localStorage.setItem('skillspot-sidebar-collapsed', String(sidebar.classList.contains('is-collapsed'))); });
  if (localStorage.getItem('skillspot-sidebar-collapsed') === 'true') sidebar.classList.add('is-collapsed');
  document.getElementById('appMenuButton')?.addEventListener('click', () => { appMenu?.classList.toggle('is-open'); document.getElementById('appMenuButton')?.setAttribute('aria-expanded', String(appMenu?.classList.contains('is-open'))); themeMenu?.classList.remove('is-open'); });
  document.getElementById('themeMenuButton')?.addEventListener('click', () => themeMenu?.classList.toggle('is-open'));
  document.querySelectorAll('[data-sidebar-theme]').forEach(button => button.addEventListener('click', () => { applyWorkspaceTheme(button.dataset.sidebarTheme); themeMenu?.classList.remove('is-open'); appMenu?.classList.remove('is-open'); }));
  document.getElementById('notificationsButton')?.addEventListener('click', () => document.getElementById('notificationPanel')?.classList.toggle('is-open'));
  const openPalette = () => { palette?.classList.add('is-open'); document.getElementById('commandInput')?.focus(); };
  const closePalette = () => palette?.classList.remove('is-open');
  document.getElementById('searchButton')?.addEventListener('click', openPalette); document.getElementById('commandClose')?.addEventListener('click', closePalette);
  palette?.addEventListener('click', event => { if (event.target === palette) closePalette(); });
  document.getElementById('commandInput')?.addEventListener('input', event => document.querySelectorAll('[data-command-item]').forEach(item => item.classList.toggle('hidden', !item.dataset.commandItem.includes(event.target.value.toLowerCase()))));
  const openMobileSidebar = () => { sidebar.style.transform = 'translateX(0)'; mobileOverlay?.classList.remove('hidden'); };
  const closeMobileSidebar = () => { sidebar.style.transform = 'translateX(-100%)'; mobileOverlay?.classList.add('hidden'); };
  document.getElementById('mobileMenuBtn')?.addEventListener('click', openMobileSidebar); mobileOverlay?.addEventListener('click', closeMobileSidebar);
  if (window.innerWidth < 1024) { sidebar.style.transform = 'translateX(-100%)'; sidebar.style.transition = 'transform .2s ease'; }
  const onPopState = () => { const tab = settingTabFromHash(); if (tab) showSettings(tab); else closeSettings(); };
  const onThemeChange = () => { if (!settingsModal.classList.contains('hidden') && settingsFrame?.dataset.initialised === 'true') visitSettingsInFrame(settingsFrame, settingTabFromHash() || 'general', workspaceTheme()); };
  const onMessage = event => {
    if (event.origin !== location.origin) return;
    if (event.data === 'skillspot:close-settings') return closeSettings();
    if (event.data?.type === 'skillspot:settings-tab' && /^[a-z-]+$/.test(event.data.tab)) { settingsFrame.dataset.tab = event.data.tab; syncSettingsHash(event.data.tab, event.data.push === true); }
  };
  const onKeydown = event => { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); openPalette(); } if (event.key === 'Escape') { closePalette(); closeSettings(); appMenu?.classList.remove('is-open'); themeMenu?.classList.remove('is-open'); } };
  window.addEventListener('popstate', onPopState);
  window.addEventListener('skillspot:themechange', onThemeChange);
  window.addEventListener('message', onMessage);
  window.addEventListener('keydown', onKeydown);
  window.__skillspotAdminCleanup = () => {
    window.removeEventListener('popstate', onPopState);
    window.removeEventListener('skillspot:themechange', onThemeChange);
    window.removeEventListener('message', onMessage);
    window.removeEventListener('keydown', onKeydown);
    delete window.__skillspotAdminCleanup;
  };
  if (settingTabFromHash()) showSettings(settingTabFromHash());
}

function initialiseVendorWorkspace() {
  const sidebar = document.getElementById('sidebar');
  if (!sidebar || sidebar.dataset.vendorNavigationReady === 'true') return;
  sidebar.dataset.vendorNavigationReady = 'true';
  const overlay = document.getElementById('mobileOverlay');
  document.getElementById('sidebarToggle')?.addEventListener('click', () => { sidebar.classList.toggle('sidebar-collapsed'); sidebar.classList.toggle('sidebar'); });
  document.getElementById('mobileMenuBtn')?.addEventListener('click', () => { sidebar.style.transform = 'translateX(0)'; overlay?.classList.remove('hidden'); document.body.style.overflow = 'hidden'; });
  overlay?.addEventListener('click', () => { sidebar.style.transform = 'translateX(-100%)'; overlay.classList.add('hidden'); document.body.style.overflow = ''; });
  if (window.innerWidth < 1024) { sidebar.style.transform = 'translateX(-100%)'; sidebar.style.transition = 'transform .25s ease'; }
}

function initialiseLanding() {
  const navbar = document.getElementById('navbar');
  const hamburger = document.getElementById('hamburger');
  const mobileMenu = document.getElementById('mobile-menu');
  if (!navbar || navbar.dataset.landingNavigationReady === 'true') return;
  navbar.dataset.landingNavigationReady = 'true';
  const updateNavbar = () => navbar.classList.toggle('nav-solid', window.scrollY > 40);
  window.addEventListener('scroll', updateNavbar, { passive: true });
  updateNavbar();
  hamburger?.addEventListener('click', () => {
    const open = mobileMenu.classList.toggle('open');
    document.getElementById('icon-open')?.classList.toggle('hidden', open);
    document.getElementById('icon-close')?.classList.toggle('hidden', !open);
    document.body.style.overflow = open ? 'hidden' : '';
  });
  mobileMenu?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => { mobileMenu.classList.remove('open'); document.getElementById('icon-open')?.classList.remove('hidden'); document.getElementById('icon-close')?.classList.add('hidden'); document.body.style.overflow = ''; }));
}

document.addEventListener('turbo:before-cache', rememberScroll);
document.addEventListener('turbo:before-render', event => {
  // Existing Blade screens contain page-local inline scripts. Turbo evaluates
  // incoming body scripts in the same document, so convert their top-level
  // lexical declarations to re-declarable variables before execution.
  event.detail.newBody.querySelectorAll('script:not([src]):not([type])').forEach(script => {
    script.textContent = script.textContent.replace(/^(\s*)(const|let)\s+/gm, '$1var ');
  });
});
document.addEventListener('turbo:before-visit', event => { if (hasDirtyForm() && !window.confirm('You have unsaved changes. Leave this page without saving them?')) event.preventDefault(); });
window.addEventListener('beforeunload', event => { if (hasDirtyForm()) { event.preventDefault(); event.returnValue = ''; } });
document.addEventListener('turbo:load', () => {
  markNonDriveForms(); markNonDriveLinks(); initialiseDirtyForms(); restoreScroll(); initialiseAdminWorkspace(); initialiseVendorWorkspace(); initialiseLanding();
  if (window.self !== window.top && location.pathname === '/admin/settings') window.parent.postMessage({ type: 'skillspot:settings-tab', tab: new URL(location.href).searchParams.get('tab') || 'general' }, location.origin);
});
document.addEventListener('click', event => { const link = event.target.closest('[data-settings-tab]'); if (link && window.self !== window.top) window.parent.postMessage({ type: 'skillspot:settings-tab', tab: link.dataset.settingsTab, push: true }, location.origin); });
window.addEventListener('message', event => {
  if (event.origin !== location.origin || event.data?.type !== 'skillspot:open-settings-tab' || window.self === window.top) return;
  const theme = ['light', 'dark'].includes(event.data.theme) ? event.data.theme : null;
  if (theme) {
    document.body.dataset.theme = theme;
    document.querySelector('.settings-workspace')?.setAttribute('data-theme', theme);
  }
  const url = new URL(location.href); url.searchParams.set('tab', event.data.tab); url.searchParams.set('embed', '1'); if (theme) url.searchParams.set('theme', theme);
  if (url.toString() !== location.href) Turbo.visit(url.toString());
});
