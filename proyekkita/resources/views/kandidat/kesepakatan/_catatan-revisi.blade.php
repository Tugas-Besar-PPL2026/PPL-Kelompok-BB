{{--
    [PBI-33 / FR-15] Banner permintaan revisi dari UMKM (Figma frame 24).
    Tampil bila versi terakhir berstatus Perlu Revisi. Variabel: $revision (SubmissionService::activeRevision), $umkmName.
    Catatan ditampilkan lewat {{ }} sehingga di-escape.
--}}
<section class="al warn mb16" dusk="catatan-revisi" style="align-items:flex-start;margin-top:18px">
    <i data-lucide="message-square-warning" class="ic" width="19" height="19"></i>
    <div>
        <b dusk="judul-catatan-revisi">Permintaan revisi dari {{ $umkmName }}@if ($revision['requestedAt']) · {{ $revision['requestedAt'] }}@endif</b>
        <div class="rv-revnote" dusk="isi-catatan-revisi" style="white-space:pre-line;margin-top:4px">{{ $revision['note'] }}</div>
        <div style="margin-top:8px;font-weight:600;font-size:13px" dusk="kuota-revisi">Revisi ke-{{ $revision['used'] }} dari batas {{ $revision['limit'] }} kali.</div>
    </div>
</section>
