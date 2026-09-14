(() => {
  const html = document.documentElement;
  const persist = () => {
    try {
      localStorage.setItem('pharmacore.sidebar', html.classList.contains('sidebar-collapsed') ? '1' : '0');
    } catch (e) {}
  };

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sidebar-toggle]')) {
      event.preventDefault();
      html.classList.toggle('sidebar-collapsed');
      html.classList.remove('sidebar-open');
      persist();
      return;
    }
    if (event.target.closest('[data-sidebar-open]')) {
      event.preventDefault();
      html.classList.toggle('sidebar-open');
      return;
    }
    if (event.target.closest('[data-sidebar-backdrop]')) {
      html.classList.remove('sidebar-open');
      return;
    }
    if (event.target.closest('.nav-link')) {
      html.classList.remove('sidebar-open');
    }
  });

  const groups = () => [...document.querySelectorAll('[data-nav-group]')];
  const storedGroups = () => {
    try {
      return JSON.parse(localStorage.getItem('pharmacore.navGroups') || '{}');
    } catch (e) {
      return {};
    }
  };
  const persistGroups = (state) => {
    try {
      localStorage.setItem('pharmacore.navGroups', JSON.stringify(state));
    } catch (e) {}
  };
  const setGroupOpen = (group, open) => {
    group.classList.toggle('is-open', open);
    const toggle = group.querySelector('.nav-group-toggle');
    toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
  };

  const saved = storedGroups();
  groups().forEach((group) => {
    const id = group.getAttribute('data-nav-group');
    if (group.classList.contains('is-current')) {
      setGroupOpen(group, true);
      return;
    }
    if (Object.prototype.hasOwnProperty.call(saved, id)) {
      setGroupOpen(group, saved[id] === true);
    }
  });

  document.addEventListener('click', (event) => {
    const toggle = event.target.closest('.nav-group-toggle');
    if (!toggle) {
      if (!event.target.closest('.nav-group') && html.classList.contains('sidebar-collapsed')) {
        groups().forEach((group) => {
          if (!group.matches(':hover')) {
            setGroupOpen(group, group.classList.contains('is-current'));
          }
        });
      }
      return;
    }

    event.preventDefault();
    const group = toggle.closest('[data-nav-group]');
    const next = !group.classList.contains('is-open');
    setGroupOpen(group, next);
    const state = storedGroups();
    state[group.getAttribute('data-nav-group')] = next;
    persistGroups(state);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      html.classList.remove('sidebar-open');
    }
  });
})();

