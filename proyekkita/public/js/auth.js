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
