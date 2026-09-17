import * as icons from 'lucide';
const { createIcons } = icons;

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
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const mobileToggle = document.getElementById('mobileToggle');

if (sidebarToggle) {
  sidebarToggle.addEventListener('click', () => {
    const collapsed = sidebar.classList.toggle('collapsed');
    try {
      localStorage.setItem('fc-collapsed', collapsed ? '1' : '0');
    } catch (e) {}
  });
}

if (mobileToggle) {
  mobileToggle.addEventListener('click', () => {
    sidebar.classList.toggle('mobile-open');
    document.body.classList.toggle('sidebar-overlay-active');
  });
}

(() => {
  try {
    if (localStorage.getItem('fc-collapsed') === '1') {
      sidebar && sidebar.classList.add('collapsed');
    }
  } catch (e) {}
})();

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

/* ---------- Notification panel ---------- */
const notifBtn = document.getElementById('notificationBtn');
const notifPanel = document.getElementById('notificationPanel');

if (notifBtn && notifPanel) {
  notifBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    notifPanel.classList.toggle('open');
  });
  document.addEventListener('click', (e) => {
    if (!notifPanel.contains(e.target)) notifPanel.classList.remove('open');
  });
}

/* ---------- Right side panel toggle ---------- */
const rightPanelEl = document.getElementById('rightPanel');
const rightPanelToggle = document.getElementById('rightPanelToggle');

if (rightPanelEl && rightPanelToggle) {
  const setRightPanel = (open) => {
    rightPanelEl.classList.toggle('closed', !open);
    const icon = rightPanelToggle.querySelector('i, svg');
    if (icon) icon.setAttribute('data-lucide', open ? 'chevrons-right' : 'chevrons-left');
    renderIcons();
    rightPanelToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    rightPanelToggle.setAttribute('title', open ? 'Hide side panel' : 'Show side panel');
    try {
      localStorage.setItem('fc-right-panel', open ? '1' : '0');
    } catch (e) {}
  };
  rightPanelToggle.addEventListener('click', () => {
    setRightPanel(rightPanelEl.classList.contains('closed'));
  });
  let rpOpen = '1';
  try {
    rpOpen = localStorage.getItem('fc-right-panel') ?? '1';
  } catch (e) {}
  setRightPanel(rpOpen !== '0');
}

/* ---------- Search overlay ---------- */
const searchBtn = document.getElementById('searchBtn');
const searchOverlay = document.getElementById('searchOverlay');
const searchClose = document.getElementById('searchClose');
const searchInput = document.getElementById('searchInput');

function openSearch() {
  if (!searchOverlay) return;
  searchOverlay.classList.add('open');
  searchInput && searchInput.focus();
}

if (searchBtn) searchBtn.addEventListener('click', openSearch);
if (searchClose) searchClose.addEventListener('click', () => searchOverlay.classList.remove('open'));
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') searchOverlay && searchOverlay.classList.remove('open');
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
  const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'alert-triangle' : 'help-circle';
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

document.querySelectorAll('[data-toast]').forEach((el) => {
  el.addEventListener('click', () => showToast(el.dataset.toast, el.dataset.toastType || 'success'));
});

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

document.querySelectorAll('.modal-close, [data-modal-close]').forEach((el) => {
  el.addEventListener('click', () => {
    (el.closest('.modal-overlay') || {}).classList && el.closest('.modal-overlay').classList.remove('open');
    window.FcModal.closeAll();
  });
});

document.querySelectorAll('.modal-overlay').forEach((m) => {
  m.addEventListener('click', (e) => {
    if (e.target === m) m.classList.remove('open');
  });
});

document.querySelectorAll('[data-open-modal]').forEach((btn) => {
  btn.addEventListener('click', () => window.FcModal.open(btn.dataset.openModal));
});

/* ---------- Confirm modal (replaces window.confirm) ---------- */
const confirmOverlay = document.getElementById('confirmModal');
const confirmModalTitle = confirmOverlay && confirmOverlay.querySelector('#confirmModalTitle');
const confirmMsg = confirmOverlay && confirmOverlay.querySelector('.confirm-message');
const confirmOkBtn = confirmOverlay && confirmOverlay.querySelector('[data-confirm-ok]');

let pendingConfirmForm = null;

document.addEventListener('submit', (e) => {
  const form = e.target.closest('form[data-confirm]');
  if (!form || !confirmOverlay) return;

  e.preventDefault();

  pendingConfirmForm = form;

  if (confirmModalTitle) confirmModalTitle.textContent = form.dataset.confirmTitle || 'Are you sure?';
  if (confirmMsg) confirmMsg.textContent = form.dataset.confirm || 'Are you sure you want to continue?';

  const okIcon = confirmOkBtn && confirmOkBtn.querySelector('i');
  const okLabel = confirmOkBtn && confirmOkBtn.querySelector('span');
  if (okIcon) okIcon.setAttribute('data-lucide', form.dataset.confirmIcon || 'check');
  if (okLabel) okLabel.textContent = form.dataset.confirmOk || 'Confirm';
  if (okIcon) renderIcons();

  if (confirmOkBtn) confirmOkBtn.classList.toggle('btn-danger', form.hasAttribute('data-confirm-danger'));

  confirmOverlay.classList.add('open');
});

if (confirmOkBtn) {
  confirmOkBtn.addEventListener('click', () => {
    if (!pendingConfirmForm) return;
    confirmOverlay.classList.remove('open');
    pendingConfirmForm.submit();
  });
}

document.querySelectorAll('[data-confirm-cancel]').forEach((btn) => {
  btn.addEventListener('click', () => {
    pendingConfirmForm = null;
    window.FcModal.closeAll();
  });
});

/* ---------- Init ---------- */
document.addEventListener('DOMContentLoaded', () => {
  renderIcons();
  animateCounters();
});

const observer = new MutationObserver(() => renderIcons());
observer.observe(document.body, { childList: true, subtree: true });