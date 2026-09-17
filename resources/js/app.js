import '@hotwired/turbo';
import * as icons from 'lucide';
const { createIcons } = icons;

/* ---------- Helpers ---------- */
function getCookie(name) {
  const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()[\]\\/+^]/g, '\\$&') + '=([^;]*)'));
  return match ? decodeURIComponent(match[1]) : null;
}

function setCookie(name, value, days = 365) {
  document.cookie = `${name}=${encodeURIComponent(value)}; path=/; max-age=${days * 86400}; SameSite=Lax`;
}

function renderIcons() {
  try {
    createIcons({ icons });
    /* lucide keeps `data-lucide` on the SVGs it generates, so a bare
       MutationObserver re-render loop would re-create every icon forever.
       Drop the marker so each icon is rendered exactly once. */
    document.querySelectorAll('svg[data-lucide]').forEach((svg) => svg.removeAttribute('data-lucide'));
  } catch (e) {
    /* ignore */
  }
}

/* ---------- Sidebar ---------- */
const NAV_SCROLL_KEY = 'fc-sidebar-scroll';

function sidebarEl() {
  return document.getElementById('sidebar');
}

function setSidebarCollapsed(collapsed) {
  const sidebar = sidebarEl();
  if (!sidebar) return;
  sidebar.classList.toggle('collapsed', collapsed);
  setCookie('fc_sidebar', collapsed ? 'collapsed' : 'expanded');
  const toggle = document.getElementById('sidebarToggle');
  if (toggle) {
    toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    toggle.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    const icon = toggle.querySelector('i, svg');
    if (icon) icon.setAttribute('data-lucide', collapsed ? 'chevrons-right' : 'chevrons-left');
    renderIcons();
  }
}

function applySidebarState() {
  const sidebar = sidebarEl();
  if (!sidebar) return;
  setSidebarCollapsed(getCookie('fc_sidebar') === 'collapsed');
}

function openSidebarDrawer(open) {
  const sidebar = sidebarEl();
  if (!sidebar) return;
  sidebar.classList.toggle('open', open);
  document.body.classList.toggle('sidebar-overlay-active', open);
}

function closeSidebarDrawer() {
  openSidebarDrawer(false);
}

function saveNavScroll() {
  const nav = document.querySelector('#sidebar .sidebar-nav');
  if (!nav) return;
  try {
    sessionStorage.setItem(NAV_SCROLL_KEY, String(nav.scrollTop));
  } catch (e) {}
}

function restoreNavScroll() {
  const nav = document.querySelector('#sidebar .sidebar-nav');
  if (!nav) return;
  let stored = null;
  try {
    stored = sessionStorage.getItem(NAV_SCROLL_KEY);
  } catch (e) {}
  if (stored !== null) nav.scrollTop = parseInt(stored, 10) || 0;
}

function updateActiveNav() {
  const section = document.body.dataset.activeSection || '';
  let active = null;
  document.querySelectorAll('#sidebar .nav-item').forEach((item) => {
    const isActive = section !== '' && item.dataset.navKey === section;
    item.classList.toggle('active', isActive);
    if (isActive) active = item;
  });
  if (active) {
    try {
      active.scrollIntoView({ block: 'nearest' });
    } catch (e) {}
  }
}

/* ---------- Live clock ---------- */
function tickClock() {
  const el = document.getElementById('currentTime');
  const hero = document.getElementById('heroTime');
  const now = new Date();
  const ts = now.toLocaleTimeString('en-GB');
  if (el) el.textContent = ts;
  if (hero) {
    hero.textContent = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ', ' + ts;
  }
}
tickClock();
setInterval(tickClock, 1000);

/* ---------- Notification + profile + station dropdowns ---------- */
function closeHeaderMenus(except) {
  const menus = {
    notif: ['notificationPanel', 'notificationBtn'],
    profile: ['profileMenu', 'profileBtn'],
    station: ['stationMenu', 'stationSwitchBtn'],
  };
  Object.entries(menus).forEach(([key, [panelId, btnId]]) => {
    if (key === except) return;
    const panel = document.getElementById(panelId);
    if (panel) panel.classList.remove('open');
    const btn = document.getElementById(btnId);
    if (btn) btn.setAttribute('aria-expanded', 'false');
  });
}

function isInside(selector, target) {
  return !!(target.closest(selector));
}

/* ---------- Live notification feed (bell) ---------- */
function notifCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

function escapeNotifText(str) {
  return String(str ?? '').replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));
}

