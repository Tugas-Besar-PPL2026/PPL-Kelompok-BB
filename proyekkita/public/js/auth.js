/* ProyekKita - interaksi halaman Masuk & Registrasi [FR-01] */
document.addEventListener('DOMContentLoaded', function () {
    // Tombol tampil/sembunyikan kata sandi.
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-toggle-password'));
            if (!input) { return; }
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('.ic-show').toggleAttribute('hidden', show);
            btn.querySelector('.ic-hide').toggleAttribute('hidden', !show);
            btn.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        });
    });

    // Navigasi atas Landing [FR-01]: tanda aktif (.on) mengikuti klik, scroll, dan hash URL.
    // Hanya berjalan di halaman yang memuat section tujuan (Landing); di Masuk/Registrasi diabaikan.
    (function () {
        var links = Array.prototype.slice.call(document.querySelectorAll('.pk-nav a'))
            .filter(function (a) { return a.hash && a.pathname === window.location.pathname; });
        var items = links.map(function (a) {
            return { link: a, section: document.getElementById(a.hash.slice(1)) };
        }).filter(function (it) { return it.section; });
        if (!items.length) { return; }

        var OFFSET = 100;      // tinggi header sticky (67px) + sedikit ruang napas
        var CLICK_LOCK = 400;  // jeda (ms) agar scroll akibat klik tidak menimpa pilihan pengguna
        var current = null;
        var lockUntil = 0;

        function setActive(item) {
            if (item === current) { return; }
            current = item;
            items.forEach(function (it) {
                var on = it === item;
                it.link.classList.toggle('on', on);
                if (on) { it.link.setAttribute('aria-current', 'true'); }
                else { it.link.removeAttribute('aria-current'); }
            });
        }

        function itemFromHash() {
            var id = window.location.hash.slice(1);
            return items.filter(function (it) { return it.section.id === id; })[0] || null;
        }

        // Section yang sedang dibaca: section terakhir yang batas atasnya sudah melewati header.
        // Dua section yang sejajar (kartu UMKM & Kandidat berdampingan) dianggap satu kelompok;
        // bila salah satunya sudah aktif, pilihan itu dipertahankan.
        function detect() {
            // Di dasar halaman, section di dekat akhir tidak bisa lagi naik ke bawah header,
            // jadi batasnya dilonggarkan agar tetap dianggap tercapai.
            var atBottom = window.innerHeight + window.pageYOffset >= document.documentElement.scrollHeight - 2;
            var limit = atBottom ? window.innerHeight * 0.75 : OFFSET;
            var reached = items.filter(function (it) {
                return it.section.getBoundingClientRect().top <= limit;
            });
            if (!reached.length) { return items[0]; }
            var topOf = function (it) { return Math.round(it.section.getBoundingClientRect().top); };
            var last = reached[reached.length - 1];
            var group = reached.filter(function (it) { return topOf(it) === topOf(last); });
            if (current && group.indexOf(current) !== -1) { return current; }
            return group[0];
        }

        function onScroll() {
            if (Date.now() < lockUntil) { return; }
            setActive(detect());
        }

        items.forEach(function (it) {
            it.link.addEventListener('click', function () {
                lockUntil = Date.now() + CLICK_LOCK;
                setActive(it);
            });
        });

        var ticking = false;
        window.addEventListener('scroll', function () {
            if (ticking) { return; }
            ticking = true;
            window.requestAnimationFrame(function () { ticking = false; onScroll(); });
        }, { passive: true });
        window.addEventListener('hashchange', function () {
            var it = itemFromHash();
            if (it) { lockUntil = Date.now() + CLICK_LOCK; setActive(it); }
        });
        window.addEventListener('pageshow', function (e) { if (e.persisted) { onScroll(); } });

        // Kondisi awal: hash URL bila ada, jika tidak berdasarkan posisi scroll.
        setActive(itemFromHash() || detect());
    })();

    // Cegah kirim ganda formulir; tombol dipulihkan bila halaman dimuat ulang dari cache (tombol Kembali).
    document.querySelectorAll('form[data-auth-form]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var submit = form.querySelector('[type="submit"]');
            if (submit) { submit.disabled = true; }
        });
    });
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            document.querySelectorAll('form[data-auth-form] [type="submit"]').forEach(function (b) { b.disabled = false; });
        }
    });
});