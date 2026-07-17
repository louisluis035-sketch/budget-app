const API_URL = location.pathname;
const STORAGE_KEY = 'salio-users-v1';
const SESSION_KEY = 'salio-session-v1';
const CATS = [
  { id: 'chakula', label: 'Chakula' },
  { id: 'usafiri', label: 'Usafiri' },
  { id: 'nyumba', label: 'Nyumba / Kodi' },
  { id: 'afya', label: 'Afya' },
  { id: 'elimu', label: 'Elimu' },
  { id: 'burudani', label: 'Burudani' },
  { id: 'mengineyo', label: 'Mengineyo' },
];
const TYPE_META = {
  mapato: { label: 'Mapato', color: 'positive' },
  matumizi: { label: 'Matumizi', color: 'negative' },
  hasara: { label: 'Hasara', color: 'negative' },
};
function fmt(n) {
  return 'TSh ' + Math.round(Number(n) || 0).toLocaleString('en-US');
}
function todayIso() {
  return new Date().toISOString().slice(0, 10);
}
function monthKey(d) {
  return d ? d.slice(0, 7) : '';
}
function uid() {
  return Math.random().toString(36).slice(2, 10);
}
async function apiRequest(action, method = 'GET', data = null) {
  const url = `${API_URL}?action=${encodeURIComponent(action)}`;
  const options = {
    method,
    headers: { 'Accept': 'application/json' },
    credentials: 'same-origin',
  };
  if (data !== null) {
    options.headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(data);
  }
  const response = await fetch(url, options);
  const result = await response.json();
  if (!response.ok || !result.success) {
    throw new Error(result.error || 'Server error');
  }
  return result;
}
async function fetchSession() {
  try {
    const result = await apiRequest('session');
    return result.user || null;
  } catch {
    return null;
  }
}
async function fetchTransactions() {
  const result = await apiRequest('transactions');
  return (result.transactions || []).map((row) => ({
    id: String(row.id),
    type: row.type,
    amount: Number(row.amount),
    category: row.category,
    note: row.note,
    date: row.date,
    createdAt: row.created_at ? new Date(row.created_at).getTime() : Date.now(),
  }));
}
async function addTransaction(payload) {
  const result = await apiRequest('transaction_add', 'POST', payload);
  const row = result.transaction;
  return {
    id: String(row.id),
    type: row.type,
    amount: Number(row.amount),
    category: row.category,
    note: row.note,
    date: row.date,
    createdAt: row.created_at ? new Date(row.created_at).getTime() : Date.now(),
  };
}
async function updateTransaction(transactionId, payload) {
  const result = await apiRequest('transaction_update', 'POST', { id: transactionId, ...payload });
  const row = result.transaction;
  return {
    id: String(row.id),
    type: row.type,
    amount: Number(row.amount),
    category: row.category,
    note: row.note,
    date: row.date,
    createdAt: row.created_at ? new Date(row.created_at).getTime() : Date.now(),
  };
}
async function deleteTransaction(transactionId) {
  await apiRequest('transaction_delete', 'POST', { id: transactionId });
}
async function loginUser(identifier, password) {
  const result = await apiRequest('login', 'POST', { identifier, password });
  return result.user;
}
async function registerUser(fullname, email, password) {
  const result = await apiRequest('register', 'POST', { fullname, email, password });
  return result.user;
}
async function logoutUser() {
  await apiRequest('logout', 'POST');
}
function loadUsers() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
  } catch {
    return {};
  }
}
function saveUsers(users) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(users));
}

async function App() {
  const localUsers = loadUsers();
  const sessionUser = await fetchSession();
  const state = {
    users: localUsers,
    session: sessionUser?.email || localStorage.getItem(SESSION_KEY) || '',
    sessionId: sessionUser?.id || null,
    screen: sessionUser ? 'app' : 'login',
    authError: ''
  };

  if (sessionUser) {
    const stored = state.users[sessionUser.email] || {
      username: sessionUser.email,
      jina: sessionUser.fullname,
      password: '',
      salioAwali: 0,
      bajetiMwezi: 0,
      bajetiAllocated: 0,
      transactions: []
    };
    stored.id = sessionUser.id;
    stored.username = sessionUser.email;
    stored.jina = sessionUser.fullname;
    stored.transactions = await fetchTransactions();
    state.users[sessionUser.email] = stored;
    saveUsers(state.users);
  }

  const root = document.getElementById('app');
  const render = () => {
    const user = state.session ? state.users[state.session] : null;
    root.innerHTML = '';
    if (state.screen === 'app' && user) {
      root.appendChild(Dashboard(state, render));
    } else {
      root.appendChild(AuthScreen(state, render));
    }
  };
  render();
}