function setNotifBadge(count) {
  const notifBtn = document.getElementById('notificationBtn');
  if (!notifBtn || typeof count !== 'number' || Number.isNaN(count)) return;
  const badge = document.getElementById('notifBadge');
  if (count > 0) {
    if (badge) {
      badge.textContent = count > 99 ? '99+' : String(count);
      badge.style.display = '';
      return;
    }
    const span = document.createElement('span');
    span.className = 'badge';
    span.id = 'notifBadge';
    span.textContent = count > 99 ? '99+' : String(count);
    notifBtn.appendChild(span);
  } else if (badge) {
    badge.remove();
  }
}

function renderNotifItems(items) {
  const notifList = document.getElementById('notificationList');
  if (!notifList) return;
  if (!items || !items.length) {
    notifList.innerHTML = '<div class="notification-empty" style="padding:18px;text-align:center;font-size:0.72rem;color:var(--text-muted);">No new notifications</div>';
    return;
  }
  notifList.innerHTML = items.map((n) => {
    const sev = ['critical', 'warning', 'info'].includes(n.severity) ? n.severity : 'info';
    const icon = sev === 'critical' ? 'triangle-alert' : 'circle-check';
    return `<div class="notification-item" data-notif-id="${escapeNotifText(n.id)}" role="button" tabindex="0">
      <div class="alert-icon ${sev}" style="width:28px;height:28px;"><i data-lucide="${icon}" style="width:13px;height:13px;"></i></div>
      <div>
        <div style="font-size:0.74rem;font-weight:700;color:var(--text-dark);">${escapeNotifText(n.title)}</div>
        <div style="font-size:0.65rem;color:var(--text-muted);">${escapeNotifText(n.message)} · ${escapeNotifText(n.time)}</div>
      </div>
    </div>`;
  }).join('');
  renderIcons();
}

async function refreshNotifications() {
  const notifPanel = document.getElementById('notificationPanel');
  if (!notifPanel || !notifPanel.dataset.feedUrl) return;
  try {
    const res = await fetch(notifPanel.dataset.feedUrl, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!res.ok) return;
    const data = await res.json();
    setNotifBadge(data.unread);
    renderNotifItems(data.items);
  } catch (e) {
    /* network hiccup: keep the last known state */
  }
}

async function markNotificationRead(id) {
  const notifPanel = document.getElementById('notificationPanel');
  if (!notifPanel || !notifPanel.dataset.readUrlTemplate || !id) return false;
  const url = notifPanel.dataset.readUrlTemplate.replace('__ID__', encodeURIComponent(id));
  try {
    const res = await fetch(url, {
      method: 'PATCH',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': notifCsrfToken() },
      credentials: 'same-origin',
    });
    if (!res.ok) return false;
    const data = await res.json().catch(() => ({}));
    setNotifBadge(typeof data.unread === 'number' ? data.unread : undefined);
    return true;
  } catch (e) {
    return false;
  }
}

function activateNotif(item) {
  const notifList = document.getElementById('notificationList');
  if (!item || item.dataset.reading === '1') return;
  item.dataset.reading = '1';
  markNotificationRead(item.dataset.notifId).then((ok) => {
    if (ok) {
      item.remove();
      if (notifList && !notifList.querySelector('[data-notif-id]')) renderNotifItems([]);
    } else {
      delete item.dataset.reading;
    }
  });
}

window.setInterval(() => {
  if (!document.hidden) refreshNotifications();
}, 30000);
window.addEventListener('focus', refreshNotifications);
document.addEventListener('visibilitychange', () => {
  if (!document.hidden) refreshNotifications();
});

/* ---------- Right side panel toggle ---------- */
function updateRightPanelToggle(open) {
  const toggle = document.getElementById('rightPanelToggle');
  if (!toggle) return;
  const icon = toggle.querySelector('i, svg');
  if (icon) icon.setAttribute('data-lucide', open ? 'chevrons-right' : 'chevrons-left');
  renderIcons();
  toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  toggle.setAttribute('title', open ? 'Hide side panel' : 'Show side panel');
}

function setRightPanel(open) {
  const panel = document.getElementById('rightPanel');
  if (panel) panel.classList.toggle('closed', !open);
  updateRightPanelToggle(open);
  setCookie('fc_right_panel', open ? '1' : '0');
}

function applyRightPanelState() {
  setRightPanel(getCookie('fc_right_panel') !== '0');
}

/* ---------- Search overlay ---------- */
function openSearch() {
  const searchOverlay = document.getElementById('searchOverlay');
  if (!searchOverlay) return;
  searchOverlay.classList.add('open');
  const searchInput = document.getElementById('searchInput');
  if (searchInput) searchInput.focus();
}

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const searchOverlay = document.getElementById('searchOverlay');
    if (searchOverlay) searchOverlay.classList.remove('open');
  }
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault();
    openSearch();
  }
});

