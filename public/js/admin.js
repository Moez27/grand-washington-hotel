/* Symphonie — Back-office (aucun script inline : compatible CSP) */
(function () {
    'use strict';

    // Menu latéral mobile
    document.querySelectorAll('[data-toggle-sidebar]').forEach((b) => b.addEventListener('click', () => document.body.classList.toggle('sidebar-open')));
    const backdrop = document.querySelector('.sidebar-backdrop');
    if (backdrop) backdrop.addEventListener('click', () => document.body.classList.remove('sidebar-open'));

    // Flash auto-close
    document.querySelectorAll('.flash-stack .alert').forEach((el) => setTimeout(() => window.bootstrap && bootstrap.Alert.getOrCreateInstance(el).close(), 6000));

    // Confirmation avant suppression
    document.querySelectorAll('form[data-confirm]').forEach((f) => f.addEventListener('submit', (e) => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));

    // Aperçu de l'image avant upload
    document.querySelectorAll('input[type=file][data-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const img = document.getElementById(input.dataset.preview);
            const file = input.files && input.files[0];
            if (!img || !file || !file.type.startsWith('image/')) return;
            img.src = URL.createObjectURL(file);
            img.classList.remove('d-none');
        });
    });

    // Aperçu de l'icône Bootstrap
    document.querySelectorAll('[data-icon-input]').forEach((input) => {
        const preview = document.createElement('span');
        preview.className = 'icon-preview ms-2';
        const i = document.createElement('i');
        preview.appendChild(i);
        input.parentElement.classList.add('d-flex', 'align-items-center', 'flex-wrap');
        input.style.flex = '1';
        input.after(preview);
        const sync = () => { i.className = 'bi ' + (/^bi-[a-z0-9-]+$/.test(input.value) ? input.value : 'bi-question'); };
        input.addEventListener('input', sync);
        sync();
    });

    // Changement rapide de statut : soumission automatique
    document.querySelectorAll('select[data-autosubmit]').forEach((s) => s.addEventListener('change', () => s.form.submit()));

    // ---------- Graphiques (Chart.js) ----------
    const dataEl = document.getElementById('dashboard-data');
    if (!dataEl || !window.Chart) return;
    const d = JSON.parse(dataEl.textContent);
    const gold = '#c8a165', navy = '#15283c';
    const palette = ['#c8a165', '#15283c', '#5b8db8', '#7fb58f', '#d98b6a', '#9a7fc7'];
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = '#6b7685';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    const money = (v) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(v) + ' DT';

    const revenueCtx = document.getElementById('chart-revenue');
    if (revenueCtx) {
        const g = revenueCtx.getContext('2d').createLinearGradient(0, 0, 0, 300);
        g.addColorStop(0, 'rgba(200,161,101,.35)');
        g.addColorStop(1, 'rgba(200,161,101,0)');
        new Chart(revenueCtx, {
            data: {
                labels: d.months,
                datasets: [
                    { type: 'line', label: "Chiffre d'affaires", data: d.revenue, borderColor: gold, backgroundColor: g, fill: true, tension: .35, pointRadius: 3, yAxisID: 'y' },
                    { type: 'bar', label: 'Réservations', data: d.bookings, backgroundColor: 'rgba(21,40,60,.12)', borderRadius: 6, yAxisID: 'y1' },
                ],
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { ticks: { callback: (v) => money(v) }, grid: { color: '#eef0f3' } },
                    y1: { position: 'right', grid: { display: false }, ticks: { precision: 0 } },
                    x: { grid: { display: false } },
                },
                plugins: { tooltip: { callbacks: { label: (c) => c.dataset.yAxisID === 'y' ? ' ' + money(c.raw) : ' ' + c.raw + ' réservations' } } },
            },
        });
    }

    const statusCtx = document.getElementById('chart-status');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: { labels: d.statusLabels, datasets: [{ data: d.statusValues, backgroundColor: ['#e9b949', '#3aa66a', '#5b8db8', '#d9534f'], borderWidth: 0 }] },
            options: { maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom' } } },
        });
    }

    const catCtx = document.getElementById('chart-category');
    if (catCtx) {
        new Chart(catCtx, {
            type: 'polarArea',
            data: { labels: d.categoryLabels, datasets: [{ data: d.categoryValues, backgroundColor: palette.map((c) => c + 'cc') }] },
            options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: (c) => ' ' + money(c.raw) } } }, scales: { r: { ticks: { display: false } } } },
        });
    }

    const roomCtx = document.getElementById('chart-rooms');
    if (roomCtx) {
        new Chart(roomCtx, {
            type: 'bar',
            data: { labels: d.roomLabels, datasets: [{ label: 'Séjours', data: d.roomValues, backgroundColor: navy, borderRadius: 6 }] },
            options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { precision: 0 }, grid: { color: '#eef0f3' } }, y: { grid: { display: false } } } },
        });
    }

    const signCtx = document.getElementById('chart-signups');
    if (signCtx) {
        new Chart(signCtx, {
            type: 'line',
            data: { labels: d.months, datasets: [{ label: 'Nouveaux clients', data: d.signups, borderColor: '#5b8db8', backgroundColor: 'rgba(91,141,184,.15)', fill: true, tension: .35 }] },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { ticks: { precision: 0 }, grid: { color: '#eef0f3' } }, x: { grid: { display: false } } } },
        });
    }
})();