function AuthScreen(state, render) {
  const wrap = document.createElement('div');
  wrap.className = 'auth-screen';
  wrap.innerHTML = `
    <div class="auth-card">
      <div class="auth-header">
        <div class="brand">Salio</div>
        <div class="tagline">Fuata fedha zako vizuri, kwa usimamizi wa kisasa wa karibu na wewe.</div>
      </div>
      <div class="tabs">
        <button class="tab-btn ${state.screen === 'login' ? 'active' : ''}" data-mode="login">Ingia</button>
        <button class="tab-btn ${state.screen === 'register' ? 'active' : ''}" data-mode="register">Jisajili</button>
      </div>
      <div class="auth-body"></div>
    </div>`;
  const body = wrap.querySelector('.auth-body');
  const form = state.screen === 'login' ? loginForm() : registerForm();
  body.appendChild(form);
  wrap.querySelectorAll('.tab-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      state.screen = btn.getAttribute('data-mode');
      state.authError = '';
      render();
    });
  });
  return wrap;

  function loginForm() {
    const formEl = document.createElement('form');
    formEl.innerHTML = `
      <div class="field">
        <label>Barua pepe au jina la mtumiaji
          <input name="identifier" required>
        </label>
      </div>
      <div class="field">
        <label>Nenosiri
          <div style="display:flex;gap:8px;align-items:center;">
            <input name="password" type="password" required>
            <button type="button" class="secondary-btn password-toggle">Onyesha</button>
          </div>
        </label>
      </div>
      <div class="warning">${state.authError || '&nbsp;'}</div>
      <button class="primary-btn" type="submit">Ingia</button>
      <div class="hint">Data yako itahifadhiwa kwenye kifaa chako.</div>`;
    formEl.querySelector('.password-toggle')?.addEventListener('click', () => {
      const input = formEl.querySelector('input[name="password"]');
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
      formEl.querySelector('.password-toggle').textContent = input.type === 'password' ? 'Onyesha' : 'Ficha';
    });
    formEl.addEventListener('submit', async (e) => {
      e.preventDefault();
      const data = Object.fromEntries(new FormData(formEl));
      try {
        const user = await loginUser(data.identifier, data.password);
        const transactions = await fetchTransactions();
        state.users[data.identifier] = {
          id: user.id,
          username: data.identifier,
          jina: user.fullname,
          password: '',
          salioAwali: 0,
          bajetiMwezi: 0,
          bajetiAllocated: 0,
          transactions,
        };
        saveUsers(state.users);
        state.authError = '';
        state.session = data.identifier;
        state.sessionId = user.id;
        state.screen = 'app';
        localStorage.setItem(SESSION_KEY, state.session);
        render();
      } catch (error) {
        state.authError = error.message || 'Barua pepe/jina la mtumiaji au nenosiri si sahihi.';
        render();
      }
    });
    return formEl;
  }

  function registerForm() {
    const formEl = document.createElement('form');
    formEl.innerHTML = `
      <div class="field">
        <label>Jina lako kamili
          <input name="jina" required>
        </label>
      </div>
      <div class="field">
        <label>Barua pepe
          <input name="email" type="email" required>
        </label>
      </div>
      <div class="field">
        <label>Nenosiri
          <div style="display:flex;gap:8px;align-items:center;">
            <input name="password" type="password" required>
            <button type="button" class="secondary-btn password-toggle">Onyesha</button>
          </div>
        </label>
      </div>
      <div class="warning">${state.authError || '&nbsp;'}</div>
      <button class="primary-btn" type="submit">Fungua Akaunti</button>
      <div class="hint">Utaweza kuanza kufuatilia matumizi yako mara moja.</div>`;
    formEl.querySelector('.password-toggle')?.addEventListener('click', () => {
      const input = formEl.querySelector('input[name="password"]');
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
      formEl.querySelector('.password-toggle').textContent = input.type === 'password' ? 'Onyesha' : 'Ficha';
    });
    formEl.addEventListener('submit', async (e) => {
      e.preventDefault();
      const data = Object.fromEntries(new FormData(formEl));
      if (!data.email || !data.password || !data.jina) {
        state.authError = 'Tafadhali jaza sehemu zote.';
        render();
        return;
      }
      if (state.users[data.email]) {
        state.authError = 'Barua pepe tayari imetumika.';
        render();
        return;
      }
      try {
        const user = await registerUser(data.jina, data.email, data.password);
        const transactions = await fetchTransactions();
        state.users[data.email] = {
          id: user.id,
          username: data.email,
          jina: data.jina,
          password: '',
          salioAwali: 0,
          bajetiMwezi: 0,
          bajetiAllocated: 0,
          transactions,
        };
        saveUsers(state.users);
        state.session = data.email;
        state.sessionId = user.id;
        state.screen = 'app';
        localStorage.setItem(SESSION_KEY, state.session);
        state.authError = '';
        render();
      } catch (error) {
        state.authError = error.message || 'Imeshindikana kusajili.';
        render();
      }
    });
    return formEl;
  }
}

