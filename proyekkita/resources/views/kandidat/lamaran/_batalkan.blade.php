{{--
    [PBI-18 / FR-10] Tombol Batalkan Lamaran dan dialog konfirmasinya.

    Partial ini dipanggil oleh _kartu.blade.php (PBI-17) lewat @includeIf, hanya untuk lamaran yang
    $application->isCancellable() (Menunggu Keputusan / Terpilih Sementara). Variabel: $application.

    Dialog memakai kelas .overlay dan .modal dari public/css/app.css. Elemen dialog dikirim ke @stack('modals')
    milik layout supaya berada di luar kartu. Aksi sebenarnya (validasi dan perubahan status) ada di
    ApplicationCancellationController dan ApplicationCancellationService; tampilan ini hanya meminta konfirmasi.
--}}
@php
    $dialogId = 'batal-lamaran-'.$application->id;
    $projectTitle = $application->project?->title;
    $activeFilter = request()->query('status');
@endphp

<button type="button" class="btn r sm" data-batal-open="{{ $dialogId }}" dusk="batalkan-lamaran-{{ $application->id }}">
    <i data-lucide="x" class="ic" width="16" height="16"></i>Batalkan Lamaran
</button>

@push('modals')
    <div class="overlay" id="{{ $dialogId }}" data-batal-overlay style="z-index:1000"
         role="dialog" aria-modal="true" aria-labelledby="{{ $dialogId }}-judul" dusk="dialog-batal-{{ $application->id }}">
        <div class="modal" style="width:480px">
            <div class="mh">
                <i data-lucide="triangle-alert" class="ic" width="22" height="22" style="color:var(--bad)"></i>
                <h3 id="{{ $dialogId }}-judul">Batalkan lamaran?</h3>
            </div>

            <div class="mb">
                <p style="margin-bottom:12px">
                    Lamaran Anda untuk proyek <strong>{{ $projectTitle }}</strong> akan diubah statusnya menjadi
                    <strong>Dibatalkan</strong>.
                </p>
                <ul style="margin:0;padding-left:18px;line-height:1.7;color:var(--ink2)">
                    <li>Setelah dibatalkan, Anda tidak dapat mengajukan lamaran kembali pada proyek ini.</li>
                    <li>Data lamaran dan riwayat statusnya tetap tersimpan.</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('kandidat.lamaran.cancel', $application->id) }}" data-batal-form>
                @csrf
                @method('PATCH')
                @if ($activeFilter)
                    <input type="hidden" name="status" value="{{ $activeFilter }}">
                @endif

                <div class="mf">
                    <button type="button" class="btn o" data-batal-close dusk="tutup-batal-{{ $application->id }}">Tidak, Kembali</button>
                    <button type="submit" class="btn rf" dusk="konfirmasi-batal-{{ $application->id }}">Ya, Batalkan Lamaran</button>
                </div>
            </form>
        </div>
    </div>
@endpush

@once
    @push('scripts')
        <script>
        (() => {
            const overlays = () => document.querySelectorAll('.overlay.show[data-batal-overlay]');

            document.addEventListener('click', (event) => {
                const opener = event.target.closest('[data-batal-open]');
                if (opener) {
                    const dialog = document.getElementById(opener.dataset.batalOpen);
                    if (dialog) {
                        dialog.classList.add('show');
                        const cancelButton = dialog.querySelector('[data-batal-close]');
                        if (cancelButton) { cancelButton.focus(); }
                    }
                    return;
                }

                const closer = event.target.closest('[data-batal-close]');
                if (closer) {
                    closer.closest('.overlay').classList.remove('show');
                    return;
                }

                // Klik pada latar gelap (di luar kotak dialog) menutup dialog.
                if (event.target.matches('.overlay[data-batal-overlay]')) {
                    event.target.classList.remove('show');
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    overlays().forEach((overlay) => overlay.classList.remove('show'));
                }
            });

            // Cegah klik ganda saat permintaan pembatalan dikirim.
            document.addEventListener('submit', (event) => {
                const form = event.target.closest('form[data-batal-form]');
                const button = form ? form.querySelector('button[type="submit"]') : null;
                if (button) { button.disabled = true; }
            });

            // Pulihkan tombol jika pengguna kembali ke halaman ini lewat tombol Back peramban.
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    document.querySelectorAll('form[data-batal-form] button[type="submit"]').forEach((button) => { button.disabled = false; });
                    overlays().forEach((overlay) => overlay.classList.remove('show'));
                }
            });
        })();
        </script>
    @endpush
@endonce