(() => {
  const modal = document.getElementById('app-modal');
  if (!modal) {
    return;
  }

  const titleEl = modal.querySelector('.modal-title');
  const bodyEl = modal.querySelector('.modal-body');
  const dialogEl = modal.querySelector('.modal-dialog');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const openModal = (title, html, wide = false) => {
    titleEl.textContent = title;
    bodyEl.innerHTML = html;
    dialogEl.classList.toggle('wide', wide);
    modal.hidden = false;
    document.body.classList.add('modal-open');
  };

  const closeModal = () => {
    modal.hidden = true;
    bodyEl.innerHTML = '';
    document.body.classList.remove('modal-open');
  };

  const confirmHtml = (message) => {
    const wrap = document.createElement('div');
    const text = document.createElement('p');
    text.textContent = message;
    wrap.appendChild(text);
    const actions = document.createElement('div');
    actions.className = 'modal-actions';
    actions.innerHTML = '<button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button type="button" class="btn btn-danger" data-confirm-ok>Confirm</button>';
    wrap.appendChild(actions);
    return wrap.innerHTML;
  };

  const submitAction = (action, method, extra = {}) => {
    const form = document.createElement('form');
    form.method = 'post';
    form.action = action;
    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = csrf;
    form.appendChild(token);
    if (method && method !== 'POST') {
      const spoof = document.createElement('input');
      spoof.type = 'hidden';
      spoof.name = '_method';
      spoof.value = method;
      form.appendChild(spoof);
    }
    Object.entries(extra).forEach(([name, value]) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = String(value);
      form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
  };

  document.addEventListener('click', async (event) => {
    const closer = event.target.closest('[data-modal-close]');
    if (closer) {
      event.preventDefault();
      closeModal();
      return;
    }

    const confirmTrigger = event.target.closest('[data-modal-confirm]');
    if (confirmTrigger) {
      event.preventDefault();
      const message = confirmTrigger.getAttribute('data-modal-confirm') || 'Are you sure?';
      const action = confirmTrigger.getAttribute('data-action') || confirmTrigger.getAttribute('href') || '';
      const method = (confirmTrigger.getAttribute('data-method') || 'POST').toUpperCase();
      const extra = {};
      if (confirmTrigger.dataset.recallReason) {
        extra.recall_reason = confirmTrigger.dataset.recallReason;
      }
      if (confirmTrigger.dataset.status) {
        extra.status = confirmTrigger.dataset.status;
      }
      openModal(confirmTrigger.getAttribute('data-modal-title') || 'Confirm', confirmHtml(message));
      bodyEl.querySelector('[data-confirm-ok]')?.addEventListener('click', () => {
        submitAction(action, method, extra);
      });
      return;
    }

    const srcTrigger = event.target.closest('[data-modal-src]');
    if (srcTrigger) {
      event.preventDefault();
      const source = document.querySelector(srcTrigger.getAttribute('data-modal-src') || '');
      if (!source) {
        return;
      }
      openModal(
        srcTrigger.getAttribute('data-modal-title') || srcTrigger.textContent.trim(),
        source.innerHTML,
        srcTrigger.hasAttribute('data-modal-wide')
      );
      const form = bodyEl.querySelector('form');
      if (form && srcTrigger.dataset.setAction) {
        form.action = srcTrigger.dataset.setAction;
      }
      document.dispatchEvent(new CustomEvent('pharmacore:content'));
      return;
    }

    const fetchTrigger = event.target.closest('[data-modal]');
    if (fetchTrigger) {
      event.preventDefault();
      const href = fetchTrigger.getAttribute('href');
      if (!href) {
        return;
      }
      const url = new URL(href, window.location.origin);
      url.searchParams.set('modal', '1');
      openModal(fetchTrigger.getAttribute('data-modal-title') || fetchTrigger.textContent.trim(), '<p>Loading…</p>', fetchTrigger.hasAttribute('data-modal-wide'));
      try {
        const response = await fetch(url.toString(), {
          headers: { 'X-Pharmacore-Modal': '1' },
          credentials: 'same-origin',
        });
        const html = await response.text();
        if (!response.ok) {
          bodyEl.innerHTML = '<p>Could not load this form.</p>';
          return;
        }
        bodyEl.innerHTML = html;
        document.dispatchEvent(new CustomEvent('pharmacore:content'));
      } catch {
        bodyEl.innerHTML = '<p>Could not load this form.</p>';
      }
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });
})();

(() => {
  const root = document.querySelector('[data-pos]');
  if (!root) {
    return;
  }

  const cartEl = root.querySelector('[data-pos-cart]');
  const itemsEl = root.querySelector('[data-pos-items]');
  const discountEl = root.querySelector('[data-pos-discount]');
  const subtotalEl = root.querySelector('[data-pos-subtotal]');
  const discountLabelEl = root.querySelector('[data-pos-discount-label]');
  const totalEl = root.querySelector('[data-pos-total]');
  const submitEl = root.querySelector('[data-pos-submit]');
  const formEl = root.querySelector('[data-pos-form]');
  const productsEl = root.querySelector('[data-pos-products]');
  const cart = [];

  const setView = (view) => {
    if (!productsEl) {
      return;
    }
    productsEl.classList.toggle('is-grid', view === 'grid');
    productsEl.classList.toggle('is-list', view === 'list');
    root.querySelectorAll('[data-pos-view]').forEach((btn) => {
      btn.classList.toggle('is-active', btn.getAttribute('data-pos-view') === view);
    });
    try {
      localStorage.setItem('pharmacore.posView', view);
    } catch (e) {}
  };

  try {
    setView(localStorage.getItem('pharmacore.posView') === 'list' ? 'list' : 'grid');
  } catch (e) {
    setView('grid');
  }

  const money = (n) => 'GHS ' + Number(n).toFixed(2);

  const findLine = (id) => cart.find((line) => line.id === id);

  const add = (product) => {
    const existing = findLine(product.id);
    if (existing) {
      existing.qty += 1;
    } else {
      cart.push({
        id: product.id,
        name: product.name,
        price: product.price,
        qty: 1,
        stock: product.stock,
      });
    }
    render();
  };

  const inc = (id) => {
    const line = findLine(id);
    if (line) {
      line.qty += 1;
      render();
    }
  };

  const dec = (id) => {
    const line = findLine(id);
    if (!line) {
      return;
    }
    line.qty -= 1;
    if (line.qty <= 0) {
      const index = cart.findIndex((item) => item.id === id);
      cart.splice(index, 1);
    }
    render();
  };

  const subtotal = () => cart.reduce((sum, line) => sum + line.qty * line.price, 0);

  const discount = () => Number(discountEl?.value) || 0;

  const total = () => Math.max(0, subtotal() - discount());

  const render = () => {
    if (cart.length === 0) {
      cartEl.innerHTML = '<div class="empty">Tap a product to add it.</div>';
    } else {
      cartEl.innerHTML = cart.map((line) => `
        <div class="cart-line">
          <div>
            <strong>${escapeHtml(line.name)}</strong>
            <div style="font-size:12px;color:var(--color-grey);">
              <button type="button" class="btn btn-outline btn-sm" data-pos-dec="${line.id}">−</button>
              <span>${line.qty}</span>
              <button type="button" class="btn btn-outline btn-sm" data-pos-inc="${line.id}">+</button>
            </div>
          </div>
          <div>${money(line.qty * line.price)}</div>
        </div>
      `).join('');
    }

    itemsEl.value = JSON.stringify(cart.map((line) => ({
      product_id: line.id,
      quantity: line.qty,
      unit_price: line.price,
    })));
    subtotalEl.textContent = money(subtotal());
    discountLabelEl.textContent = money(discount());
    totalEl.textContent = money(total());
    submitEl.disabled = cart.length === 0;
  };

  const escapeHtml = (value) => {
    const el = document.createElement('div');
    el.textContent = value;
    return el.innerHTML;
  };

  root.addEventListener('click', (event) => {
    const productBtn = event.target.closest('[data-pos-add]');
    if (productBtn && root.contains(productBtn)) {
      add({
        id: Number(productBtn.dataset.id),
        name: productBtn.dataset.name || '',
        price: Number(productBtn.dataset.price),
        stock: Number(productBtn.dataset.stock),
      });
      return;
    }

    const incBtn = event.target.closest('[data-pos-inc]');
    if (incBtn) {
      inc(Number(incBtn.getAttribute('data-pos-inc')));
      return;
    }

    const decBtn = event.target.closest('[data-pos-dec]');
    if (decBtn) {
      dec(Number(decBtn.getAttribute('data-pos-dec')));
      return;
    }

    const viewBtn = event.target.closest('[data-pos-view]');
    if (viewBtn) {
      setView(viewBtn.getAttribute('data-pos-view') === 'list' ? 'list' : 'grid');
    }
  });

  discountEl?.addEventListener('input', render);
  formEl?.addEventListener('submit', (event) => {
    if (cart.length === 0) {
      event.preventDefault();
    }
  });

  render();
})();

(() => {
  const wrap = document.querySelector('[data-auth-wrap]');
  const canvas = document.querySelector('[data-auth-field]');
  if (!wrap || !canvas) {
    return;
  }

  const ctx = canvas.getContext('2d');
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const pointer = { x: 0, y: 0, tx: 0, ty: 0, active: false };
  const particles = [];
  const symbols = [];
  let width = 0;
  let height = 0;
  let dpr = 1;

  const rand = (min, max) => min + Math.random() * (max - min);

  const resize = () => {
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    width = wrap.clientWidth;
    height = wrap.clientHeight;
    canvas.width = Math.floor(width * dpr);
    canvas.height = Math.floor(height * dpr);
    canvas.style.width = width + 'px';
    canvas.style.height = height + 'px';
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  };

  const seed = () => {
    particles.length = 0;
    symbols.length = 0;
    const count = Math.round((width * height) / 14000);
    for (let i = 0; i < count; i += 1) {
      particles.push({
        x: rand(0, width),
        y: rand(0, height),
        vx: rand(-0.18, 0.18),
        vy: rand(-0.18, 0.18),
        r: rand(1.1, 2.6),
        kind: Math.random() > 0.78 ? 'plus' : (Math.random() > 0.82 ? 'capsule' : 'dot'),
        pulse: rand(0, Math.PI * 2),
      });
    }
    const kinds = ['adinkrahene', 'sankofa', 'dwennimmen', 'eban', 'osram', 'nkyinkyim', 'akoma', 'gyenyame'];
    const symbolCount = Math.max(10, Math.round((width * height) / 70000));
    for (let i = 0; i < symbolCount; i += 1) {
      symbols.push({
        x: rand(40, width - 40),
        y: rand(40, height - 40),
        vx: rand(-0.06, 0.06),
        vy: rand(-0.06, 0.06),
        s: rand(26, 52),
        rot: rand(0, Math.PI * 2),
        vr: rand(-0.002, 0.002),
        kind: kinds[i % kinds.length],
        alpha: rand(0.16, 0.28),
      });
    }
  };

  const drawPlus = (x, y, r) => {
    ctx.beginPath();
    ctx.moveTo(x - r, y);
    ctx.lineTo(x + r, y);
    ctx.moveTo(x, y - r);
    ctx.lineTo(x, y + r);
    ctx.stroke();
  };

  const drawCapsule = (x, y, r) => {
    ctx.beginPath();
    ctx.roundRect(x - r * 1.6, y - r * 0.7, r * 3.2, r * 1.4, r);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(x, y - r * 0.5);
    ctx.lineTo(x, y + r * 0.5);
    ctx.stroke();
  };

  const drawSymbol = (symbol) => {
    ctx.save();
    ctx.translate(symbol.x, symbol.y);
    ctx.rotate(symbol.rot);
    ctx.strokeStyle = `rgba(154, 125, 79, ${symbol.alpha})`;
    ctx.fillStyle = `rgba(154, 125, 79, ${symbol.alpha * 0.35})`;
    ctx.lineWidth = 1.7;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    const s = symbol.s;

    if (symbol.kind === 'adinkrahene') {
      [0.42, 0.27, 0.12].forEach((n, i) => {
        ctx.beginPath();
        ctx.arc(0, 0, s * n, 0, Math.PI * 2);
        i === 2 ? ctx.fill() : ctx.stroke();
      });
    } else if (symbol.kind === 'sankofa') {
      ctx.beginPath();
      ctx.moveTo(0, s * 0.38);
      ctx.bezierCurveTo(-s * 0.55, s * 0.05, -s * 0.4, -s * 0.4, 0, -s * 0.12);
      ctx.bezierCurveTo(s * 0.4, -s * 0.4, s * 0.55, s * 0.05, 0, s * 0.38);
      ctx.stroke();
      ctx.beginPath();
      ctx.arc(-s * 0.08, -s * 0.02, s * 0.08, 0, Math.PI * 2);
      ctx.stroke();
    } else if (symbol.kind === 'dwennimmen') {
      ctx.beginPath();
      ctx.arc(-s * 0.18, 0, s * 0.28, Math.PI * 0.2, Math.PI * 1.7);
      ctx.stroke();
      ctx.beginPath();
      ctx.arc(s * 0.18, 0, s * 0.28, Math.PI * 1.2, Math.PI * 0.7, true);
      ctx.stroke();
    } else if (symbol.kind === 'eban') {
      ctx.strokeRect(-s * 0.38, -s * 0.38, s * 0.76, s * 0.76);
      ctx.strokeRect(-s * 0.2, -s * 0.2, s * 0.4, s * 0.4);
    } else if (symbol.kind === 'osram') {
      ctx.beginPath();
      ctx.arc(-s * 0.08, 0, s * 0.28, -0.6, 2.4);
      ctx.stroke();
      for (let i = 0; i < 8; i += 1) {
        const a = (Math.PI * 2 * i) / 8;
        ctx.beginPath();
        ctx.moveTo(s * 0.18 + Math.cos(a) * s * 0.06, -s * 0.12 + Math.sin(a) * s * 0.06);
        ctx.lineTo(s * 0.18 + Math.cos(a) * s * 0.18, -s * 0.12 + Math.sin(a) * s * 0.18);
        ctx.stroke();
      }
    } else if (symbol.kind === 'nkyinkyim') {
      ctx.beginPath();
      ctx.moveTo(-s * 0.4, s * 0.22);
      ctx.lineTo(-s * 0.12, -s * 0.22);
      ctx.lineTo(s * 0.12, s * 0.22);
      ctx.lineTo(s * 0.4, -s * 0.22);
      ctx.stroke();
    } else if (symbol.kind === 'akoma') {
      ctx.beginPath();
      ctx.moveTo(0, s * 0.34);
      ctx.bezierCurveTo(-s * 0.46, s * 0.02, -s * 0.3, -s * 0.32, 0, -s * 0.08);
      ctx.bezierCurveTo(s * 0.3, -s * 0.32, s * 0.46, s * 0.02, 0, s * 0.34);
      ctx.stroke();
    } else {
      ctx.beginPath();
      ctx.moveTo(0, -s * 0.42);
      ctx.quadraticCurveTo(s * 0.34, -s * 0.18, s * 0.18, s * 0.08);
      ctx.quadraticCurveTo(s * 0.36, s * 0.32, 0, s * 0.42);
      ctx.quadraticCurveTo(-s * 0.36, s * 0.32, -s * 0.18, s * 0.08);
      ctx.quadraticCurveTo(-s * 0.34, -s * 0.18, 0, -s * 0.42);
      ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(0, -s * 0.18);
      ctx.lineTo(0, s * 0.18);
      ctx.stroke();
    }
    ctx.restore();
  };

  const burst = (x, y) => {
    for (let i = 0; i < 10; i += 1) {
      const angle = rand(0, Math.PI * 2);
      const speed = rand(0.6, 1.8);
      particles.push({
        x,
        y,
        vx: Math.cos(angle) * speed,
        vy: Math.sin(angle) * speed,
        r: rand(1.2, 2.4),
        kind: Math.random() > 0.5 ? 'plus' : 'dot',
        pulse: 0,
        life: 90,
      });
    }
  };

  const step = () => {
    pointer.x += (pointer.tx - pointer.x) * 0.12;
    pointer.y += (pointer.ty - pointer.y) * 0.12;
    ctx.clearRect(0, 0, width, height);

    symbols.forEach((symbol) => {
      if (!reduced) {
        symbol.x += symbol.vx;
        symbol.y += symbol.vy;
        symbol.rot += symbol.vr;
        if (symbol.x < 20 || symbol.x > width - 20) symbol.vx *= -1;
        if (symbol.y < 20 || symbol.y > height - 20) symbol.vy *= -1;
      }
      drawSymbol(symbol);
    });

    ctx.lineWidth = 1;
    for (let i = 0; i < particles.length; i += 1) {
      const a = particles[i];
      for (let j = i + 1; j < particles.length; j += 1) {
        const b = particles[j];
        const dx = a.x - b.x;
        const dy = a.y - b.y;
        const dist = Math.hypot(dx, dy);
        if (dist < 90) {
          ctx.strokeStyle = `rgba(42, 122, 75, ${0.12 * (1 - dist / 90)})`;
          ctx.beginPath();
          ctx.moveTo(a.x, a.y);
          ctx.lineTo(b.x, b.y);
          ctx.stroke();
        }
      }
    }

    for (let i = particles.length - 1; i >= 0; i -= 1) {
      const p = particles[i];
      if (!reduced) {
        if (pointer.active) {
          const dx = p.x - pointer.x;
          const dy = p.y - pointer.y;
          const dist = Math.hypot(dx, dy) || 1;
          if (dist < 140) {
            const force = (140 - dist) / 140;
            p.vx += (dx / dist) * force * 0.22;
            p.vy += (dy / dist) * force * 0.22;
          }
        }
        p.vx *= 0.99;
        p.vy *= 0.99;
        p.x += p.vx;
        p.y += p.vy;
        p.pulse += 0.03;
        if (p.x < -10) p.x = width + 10;
        if (p.x > width + 10) p.x = -10;
        if (p.y < -10) p.y = height + 10;
        if (p.y > height + 10) p.y = -10;
      }

      const glow = 0.22 + Math.sin(p.pulse) * 0.08;
      ctx.strokeStyle = `rgba(42, 122, 75, ${0.38 + glow})`;
      ctx.fillStyle = `rgba(42, 122, 75, ${0.28 + glow})`;
      ctx.lineWidth = 1.2;
      if (p.kind === 'plus') {
        drawPlus(p.x, p.y, p.r + 1.6);
      } else if (p.kind === 'capsule' && ctx.roundRect) {
        drawCapsule(p.x, p.y, p.r + 1.1);
      } else {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fill();
      }

      if (p.life !== undefined) {
        p.life -= 1;
        if (p.life <= 0) {
          particles.splice(i, 1);
        }
      }
    }

    if (!reduced) {
      requestAnimationFrame(step);
    }
  };

  const onMove = (event) => {
    const rect = wrap.getBoundingClientRect();
    pointer.tx = event.clientX - rect.left;
    pointer.ty = event.clientY - rect.top;
    pointer.active = true;
  };

  wrap.addEventListener('mousemove', onMove);
  wrap.addEventListener('mouseleave', () => { pointer.active = false; });
  wrap.addEventListener('click', (event) => {
    if (event.target.closest('form, a, button, input, select, textarea, .auth-card, .auth-portrait')) {
      return;
    }
    const rect = wrap.getBoundingClientRect();
    burst(event.clientX - rect.left, event.clientY - rect.top);
  });

  window.addEventListener('resize', () => {
    resize();
    seed();
  });

  resize();
  seed();
  step();
})();

(() => {
  const money = (n) => 'GHS ' + (Number.isFinite(n) ? n : 0).toFixed(2);

  const addRow = (table) => {
    const body = table.querySelector('[data-line-body]');
    const template = table.querySelector('[data-line-template]');
    if (!body || !template) {
      return;
    }
    const row = template.content.firstElementChild.cloneNode(true);
    body.appendChild(row);
    updateRow(row);
    updateGrand(table);
  };

  const updateRow = (row) => {
    const select = row.querySelector('[data-line-product]');
    const stockEl = row.querySelector('[data-line-stock]');
    if (select && stockEl) {
      const option = select.selectedOptions[0];
      stockEl.textContent = option?.dataset.stock || '—';
    }

    const qty = parseFloat(row.querySelector('[data-line-qty]')?.value || '');
    const cost = parseFloat(row.querySelector('[data-line-cost]')?.value || '');
    const totalEl = row.querySelector('[data-line-total]');
    if (totalEl) {
      totalEl.textContent = qty > 0 && cost >= 0 && !Number.isNaN(cost) ? money(qty * cost) : '—';
    }
  };

  const updateGrand = (table) => {
    const grand = table.querySelector('[data-line-grand]');
    if (!grand) {
      return;
    }
    let sum = 0;
    let has = false;
    table.querySelectorAll('[data-line-row]').forEach((row) => {
      const qty = parseFloat(row.querySelector('[data-line-qty]')?.value || '');
      const cost = parseFloat(row.querySelector('[data-line-cost]')?.value || '');
      if (qty > 0 && !Number.isNaN(cost)) {
        sum += qty * cost;
        has = true;
      }
    });
    grand.textContent = has ? money(sum) : '—';
  };

  const seed = (root = document) => {
    root.querySelectorAll('[data-line-table]').forEach((table) => {
      const body = table.querySelector('[data-line-body]');
      if (body && body.children.length === 0) {
        addRow(table);
      }
    });
  };

  document.addEventListener('click', (event) => {
    const add = event.target.closest('[data-line-add]');
    if (add) {
      event.preventDefault();
      const table = add.closest('[data-line-table]');
      if (table) {
        addRow(table);
      }
      return;
    }

    const remove = event.target.closest('[data-line-remove]');
    if (!remove) {
      return;
    }
    event.preventDefault();
    const table = remove.closest('[data-line-table]');
    const row = remove.closest('[data-line-row]');
    if (!table || !row) {
      return;
    }
    const rows = table.querySelectorAll('[data-line-body] [data-line-row]');
    if (rows.length <= 1) {
      row.querySelectorAll('input, select').forEach((field) => {
        field.value = '';
      });
      updateRow(row);
      updateGrand(table);
      return;
    }
    row.remove();
    updateGrand(table);
  });

  document.addEventListener('input', (event) => {
    const row = event.target.closest('[data-line-row]');
    if (!row) {
      return;
    }
    updateRow(row);
    updateGrand(row.closest('[data-line-table]'));
  });

  document.addEventListener('change', (event) => {
    const row = event.target.closest('[data-line-row]');
    if (!row) {
      return;
    }
    updateRow(row);
    updateGrand(row.closest('[data-line-table]'));
  });

  seed();
  document.addEventListener('pharmacore:content', () => seed());
})();

(() => {
  const panels = () => [...document.querySelectorAll('.help-acc')];
  if (panels().length === 0) {
    return;
  }

  const openHash = () => {
    const id = decodeURIComponent((location.hash || '').replace(/^#/, ''));
    if (!id) {
      return;
    }
    const el = document.getElementById(id);
    if (el && el.classList.contains('help-acc')) {
      el.open = true;
      el.scrollIntoView({ block: 'start' });
    }
  };

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-help-expand]')) {
      panels().forEach((panel) => { panel.open = true; });
      return;
    }
    if (event.target.closest('[data-help-collapse]')) {
      panels().forEach((panel) => { panel.open = false; });
    }
  });

  document.querySelectorAll('.help-toc a').forEach((link) => {
    link.addEventListener('click', () => {
      const id = decodeURIComponent((link.getAttribute('href') || '').replace(/^#/, ''));
      const el = document.getElementById(id);
      if (el && el.classList.contains('help-acc')) {
        el.open = true;
      }
    });
  });

  openHash();
  window.addEventListener('hashchange', openHash);
})();