function Dashboard(state, render) {
  const user = state.users[state.session];
  const shell = document.createElement('div');
  shell.className = 'dashboard-shell';

  let view = 'dashboard';
  let selectedHistoryDate = todayIso();

  function renderShell() {
    const stats = computeStats(user);
    shell.innerHTML = '';
    const topbar = document.createElement('div');
    topbar.className = 'topbar';
    topbar.innerHTML = `
      <div class="brand" style="font-size:24px;">Salio</div>
      <div class="nav">
        <button class="nav-btn ${view === 'dashboard' ? 'active' : ''}" data-view="dashboard">Dashboard</button>
        <button class="nav-btn ${view === 'history' ? 'active' : ''}" data-view="history">Historia</button>
        <button class="nav-btn ${view === 'profile' ? 'active' : ''}" data-view="profile">Wasifu</button>
        <button class="secondary-btn" id="logout">Toka</button>
      </div>`;
    shell.appendChild(topbar);

    const stripe = document.createElement('div');
    stripe.className = 'stripe';
    shell.appendChild(stripe);

    if (view === 'dashboard') {
      const hero = document.createElement('div');
      hero.className = 'hero-card';
      hero.innerHTML = `
        <div class="hero-top">
          <div>
            <div class="hero-label">Salio lako la sasa</div>
            <div class="balance">${fmt(stats.balance)}</div>
            <div class="trend">Habari ${user.jina.split(' ')[0]} — unaendelea vizuri.</div>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button class="primary-btn" id="add-transaction">+ Ongeza muamala</button>
            <button class="secondary-btn" id="set-budget">Weka Bajeti</button>
          </div>
        </div>
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-label">Matumizi ya leo</div>
            <div class="stat-value">${fmt(stats.expenseToday)}</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Matumizi ya ${stats.currentMonthLabel}</div>
            <div class="stat-value">${fmt(stats.expenseMonth)}</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Hasara ya mwezi</div>
            <div class="stat-value">${fmt(stats.lossMonth)}</div>
          </div>
        </div>
        <div class="budget-card">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
            <span>Bajeti ya mwezi</span>
            <span>${stats.allocatedBudget ? 'Iliyokamatia: ' + fmt(stats.allocatedBudget) : 'Hajaweka bajeti'}</span>
          </div>
          <div style="font-size:12px;color:var(--muted);margin-top:6px;">Kiasi: ${fmt(stats.budgetUsed)} / ${fmt(stats.allocatedBudget)} · Ilibaki: ${fmt(stats.budgetRemaining)}</div>
          <div class="budget-bar"><div style="width:${stats.allocatedBudget ? Math.min(100, Math.max(0, (stats.budgetUsed / stats.allocatedBudget) * 100)) : 0}%;background:${stats.budgetRemaining > 0 ? 'var(--accent)' : 'var(--danger)'}" ></div></div>
        </div>
        <div class="section-head">
          <div class="section-title">Historia ya matumizi</div>
        </div>
        <div class="list">
          ${stats.monthlyRecords.length ? stats.monthlyRecords.map((record) => `
            <div class="item">
              <div>
                <div>${record.label}</div>
                <div class="meta">Mapato ${fmt(record.income)} · Matumizi ${fmt(record.expense)} · Hasara ${fmt(record.loss)}</div>
              </div>
              <div class="amount negative">${fmt(record.expense)}</div>
            </div>`).join('') : '<div class="empty">Hakuna historia ya miezi iliyopita bado.</div>'}
        </div>`;
      shell.appendChild(hero);

      const head = document.createElement('div');
      head.className = 'section-head';
      head.innerHTML = `
        <div class="section-title">Muamala wa hivi karibuni</div>
      `;
      shell.appendChild(head);

      const list = document.createElement('div');
      list.className = 'list';
      if (!user.transactions.length) {
        list.innerHTML = '<div class="empty">Bado hakuna muamala. Bonyeza “+ Ongeza muamala” kuanza.</div>';
      } else {
        [...user.transactions].sort((a, b) => b.date.localeCompare(a.date)).forEach((t) => {
          const item = document.createElement('div');
          item.className = 'item';
          const meta = TYPE_META[t.type];
          const categoryLabel = CATS.find((c) => c.id === t.category)?.label || '';
          item.innerHTML = `
            <div>
              <div>${t.note || meta.label}</div>
              <div class="meta">${meta.label} · ${t.date}${categoryLabel ? ' · ' + categoryLabel : ''}</div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="amount ${meta.color}">${meta.color === 'positive' ? '+' : '-'}${fmt(t.amount)}</div>
              <button class="secondary-btn" data-id="${t.id}" data-action="edit" title="Hariri">✎</button>
              <button class="secondary-btn" data-id="${t.id}" data-action="delete" title="Futa">x</button>
            </div>`;
          list.appendChild(item);
        });
      }
      shell.appendChild(list);
    } else if (view === 'history') {
      const card = document.createElement('div');
      card.className = 'profile-card';
      const historyItems = [...user.transactions]
        .filter((t) => t.date === selectedHistoryDate)
        .sort((a, b) => b.date.localeCompare(a.date));
      const deletedItems = [...(user.deletedTransactions || [])]
        .filter((t) => t.date === selectedHistoryDate)
        .sort((a, b) => (b.deletedAt || 0) - (a.deletedAt || 0));
      const totalIncome = historyItems.filter((t) => t.type === 'mapato').reduce((sum, t) => sum + t.amount, 0);
      const totalExpense = historyItems.filter((t) => t.type === 'matumizi').reduce((sum, t) => sum + t.amount, 0);
      const totalLoss = historyItems.filter((t) => t.type === 'hasara').reduce((sum, t) => sum + t.amount, 0);
      card.innerHTML = `
        <div class="section-title">Historia ya matumizi</div>
        <div class="field" style="margin-top:12px;">
          <label>Tarehe
            <input id="history-date" type="date" value="${selectedHistoryDate}">
          </label>
        </div>
        <div class="list" style="margin-top:12px;">
          <div class="item" style="border:1px solid var(--border);">
            <div>
              <div>Muhtasari wa tarehe</div>
              <div class="meta">Mapato ${fmt(totalIncome)} · Matumizi ${fmt(totalExpense)} · Hasara ${fmt(totalLoss)}</div>
            </div>
            <div class="amount negative">${fmt(totalExpense)}</div>
          </div>
          ${historyItems.length ? historyItems.map((t) => {
            const meta = TYPE_META[t.type];
            const categoryLabel = CATS.find((c) => c.id === t.category)?.label || '';
            return `
              <div class="item">
                <div>
                  <div>${t.note || meta.label}</div>
                  <div class="meta">${meta.label} · ${t.date}${categoryLabel ? ' · ' + categoryLabel : ''}</div>
                </div>
                <div class="amount ${meta.color}">${meta.color === 'positive' ? '+' : '-'}${fmt(t.amount)}</div>
              </div>`;
          }).join('') : '<div class="empty">Hakuna data iliyohifadhiwa kwa tarehe hii.</div>'}
        </div>
        <div class="section-head" style="margin-top:16px;">
          <div class="section-title">Muamala uliofutwa</div>
        </div>
        <div class="list">
          ${deletedItems.length ? deletedItems.map((t) => {
            const meta = TYPE_META[t.type];
            const categoryLabel = CATS.find((c) => c.id === t.category)?.label || '';
            return `
              <div class="item">
                <div>
                  <div>${t.note || meta.label}</div>
                  <div class="meta">${meta.label} · ${t.date}${categoryLabel ? ' · ' + categoryLabel : ''} · Imefutwa</div>
                </div>
                <div class="amount ${meta.color}">${meta.color === 'positive' ? '+' : '-'}${fmt(t.amount)}</div>
              </div>`;
          }).join('') : '<div class="empty">Hakuna muamala uliofutwa kwa tarehe hii.</div>'}
        </div>`;
      shell.appendChild(card);
      shell.querySelector('#history-date')?.addEventListener('change', (event) => {
        selectedHistoryDate = event.target.value || todayIso();
        renderShell();
      });
    } else {
      const card = document.createElement('div');
      card.className = 'profile-card';
      card.innerHTML = `
        <div class="section-title">Wasifu</div>
        <div class="auth-body" style="padding:0;margin-top:12px;">
          <div class="field">
            <label>Jina la mtumiaji
              <input id="profile-user" value="${user.username}" disabled>
            </label>
          </div>
          <div class="field">
            <label>Jina kamili
              <input id="profile-name" value="${user.jina}">
            </label>
          </div>
          <div class="section-title" style="margin:16px 0 12px;font-size:14px;color:var(--accent);">Weka Bajeti ya Mwezi</div>
          <div class="field">
            <label>Bajeti ya mwezi (TSh)
              <input id="profile-budget" type="number" value="${user.bajetiMwezi}" min="0">
            </label>
            <div style="color:var(--muted);font-size:12px;margin-top:6px;">Weka kiasi unachopanga kumeza kwa mwezi. Muamala zilizo zaidi haijaruhusiwa.</div>
          </div>
          <div class="field">
            <label>Salio la awali
              <input value="${fmt(user.salioAwali)}" disabled>
            </label>
          </div>
          <div class="profile-actions">
            <button class="primary-btn" id="save-profile">Hifadhi mabadiliko</button>
          </div>
        </div>`;
      shell.appendChild(card);
    }

    shell.querySelectorAll('.nav-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        view = btn.getAttribute('data-view');
        renderShell();
      });
    });
    shell.querySelector('#logout')?.addEventListener('click', async () => {
      try {
        await logoutUser();
      } catch {
        // ignore logout failures, still clear session locally
      }
      state.session = '';
      state.sessionId = null;
      localStorage.removeItem(SESSION_KEY);
      state.screen = 'login';
      render();
    });
    shell.querySelector('#add-transaction')?.addEventListener('click', () => {
      openModal();
    });
    shell.querySelector('#set-budget')?.addEventListener('click', () => {
      openBudgetModal();
    });
    shell.querySelectorAll('[data-action="edit"]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        const transaction = user.transactions.find((t) => t.id === id);
        if (!transaction) return;
        if (!canModifyTransaction(transaction)) {
          alert('Muamala huu hauwezi kuhaririwa baada ya masaa 2.');
          return;
        }
        openModal(transaction);
      });
    });
    shell.querySelectorAll('[data-action="delete"]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const id = btn.getAttribute('data-id');
        const transaction = user.transactions.find((t) => t.id === id);
        if (!transaction) return;
        if (!canModifyTransaction(transaction)) {
          alert('Muamala huu hauwezi kufutwa baada ya masaa 2.');
          return;
        }
        if (!confirm('Unataka kufuta muamala huu?')) return;
        try {
          await deleteTransaction(id);
          user.deletedTransactions = user.deletedTransactions || [];
          user.deletedTransactions.unshift({ ...transaction, deletedAt: Date.now() });
          user.transactions = user.transactions.filter((t) => t.id !== id);
          saveUsers(state.users);
          render();
        } catch (error) {
          alert(error.message || 'Imeshindikana kufuta muamala.');
        }
      });
    });
    shell.querySelector('#save-profile')?.addEventListener('click', () => {
      const name = shell.querySelector('#profile-name').value;
      const budget = Number(shell.querySelector('#profile-budget').value || 0);
      const currentAllocated = Number(user.bajetiAllocated || user.bajetiMwezi || 0);
      const currentStats = computeStats(user);
      const availableMain = currentStats.balance + currentAllocated;
      if (budget > availableMain) {
        alert(`Bajeti isiyofaa. Salio lako linaloopatikana ni ${fmt(availableMain)}, lakini unataka kuweka bajeti ya ${fmt(budget)}.`);
        return;
      }
      const delta = budget - currentAllocated;
      if (delta > 0) {
        user.salioAwali = Number(user.salioAwali || 0) - delta;
      } else if (delta < 0) {
        user.salioAwali = Number(user.salioAwali || 0) + Math.abs(delta);
      }
      user.jina = name;
      user.bajetiMwezi = budget;
      user.bajetiAllocated = budget;
      saveUsers(state.users);
      render();
    });
  }

  renderShell();
  return shell;

  function canModifyTransaction(transaction) {
    const createdAt = Number(transaction.createdAt || 0);
    if (!createdAt) return true;
    return Date.now() - createdAt <= 2 * 60 * 60 * 1000;
  }

  function checkBudgetForPayload(payload, existingTransaction = null) {
    const allocatedBudget = user.bajetiAllocated || user.bajetiMwezi || 0;
    if (!allocatedBudget || payload.type !== 'matumizi') {
      return { allowed: true, remaining: allocatedBudget, used: 0, overBudget: false };
    }
    const transactions = (user.transactions || [])
      .filter((t) => t.type === 'matumizi' && isInMonthWindow(t.date) && t.id !== existingTransaction?.id)
      .map((t) => ({ ...t }));
    const proposedTransaction = { ...payload, date: payload.date || todayIso() };
    if (isInMonthWindow(proposedTransaction.date)) {
      transactions.push(proposedTransaction);
    }
    const used = transactions.reduce((sum, t) => {
      if (t.type === 'matumizi' && isInMonthWindow(t.date)) {
        return sum + Number(t.amount || 0);
      }
      return sum;
    }, 0);
    const availableAfterSpend = allocatedBudget - used;
    const remaining = Math.max(0, availableAfterSpend);
    return { allowed: true, remaining, used, overBudget: availableAfterSpend < 0 };
  }

  function openModal(existingTransaction = null) {
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    const editing = Boolean(existingTransaction);
    const selectedType = existingTransaction?.type || 'matumizi';
    backdrop.innerHTML = `
      <div class="modal-card">
        <div class="modal-head">
          <div class="section-title">${editing ? 'Badilisha muamala' : 'Ongeza muamala'}</div>
          <button class="secondary-btn" id="close-modal">×</button>
        </div>
        <div class="type-row">
          <button class="type-btn ${selectedType === 'matumizi' ? 'active' : ''}" data-type="matumizi">Matumizi</button>
          <button class="type-btn ${selectedType === 'mapato' ? 'active' : ''}" data-type="mapato">Mapato</button>
          <button class="type-btn ${selectedType === 'hasara' ? 'active' : ''}" data-type="hasara">Hasara</button>
        </div>
        <div class="auth-body" style="padding:0;">
          <div class="field">
            <label>Kiasi (TSh)
              <input id="tx-amount" type="number" min="1" step="1" required value="${existingTransaction?.amount || ''}">
            </label>
          </div>
          <div class="field" id="category-wrap" style="display:${selectedType === 'matumizi' ? 'block' : 'none'}">
            <label>Kundi
              <select id="tx-category">
                ${CATS.map((c) => `<option value="${c.id}" ${existingTransaction?.category === c.id ? 'selected' : ''}>${c.label}</option>`).join('')}
              </select>
            </label>
          </div>
          <div class="field">
            <label>Maelezo (hiari)
              <input id="tx-note" placeholder="mf. Chakula cha mchana" value="${existingTransaction?.note || ''}">
            </label>
          </div>
          <div class="field">
            <label>Tarehe
              <input id="tx-date" type="date" value="${existingTransaction?.date || todayIso()}">
            </label>
          </div>
          <div class="modal-actions">
            <button class="secondary-btn" id="cancel-modal">Ghairi</button>
            <button class="primary-btn" id="save-modal">Hifadhi</button>
          </div>
        </div>
      </div>`;
    document.body.appendChild(backdrop);
    const typeButtons = backdrop.querySelectorAll('.type-btn');
    const categoryWrap = backdrop.querySelector('#category-wrap');
    let type = selectedType;
    typeButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        typeButtons.forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        type = btn.getAttribute('data-type');
        categoryWrap.style.display = type === 'matumizi' ? 'block' : 'none';
      });
    });
    backdrop.querySelector('#close-modal')?.addEventListener('click', () => backdrop.remove());
    backdrop.querySelector('#cancel-modal')?.addEventListener('click', () => backdrop.remove());
    backdrop.querySelector('#save-modal')?.addEventListener('click', () => {
      const amount = Number(backdrop.querySelector('#tx-amount').value || 0);
      if (!amount || amount <= 0) return;
      const payload = {
        type,
        amount,
        category: type === 'matumizi' ? backdrop.querySelector('#tx-category').value : null,
        note: backdrop.querySelector('#tx-note').value,
        date: backdrop.querySelector('#tx-date').value || todayIso()
      };
      const budgetCheck = checkBudgetForPayload(payload, editing ? existingTransaction : null);
      if ((payload.type === 'matumizi' || payload.type === 'hasara') && payload.amount > 0) {
        const updatedTransactions = editing
          ? user.transactions.filter((t) => t.id !== existingTransaction.id).concat({ ...existingTransaction, ...payload, id: existingTransaction.id })
          : user.transactions.concat({ id: 'temp', ...payload });
        const tempUser = { ...user, transactions: updatedTransactions };
        const newStats = computeStats(tempUser);
        if (newStats.balance < 0) {
          alert(`Salio haijatosha kwa muamala huu. Salio lako sasa: ${fmt(computeStats(user).balance)}, lakini muamala huu ungefanya salio kuwa hasi.`);
          return;
        }
      }
      if (editing && existingTransaction) {
        updateTransaction(existingTransaction.id, payload)
          .then((updated) => {
            Object.assign(existingTransaction, updated);
            saveUsers(state.users);
            backdrop.remove();
            render();
          })
          .catch((error) => {
            alert(error.message || 'Imeshindikana kusasisha muamala.');
          });
      } else {
        addTransaction(payload)
          .then((created) => {
            user.transactions.unshift(created);
            saveUsers(state.users);
            backdrop.remove();
            render();
          })
          .catch((error) => {
            alert(error.message || 'Imeshindikana kuhifadhi muamala.');
          });
      }
    });
  }

  function openBudgetModal() {
    const currentStats = computeStats(user);
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    backdrop.innerHTML = `
      <div class="modal-card">
        <div class="modal-head">
          <div class="section-title">Weka Bajeti ya Mwezi</div>
          <button class="secondary-btn" id="close-budget-modal">×</button>
        </div>
        <div class="auth-body" style="padding:0;">
          <div class="field">
            <label>Kiasi cha kuhamisha kwenye bajeti (TSh)
              <input id="budget-amount" type="number" min="1" step="1000" value="" placeholder="Mf. 500000">
            </label>
            <div style="color:var(--muted);font-size:12px;margin-top:6px;">Salio lako sasa: ${fmt(currentStats.balance)}. Kiasi kitachukuliwa kutoka salio na kuongezwa kwenye bajeti ya mwezi.</div>
          </div>
          <div class="modal-actions">
            <button class="secondary-btn" id="cancel-budget-modal">Ghairi</button>
            <button class="primary-btn" id="save-budget-modal">Weka Bajeti</button>
          </div>
        </div>
      </div>`;
    document.body.appendChild(backdrop);
    backdrop.querySelector('#close-budget-modal')?.addEventListener('click', () => backdrop.remove());
    backdrop.querySelector('#cancel-budget-modal')?.addEventListener('click', () => backdrop.remove());
    backdrop.querySelector('#save-budget-modal')?.addEventListener('click', () => {
      const transferAmount = Number(backdrop.querySelector('#budget-amount').value || 0);
      if (!transferAmount || transferAmount <= 0) {
        alert('Tafadhali ingiza kiasi halali cha kuhamisha kwenye bajeti.');
        return;
      }
      const currentStats = computeStats(user);
      if (transferAmount > currentStats.balance) {
        alert(`Salio lako halitoshi. Salio lako sasa ni ${fmt(currentStats.balance)}, lakini unataka kuhamisha ${fmt(transferAmount)}.`);
        return;
      }
      const currentAllocated = Number(user.bajetiAllocated || user.bajetiMwezi || 0);
      user.salioAwali = Number(user.salioAwali || 0) - transferAmount;
      user.bajetiAllocated = currentAllocated + transferAmount;
      user.bajetiMwezi = currentAllocated + transferAmount;
      saveUsers(state.users);
      backdrop.remove();
      render();
    });
  }
}