/* ---------- Toasts ---------- */
function showToast(message, type = 'success') {
  const container = document.getElementById('toastContainer');
  if (!container) return;
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  const icon = type === 'success' ? 'circle-check' : type === 'error' ? 'triangle-alert' : 'circle-help';
  toast.innerHTML = `<i data-lucide="${icon}" style="width:16px;height:16px;flex-shrink:0;"></i><span>${message}</span>`;
  container.appendChild(toast);
  renderIcons();
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(8px)';
    setTimeout(() => toast.remove(), 220);
  }, 4200);
}

window.showToast = showToast;

/* ---------- KPI counters ---------- */
function animateCounters() {
  document.querySelectorAll('[data-counter]').forEach((el) => {
    const target = parseFloat(el.dataset.counter);
    const isInt = Number.isInteger(target);
    const decimals = isInt ? 0 : Intl.NumberFormat('en', { maximumFractionDigits: 2 }).format(target).split('.')[1]?.length ?? 0;
    const dur = 900;
    const start = performance.now();
    const step = (t) => {
      const p = Math.min(1, (t - start) / dur);
      const eased = 1 - Math.pow(1 - p, 3);
      const val = target * eased;
      el.textContent = Intl.NumberFormat('en', { maximumFractionDigits: decimals }).format(val);
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  });
}

/* ---------- Generic modal helpers ---------- */
window.FcModal = {
  open(id) {
    const overlay = document.getElementById(id) || document.querySelector(`[data-modal="${id}"]`);
    if (overlay) overlay.classList.add('open');
  },
  closeAll() {
    document.querySelectorAll('.modal-overlay.open').forEach((m) => m.classList.remove('open'));
  },
};

/* ---------- Confirm modal (replaces window.confirm) ---------- */
let pendingConfirmForm = null;

document.addEventListener('submit', (e) => {
  const form = e.target.closest('form[data-confirm]');
  if (!form) return;
  const confirmOverlay = document.getElementById('confirmModal');
  if (!confirmOverlay) return;

  e.preventDefault();

  pendingConfirmForm = form;

  const title = confirmOverlay.querySelector('#confirmModalTitle');
  const msg = confirmOverlay.querySelector('.confirm-message');
  const okBtn = confirmOverlay.querySelector('[data-confirm-ok]');

  if (title) title.textContent = form.dataset.confirmTitle || 'Are you sure?';
  if (msg) msg.textContent = form.dataset.confirm || 'Are you sure you want to continue?';

  const okIcon = okBtn && okBtn.querySelector('i');
  const okLabel = okBtn && okBtn.querySelector('span');
  if (okIcon) okIcon.setAttribute('data-lucide', form.dataset.confirmIcon || 'check');
  if (okLabel) okLabel.textContent = form.dataset.confirmOk || 'Confirm';
  if (okIcon) renderIcons();

  if (okBtn) okBtn.classList.toggle('btn-danger', form.hasAttribute('data-confirm-danger'));

  confirmOverlay.classList.add('open');
});

/* ---------- Global delegated click handling ---------- */
document.addEventListener('click', (e) => {
  /* Sidebar collapse (desktop) */
  if (e.target.closest('#sidebarToggle')) {
    const sidebar = sidebarEl();
    setSidebarCollapsed(!(sidebar && sidebar.classList.contains('collapsed')));
    return;
  }

  /* Mobile drawer */
  if (e.target.closest('#mobileToggle')) {
    const sidebar = sidebarEl();
    const opening = !(sidebar && sidebar.classList.contains('open'));
    closeHeaderMenus();
    openSidebarDrawer(opening);
    return;
  }
  if (e.target.closest('#sidebarOverlay')) {
    closeSidebarDrawer();
    return;
  }
  if (e.target.closest('#sidebar .nav-item')) {
    closeSidebarDrawer();
  }

  /* Header dropdowns */
  if (e.target.closest('#notificationBtn')) {
    const panel = document.getElementById('notificationPanel');
    const opening = !(panel && panel.classList.contains('open'));
    closeHeaderMenus('notif');
    if (panel) panel.classList.toggle('open', opening);
    e.target.closest('#notificationBtn').setAttribute('aria-expanded', opening ? 'true' : 'false');
    if (opening) refreshNotifications();
    return;
  }
  if (e.target.closest('#profileBtn')) {
    const menu = document.getElementById('profileMenu');
    const opening = !(menu && menu.classList.contains('open'));
    closeHeaderMenus('profile');
    if (menu) menu.classList.toggle('open', opening);
    e.target.closest('#profileBtn').setAttribute('aria-expanded', opening ? 'true' : 'false');
    return;
  }
  if (e.target.closest('#stationSwitchBtn')) {
    const menu = document.getElementById('stationMenu');
    const opening = !(menu && menu.classList.contains('open'));
    closeHeaderMenus('station');
    if (menu) menu.classList.toggle('open', opening);
    e.target.closest('#stationSwitchBtn').setAttribute('aria-expanded', opening ? 'true' : 'false');
    return;
  }

  /* Right panel */
  if (e.target.closest('#rightPanelToggle')) {
    const panel = document.getElementById('rightPanel');
    setRightPanel(panel && panel.classList.contains('closed'));
    return;
  }

  /* Search */
  if (e.target.closest('#searchBtn')) {
    openSearch();
    return;
  }
  if (e.target.closest('#searchClose')) {
    const overlay = document.getElementById('searchOverlay');
    if (overlay) overlay.classList.remove('open');
    return;
  }

  /* Notification item activation */
  const notifItem = e.target.closest('[data-notif-id]');
  if (notifItem) {
    activateNotif(notifItem);
    return;
  }

  /* Modals */
  const closeBtn = e.target.closest('.modal-close, [data-modal-close]');
  if (closeBtn) {
    const overlay = closeBtn.closest('.modal-overlay');
    if (overlay) overlay.classList.remove('open');
    window.FcModal.closeAll();
    return;
  }
  const overlay = e.target.closest('.modal-overlay');
  if (overlay && e.target === overlay) {
    overlay.classList.remove('open');
    return;
  }
  const openBtn = e.target.closest('[data-open-modal]');
  if (openBtn) {
    window.FcModal.open(openBtn.dataset.openModal);
    return;
  }

  /* Confirm modal actions */
  const confirmOk = e.target.closest('[data-confirm-ok]');
  if (confirmOk) {
    const confirmOverlay = document.getElementById('confirmModal');
    if (pendingConfirmForm) {
      if (confirmOverlay) confirmOverlay.classList.remove('open');
      const form = pendingConfirmForm;
      pendingConfirmForm = null;
      form.submit();
    }
    return;
  }
  const confirmCancel = e.target.closest('[data-confirm-cancel]');
  if (confirmCancel) {
    pendingConfirmForm = null;
    window.FcModal.closeAll();
    return;
  }

  /* Toast triggers */
  const toastTrigger = e.target.closest('[data-toast]');
  if (toastTrigger) {
    showToast(toastTrigger.dataset.toast, toastTrigger.dataset.toastType || 'success');
    return;
  }

  /* Outside click closes header menus */
  const insideMenu = isInside('#notificationPanel, #profileMenu, #stationMenu, #notificationBtn, #profileBtn, #stationSwitchBtn', e.target);
  if (!insideMenu) closeHeaderMenus();
});

/* Notification list keyboard activation */
document.addEventListener('keydown', (e) => {
  if (e.key !== 'Enter' && e.key !== ' ') return;
  const item = e.target.closest('[data-notif-id]');
  if (item) {
    e.preventDefault();
    activateNotif(item);
  }
});

/* Mark all notifications read (AJAX with native fallback) */
document.addEventListener('submit', async (e) => {
  const form = e.target.closest('#notifMarkAllForm');
  if (!form) return;
  const notifPanel = document.getElementById('notificationPanel');
  const url = notifPanel && notifPanel.dataset.readAllUrl;
  if (!url) return;
  e.preventDefault();
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': notifCsrfToken() },
      credentials: 'same-origin',
    });
    if (res.ok) {
      setNotifBadge(0);
      renderNotifItems([]);
    } else {
      form.submit();
    }
  } catch (err) {
    form.submit();
  }
});

/* ---------- Turbo lifecycle ---------- */
document.addEventListener('turbo:before-cache', () => {
  closeHeaderMenus();
  closeSidebarDrawer();
  window.FcModal.closeAll();
  saveNavScroll();
});

document.addEventListener('turbo:before-render', () => {
  if (window.__fcLiveTimer) {
    clearInterval(window.__fcLiveTimer);
    window.__fcLiveTimer = null;
  }
});

function pageInit() {
  renderIcons();
  animateCounters();
  applySidebarState();
  applyRightPanelState();
  updateActiveNav();
  closeSidebarDrawer();
  closeHeaderMenus();
  restoreNavScroll();
}

document.addEventListener('turbo:load', pageInit);

/* Fallback when Turbo is unavailable */
if (!window.Turbo) {
  document.addEventListener('DOMContentLoaded', pageInit);
}

const observer = new MutationObserver(() => renderIcons());
observer.observe(document.documentElement, { childList: true, subtree: true });
