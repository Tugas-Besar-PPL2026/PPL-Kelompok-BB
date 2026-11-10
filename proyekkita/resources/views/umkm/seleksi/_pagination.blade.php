{{-- Navigasi halaman daftar pelamar. Dipanggil lewat $paginator->links('umkm.seleksi._pagination'). --}}
@if ($paginator->hasPages())
    <nav class="pager" aria-label="Navigasi halaman">
        <span>Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} pelamar</span>
        <div class="row">
            @if ($paginator->onFirstPage())
                <span class="btn o sm dis" aria-disabled="true">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn o sm" rel="prev">Sebelumnya</a>
            @endif
            <span>Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn o sm" rel="next">Berikutnya</a>
            @else
                <span class="btn o sm dis" aria-disabled="true">Berikutnya</span>
            @endif
        </div>
    </nav>
@endif