function formatMonthLabel(monthKeyValue) {
  if (!monthKeyValue) return '';
  const [year, month] = monthKeyValue.split('-');
  const date = new Date(Number(year), Number(month) - 1, 1);
  return date.toLocaleString('sw', { month: 'long', year: 'numeric' }).replace(/^./, (char) => char.toUpperCase());
}

function getMonthWindowBounds() {
  const now = new Date();
  const start = new Date(now.getFullYear(), now.getMonth(), 1);
  const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  return { start, end };
}

function isInMonthWindow(dateValue) {
  if (!dateValue) return false;
  const { start, end } = getMonthWindowBounds();
  const txDate = new Date(`${dateValue}T00:00:00`);
  if (Number.isNaN(txDate.getTime())) return false;
  return txDate >= start && txDate <= end;
}

function buildMonthSummary(user, month) {
  let income = 0;
  let expense = 0;
  let loss = 0;
  for (const t of user.transactions) {
    if (monthKey(t.date) !== month) continue;
    if (t.type === 'mapato') income += t.amount;
    if (t.type === 'matumizi') expense += t.amount;
    if (t.type === 'hasara') loss += t.amount;
  }
  return { month, label: formatMonthLabel(month), income, expense, loss };
}

function syncMonthlyRecords(user) {
  const now = todayIso();
  const curMonth = monthKey(now);
  const months = new Set([curMonth]);
  (user.monthlyRecords ? Object.keys(user.monthlyRecords) : []).forEach((month) => months.add(month));
  user.transactions.forEach((t) => {
    const month = monthKey(t.date);
    if (month) months.add(month);
  });
  const records = Array.from(months).filter(Boolean).map((month) => buildMonthSummary(user, month));
  records.sort((a, b) => b.month.localeCompare(a.month));
  user.monthlyRecords = Object.fromEntries(records.map((record) => [record.month, record]));
  return records;
}

