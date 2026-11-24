{{--
    [PBI-24, PBI-25, PBI-26] Gaya tambahan halaman kesepakatan. Dipanggil dengan @include('kesepakatan._gaya').
    Hanya menambah kelas baru berawalan "ks-", tidak mengubah kelas global di public/css/app.css.
--}}
@once
@push('styles')
<style>
    .ks-stack{display:grid;gap:18px;align-content:start}
    .ks-stack>.card+.card{margin-top:0}
    .ks-code{font-size:12px;font-weight:600;background:#EEF0F6;color:var(--ink2);padding:3px 9px;border-radius:6px}
    .ks-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap}
    .ks-head .tags{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .ks-title{font-size:20px;font-weight:700;letter-spacing:-.3px;margin:12px 0 4px}
    .ks-due{text-align:right}
    .ks-due .l{font-size:13px;color:var(--mut)}
    .ks-due .v{font-size:19px;font-weight:700;margin-top:2px}
    .ks-due .n{font-size:13px;color:var(--mut);margin-top:2px}
    .ks-due .n.late{color:var(--bad);font-weight:600}

    /* Linimasa 5 tahap pada detail kesepakatan */
    .ks-steps{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;border-top:1px solid var(--line);margin-top:18px;padding-top:16px}
    .ks-step .bar{height:5px;border-radius:4px;background:var(--line);margin-bottom:10px}
    .ks-step.done .bar{background:var(--ok)}.ks-step.current .bar{background:var(--pri2)}
    .ks-step .lb{display:flex;gap:7px;align-items:center;font-weight:600;font-size:13.5px}
    .ks-step.done .lb .ic{color:var(--ok)}.ks-step.current .lb{color:var(--pri)}.ks-step.todo .lb .ic{color:#9A9EAD}
    .ks-step .nt{font-size:12.5px;color:var(--mut);margin:3px 0 0 24px}

    /* Kartu kandidat yang diterima (PBI-24) */
    .ks-cand{display:flex;gap:16px;align-items:center;flex-wrap:wrap;padding:18px 22px}
    .ks-cand .av{width:56px;height:56px;border-radius:12px;font-size:18px}
    .ks-cand .nm{font-weight:600;font-size:16px}
    .ks-cand .acc{margin-left:auto;display:flex;gap:12px;align-items:center;flex-wrap:wrap}

    /* Panel kanan (alur kirim, tanggapan) */
    .ks-btns{display:grid;gap:10px;margin-top:16px}
    .ks-btns .btn{width:100%;justify-content:center}
    .ks-panel .tl{margin-top:4px}

    /* Fakta singkat: tenggat, kompensasi, batas revisi (PBI-25) */
    .ks-facts{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:18px 0}
    .ks-fact{border:1px solid var(--line);border-radius:10px;padding:14px 16px}
    .ks-fact .l{font-size:12.5px;color:var(--mut)}
    .ks-fact .v{font-size:19px;font-weight:700;margin-top:4px}
    .ks-h{font-size:14.5px;font-weight:700;margin:18px 0 8px}
    .ks-text{font-size:14px;line-height:1.6;color:var(--ink2);white-space:pre-line;word-break:break-word}
    .ks-list{margin:0;padding-left:20px;font-size:14px;line-height:1.7}

    /* Daftar kesepakatan kandidat dalam bentuk kartu (PBI-26) */
    .ks-cards{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;padding:0 0 6px}
    .ks-card{display:block;border:1px solid var(--line);border-radius:10px;padding:14px 16px;color:var(--ink)}
    .ks-card:hover{border-color:#C9CFEA}
    .ks-card.on{border-color:var(--pri2);box-shadow:0 0 0 2px #E2E8FF;background:#F7F9FF}
    .ks-card .t{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
    .ks-card .nm{font-weight:600}
    .ks-card .prow{display:flex;gap:10px;align-items:center;margin-top:12px}
    .ks-card .prow .prog{flex:1}
    table.t.ks-table{min-width:760px}
    .ks-table .nm{font-weight:600}
    .ks-table .prow{display:flex;gap:8px;align-items:center;min-width:130px}
    .ks-table .prow .prog{width:90px}
    .ks-kv{grid-template-columns:130px 1fr}
    .ks-kv dd ul{margin:0;padding-left:18px}

    @media (max-width:1000px){
        .ks-steps{grid-template-columns:1fr 1fr}
        .ks-cards{grid-template-columns:1fr}
        .ks-cand .acc{margin-left:0}
    }
    @media (max-width:640px){
        .ks-steps,.ks-facts{grid-template-columns:1fr}
        .ks-due{text-align:left}
        .ks-title{font-size:18px}
        .ks-kv{grid-template-columns:1fr}
    }
</style>
@endpush
@endonce
