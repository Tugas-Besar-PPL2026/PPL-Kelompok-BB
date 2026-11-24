{{--
    Gaya bersama halaman Penyerahan Hasil (kandidat, PBI-31/33) dan Tinjau Hasil (UMKM, PBI-32).
    Dipanggil di dalam @push('styles').
--}}
<style>
    /* ----- PBI-31: ringkasan kesepakatan ----- */
    .ps-head{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .ps-code{font-size:12px;font-weight:600;background:#EEF0F6;color:var(--ink2);padding:3px 9px;border-radius:6px}
    .ps-title{font-size:20px;font-weight:700;letter-spacing:-.3px;margin:12px 0 4px}
    .ps-deadline{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:6px}
    .ps-late{color:var(--bad);font-weight:600;font-size:13px;display:inline-flex;gap:6px;align-items:center}
    .ps-steps{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;border-top:1px solid var(--line);margin-top:18px;padding-top:16px}
    .ps-step .bar{height:5px;border-radius:4px;background:var(--line);margin-bottom:10px}
    .ps-step.done .bar{background:var(--ok)}.ps-step.current .bar{background:var(--pri2)}
    .ps-step .lb{display:flex;gap:7px;align-items:center;font-weight:600;font-size:13.5px}
    .ps-step.done .lb .ic{color:var(--ok)}.ps-step.current .lb{color:var(--pri)}.ps-step.todo .lb .ic{color:#9A9EAD}
    .ps-step .nt{font-size:12.5px;color:var(--mut);margin:3px 0 0 24px}

    /* ----- PBI-31: form penyerahan ----- */
    .ps-grid{margin-top:18px;align-items:start}
    .ps-drop{border:1px dashed #AEB4D6;border-radius:10px;background:#F7F8FF;padding:22px 16px;display:flex;flex-direction:column;align-items:center;gap:6px;text-align:center;cursor:pointer}
    .ps-drop:hover,.ps-drop.dragover{border-color:var(--pri2);background:#EEF1FF}
    .ps-drop.err{border-color:var(--bad);background:#FFF8F7}
    .ps-drop .t{font-weight:600;font-size:14px}.ps-drop .s{font-size:12.5px;color:var(--mut)}
    .ps-file{display:flex;align-items:center;gap:12px;border:1px solid var(--line);border-radius:10px;padding:10px 12px;margin-top:10px}
    .ps-file[hidden]{display:none}
    .ps-file .fi{width:36px;height:36px;border-radius:8px;background:#EEF1FF;color:var(--pri);display:flex;align-items:center;justify-content:center;flex:none}
    .ps-file .nm{font-weight:600;font-size:13.5px;word-break:break-all}
    .ps-file .mark{margin-left:auto;display:flex;align-items:center}
    .ps-file .mark.ok{color:var(--ok)}.ps-file .mark.bad{color:var(--bad)}
    .ps-file button{background:none;border:0;color:var(--mut);cursor:pointer;display:flex;padding:4px}
    .ps-counter{display:flex;justify-content:space-between;gap:10px}
    .ps-actions{display:flex;justify-content:flex-end;margin-top:16px}

    /* ----- PBI-31: riwayat versi ----- */
    .rv-list{display:grid;gap:4px}
    .rv-item .t1{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .rv-item .t3{word-break:break-word;white-space:pre-line}
    .rv-item .rv-file,.rv-item .rv-link{display:flex;gap:6px;align-items:center;font-size:13px;margin-top:6px;word-break:break-all}
    .rv-item .rv-note{margin-top:8px;padding:8px 10px;border-radius:8px;background:#FFF8EA;border:1px solid #F6DDA6;font-size:13px;white-space:pre-line}

    /* ----- PBI-32: tinjau hasil (UMKM) ----- */
    .tr-stack{display:grid;gap:18px}
    .tr-item{display:flex;align-items:center;gap:12px;border:1px solid var(--line);border-radius:10px;padding:12px 14px;margin-top:10px}
    .tr-item .fi{width:36px;height:36px;border-radius:8px;background:#EEF1FF;color:var(--pri);display:flex;align-items:center;justify-content:center;flex:none}
    .tr-item .nm{font-weight:600;font-size:13.5px;word-break:break-all}
    .tr-item a.go{margin-left:auto;color:var(--pri);display:flex}
    .tr-desc{margin-top:12px;border:1px solid var(--line);border-radius:10px;padding:12px 14px;background:#FAFAFC}
    .tr-desc .lb{font-size:12.5px;color:var(--mut);margin-bottom:4px}
    .tr-desc .tx{white-space:pre-line;word-break:break-word}
    .tr-quota{border:1px solid var(--line);border-radius:10px;padding:12px 14px;margin-bottom:14px}
    .tr-quota .row{display:flex;justify-content:space-between;font-size:13px;font-weight:600}
    .tr-quota .bar{height:6px;border-radius:4px;background:var(--line);margin-top:10px;overflow:hidden}
    .tr-quota .bar span{display:block;height:100%;background:#E3A008}
    .tr-opt{display:flex;gap:12px;align-items:flex-start;border:1px solid var(--line);border-radius:10px;padding:12px 14px;margin-bottom:10px;cursor:pointer}
    .tr-opt:has(input:checked){border-color:var(--pri2);box-shadow:0 0 0 3px #E6ECFF}
    .tr-opt.off{opacity:.55;cursor:not-allowed}
    .tr-opt b{display:block;font-size:14px}.tr-opt .sub{font-size:12.5px}
    .tr-table{width:100%;border-collapse:collapse;font-size:13.5px}
    .tr-table th{text-align:left;font-size:11.5px;letter-spacing:.4px;text-transform:uppercase;color:var(--mut);background:#F6F7FA;padding:10px 12px}
    .tr-table td{padding:12px;border-top:1px solid var(--line);vertical-align:top}
    .tr-table td.nt{white-space:pre-line;font-size:12.5px;max-width:240px}
    @media (max-width:900px){.ps-steps{grid-template-columns:1fr 1fr}}
    @media (max-width:640px){.ps-steps{grid-template-columns:1fr}.ps-title{font-size:18px}}
</style>