function computeStats(user) {
  const now = todayIso();
  const curMonth = monthKey(now);
  let expenseMonth = 0;
  let expenseToday = 0;
  let lossMonth = 0;
  let incomeMonth = 0;
  for (const t of user.transactions) {
    const inWindow = isInMonthWindow(t.date);
    if (t.type === 'mapato' && inWindow) incomeMonth += t.amount;
    if (t.type === 'matumizi' && inWindow) expenseMonth += t.amount;
    if (t.type === 'matumizi' && t.date === now) expenseToday += t.amount;
    if (t.type === 'hasara' && inWindow) lossMonth += t.amount;
  }
  const allocated = Number(user.bajetiAllocated || user.bajetiMwezi || 0);
  const budgetUsed = Math.min(allocated, Math.max(0, expenseMonth));
  const budgetRemaining = Math.max(0, allocated - budgetUsed);
  const outOfBudgetExpense = Math.max(0, expenseMonth - budgetUsed);
  const balance = user.salioAwali + incomeMonth - lossMonth - outOfBudgetExpense;
  const monthlyRecords = syncMonthlyRecords(user).filter((record) => record.month !== curMonth);
  const currentMonthLabel = formatMonthLabel(curMonth);
  return { balance, expenseToday, expenseMonth, lossMonth, budgetUsed, budgetRemaining, monthlyRecords, currentMonthLabel, allocatedBudget: allocated };
}

App();
