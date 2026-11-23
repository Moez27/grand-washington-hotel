/* Symphonie Hotel & Spa — scripts du site client (aucun script inline : compatible CSP) */
(function () {
    'use strict';

    // Apparition au scroll
    const io = 'IntersectionObserver' in window ? new IntersectionObserver((entries) => {
        entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
    }, { threshold: 0.12 }) : null;
    document.querySelectorAll('.reveal').forEach((el) => io ? io.observe(el) : el.classList.add('visible'));

    // Fermeture automatique des messages flash
    document.querySelectorAll('.flash-stack .alert').forEach((el) => {
        setTimeout(() => { if (window.bootstrap) { bootstrap.Alert.getOrCreateInstance(el).close(); } }, 6000);
    });

    // Cohérence des dates arrivée / départ
    function linkDates(inEl, outEl) {
        if (!inEl || !outEl) return;
        const sync = () => {
            if (!inEl.value) return;
            const d = new Date(inEl.value);
            d.setDate(d.getDate() + 1);
            const min = d.toISOString().slice(0, 10);
            outEl.min = min;
            if (!outEl.value || outEl.value < min) outEl.value = min;
        };
        inEl.addEventListener('change', sync);
    }
    document.querySelectorAll('form').forEach((f) => {
        linkDates(f.querySelector('[name$="checkIn"], [name$="[checkIn]"]'), f.querySelector('[name$="checkOut"], [name$="[checkOut]"]'));
    });

    // Estimation du prix en direct sur la fiche chambre
    const booking = document.querySelector('[data-booking]');
    if (booking) {
        const nightly = parseFloat(booking.dataset.price);
        const fmt = (n) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(n) + ' DT';
        const inEl = booking.querySelector('[data-checkin]');
        const outEl = booking.querySelector('[data-checkout]');
        const guestsEl = booking.querySelector('[data-guests]');
        const out = {
            nights: booking.querySelector('[data-q-nights]'),
            room: booking.querySelector('[data-q-room]'),
            extras: booking.querySelector('[data-q-extras]'),
            total: booking.querySelector('[data-q-total]'),
            box: booking.querySelector('.quote-box'),
        };
        const update = () => {
            const a = new Date(inEl.value), b = new Date(outEl.value);
            const nights = Math.round((b - a) / 86400000);
            if (!inEl.value || !outEl.value || !(nights > 0)) { out.box.classList.add('d-none'); return; }
            const guests = Math.max(1, parseInt(guestsEl.value || '1', 10));
            let extras = 0;
            booking.querySelectorAll('input[type=checkbox][data-price]:checked').forEach((c) => {
                const p = parseFloat(c.dataset.price);
                extras += c.dataset.pricing === 'per_night' ? p * nights : c.dataset.pricing === 'per_person_night' ? p * nights * guests : p;
            });
            out.nights.textContent = nights + ' nuit' + (nights > 1 ? 's' : '');
            out.room.textContent = fmt(nightly * nights);
            out.extras.textContent = fmt(extras);
            out.total.textContent = fmt(nightly * nights + extras);
            out.box.classList.remove('d-none');
        };
        booking.addEventListener('input', update);
        booking.addEventListener('change', update);
        update();
    }

    // Confirmation des actions sensibles
    document.querySelectorAll('[data-confirm]').forEach((el) => {
        el.addEventListener('submit', (e) => { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
    });
})();
