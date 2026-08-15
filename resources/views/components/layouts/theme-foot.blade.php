<script src="{{ asset('theme/vendor/jquery/jquery.min.js') }}" data-navigate-once></script>
<script src="{{ asset('theme/vendor/bootstrap/js/bootstrap.bundle.min.js') }}" data-navigate-once></script>
<script src="{{ asset('theme/js/app.js') }}?v={{ filemtime(public_path('theme/js/app.js')) }}" data-navigate-once></script>
@livewireScripts
<script data-navigate-once>
(() => {
  const register = () => {
    if (!window.Alpine || window.__nyDatepicker) return;
    window.__nyDatepicker = true;
    Alpine.data('nyDatepicker', (cfg = {}) => {
    const fmt = (d) => {
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${y}-${m}-${day}`;
    };
    const parse = (iso) => {
      if (!iso) return null;
      const [y, m, d] = iso.split('-').map(Number);
      if (!y || !m || !d) return null;
      return new Date(y, m - 1, d);
    };

    const cap = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';

    return {
      open: false,
      iso: '',
      view: new Date(),
      panel: 'days',
      yearAnchor: new Date().getFullYear() - 2,
      locale: cfg.locale || 'ru',
      todayLabel: cfg.todayLabel || '',
      clearLabel: cfg.clearLabel || '',
      placeholder: cfg.placeholder || '',
      init() {
        this.syncFromInput();
        const input = this.$refs.input;
        if (!input) return;
        const sync = () => {
          if (input.value !== this.iso) this.syncFromInput();
        };
        input.addEventListener('input', sync);
        this.$watch('open', (on) => {
          if (!on) return;
          this.panel = 'days';
          this.place();
        });
        this.$watch('panel', () => { if (this.open) this.place(); });
      },
      syncFromInput() {
        this.iso = this.$refs.input?.value || '';
        const d = parse(this.iso);
        this.view = d ? new Date(d.getFullYear(), d.getMonth(), 1) : new Date();
      },
      get display() {
        const d = parse(this.iso);
        return d ? d.toLocaleDateString(this.locale, { day: '2-digit', month: '2-digit', year: 'numeric' }) : '';
      },
      get monthLabel() {
        return cap(this.view.toLocaleDateString(this.locale, { month: 'long' }));
      },
      get yearLabel() {
        return String(this.view.getFullYear());
      },
      get yearRangeTitle() {
        return `${this.yearAnchor} – ${this.yearAnchor + 11}`;
      },
      get months() {
        const y = this.view.getFullYear();
        const now = new Date();
        return Array.from({ length: 12 }, (_, i) => ({
          index: i,
          label: cap(new Date(y, i, 1).toLocaleDateString(this.locale, { month: 'short' }).replace('.', '')),
          isSelected: this.view.getMonth() === i,
          isCurrent: now.getFullYear() === y && now.getMonth() === i,
        }));
      },
      get years() {
        const nowY = new Date().getFullYear();
        const selected = this.view.getFullYear();
        return Array.from({ length: 12 }, (_, i) => {
          const year = this.yearAnchor + i;
          return { year, isSelected: year === selected, isCurrent: year === nowY };
        });
      },
      get weekdays() {
        return Array.from({ length: 7 }, (_, i) => {
          const d = new Date(2026, 0, 5 + i);
          return d.toLocaleDateString(this.locale, { weekday: 'short' }).replace('.', '').slice(0, 2);
        });
      },
      get cells() {
        const y = this.view.getFullYear();
        const m = this.view.getMonth();
        const start = (new Date(y, m, 1).getDay() + 6) % 7;
        const daysInMonth = new Date(y, m + 1, 0).getDate();
        const prevDays = new Date(y, m, 0).getDate();
        const today = fmt(new Date());
        const cells = [];
        for (let i = 0; i < 42; i++) {
          const n = i - start + 1;
          let date;
          let inMonth = true;
          if (n < 1) {
            date = new Date(y, m - 1, prevDays + n);
            inMonth = false;
          } else if (n > daysInMonth) {
            date = new Date(y, m + 1, n - daysInMonth);
            inMonth = false;
          } else {
            date = new Date(y, m, n);
          }
          const iso = fmt(date);
          cells.push({
            iso,
            day: date.getDate(),
            inMonth,
            isToday: iso === today,
            isSelected: iso === this.iso,
          });
        }
        return cells;
      },
      toggle() {
        this.open = !this.open;
      },
      onEsc() {
        if (!this.open) return;
        if (this.panel !== 'days') this.panel = 'days';
        else this.open = false;
      },
      openMonths() {
        this.panel = 'months';
      },
      openYears() {
        this.yearAnchor = this.view.getFullYear() - 2;
        this.panel = 'years';
      },
      shift(delta) {
        if (this.panel === 'years') {
          this.yearAnchor += delta * 12;
          return;
        }
        if (this.panel === 'months') {
          this.view = new Date(this.view.getFullYear() + delta, this.view.getMonth(), 1);
          return;
        }
        this.view = new Date(this.view.getFullYear(), this.view.getMonth() + delta, 1);
      },
      pickMonth(index) {
        this.view = new Date(this.view.getFullYear(), index, 1);
        this.panel = 'days';
      },
      pickYear(year) {
        this.view = new Date(year, this.view.getMonth(), 1);
        this.panel = 'days';
      },
      pick(iso) {
        this.commit(iso);
        this.open = false;
      },
      pickToday() {
        this.pick(fmt(new Date()));
      },
      clearDate() {
        this.commit('');
        this.open = false;
      },
      commit(iso) {
        this.iso = iso;
        const el = this.$refs.input;
        if (!el) return;
        el.value = iso;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
      },
      place() {
        this.$nextTick(() => {
          const pop = this.$refs.pop;
          const btn = this.$refs.trigger;
          if (!pop || !btn) return;
          const r = btn.getBoundingClientRect();
          const width = Math.max(r.width, 308);
          pop.style.position = 'fixed';
          pop.style.width = width + 'px';
          let left = r.left;
          if (left + width > window.innerWidth - 8) left = window.innerWidth - width - 8;
          if (left < 8) left = 8;
          pop.style.left = left + 'px';
          this.$nextTick(() => {
            const h = pop.offsetHeight;
            const below = r.bottom + 8;
            const top = (below + h > window.innerHeight - 8 && r.top > h + 8)
              ? r.top - h - 8
              : below;
            pop.style.top = top + 'px';
          });
        });
      },
    };
    });
  };
  document.addEventListener('alpine:init', register);
  register();
})();
</script>
<script data-navigate-once>
(() => {
  const icons = { success: 'check-circle', delete: 'delete', danger: 'delete', warning: 'alert', error: 'alert-circle', info: 'information' };

  const showToast = (message, type = 'success') => {
    if (!message || !window.bootstrap?.Toast) return;
    const container = document.getElementById('ny-toasts');
    if (!container) return;
    const icon = icons[type] || icons.success;
    const el = document.createElement('div');
    el.className = 'toast ny-toast';
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    el.innerHTML = '<div class="ny-toast-body"><span class="ny-toast-icon"><i class="mdi mdi-' + icon + '"></i></span><div class="ny-toast-msg"></div><button type="button" class="ny-toast-close" data-bs-dismiss="toast" aria-label="Close"><i class="mdi mdi-close"></i></button></div><div class="ny-toast-progress"></div>';
    el.querySelector('.ny-toast-msg').textContent = message;
    container.appendChild(el);
    const toast = new bootstrap.Toast(el, { delay: 4200, animation: false });
    el.addEventListener('hide.bs.toast', (e) => {
      if (el.classList.contains('is-out')) return;
      e.preventDefault();
      el.classList.add('is-out');
      const finish = () => toast.hide();
      const onEnd = (ev) => {
        if (ev.target !== el || ev.animationName !== 'ny-toast-out') return;
        el.removeEventListener('animationend', onEnd);
        finish();
      };
      el.addEventListener('animationend', onEnd);
      setTimeout(finish, 380);
    });
    el.addEventListener('hidden.bs.toast', () => el.remove());
    toast.show();
  };

  const consumeFlash = () => {
    const el = document.getElementById('ny-toasts');
    if (!el?.dataset.flashMessage) return;
    showToast(el.dataset.flashMessage, el.dataset.flashType || 'success');
    delete el.dataset.flashMessage;
    delete el.dataset.flashType;
  };

  const onToast = (payload) => {
    const data = Array.isArray(payload) ? payload[0] : payload;
    showToast(data?.message, data?.type || 'success');
  };
  if (window.Livewire) {
    Livewire.on('toast', onToast);
  } else {
    document.addEventListener('livewire:init', () => Livewire.on('toast', onToast));
  }
  document.addEventListener('DOMContentLoaded', consumeFlash);
  document.addEventListener('livewire:navigated', consumeFlash);
})();
</script>

