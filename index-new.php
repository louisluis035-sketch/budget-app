<!DOCTYPE html>
<html lang="sw">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Salio - Mwelekeo wa Fedha</title>
    <style>
      :root {
        --bg: #07111f;
        --panel: #101c2e;
        --panel-2: #14233a;
        --text: #f4efe7;
        --muted: #8fa1b8;
        --accent: #c9a227;
        --accent-2: #7fb3a3;
        --danger: #e0847a;
        --border: #24374f;
      }
      * { box-sizing: border-box; }
      body {
        margin: 0;
        min-height: 100vh;
        font-family: Inter, Segoe UI, Arial, sans-serif;
        color: var(--text);
        background: radial-gradient(circle at top left, #16263d 0%, var(--bg) 55%, #0b1526 100%);
      }
      button, input, select { font-family: inherit; }
      button { cursor: pointer; }
      .app { min-height: 100vh; }
      .auth-screen {
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 24px;
      }
      .auth-card, .dashboard-shell, .modal-card {
        background: rgba(16, 28, 46, 0.95);
        border: 1px solid var(--border);
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.28);
      }
      .auth-card {
        width: min(100%, 440px);
        border-radius: 22px;
        overflow: hidden;
      }
      .auth-header { padding: 22px 24px 10px; }
      .brand { font-size: 34px; font-weight: 800; letter-spacing: -0.03em; }
      .tagline { color: var(--muted); font-size: 14px; margin-top: 6px; }
      .tabs { display: flex; gap: 8px; padding: 0 24px 16px; }
      .tab-btn {
        flex: 1; padding: 10px 12px; border-radius: 10px;
        border: 1px solid var(--border); background: transparent; color: var(--muted); font-weight: 700;
      }
      .tab-btn.active { background: var(--accent); color: #0f1b2d; border-color: var(--accent); }
      .auth-body { padding: 0 24px 24px; display: grid; gap: 13px; }
      .field label { display: grid; gap: 7px; font-size: 12px; color: var(--muted); }
      .field input, .field select {
        width: 100%; border: 1px solid var(--border); background: #0f1b2d;
        color: var(--text); padding: 11px 12px; border-radius: 10px;
      }
      .primary-btn {
        border: none; background: linear-gradient(135deg, var(--accent), #e2b84d);
        color: #0f1b2d; padding: 12px 14px; border-radius: 10px; font-weight: 800;
      }
      .primary-btn:disabled { opacity: 0.6; cursor: not-allowed; }
      .secondary-btn {
        border: 1px solid var(--border); background: transparent; color: var(--muted);
        padding: 10px 12px; border-radius: 10px; font-weight: 700;
      }
      .warning { color: var(--danger); font-size: 13px; min-height: 18px; }
      .hint { color: #59708d; font-size: 12px; text-align: center; margin-top: 6px; }
      .dashboard-shell { max-width: 960px; margin: 0 auto; min-height: 100vh; padding: 18px 18px 40px; }
      .topbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; }
      .nav { display: flex; flex-wrap: wrap; gap: 8px; }
      .nav-btn {
        border: 1px solid var(--border); border-radius: 999px; background: transparent;
        color: var(--muted); padding: 8px 12px; font-weight: 700;
      }
      .nav-btn.active { background: #16263d; color: var(--text); }
      .stripe {
        height: 10px; border-radius: 999px;
        background: repeating-linear-gradient(60deg, var(--accent) 0 6px, #0f1b2d 6px 12px);
        margin-bottom: 18px;
      }
      .hero-card {
        background: linear-gradient(135deg, #16263d, #0f1b2d); border: 1px solid var(--border);
        border-radius: 20px; padding: 22px; margin-bottom: 16px;
      }
      .hero-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; flex-wrap: wrap; }
      .hero-label { color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; }
      .balance { font-weight: 800; font-size: 34px; margin-top: 6px; font-variant-numeric: tabular-nums; }
      .trend { color: var(--accent-2); font-size: 13px; margin-top: 8px; }
      .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-top: 16px; }
      .stat-card { border: 1px solid var(--border); border-radius: 16px; padding: 14px; background: rgba(255,255,255,0.03); }
      .stat-label { color: var(--muted); font-size: 12px; }
      .stat-value { font-size: 20px; font-weight: 800; margin-top: 6px; font-variant-numeric: tabular-nums; }
      .budget-card { margin-top: 16px; border: 1px solid var(--border); border-radius: 16px; padding: 16px; background: rgba(255,255,255,0.03); }
      .budget-bar { height: 8px; border-radius: 999px; background: #0f1b2d; overflow: hidden; margin-top: 10px; }
      .budget-bar > div { height: 100%; background: var(--accent); transition: width .25s ease; }
      .section-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 24px 0 10px; }
      .section-title { font-size: 18px; font-weight: 800; }
      .list { display: grid; gap: 10px; }
      .item {
        display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 14px;
        background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 14px;
      }
      .item .meta { color: var(--muted); font-size: 12px; margin-top: 3px; }
      .amount { font-weight: 800; font-variant-numeric: tabular-nums; }
      .amount.positive { color: var(--accent-2); }
      .amount.negative { color: var(--danger); }
      .empty { border: 1px dashed var(--border); border-radius: 14px; padding: 24px; color: var(--muted); text-align: center; }
      .profile-card { max-width: 460px; border: 1px solid var(--border); border-radius: 18px; padding: 18px; background: rgba(255,255,255,0.03); }
      .profile-actions { display: flex; gap: 10px; margin-top: 16px; }
      .modal-backdrop { position: fixed; inset: 0; background: rgba(7, 17, 31, 0.8); display: grid; place-items: center; padding: 18px; z-index: 100; }
      .modal-card { width: min(100%, 430px); border-radius: 18px; padding: 18px; }
      .modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
      .type-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 12px; }
      .type-btn { border: 1px solid var(--border); background: transparent; color: var(--muted); border-radius: 10px; padding: 9px 8px; font-weight: 700; }
      .type-btn.active { background: #16263d; color: var(--text); }
      .modal-actions { display: flex; gap: 10px; margin-top: 16px; }
      .modal-actions > * { flex: 1; }
      .loading-dot { text-align: center; padding: 40px; color: var(--muted); }
      @media (max-width: 640px) { .type-row { grid-template-columns: 1fr; } }
    </style>
  </head>
  <body>
    <div id="app"><div class="loading-dot">Inapakia…</div></div>
    <script>
      // Sehemu ya localStorage imeondolewa kabisa. Data zote sasa
      // zinatoka/zinakwenda kwa api.php (server, iliyounganishwa na MySQL).

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

      async function apiCall(action, payload = {}) {
        const res = await fetch('api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ action, ...payload }),
        });
        const rawText = await res.text();
        let data;
        try {
          data = JSON.parse(rawText);
        } catch {
          // Server haikurudisha JSON halali — onyesha maandishi halisi
          // (mfano error ya PHP au ukurasa wa HTML) badala ya kuuficha,
          // ili tuweze kuona tatizo la kweli bila kutumia DevTools.
          const info = `HALI: ${res.status} ${res.statusText} | URL: ${res.url} | UREFU WA JIBU: ${rawText.length} | MAUDHUI: ${rawText.slice(0, 500) || '(TUPU KABISA)'}`;
          throw new Error(info);
        }
        if (!res.ok) {
          throw new Error(data.error || 'Hitilafu isiyojulikana.');
        }
        return data;
      }

      const appState = {
        screen: 'loading', // loading | login | register | app
        data: null,        // { user, transactions, deletedTransactions, stats }
        authError: '',
      };

      const root = document.getElementById('app');

      async function boot() {
        try {
          appState.data = await apiCall('state');
          appState.screen = 'app';
        } catch {
          appState.screen = 'login';
        }
        render();
      }

      function render() {
        root.innerHTML = '';
        if (appState.screen === 'app' && appState.data) {
          root.appendChild(Dashboard());
        } else {
          root.appendChild(AuthScreen());
        }
      }

      function AuthScreen() {
        const wrap = document.createElement('div');
        wrap.className = 'auth-screen';
        const mode = appState.screen === 'register' ? 'register' : 'login';
        wrap.innerHTML = `
          <div class="auth-card">
            <div class="auth-header">
              <div class="brand">Salio</div>
              <div class="tagline">Fuata fedha zako vizuri, kwa usimamizi wa kisasa wa karibu na wewe.</div>
            </div>
            <div class="tabs">
              <button class="tab-btn ${mode === 'login' ? 'active' : ''}" data-mode="login">Ingia</button>
              <button class="tab-btn ${mode === 'register' ? 'active' : ''}" data-mode="register">Jisajili</button>
            </div>
            <div class="auth-body"></div>
          </div>`;
        const body = wrap.querySelector('.auth-body');
        body.appendChild(mode === 'login' ? loginForm() : registerForm());
        wrap.querySelectorAll('.tab-btn').forEach((btn) => {
          btn.addEventListener('click', () => {
            appState.screen = btn.getAttribute('data-mode');
            appState.authError = '';
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
            <div class="warning">${appState.authError || '&nbsp;'}</div>
            <button class="primary-btn" type="submit">Ingia</button>
            <div class="hint">Data yako imehifadhiwa kwa usalama upande wa server.</div>`;
          formEl.querySelector('.password-toggle')?.addEventListener('click', () => {
            const input = formEl.querySelector('input[name="password"]');
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            formEl.querySelector('.password-toggle').textContent = input.type === 'password' ? 'Onyesha' : 'Ficha';
          });
          formEl.addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(formEl));
            const btn = formEl.querySelector('button[type="submit"]');
            btn.disabled = true;
            try {
              appState.data = await apiCall('login', data);
              appState.screen = 'app';
              appState.authError = '';
            } catch (err) {
              appState.authError = err.message;
            }
            render();
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
            <div class="warning">${appState.authError || '&nbsp;'}</div>
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
              appState.authError = 'Tafadhali jaza sehemu zote.';
              render();
              return;
            }
            const btn = formEl.querySelector('button[type="submit"]');
            btn.disabled = true;
            try {
              appState.data = await apiCall('register', data);
              appState.screen = 'app';
              appState.authError = '';
            } catch (err) {
              appState.authError = err.message;
            }
            render();
          });
          return formEl;
        }
      }

      function Dashboard() {
        const shell = document.createElement('div');
        shell.className = 'dashboard-shell';
        let view = 'dashboard';
        let selectedHistoryDate = todayIso();

        async function refresh() {
          appState.data = await apiCall('state');
          renderShell();
        }

        function canModifyTransaction(t) {
          const createdAt = Number(t.createdAt || 0);
          if (!createdAt) return true;
          return Date.now() - createdAt <= 2 * 60 * 60 * 1000;
        }

        function renderShell() {
          const { user, transactions, deletedTransactions, stats } = appState.data;
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
                <div class="budget-bar"><div style="width:${stats.allocatedBudget ? Math.min(100, Math.max(0, (stats.budgetUsed / stats.allocatedBudget) * 100)) : 0}%;background:${stats.budgetRemaining > 0 ? 'var(--accent)' : 'var(--danger)'}"></div></div>
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
            head.innerHTML = `<div class="section-title">Muamala wa hivi karibuni</div>`;
            shell.appendChild(head);

            const list = document.createElement('div');
            list.className = 'list';
            if (!transactions.length) {
              list.innerHTML = '<div class="empty">Bado hakuna muamala. Bonyeza “+ Ongeza muamala” kuanza.</div>';
            } else {
              transactions.forEach((t) => {
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
            const historyItems = transactions.filter((t) => t.date === selectedHistoryDate);
            const deletedItems = deletedTransactions.filter((t) => t.date === selectedHistoryDate);
            const totalIncome = historyItems.filter((t) => t.type === 'mapato').reduce((s, t) => s + t.amount, 0);
            const totalExpense = historyItems.filter((t) => t.type === 'matumizi').reduce((s, t) => s + t.amount, 0);
            const totalLoss = historyItems.filter((t) => t.type === 'hasara').reduce((s, t) => s + t.amount, 0);
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
                  <div style="color:var(--muted);font-size:12px;margin-top:6px;">Weka kiasi unachopanga kumeza kwa mwezi.</div>
                </div>
                <div class="field">
                  <label>Salio la awali
                    <input value="${fmt(user.salioAwali)}" disabled>
                  </label>
                </div>
                <div class="warning" id="profile-warning">&nbsp;</div>
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
            await apiCall('logout');
            appState.data = null;
            appState.screen = 'login';
            render();
          });
          shell.querySelector('#add-transaction')?.addEventListener('click', () => openModal());
          shell.querySelector('#set-budget')?.addEventListener('click', () => openBudgetModal());
          shell.querySelectorAll('[data-action="edit"]').forEach((btn) => {
            btn.addEventListener('click', () => {
              const id = btn.getAttribute('data-id');
              const t = transactions.find((tx) => tx.id === id);
              if (!t) return;
              if (!canModifyTransaction(t)) {
                alert('Muamala huu hauwezi kuhaririwa baada ya masaa 2.');
                return;
              }
              openModal(t);
            });
          });
          shell.querySelectorAll('[data-action="delete"]').forEach((btn) => {
            btn.addEventListener('click', async () => {
              const id = btn.getAttribute('data-id');
              const t = transactions.find((tx) => tx.id === id);
              if (!t) return;
              if (!canModifyTransaction(t)) {
                alert('Muamala huu hauwezi kufutwa baada ya masaa 2.');
                return;
              }
              if (!confirm('Unataka kufuta muamala huu?')) return;
              try {
                await apiCall('delete_transaction', { id });
                await refresh();
              } catch (err) {
                alert(err.message);
              }
            });
          });
          shell.querySelector('#save-profile')?.addEventListener('click', async () => {
            const jina = shell.querySelector('#profile-name').value;
            const bajeti = Number(shell.querySelector('#profile-budget').value || 0);
            try {
              await apiCall('update_profile', { jina, bajeti });
              await refresh();
            } catch (err) {
              shell.querySelector('#profile-warning').textContent = err.message;
            }
          });
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
                <div class="warning" id="tx-warning">&nbsp;</div>
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
          backdrop.querySelector('#save-modal')?.addEventListener('click', async () => {
            const amount = Number(backdrop.querySelector('#tx-amount').value || 0);
            if (!amount || amount <= 0) return;
            const payload = {
              id: editing ? existingTransaction.id : undefined,
              type,
              amount,
              category: type === 'matumizi' ? backdrop.querySelector('#tx-category').value : null,
              note: backdrop.querySelector('#tx-note').value,
              date: backdrop.querySelector('#tx-date').value || todayIso(),
            };
            try {
              await apiCall('save_transaction', payload);
              backdrop.remove();
              await refresh();
            } catch (err) {
              backdrop.querySelector('#tx-warning').textContent = err.message;
            }
          });
        }

        function openBudgetModal() {
          const { stats } = appState.data;
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
                  <div style="color:var(--muted);font-size:12px;margin-top:6px;">Salio lako sasa: ${fmt(stats.balance)}. Kiasi kitachukuliwa kutoka salio na kuongezwa kwenye bajeti ya mwezi.</div>
                </div>
                <div class="warning" id="budget-warning">&nbsp;</div>
                <div class="modal-actions">
                  <button class="secondary-btn" id="cancel-budget-modal">Ghairi</button>
                  <button class="primary-btn" id="save-budget-modal">Weka Bajeti</button>
                </div>
              </div>
            </div>`;
          document.body.appendChild(backdrop);
          backdrop.querySelector('#close-budget-modal')?.addEventListener('click', () => backdrop.remove());
          backdrop.querySelector('#cancel-budget-modal')?.addEventListener('click', () => backdrop.remove());
          backdrop.querySelector('#save-budget-modal')?.addEventListener('click', async () => {
            const amount = Number(backdrop.querySelector('#budget-amount').value || 0);
            if (!amount || amount <= 0) {
              backdrop.querySelector('#budget-warning').textContent = 'Tafadhali ingiza kiasi halali cha kuhamisha kwenye bajeti.';
              return;
            }
            try {
              await apiCall('set_budget', { amount });
              backdrop.remove();
              await refresh();
            } catch (err) {
              backdrop.querySelector('#budget-warning').textContent = err.message;
            }
          });
        }

        renderShell();
        return shell;
      }

      boot();
    </script>
  </body>
</html>