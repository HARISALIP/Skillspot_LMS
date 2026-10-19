import * as Turbo from '@hotwired/turbo';

Turbo.config.drive.progressBarDelay = 120;
let navigationLoadingTimer;

const storageKey = name => `skillspot:navigation:${name}`;
const settingsHash = (tab, gateway) => `#settings/${tab}${gateway ? `/${gateway}` : ''}`;
const settingTabFromHash = () => location.hash.match(/^#settings\/([a-z-]+)(?:\/[a-z-]+)?$/)?.[1];
const gatewayNames = ['new', 'braintree', 'gocardless', 'mpesa', 'paymob', 'paypal', 'paytm', 'razorpay', 'stripe'];
const settingGatewayFromHash = () => gatewayNames.includes(location.hash.split('/')[2]) && location.hash.startsWith('#settings/payment-gateways/') ? location.hash.split('/')[2] : null;

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

function keepLinksInCurrentTab() {
  document.querySelectorAll('a[target], form[target]').forEach(element => element.removeAttribute('target'));
}

function markNonDriveLinks() {
  document.querySelectorAll('a[download], a[href*="/download"], a[href*="/export"], a[href^="mailto:"], a[href^="tel:"]').forEach(link => { link.dataset.turbo = 'false'; });
}

function showNavigationLoading() {
  if (window.self !== window.top && location.pathname === '/admin/settings') return;
  clearTimeout(navigationLoadingTimer);
  navigationLoadingTimer = setTimeout(() => {
    if (document.querySelector('.skillspot-route-loading')) return;
    const surface = document.createElement('div');
    surface.className = 'skillspot-route-loading';
    surface.setAttribute('role', 'status');
    surface.setAttribute('aria-live', 'polite');
    surface.innerHTML = '<span class="sr-only">Loading next page</span>';
    document.body.append(surface);
  }, 120);
}

function clearPendingFeedback() {
  clearTimeout(navigationLoadingTimer);
  document.querySelector('.skillspot-route-loading')?.remove();
  document.querySelectorAll('form[aria-busy="true"]').forEach(form => form.removeAttribute('aria-busy'));
  document.querySelectorAll('[data-pending]').forEach(button => button.removeAttribute('data-pending'));
}

function initialiseDirtyForms() {
  document.querySelectorAll('form:not([data-turbo="false"])').forEach(form => {
    if (form.method.toUpperCase() === 'GET' || form.dataset.dirtyTracking === 'false') return;
    if (form.dataset.dirtyTracking === 'true') return;
    form.dataset.dirtyTracking = 'true';
    form.addEventListener('input', () => { form.dataset.dirty = 'true'; }, { passive: true });
    form.addEventListener('change', () => { form.dataset.dirty = 'true'; }, { passive: true });
    form.addEventListener('submit', () => { form.dataset.dirty = 'false'; });
  });
}

const hasDirtyForm = () => Boolean(document.querySelector('form[data-dirty="true"]'));

function initialiseSettingsNavigation() {
  if (window.self === window.top || location.pathname !== '/admin/settings') return;
  const workspace = document.querySelector('.settings-workspace');
  if (!workspace) return;
  if (window.__skillspotSettingsNavigation) return window.__skillspotSettingsNavigation.reset();

  const cache = new Map();
  const pending = new Map();
  const scroll = new Map();
  let sequence = 0;
  const keyFor = url => { const params = new URLSearchParams(url.search); params.delete('theme'); params.sort(); return `${url.pathname}?${params}`; };
  const currentKey = () => keyFor(new URL(location.href));
  let renderedKey = currentKey();
  let failedTarget = null;
  const markup = document => ({ panel: document.querySelector('.settings-content')?.outerHTML, extras: document.querySelector('#settingsExtras')?.outerHTML });
  const reset = () => {
    sequence++;
    cache.clear(); pending.clear(); scroll.clear();
    renderedKey = currentKey(); failedTarget = null;
    cache.set(currentKey(), { panel: document.querySelector('.settings-content')?.outerHTML, extras: document.querySelector('#settingsExtras')?.outerHTML });
  };
  const load = url => {
    const key = keyFor(url);
    if (cache.has(key)) return Promise.resolve(cache.get(key));
    if (pending.has(key)) return pending.get(key);
    const request = fetch(url, { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      .then(async response => {
        if (!response.ok || new URL(response.url).pathname !== url.pathname) throw new Error('Could not load settings. Check your connection and try again.');
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const result = markup(page);
        if (!result.panel || !result.extras) throw new Error('Settings are unavailable. Reload this page and sign in again.');
        cache.set(key, result);
        return result;
      }).finally(() => pending.delete(key));
    pending.set(key, request);
    return request;
  };
  const activate = tab => {
    document.querySelector('.settings-workspace')?.setAttribute('data-settings-current', tab);
    document.querySelectorAll('.settings-sidebar [data-settings-tab]').forEach(link => {
      const active = link.dataset.settingsTab === tab;
      link.classList.toggle('is-active', active);
      link.classList.toggle('bg-brand-600', active && link.parentElement?.classList.contains('md:hidden'));
      link.classList.toggle('text-white', active && link.parentElement?.classList.contains('md:hidden'));
      link.classList.toggle('bg-white', !active && link.parentElement?.classList.contains('md:hidden'));
      link.classList.toggle('text-gray-600', !active && link.parentElement?.classList.contains('md:hidden'));
      if (active) link.setAttribute('aria-current', 'page'); else link.removeAttribute('aria-current');
    });
  };
  const render = (result, url) => {
    const page = new DOMParser().parseFromString(result.panel + result.extras, 'text/html');
    document.querySelector('.settings-content')?.replaceWith(page.querySelector('.settings-content'));
    document.querySelector('#settingsExtras')?.replaceWith(page.querySelector('#settingsExtras'));
    activate(url.searchParams.get('tab') || 'general');
    const panel = document.querySelector('.settings-content');
    if (panel) panel.scrollTop = scroll.get(keyFor(url)) || 0;
    window.__skillspotInitSettingsPanel?.();
    initialiseDirtyForms();
  };
  const navigate = async (url, { notify = false, force = false } = {}) => {
    const target = new URL(url, location.href);
    if (target.origin !== location.origin || target.pathname !== location.pathname || !target.searchParams.has('tab')) return;
    if (!force && keyFor(target) === renderedKey) return;
    if (hasDirtyForm() && !window.confirm('You have unsaved changes. Leave this page without saving them?')) {
      if (!notify) {
        const current = new URL(location.href);
        window.parent.postMessage({ type: 'skillspot:settings-tab', tab: current.searchParams.get('tab'), gateway: current.searchParams.get('gateway') }, location.origin);
      }
      return;
    }
    const oldPanel = document.querySelector('.settings-content');
    if (oldPanel) scroll.set(currentKey(), oldPanel.scrollTop);
    const id = ++sequence;
    const cached = cache.get(keyFor(target));
    activate(target.searchParams.get('tab'));
    if (!cached && oldPanel) {
      oldPanel.setAttribute('aria-busy', 'true');
      oldPanel.innerHTML = '<div role="status" class="p-8"><span class="sr-only">Loading settings</span><div class="max-w-xl space-y-4"><div class="skillspot-route-loading__line"></div><div class="skillspot-route-loading__line"></div><div class="skillspot-route-loading__line"></div></div></div>';
    }
    try {
      const result = cached || await load(target);
      if (id !== sequence) return;
      render(result, target);
      renderedKey = keyFor(target); failedTarget = null;
      // Parent hash owns browser history; a second iframe entry makes Back skip tabs.
      history.replaceState({ ...history.state, settingsTab: target.searchParams.get('tab') }, '', target);
      if (notify) window.parent.postMessage({ type: 'skillspot:settings-tab', tab: target.searchParams.get('tab'), gateway: target.searchParams.get('gateway'), push: true }, location.origin);
    } catch (error) {
      if (id !== sequence) return;
      failedTarget = target.href;
      if (oldPanel) {
        oldPanel.setAttribute('aria-busy', 'false');
        oldPanel.innerHTML = `<div role="alert" class="m-8 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">${error.message}<button type="button" class="ml-3 font-semibold underline" data-settings-retry>Retry</button></div>`;
      }
    }
  };
  document.addEventListener('click', event => {
    const link = event.target.closest('a[data-settings-tab]');
    if (!link || !document.querySelector('.settings-workspace')?.contains(link) || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
    event.preventDefault();
    navigate(link.href, { notify: true });
  }, true);
  document.addEventListener('click', event => {
    if (!event.target.closest('[data-settings-retry]')) return;
    navigate(failedTarget || location.href, { notify: true, force: true });
  });
  let hoverTimer;
  document.addEventListener('pointerover', event => {
    const link = event.target.closest('a[data-settings-tab]');
    if (!link || !document.querySelector('.settings-workspace')?.contains(link)) return;
    clearTimeout(hoverTimer);
    hoverTimer = setTimeout(() => load(new URL(link.href)).catch(() => {}), 120);
  });
  window.addEventListener('popstate', () => navigate(location.href));
  window.__skillspotSettingsNavigation = { reset, navigate };
  window.__skillspotInvalidateSettingsCache = () => { cache.clear(); pending.clear(); };
  reset();
}

function syncSettingsHash(tab, push = false, gateway = null) {
  if (!tab || location.hash === settingsHash(tab, gateway)) return;
  const target = `${location.pathname}${location.search}${settingsHash(tab, gateway)}`;
  if (push) history.pushState({ settingsTab: tab }, '', target);
  else history.replaceState({ ...history.state, settingsTab: tab }, '', target);
}

function visitSettingsInFrame(frame, tab, theme) {
  frame?.contentWindow?.postMessage({ type: 'skillspot:open-settings-tab', tab, gateway: tab === 'payment-gateways' ? settingGatewayFromHash() : null, theme }, location.origin);
}

function initialiseAdminWorkspace() {
  const sidebar = document.getElementById('sidebar');
  const appMenu = document.getElementById('appMenu');
  const themeMenu = document.getElementById('themeMenu');
  const palette = document.getElementById('commandPalette');
  const settingsModal = document.getElementById('settingsModal');
  const settingsFrame = document.getElementById('settingsFrame');
  const settingsLoading = document.getElementById('settingsLoading');
  const settingsSurface = document.getElementById('settingsSurface');
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
  applyWorkspaceTheme(localStorage.getItem('skillspot-theme') || localStorage.getItem('skillspot-sidebar-theme') || 'light');
  const loadSettingsFrame = tab => {
    if (!settingsFrame) return;
    if (settingsFrame.dataset.initialised === 'true') return visitSettingsInFrame(settingsFrame, tab, workspaceTheme());
    if (settingsFrame.dataset.loading === 'true' && settingsFrame.dataset.tab === tab) return;
    settingsLoading?.classList.remove('hidden');
    settingsFrame.classList.add('opacity-0');
    settingsSurface?.setAttribute('aria-busy', 'true');
    const url = new URL(settingsFrame.dataset.settingsUrl, location.origin);
    url.searchParams.set('tab', tab); url.searchParams.set('embed', '1'); url.searchParams.set('theme', workspaceTheme());
    if (tab === 'payment-gateways' && settingGatewayFromHash()) url.searchParams.set('gateway', settingGatewayFromHash());
    settingsFrame.src = url.toString(); settingsFrame.dataset.tab = tab; settingsFrame.dataset.loading = 'true';
  };
  const showSettings = (tab = 'general', addHistory = false) => {
    settingsModal.classList.remove('hidden');
    document.documentElement.classList.add('settings-open');
    document.body.classList.add('settings-open');
    appMenu?.classList.remove('is-open');
    if (addHistory) syncSettingsHash(tab, true);
    loadSettingsFrame(tab);
  };
  const closeSettings = () => {
    settingsModal.classList.add('hidden'); document.documentElement.classList.remove('settings-open'); document.body.classList.remove('settings-open');
    if (settingTabFromHash()) history.pushState({}, '', `${location.pathname}${location.search}`);
  };
  settingsFrame?.addEventListener('load', () => {
    settingsFrame.dataset.initialised = 'true'; settingsFrame.dataset.loading = 'false';
    settingsFrame.classList.remove('opacity-0'); settingsLoading?.classList.add('hidden'); settingsSurface?.setAttribute('aria-busy', 'false');
  });
  document.getElementById('settingsModalButton')?.addEventListener('click', () => showSettings(settingTabFromHash() || 'general', !settingTabFromHash()));
  settingsModal.addEventListener('click', event => { if (event.target === settingsModal) closeSettings(); });
  document.getElementById('sidebarToggle')?.addEventListener('click', () => { sidebar.classList.toggle('is-collapsed'); localStorage.setItem('skillspot-sidebar-collapsed', String(sidebar.classList.contains('is-collapsed'))); });
  if (window.innerWidth >= 1024 && localStorage.getItem('skillspot-sidebar-collapsed') === 'true') sidebar.classList.add('is-collapsed');
  document.getElementById('appMenuButton')?.addEventListener('click', () => { appMenu?.classList.toggle('is-open'); document.getElementById('appMenuButton')?.setAttribute('aria-expanded', String(appMenu?.classList.contains('is-open'))); themeMenu?.classList.remove('is-open'); });
  document.getElementById('themeMenuButton')?.addEventListener('click', () => themeMenu?.classList.toggle('is-open'));
  document.querySelectorAll('[data-sidebar-theme]').forEach(button => button.addEventListener('click', () => { applyWorkspaceTheme(button.dataset.sidebarTheme); themeMenu?.classList.remove('is-open'); appMenu?.classList.remove('is-open'); }));
  document.getElementById('notificationsButton')?.addEventListener('click', () => document.getElementById('notificationPanel')?.classList.toggle('is-open'));
  const openPalette = () => { palette?.classList.add('is-open'); document.getElementById('commandInput')?.focus(); };
  const closePalette = () => { if (palette?.classList.contains('is-open')) { palette.classList.remove('is-open'); document.getElementById('searchButton')?.focus(); } };
  document.getElementById('searchButton')?.addEventListener('click', openPalette); document.getElementById('commandClose')?.addEventListener('click', closePalette);
  palette?.addEventListener('click', event => { if (event.target === palette) closePalette(); });
  document.getElementById('commandInput')?.addEventListener('input', event => document.querySelectorAll('[data-command-item]').forEach(item => item.classList.toggle('hidden', !item.dataset.commandItem.includes(event.target.value.toLowerCase()))));
  const mobileMenuButton = document.getElementById('mobileMenuBtn');
  const openMobileSidebar = () => { sidebar.classList.add('is-mobile-open'); mobileOverlay?.classList.remove('hidden'); mobileMenuButton?.setAttribute('aria-expanded', 'true'); sidebar.querySelector('a,button')?.focus(); };
  const closeMobileSidebar = () => { if (!sidebar.classList.contains('is-mobile-open')) return; sidebar.classList.remove('is-mobile-open'); mobileOverlay?.classList.add('hidden'); mobileMenuButton?.setAttribute('aria-expanded', 'false'); mobileMenuButton?.focus(); };
  document.getElementById('mobileMenuBtn')?.addEventListener('click', openMobileSidebar); mobileOverlay?.addEventListener('click', closeMobileSidebar);
  const onPopState = () => { const tab = settingTabFromHash(); if (tab) showSettings(tab); else closeSettings(); };
  const onThemeChange = () => { if (!settingsModal.classList.contains('hidden') && settingsFrame?.dataset.initialised === 'true') visitSettingsInFrame(settingsFrame, settingTabFromHash() || 'general', workspaceTheme()); };
  const onMessage = event => {
    if (event.origin !== location.origin) return;
    if (event.data === 'skillspot:close-settings') return closeSettings();
    if (event.data?.type === 'skillspot:settings-tab' && /^[a-z-]+$/.test(event.data.tab)) { settingsFrame.dataset.tab = event.data.tab; const gateway = event.data.tab === 'payment-gateways' && gatewayNames.includes(event.data.gateway) ? event.data.gateway : null; syncSettingsHash(event.data.tab, event.data.push === true, gateway); }
  };
  const onKeydown = event => { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); openPalette(); } if (event.key === 'Escape') { closePalette(); closeMobileSidebar(); closeSettings(); appMenu?.classList.remove('is-open'); themeMenu?.classList.remove('is-open'); } };
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
  window.__skillspotVendorCleanup?.();
  const sidebar = document.querySelector('[data-navigation-sidebar="vendor"]')?.closest('#sidebar');
  if (!sidebar || sidebar.dataset.vendorNavigationReady === 'true') return;
  sidebar.dataset.vendorNavigationReady = 'true';
  const overlay = document.getElementById('mobileOverlay');
  const syncMobileSidebar = () => {
    if (window.innerWidth >= 1024) {
      sidebar.style.removeProperty('transform');
      sidebar.style.removeProperty('transition');
      overlay?.classList.add('hidden');
      document.body.style.removeProperty('overflow');
    } else if (!sidebar.style.transform) {
      sidebar.style.transform = 'translateX(-100%)';
      sidebar.style.transition = 'transform .25s ease';
    }
  };
  document.getElementById('sidebarToggle')?.addEventListener('click', () => { sidebar.classList.toggle('sidebar-collapsed'); sidebar.classList.toggle('sidebar'); });
  document.getElementById('mobileMenuBtn')?.addEventListener('click', () => { sidebar.style.transform = 'translateX(0)'; overlay?.classList.remove('hidden'); document.body.style.overflow = 'hidden'; });
  overlay?.addEventListener('click', () => { sidebar.style.transform = 'translateX(-100%)'; overlay.classList.add('hidden'); document.body.style.overflow = ''; });
  window.addEventListener('resize', syncMobileSidebar);
  window.__skillspotVendorCleanup = () => {
    window.removeEventListener('resize', syncMobileSidebar);
    delete window.__skillspotVendorCleanup;
  };
  syncMobileSidebar();
}

function initialiseCourseWorkspace() {
  window.__skillspotCourseWorkspaceCleanup?.();
  const workspace = document.querySelector('.course-workspace');
  document.body.classList.toggle('course-workspace-page', Boolean(workspace));
  if (!workspace) return;
  const sidebar = document.getElementById('sidebar');
  const sidebarCollapsedClass = sidebar?.classList.contains('frappe-sidebar') ? 'is-collapsed' : 'sidebar-collapsed';
  let sidebarBeforeEditor;
  let sidebarChangedByUser = false;
  const rememberSidebarChoice = () => { if (sidebarBeforeEditor !== undefined) sidebarChangedByUser = true; };
  const sidebarToggle = document.getElementById('sidebarToggle');
  sidebarToggle?.addEventListener('click', rememberSidebarChoice);
  import('./course-creation-editor').then(({ initializeCourseEditor }) => initializeCourseEditor());

  const showTab = requestedTab => {
    const tab = workspace.querySelector(`[data-course-tab="${requestedTab}"]`) ? requestedTab : 'overview';
    if (sidebar && tab === 'editor') {
      if (sidebarBeforeEditor === undefined) sidebarBeforeEditor = sidebar.classList.contains(sidebarCollapsedClass);
      sidebar.classList.add(sidebarCollapsedClass);
    } else if (sidebar && sidebarBeforeEditor !== undefined) {
      if (!sidebarChangedByUser) sidebar.classList.toggle(sidebarCollapsedClass, sidebarBeforeEditor);
      sidebarBeforeEditor = undefined; sidebarChangedByUser = false;
    }
    workspace.querySelectorAll('[data-course-tab]').forEach(element => element.classList.toggle('is-active', element.dataset.courseTab === tab));
    workspace.querySelectorAll('[data-course-panel]').forEach(element => { element.hidden = element.dataset.coursePanel !== tab; });
    document.querySelectorAll('[data-tab-action]').forEach(element => element.classList.toggle('hidden', element.dataset.tabAction !== tab));
  };
  const syncTab = () => showTab(location.hash.slice(1) || 'overview');
  window.addEventListener('hashchange', syncTab);
  syncTab();
  window.__skillspotCourseWorkspaceCleanup = () => {
    window.removeEventListener('hashchange', syncTab);
    sidebarToggle?.removeEventListener('click', rememberSidebarChoice);
    delete window.__skillspotCourseWorkspaceCleanup;
  };
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

document.addEventListener('turbo:before-cache', () => { rememberScroll(); clearPendingFeedback(); });
document.addEventListener('turbo:before-render', event => {
  // Existing Blade screens contain page-local inline scripts. Turbo evaluates
  // incoming body scripts in the same document, so convert their top-level
  // lexical declarations to re-declarable variables before execution.
  event.detail.newBody.querySelectorAll('script:not([src]):not([type])').forEach(script => {
    script.textContent = script.textContent.replace(/^(\s*)(const|let)\s+/gm, '$1var ');
  });
});
document.addEventListener('turbo:before-visit', event => { if (hasDirtyForm() && !window.confirm('You have unsaved changes. Leave this page without saving them?')) event.preventDefault(); });
document.addEventListener('turbo:before-visit', event => { if (!event.defaultPrevented) showNavigationLoading(); });
document.addEventListener('turbo:submit-start', showNavigationLoading);
document.addEventListener('turbo:submit-end', clearPendingFeedback);
document.addEventListener('turbo:fetch-request-error', clearPendingFeedback);
document.addEventListener('submit', event => {
  const button = event.submitter;
  if (!button || event.target.dataset.ajax !== undefined || button.dataset.pending !== undefined) return;
  queueMicrotask(() => {
    if (event.defaultPrevented) return;
    const form = event.target;
    form.setAttribute('aria-busy', 'true');
    if (button.id !== 'loginBtn') button.dataset.pending = form.querySelector('input[type="file"]') ? 'Uploading…' : form.method.toUpperCase() === 'GET' ? 'Loading…' : 'Saving…';
  });
}, true);
window.addEventListener('beforeunload', event => { if (hasDirtyForm()) { event.preventDefault(); event.returnValue = ''; } });
document.addEventListener('turbo:load', () => {
  clearPendingFeedback();
  keepLinksInCurrentTab(); markNonDriveForms(); markNonDriveLinks(); initialiseDirtyForms(); restoreScroll(); initialiseAdminWorkspace(); initialiseVendorWorkspace(); initialiseCourseWorkspace(); initialiseLanding(); initialiseSettingsNavigation();
  if (window.self !== window.top && location.pathname === '/admin/settings') { const url = new URL(location.href); window.parent.postMessage({ type: 'skillspot:settings-tab', tab: url.searchParams.get('tab') || 'general', gateway: url.searchParams.get('gateway') }, location.origin); }
});
document.addEventListener('click', event => { const link = event.target.closest('[data-settings-tab]'); if (link && window.self !== window.top && !event.defaultPrevented) window.parent.postMessage({ type: 'skillspot:settings-tab', tab: link.dataset.settingsTab, gateway: link.dataset.settingsGateway, push: true }, location.origin); });
window.addEventListener('message', event => {
  if (event.origin !== location.origin || event.data?.type !== 'skillspot:open-settings-tab' || window.self === window.top) return;
  const theme = ['light', 'dark'].includes(event.data.theme) ? event.data.theme : null;
  if (theme) {
    document.body.dataset.theme = theme;
    document.querySelector('.settings-workspace')?.setAttribute('data-theme', theme);
  }
  const url = new URL(location.href); url.searchParams.set('tab', event.data.tab); url.searchParams.set('embed', '1'); if (theme) url.searchParams.set('theme', theme);
  if (event.data.tab === 'payment-gateways' && gatewayNames.includes(event.data.gateway)) url.searchParams.set('gateway', event.data.gateway); else url.searchParams.delete('gateway');
  const current = new URL(location.href);
  if (url.searchParams.get('tab') === current.searchParams.get('tab') && url.searchParams.get('gateway') === current.searchParams.get('gateway')) return;
  window.__skillspotSettingsNavigation?.navigate(url.toString());
});
